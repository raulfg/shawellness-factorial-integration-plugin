<?php
/**
 * List / grid layout toggle buttons (no wrapper).
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

$list_label = __( 'List view', 'sha-factorial-jobs' );
$grid_label = __( 'Grid view', 'sha-factorial-jobs' );
?>
<div class="sfj-layout-toggle" data-sfj-layout-toggle role="group" aria-label="<?php esc_attr_e( 'Job list layout', 'sha-factorial-jobs' ); ?>">
	<button
		type="button"
		class="sfj-layout-toggle__btn sfj-hint-target"
		data-sfj-layout-btn="list"
		aria-pressed="false"
		data-sfj-tooltip="<?php echo esc_attr( $list_label ); ?>"
		title="<?php echo esc_attr( $list_label ); ?>"
	>
		<span class="sfj-layout-toggle__icon sfj-layout-toggle__icon--list" aria-hidden="true"></span>
		<span class="screen-reader-text"><?php echo esc_html( $list_label ); ?></span>
	</button>
	<button
		type="button"
		class="sfj-layout-toggle__btn sfj-hint-target"
		data-sfj-layout-btn="grid"
		aria-pressed="false"
		data-sfj-tooltip="<?php echo esc_attr( $grid_label ); ?>"
		title="<?php echo esc_attr( $grid_label ); ?>"
	>
		<span class="sfj-layout-toggle__icon sfj-layout-toggle__icon--grid" aria-hidden="true"></span>
		<span class="screen-reader-text"><?php echo esc_html( $grid_label ); ?></span>
	</button>
</div>
