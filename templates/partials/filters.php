<?php
/**
 * Filters partial.
 *
 * @package ShaFactorialJobs
 * @var array<int, array<string, mixed>> $jobs
 * @var Sha_Factorial_Lookup_Repository|null $lookups
 * @var array<string, array<string, string>> $lookup_labels
 * @var array<string, bool>               $card_fields
 * @var bool                              $show_layout_toggle
 */

defined( 'ABSPATH' ) || exit;

$show_layout_toggle = ! empty( $show_layout_toggle );

$card_fields = isset( $card_fields ) && is_array( $card_fields )
	? sha_factorial_jobs_normalize_card_fields( $card_fields )
	: sha_factorial_jobs_normalize_card_fields( sha_factorial_jobs_get_settings()['card_fields'] ?? array() );

$dimensions = array(
	'region'          => array(),
	'location_id'     => array(),
	'legal_entity_id' => array(),
	'team_id'         => array(),
	'category'        => array(),
	'contract_type'   => array(),
	'workplace_type'  => array(),
	'schedule_type'   => array(),
);

$field_labels = array(
	'region'          => __( 'Region', 'sha-factorial-jobs' ),
	'location_id'     => __( 'Location', 'sha-factorial-jobs' ),
	'legal_entity_id' => __( 'Legal entity', 'sha-factorial-jobs' ),
	'team_id'         => __( 'Area / Department', 'sha-factorial-jobs' ),
	'category'        => __( 'Category', 'sha-factorial-jobs' ),
	'contract_type'   => __( 'Contract type', 'sha-factorial-jobs' ),
	'workplace_type'  => __( 'Workplace type', 'sha-factorial-jobs' ),
	'schedule_type'   => __( 'Schedule', 'sha-factorial-jobs' ),
);

$settings       = sha_factorial_jobs_get_settings();
$lookups        = isset( $lookups ) && $lookups instanceof Sha_Factorial_Lookup_Repository ? $lookups : null;
$lookup_labels  = isset( $lookup_labels ) && is_array( $lookup_labels ) ? $lookup_labels : array();
$enum_fields    = array( 'category', 'contract_type', 'workplace_type', 'schedule_type' );
$lookup_fields  = array(
	'location_id'     => 'locations',
	'legal_entity_id' => 'legal_entities',
	'team_id'         => 'teams',
);

foreach ( $jobs as $job ) {
	$region = (string) ( $job['_region'] ?? '' );

	if ( '' !== $region ) {
		$default_region_label = 'es' === $region
			? __( 'Spain', 'sha-factorial-jobs' )
			: __( 'Mexico', 'sha-factorial-jobs' );
		$dimensions['region'][ $region ] = (string) ( $settings['connections'][ $region ]['label'] ?? $default_region_label );
	}

	foreach ( $lookup_fields as $field => $lookup_type ) {
		$id = (string) ( $job[ $field ] ?? '' );

		if ( '' === $id ) {
			continue;
		}

		$label = sha_factorial_jobs_resolve_lookup_name( $lookups, $region, $lookup_type, $id, $lookup_labels );
		$value = '' !== $label ? $label : $region . ':' . $id;
		$dimensions[ $field ][ $value ] = '' !== $label ? $label : $id;
	}

	foreach ( $enum_fields as $field ) {
		$slug = (string) ( $job[ $field ] ?? '' );
		if ( '' !== $slug ) {
			$dimensions[ $field ][ $slug ] = sha_factorial_jobs_enum_label( $field, $slug );
		}
	}
}

foreach ( $dimensions as &$options ) {
	natcasesort( $options );
}
unset( $options );

foreach ( array_keys( $dimensions ) as $dimension ) {
	if ( ! sha_factorial_jobs_filter_dimension_enabled( $dimension, $card_fields ) ) {
		unset( $dimensions[ $dimension ] );
	}
}

$filters_class = 'sfj-filters';

if ( ! sha_factorial_jobs_filter_has_button_style() ) {
	$filters_class .= ' sfj-filters--plain';
}
?>
<div class="<?php echo esc_attr( $filters_class ); ?>" data-sfj-filters>
	<div class="sfj-filters__fields">
	<?php foreach ( $dimensions as $field => $options ) : ?>
		<?php if ( count( $options ) < 1 ) : continue; endif; ?>
		<?php
		$is_locked = count( $options ) < 2;
		$only_value = $is_locked ? (string) array_key_first( $options ) : '';
		$only_label = $is_locked ? (string) $options[ $only_value ] : '';
		?>
		<label class="sfj-filters__field<?php echo $is_locked ? ' sfj-filters__field--locked' : ''; ?>"<?php echo $is_locked ? ' hidden' : ''; ?>>
			<span class="sfj-filters__label"><?php echo esc_html( $field_labels[ $field ] ?? $field ); ?></span>
			<select data-sfj-filter="<?php echo esc_attr( $field ); ?>"<?php echo $is_locked ? ' data-sfj-filter-locked="true"' : ''; ?>>
				<?php if ( ! $is_locked ) : ?>
					<option value=""><?php esc_html_e( 'All', 'sha-factorial-jobs' ); ?></option>
				<?php endif; ?>
				<?php if ( $is_locked ) : ?>
					<option value="<?php echo esc_attr( $only_value ); ?>" selected><?php echo esc_html( $only_label ); ?></option>
				<?php else : ?>
					<?php foreach ( $options as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</label>
	<?php endforeach; ?>
	</div>
	<div class="sfj-filters__toolbar">
		<div class="sfj-filters__field sfj-filters__layout">
			<span class="sfj-filters__label sfj-filters__label--actions"><?php esc_html_e( 'Actions', 'sha-factorial-jobs' ); ?></span>
			<div class="sfj-filters__toolbar-controls">
				<?php
				$clear_label = __( 'Clear filters', 'sha-factorial-jobs' );
				?>
				<button
					type="button"
					class="sfj-filters__clear sfj-toolbar-icon-btn sfj-hint-target"
					data-sfj-filter-clear
					hidden
					data-sfj-tooltip="<?php echo esc_attr( $clear_label ); ?>"
					title="<?php echo esc_attr( $clear_label ); ?>"
				>
					<span class="sfj-toolbar-icon sfj-toolbar-icon--clear" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php echo esc_html( $clear_label ); ?></span>
				</button>
				<?php if ( $show_layout_toggle ) : ?>
					<?php echo sha_factorial_jobs_render_template( 'partials/layout-toggle-buttons.php', array() ); ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
