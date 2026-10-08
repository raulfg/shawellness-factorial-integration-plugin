<?php
/**
 * Jobs list template.
 *
 * @package ShaFactorialJobs
 * @var array<int, array<string, mixed>> $jobs
 * @var string $layout
 * @var bool $show_filters
 * @var array<string, bool> $card_fields
 * @var bool $show_card
 * @var Sha_Factorial_Lookup_Repository $lookups
 */

defined( 'ABSPATH' ) || exit;

$is_grid         = 'grid' === $layout;
$layout_classes  = array( 'sfj-jobs', $is_grid ? 'sfj-jobs--grid' : 'sfj-jobs--list' );
if ( ! $is_grid ) {
	$layout_classes[] = 'sfj-jobs--no-card';
}
$wrapper_class = sha_factorial_jobs_direction_class( implode( ' ', $layout_classes ) );
$has_jobs      = ! empty( $jobs );
?>
<div
	class="<?php echo esc_attr( $wrapper_class ); ?>"
	<?php echo sha_factorial_jobs_direction_attr(); ?>
	<?php echo sha_factorial_jobs_markup_snippet_exclusion_attr(); ?>
	data-sfj-jobs
	<?php if ( $has_jobs ) : ?>
		data-sfj-initial-layout="<?php echo esc_attr( $is_grid ? 'grid' : 'list' ); ?>"
	<?php endif; ?>
>
	<?php if ( $has_jobs ) : ?>
		<div class="sfj-jobs__toolbar<?php echo $show_filters ? '' : ' sfj-jobs__toolbar--layout-only'; ?>">
			<?php if ( $show_filters ) : ?>
				<?php
				echo sha_factorial_jobs_render_template(
					'partials/filters.php',
					array(
						'jobs'                 => $jobs,
						'lookups'              => $lookups,
						'card_fields'          => $card_fields,
						'show_layout_toggle'   => true,
					)
				);
				?>
			<?php else : ?>
				<?php echo sha_factorial_jobs_render_template( 'partials/layout-toggle.php', array() ); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! $has_jobs ) : ?>
		<p class="sfj-empty"><?php esc_html_e( 'No job postings available.', 'sha-factorial-jobs' ); ?></p>
	<?php else : ?>
		<div class="sfj-jobs__items">
			<?php foreach ( $jobs as $job ) : ?>
				<?php
				echo sha_factorial_jobs_render_template(
					'partials/job-card.php',
					array(
						'job'          => $job,
						'card_fields'  => $card_fields,
						'lookups'      => $lookups,
						'preview_mode' => false,
					)
				);
				?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
