<?php
/**
 * Job card partial.
 *
 * @package ShaFactorialJobs
 * @var array<string, mixed>              $job
 * @var array<string, bool>               $card_fields
 * @var bool                              $preview_mode
 * @var Sha_Factorial_Lookup_Repository|null $lookups
 * @var array<string, array<string, string>> $lookup_labels
 */

defined( 'ABSPATH' ) || exit;

$region         = (string) ( $job['_region'] ?? 'es' );
$job_id         = (string) ( $job['id'] ?? '' );
$title          = (string) ( $job['title'] ?? '' );
$preview_mode   = ! empty( $preview_mode );
$card_fields    = isset( $card_fields ) && is_array( $card_fields ) ? $card_fields : sha_factorial_jobs_normalize_card_fields( array() );
$lookup_labels  = isset( $lookup_labels ) && is_array( $lookup_labels ) ? $lookup_labels : array();
$has_detail     = sha_factorial_jobs_job_has_detail( $job );
$detail_url     = '';

if ( $has_detail && '' !== $job_id && ! $preview_mode ) {
	$detail_url = sha_factorial_jobs_get_job_detail_url( $region, $job_id );
}

$apply_url = ! empty( $job['url'] ) ? (string) $job['url'] : '';

$show = static function ( string $field ) use ( $card_fields, $preview_mode ): bool {
	if ( $preview_mode ) {
		return true;
	}

	return sha_factorial_jobs_card_field_enabled( $field, $card_fields );
};

$location_name = sha_factorial_jobs_resolve_lookup_name( $lookups ?? null, $region, 'locations', (string) ( $job['location_id'] ?? '' ), $lookup_labels );
$team_name     = sha_factorial_jobs_resolve_lookup_name( $lookups ?? null, $region, 'teams', (string) ( $job['team_id'] ?? '' ), $lookup_labels );
$entity_name   = sha_factorial_jobs_resolve_lookup_name( $lookups ?? null, $region, 'legal_entities', (string) ( $job['legal_entity_id'] ?? '' ), $lookup_labels );
$salary        = sha_factorial_jobs_format_salary( $job );
$published_at  = ! empty( $job['published_at'] ) ? strtotime( (string) $job['published_at'] ) : false;
$region_label  = sha_factorial_jobs_get_region_label( $region );
$field_definitions = sha_factorial_jobs_get_card_field_definitions();

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
		'<%1$s class="sfj-hint-target sfj-meta-item" data-sfj-card-field="%2$s" data-sfj-tooltip="%3$s" tabindex="0">%4$s<span class="sfj-meta-item__text">%5$s</span></%1$s>',
		tag_escape( $tag ),
		esc_attr( $field ),
		esc_attr( $tooltip ),
		$field_icon( $field, true ),
		esc_html( $value )
	);
};

$place_text = '';
if ( '' !== $location_name ) {
	$place_text = $region_label . ' · ' . $location_name;
} else {
	$place_text = $region_label;
}
?>
<article
	class="sfj-job-card"
	data-sfj-job-card
	data-region="<?php echo esc_attr( $region ); ?>"
	data-location-id="<?php echo esc_attr( $location_name ); ?>"
	data-team-id="<?php echo esc_attr( $team_name ); ?>"
	data-legal-entity-id="<?php echo esc_attr( $entity_name ); ?>"
	data-category="<?php echo esc_attr( (string) ( $job['category'] ?? '' ) ); ?>"
	data-contract-type="<?php echo esc_attr( (string) ( $job['contract_type'] ?? '' ) ); ?>"
	data-workplace-type="<?php echo esc_attr( (string) ( $job['workplace_type'] ?? '' ) ); ?>"
	data-schedule-type="<?php echo esc_attr( (string) ( $job['schedule_type'] ?? '' ) ); ?>"
	data-remote="<?php echo ! empty( $job['remote'] ) ? 'true' : 'false'; ?>"
>
	<header class="sfj-job-card__header">
		<div class="sfj-job-card__badges">
			<?php if ( $show( 'contract_type' ) && ! empty( $job['contract_type'] ) ) : ?>
				<?php
				$contract_label = sha_factorial_jobs_enum_label( 'contract_type', (string) $job['contract_type'] );
				?>
				<span
					class="sfj-job-card__badge sfj-hint-target"
					data-sfj-card-field="contract_type"
					data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'contract_type', $contract_label, $field_definitions ) ); ?>"
					tabindex="0"
				>
					<?php echo $field_icon( 'contract_type', true ); ?>
					<?php echo esc_html( $contract_label ); ?>
				</span>
			<?php endif; ?>
			<?php if ( $show( 'category' ) && ! empty( $job['category'] ) ) : ?>
				<?php
				$category_label = sha_factorial_jobs_enum_label( 'category', (string) $job['category'] );
				?>
				<span
					class="sfj-job-card__badge sfj-hint-target"
					data-sfj-card-field="category"
					data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'category', $category_label, $field_definitions ) ); ?>"
					tabindex="0"
				>
					<?php echo $field_icon( 'category', true ); ?>
					<?php echo esc_html( $category_label ); ?>
				</span>
			<?php endif; ?>
			<?php if ( $show( 'remote' ) && ! empty( $job['remote'] ) ) : ?>
				<?php $remote_label = __( 'Remote', 'sha-factorial-jobs' ); ?>
				<span
					class="sfj-job-card__badge sfj-job-card__badge--remote sfj-hint-target"
					data-sfj-card-field="remote"
					data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'remote', $remote_label, $field_definitions ) ); ?>"
					tabindex="0"
				>
					<?php echo $field_icon( 'remote', true ); ?>
					<?php echo esc_html( $remote_label ); ?>
				</span>
			<?php endif; ?>
		</div>
		<h3 class="sfj-job-card__title">
			<?php if ( $show( 'detail_link' ) && '' !== $detail_url ) : ?>
				<a class="sfj-job-card__title-link" href="<?php echo esc_url( $detail_url ); ?>" data-sfj-card-field="detail_link"<?php echo sha_factorial_jobs_detail_link_rel_attr(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $title ); ?></a>
			<?php else : ?>
				<span data-sfj-card-field="title"><?php echo esc_html( $title ); ?></span>
			<?php endif; ?>
		</h3>
		<div class="sfj-job-card__facts">
	<?php if ( $show( 'location' ) && '' !== $place_text ) : ?>
		<p
			class="sfj-job-card__place sfj-hint-target"
			data-sfj-card-field="location"
			data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'location', $place_text, $field_definitions ) ); ?>"
			tabindex="0"
		>
			<?php echo $field_icon( 'location', true ); ?>
			<span class="sfj-meta-item__text"><?php echo esc_html( $place_text ); ?></span>
		</p>
	<?php endif; ?>

	<ul class="sfj-job-card__meta">
		<?php
		if ( $show( 'workplace_type' ) && ! empty( $job['workplace_type'] ) ) {
			$hint_item( 'workplace_type', sha_factorial_jobs_enum_label( 'workplace_type', (string) $job['workplace_type'] ) );
		}
		if ( $show( 'schedule_type' ) && ! empty( $job['schedule_type'] ) ) {
			$hint_item( 'schedule_type', sha_factorial_jobs_enum_label( 'schedule_type', (string) $job['schedule_type'] ) );
		}
		if ( $show( 'team' ) && '' !== $team_name ) {
			$hint_item( 'team', $team_name );
		}
		if ( $show( 'legal_entity' ) && '' !== $entity_name ) {
			$hint_item( 'legal_entity', $entity_name );
		}
		?>
	</ul>

	<?php if ( $show( 'published_at' ) && false !== $published_at ) : ?>
		<?php $date_text = sha_factorial_jobs_format_published_date( $published_at ); ?>
		<p
			class="sfj-job-card__date sfj-hint-target"
			data-sfj-card-field="published_at"
			data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'published_at', $date_text, $field_definitions ) ); ?>"
			tabindex="0"
		>
			<?php echo $field_icon( 'published_at', true ); ?>
			<time datetime="<?php echo esc_attr( gmdate( 'c', $published_at ) ); ?>">
				<?php echo esc_html( $date_text ); ?>
			</time>
		</p>
	<?php endif; ?>

	<?php if ( $show( 'salary' ) && '' !== $salary ) : ?>
		<p
			class="sfj-job-card__salary sfj-hint-target"
			data-sfj-card-field="salary"
			data-sfj-tooltip="<?php echo esc_attr( sha_factorial_jobs_field_tooltip( 'salary', $salary, $field_definitions ) ); ?>"
			tabindex="0"
		>
			<?php echo $field_icon( 'salary', true ); ?>
			<?php echo esc_html( $salary ); ?>
		</p>
	<?php endif; ?>
		</div>
	</header>

	<div class="sfj-job-card__actions">
		<?php if ( $show( 'detail_link' ) && ( $preview_mode || '' !== $detail_url ) ) : ?>
			<a class="sfj-job-card__link" href="<?php echo $preview_mode ? '#' : esc_url( $detail_url ); ?>" data-sfj-card-field="detail_link"<?php echo $preview_mode ? '' : sha_factorial_jobs_detail_link_rel_attr(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo $preview_mode ? 'onclick="return false;"' : ''; ?>>
				<?php esc_html_e( 'View detail', 'sha-factorial-jobs' ); ?>
			</a>
		<?php endif; ?>
		<?php if ( $show( 'apply_link' ) && '' !== $apply_url ) : ?>
			<a class="sfj-job-card__cta" href="<?php echo esc_url( $apply_url ); ?>" target="_blank" rel="noopener noreferrer" data-sfj-card-field="apply_link">
				<?php esc_html_e( 'Apply', 'sha-factorial-jobs' ); ?>
			</a>
		<?php endif; ?>
	</div>
</article>
