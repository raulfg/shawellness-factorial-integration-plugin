<?php
/**
 * Single job template.
 *
 * @package ShaFactorialJobs
 * @var array<string, mixed> $job
 * @var string $region
 * @var string $list_url
 * @var bool $show_card
 * @var Sha_Factorial_Lookup_Repository $lookups
 */

defined( 'ABSPATH' ) || exit;

$title     = (string) ( $job['title'] ?? '' );
$apply_url = ! empty( $job['url'] ) ? (string) $job['url'] : '';
$list_url  = isset( $list_url ) ? (string) $list_url : sha_factorial_jobs_get_list_url();
$show_card = ! isset( $show_card ) || $show_card;
$single_class = sha_factorial_jobs_direction_class(
	'sfj-job-single' . ( $show_card ? '' : ' sfj-job-single--no-card' )
);
$field_definitions = sha_factorial_jobs_get_card_field_definitions();

$location_name = $lookups->get_name( $region, 'locations', (string) ( $job['location_id'] ?? '' ) );
$team_name     = $lookups->get_name( $region, 'teams', (string) ( $job['team_id'] ?? '' ) );
$entity_name   = $lookups->get_name( $region, 'legal_entities', (string) ( $job['legal_entity_id'] ?? '' ) );
$region_label  = sha_factorial_jobs_get_region_label( $region );
$place_text    = '' !== $location_name ? $region_label . ' · ' . $location_name : $region_label;

$field_icon = static function ( string $field, bool $decorative = false ) use ( $field_definitions ): string {
	if ( $decorative ) {
		return sprintf(
			'<span class="sfj-field-icon sfj-field-icon--decor" data-sfj-icon="%1$s" aria-hidden="true"></span>',
			esc_attr( $field )
		);
	}

	$label = (string) ( $field_definitions[ $field ]['label'] ?? $field );

	return sprintf(
		'<span class="sfj-field-icon" data-sfj-icon="%1$s" data-sfj-tooltip="%2$s" role="img" aria-label="%2$s" tabindex="0"></span>',
		esc_attr( $field ),
		esc_attr( $label )
	);
};

$hint_item = static function ( string $field, string $value, string $tag = 'li' ) use ( $field_definitions, $field_icon ): void {
	if ( '' === $value ) {
		return;
	}

	$tooltip = sha_factorial_jobs_field_tooltip( $field, $value, $field_definitions );

	printf(
		'<%1$s class="sfj-hint-target sfj-meta-item" data-sfj-tooltip="%2$s" tabindex="0">%3$s<span class="sfj-meta-item__text">%4$s</span></%1$s>',
		tag_escape( $tag ),
		esc_attr( $tooltip ),
		$field_icon( $field, true ),
		esc_html( $value )
	);
};
?>
<article class="<?php echo esc_attr( $single_class ); ?>"<?php echo sha_factorial_jobs_direction_attr(); ?><?php echo sha_factorial_jobs_markup_snippet_exclusion_attr(); ?>>
	<p class="sfj-job-single__back">
		<a class="sfj-job-single__back-link" href="<?php echo esc_url( $list_url ); ?>">
			<?php esc_html_e( 'Back to listing', 'sha-factorial-jobs' ); ?>
		</a>
	</p>
	<header class="sfj-job-single__header">
		<div class="sfj-job-card__badges">
			<?php if ( ! empty( $job['contract_type'] ) ) : ?>
				<?php
				$contract_label = sha_factorial_jobs_enum_label( 'contract_type', (string) $job['contract_type'] );
				?>
				<span
					class="sfj-job-card__badge sfj-hint-target"
					data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'contract_type', $contract_label, $field_definitions ) ); ?>"
					tabindex="0"
				>
					<?php echo $field_icon( 'contract_type', true ); ?>
					<?php echo esc_html( $contract_label ); ?>
				</span>
			<?php endif; ?>
			<?php if ( ! empty( $job['category'] ) ) : ?>
				<?php
				$category_label = sha_factorial_jobs_enum_label( 'category', (string) $job['category'] );
				?>
				<span
					class="sfj-job-card__badge sfj-hint-target"
					data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'category', $category_label, $field_definitions ) ); ?>"
					tabindex="0"
				>
					<?php echo $field_icon( 'category', true ); ?>
					<?php echo esc_html( $category_label ); ?>
				</span>
			<?php endif; ?>
			<?php if ( ! empty( $job['remote'] ) ) : ?>
				<?php $remote_label = __( 'Remote', 'sha-factorial-jobs' ); ?>
				<span
					class="sfj-job-card__badge sfj-job-card__badge--remote sfj-hint-target"
					data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'remote', $remote_label, $field_definitions ) ); ?>"
					tabindex="0"
				>
					<?php echo $field_icon( 'remote', true ); ?>
					<?php echo esc_html( $remote_label ); ?>
				</span>
			<?php endif; ?>
		</div>
		<h2><?php echo esc_html( $title ); ?></h2>
		<div class="sfj-job-card__facts">
	<?php if ( '' !== $place_text ) : ?>
		<p
			class="sfj-job-card__place sfj-hint-target"
			data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'location', $place_text, $field_definitions ) ); ?>"
			tabindex="0"
		>
			<?php echo $field_icon( 'location', true ); ?>
			<span class="sfj-meta-item__text"><?php echo esc_html( $place_text ); ?></span>
		</p>
	<?php endif; ?>

	<ul class="sfj-job-card__meta sfj-job-single__meta">
		<?php
		if ( ! empty( $job['workplace_type'] ) ) {
			$hint_item( 'workplace_type', sha_factorial_jobs_enum_label( 'workplace_type', (string) $job['workplace_type'] ) );
		}
		if ( ! empty( $job['schedule_type'] ) ) {
			$hint_item( 'schedule_type', sha_factorial_jobs_enum_label( 'schedule_type', (string) $job['schedule_type'] ) );
		}
		if ( '' !== $team_name ) {
			$hint_item( 'team', $team_name );
		}
		if ( '' !== $entity_name ) {
			$hint_item( 'legal_entity', $entity_name );
		}
		?>
	</ul>

	<?php
	$salary       = sha_factorial_jobs_format_salary( $job );
	$published_at = ! empty( $job['published_at'] ) ? strtotime( (string) $job['published_at'] ) : false;
	?>
	<?php if ( false !== $published_at ) : ?>
		<?php $date_text = sha_factorial_jobs_format_published_date( $published_at ); ?>
		<p
			class="sfj-job-card__date sfj-hint-target"
			data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'published_at', $date_text, $field_definitions ) ); ?>"
			tabindex="0"
		>
			<?php echo $field_icon( 'published_at', true ); ?>
			<time datetime="<?php echo esc_attr( gmdate( 'c', $published_at ) ); ?>">
				<?php echo esc_html( $date_text ); ?>
			</time>
		</p>
	<?php endif; ?>

	<?php if ( '' !== $salary ) : ?>
		<p
			class="sfj-job-card__salary sfj-hint-target"
			data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'salary', $salary, $field_definitions ) ); ?>"
			tabindex="0"
		>
			<?php echo $field_icon( 'salary', true ); ?>
			<?php echo esc_html( $salary ); ?>
		</p>
	<?php endif; ?>
		</div>
	</header>

	<?php if ( ! empty( $job['description'] ) ) : ?>
		<div class="sfj-job-single__description">
			<?php echo wp_kses_post( wpautop( (string) $job['description'] ) ); ?>
		</div>
	<?php endif; ?>

	<div class="sfj-job-single__actions">
		<?php if ( '' !== $apply_url ) : ?>
			<a class="sfj-job-single__cta" href="<?php echo esc_url( $apply_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Apply', 'sha-factorial-jobs' ); ?>
			</a>
		<?php endif; ?>
		<a class="sfj-job-single__back-link" href="<?php echo esc_url( $list_url ); ?>">
			<?php esc_html_e( 'Back to listing', 'sha-factorial-jobs' ); ?>
		</a>
	</div>
</article>
