<?php
/**
 * List / grid layout toggle (desktop only).
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sfj-filters__field sfj-filters__layout">
	<span class="sfj-filters__label sfj-filters__label--actions"><?php esc_html_e( 'Actions', 'sha-factorial-jobs' ); ?></span>
	<div class="sfj-filters__toolbar-controls sfj-filters__toolbar-controls--layout-only">
		<?php echo sha_factorial_jobs_render_template( 'partials/layout-toggle-buttons.php', array() ); ?>
	</div>
</div>
