<?php
/**
 * Helper functions.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, mixed>
 */
function sha_factorial_jobs_get_settings(): array {
	$defaults = Sha_Factorial_Activator::get_default_settings();
	$stored   = get_option( 'sha_factorial_jobs_settings', array() );

	if ( ! is_array( $stored ) ) {
		return $defaults;
	}

	$settings = wp_parse_args( $stored, $defaults );
	$settings['card_fields'] = sha_factorial_jobs_normalize_card_fields( $settings['card_fields'] ?? array() );
	$settings['styles']      = sha_factorial_jobs_normalize_styles( $settings['styles'] ?? array() );

	if ( ! isset( $stored['card_fields'] ) && isset( $settings['defaults']['show_salary'] ) ) {
		$settings['card_fields']['salary'] = (bool) $settings['defaults']['show_salary'];
	}

	return $settings;
}

/**
 * Determines whether the visual style editor is available in the admin panel.
 */
function sha_factorial_jobs_style_editor_enabled(): bool {
	$enabled = defined( 'SHA_FACTORIAL_JOBS_STYLE_EDITOR_ENABLED' )
		? SHA_FACTORIAL_JOBS_STYLE_EDITOR_ENABLED
		: false;

	return (bool) apply_filters( 'sha_factorial_jobs_style_editor_enabled', $enabled );
}

/**
 * @return array<string, array<string, mixed>>
 */
function sha_factorial_jobs_get_style_definitions(): array {
	return array(
		'accent_color' => array(
			'label'   => __( 'Accent color', 'sha-factorial-jobs' ),
			'hint'    => __( 'Apply button and highlighted elements.', 'sha-factorial-jobs' ),
			'type'    => 'color',
			'default' => '#2271b1',
			'group'   => 'colors',
			'css_var' => '--sfj-accent',
		),
		'accent_text_color' => array(
			'label'   => __( 'Text on accent', 'sha-factorial-jobs' ),
			'hint'    => __( 'Apply button text.', 'sha-factorial-jobs' ),
			'type'    => 'color',
			'default' => '#ffffff',
			'group'   => 'colors',
			'css_var' => '--sfj-accent-text',
		),
		'link_color' => array(
			'label'   => __( 'Link color', 'sha-factorial-jobs' ),
			'hint'    => __( 'View detail and back to listing.', 'sha-factorial-jobs' ),
			'type'    => 'color',
			'default' => '#135e96',
			'group'   => 'colors',
			'css_var' => '--sfj-link',
		),
		'badge_background' => array(
			'label'   => __( 'Badge background', 'sha-factorial-jobs' ),
			'hint'    => __( 'Category and remote badge.', 'sha-factorial-jobs' ),
			'type'    => 'color',
			'default' => '#e8f2fc',
			'group'   => 'colors',
			'css_var' => '--sfj-badge-bg',
		),
		'badge_text' => array(
			'label'   => __( 'Badge text', 'sha-factorial-jobs' ),
			'type'    => 'color',
			'default' => '#135e96',
			'group'   => 'colors',
			'css_var' => '--sfj-badge-text',
		),
		'muted_text_color' => array(
			'label'   => __( 'Secondary text', 'sha-factorial-jobs' ),
			'hint'    => __( 'Meta, dates, and filters.', 'sha-factorial-jobs' ),
			'type'    => 'color',
			'default' => '#5c6b7a',
			'group'   => 'colors',
			'css_var' => '--sfj-muted',
		),
		'show_card' => array(
			'label'   => __( 'Container card', 'sha-factorial-jobs' ),
			'hint'    => __( 'Disable to remove the box around each job posting (no border, background, or shadow).', 'sha-factorial-jobs' ),
			'type'    => 'checkbox',
			'default' => true,
			'group'   => 'card',
		),
		'card_background' => array(
			'label'   => __( 'Card background', 'sha-factorial-jobs' ),
			'type'    => 'color',
			'default' => '#ffffff',
			'group'   => 'card',
			'css_var' => '--sfj-card-bg',
		),
		'card_border_color' => array(
			'label'   => __( 'Card border', 'sha-factorial-jobs' ),
			'type'    => 'color',
			'default' => '#e4ebe8',
			'group'   => 'card',
			'css_var' => '--sfj-card-border',
		),
		'card_border_radius' => array(
			'label'   => __( 'Border radius', 'sha-factorial-jobs' ),
			'type'    => 'range',
			'default' => 16,
			'min'     => 0,
			'max'     => 32,
			'unit'    => 'px',
			'group'   => 'card',
			'css_var' => '--sfj-card-radius',
		),
		'card_padding' => array(
			'label'   => __( 'Card padding', 'sha-factorial-jobs' ),
			'type'    => 'range',
			'default' => 24,
			'min'     => 12,
			'max'     => 40,
			'unit'    => 'px',
			'group'   => 'card',
			'css_var' => '--sfj-card-padding',
		),
		'card_shadow' => array(
			'label'   => __( 'Card shadow', 'sha-factorial-jobs' ),
			'type'    => 'checkbox',
			'default' => true,
			'group'   => 'card',
			'css_var' => '--sfj-card-shadow',
			'on'      => '0 10px 30px rgba(20, 34, 30, 0.05)',
			'off'     => 'none',
		),
		'grid_gap' => array(
			'label'   => __( 'Gap between cards', 'sha-factorial-jobs' ),
			'type'    => 'range',
			'default' => 20,
			'min'     => 8,
			'max'     => 40,
			'unit'    => 'px',
			'group'   => 'spacing',
			'css_var' => '--sfj-grid-gap',
		),
		'title_font_size' => array(
			'label'   => __( 'Title size', 'sha-factorial-jobs' ),
			'type'    => 'range',
			'default' => 125,
			'min'     => 100,
			'max'     => 200,
			'unit'    => '%',
			'group'   => 'spacing',
			'css_var' => '--sfj-title-size',
		),
		'filter_button_style' => array(
			'label'   => __( 'Button style', 'sha-factorial-jobs' ),
			'hint'    => __( 'Disable for a flat select with only a bottom border.', 'sha-factorial-jobs' ),
			'type'    => 'checkbox',
			'default' => true,
			'group'   => 'filters',
		),
		'filter_label_size' => array(
			'label'   => __( 'Label size', 'sha-factorial-jobs' ),
			'hint'    => __( 'Text above the select (Category, Workplace type…).', 'sha-factorial-jobs' ),
			'type'    => 'range',
			'default' => 100,
			'min'     => 80,
			'max'     => 140,
			'unit'    => '%',
			'scale'   => true,
			'group'   => 'filters',
			'css_var' => '--sfj-filter-label-scale',
		),
		'filter_select_size' => array(
			'label'   => __( 'Select size', 'sha-factorial-jobs' ),
			'hint'    => __( 'Dropdown text and padding.', 'sha-factorial-jobs' ),
			'type'    => 'range',
			'default' => 100,
			'min'     => 80,
			'max'     => 140,
			'unit'    => '%',
			'scale'   => true,
			'group'   => 'filters',
			'css_var' => '--sfj-filter-select-scale',
		),
		'filter_background' => array(
			'label'   => __( 'Select background', 'sha-factorial-jobs' ),
			'type'    => 'color',
			'default' => '#ffffff',
			'group'   => 'filters',
			'css_var' => '--sfj-filter-bg',
		),
		'filter_border_color' => array(
			'label'   => __( 'Select border', 'sha-factorial-jobs' ),
			'type'    => 'color',
			'default' => '#e4ebe8',
			'group'   => 'filters',
			'css_var' => '--sfj-filter-border',
		),
		'filter_text_color' => array(
			'label'   => __( 'Label text', 'sha-factorial-jobs' ),
			'type'    => 'color',
			'default' => '#5c6b7a',
			'group'   => 'filters',
			'css_var' => '--sfj-filter-text',
		),
		'filter_border_radius' => array(
			'label'   => __( 'Border radius', 'sha-factorial-jobs' ),
			'type'    => 'range',
			'default' => 10,
			'min'     => 0,
			'max'     => 20,
			'unit'    => 'px',
			'group'   => 'filters',
			'css_var' => '--sfj-filter-radius',
		),
		'filter_gap' => array(
			'label'   => __( 'Gap between filters', 'sha-factorial-jobs' ),
			'type'    => 'range',
			'default' => 12,
			'min'     => 8,
			'max'     => 32,
			'unit'    => 'px',
			'group'   => 'filters',
			'css_var' => '--sfj-filter-gap',
		),
		'filter_spacing_bottom' => array(
			'label'   => __( 'Bottom margin', 'sha-factorial-jobs' ),
			'hint'    => __( 'Space between filters and listing.', 'sha-factorial-jobs' ),
			'type'    => 'range',
			'default' => 20,
			'min'     => 8,
			'max'     => 40,
			'unit'    => 'px',
			'group'   => 'filters',
			'css_var' => '--sfj-filter-spacing',
		),
	);
}

/**
 * @return array<string, string>
 */
function sha_factorial_jobs_get_style_group_labels(): array {
	return array(
		'colors'  => __( 'Colors', 'sha-factorial-jobs' ),
		'card'    => __( 'Card', 'sha-factorial-jobs' ),
		'filters' => __( 'Filters', 'sha-factorial-jobs' ),
		'spacing' => __( 'Spacing and typography', 'sha-factorial-jobs' ),
	);
}

/**
 * @param array<string, mixed> $stored Stored style values.
 * @return array<string, string|bool|int>
 */
function sha_factorial_jobs_normalize_styles( array $stored ): array {
	$normalized = array();
	$legacy_filter_size = null;

	if ( isset( $stored['filter_size'] ) && ! isset( $stored['filter_select_size'] ) && ! isset( $stored['filter_label_size'] ) ) {
		$legacy_filter_size = max( 80, min( 140, (int) $stored['filter_size'] ) );
	}

	foreach ( sha_factorial_jobs_get_style_definitions() as $key => $definition ) {
		$type    = $definition['type'];
		$default = $definition['default'];

		if ( ! isset( $stored[ $key ] ) ) {
			if ( null !== $legacy_filter_size && in_array( $key, array( 'filter_select_size', 'filter_label_size' ), true ) ) {
				$normalized[ $key ] = $legacy_filter_size;
				continue;
			}

			$normalized[ $key ] = $default;
			continue;
		}

		$value = $stored[ $key ];

		if ( 'color' === $type ) {
			$color = sanitize_hex_color( (string) $value );
			$normalized[ $key ] = $color ? $color : (string) $default;
			continue;
		}

		if ( 'checkbox' === $type ) {
			$normalized[ $key ] = (bool) $value;
			continue;
		}

		$min = (int) ( $definition['min'] ?? 0 );
		$max = (int) ( $definition['max'] ?? 100 );
		$normalized[ $key ] = max( $min, min( $max, (int) $value ) );
	}

	return $normalized;
}

/**
 * @param array<string, string|bool|int> $styles Style settings.
 * @return array<string, string>
 */
function sha_factorial_jobs_get_style_variables( array $styles ): array {
	$variables = array();
	$styles    = sha_factorial_jobs_normalize_styles( $styles );

	foreach ( sha_factorial_jobs_get_style_definitions() as $key => $definition ) {
		$css_var = $definition['css_var'] ?? '';
		$value   = $styles[ $key ] ?? $definition['default'];

		if ( '' === $css_var ) {
			continue;
		}

		if ( 'checkbox' === $definition['type'] ) {
			$variables[ $css_var ] = ! empty( $value )
				? (string) ( $definition['on'] ?? 'none' )
				: (string) ( $definition['off'] ?? 'none' );
			continue;
		}

		if ( 'range' === $definition['type'] && '%' === ( $definition['unit'] ?? '' ) ) {
			if ( ! empty( $definition['scale'] ) ) {
				$variables[ $css_var ] = (string) round( (int) $value / 100, 2 );
			} else {
				$variables[ $css_var ] = round( (int) $value / 100, 2 ) . 'rem';
			}
			continue;
		}

		$unit = (string) ( $definition['unit'] ?? '' );
		$variables[ $css_var ] = (string) $value . $unit;
	}

	$variables['--sfj-salary-color'] = (string) ( $styles['accent_color'] ?? '#2271b1' );

	return $variables;
}

/**
 * @param array<string, string|bool|int> $styles Style settings.
 */
function sha_factorial_jobs_get_styles_inline_style( array $styles ): string {
	$parts = array();

	foreach ( sha_factorial_jobs_get_style_variables( $styles ) as $name => $value ) {
		$parts[] = $name . ':' . $value;
	}

	return implode( ';', $parts );
}

/**
 * @param array<string, string|bool|int> $styles Style settings.
 */
function sha_factorial_jobs_filter_has_button_style( array $styles = array() ): bool {
	if ( empty( $styles ) ) {
		$settings = sha_factorial_jobs_get_settings();
		$styles   = $settings['styles'] ?? array();
	}

	$styles = sha_factorial_jobs_normalize_styles( $styles );

	return ! empty( $styles['filter_button_style'] );
}

/**
 * @param array<string, string|bool|int> $styles Style settings.
 */
function sha_factorial_jobs_should_show_card( array $styles = array() ): bool {
	if ( empty( $styles ) ) {
		$settings = sha_factorial_jobs_get_settings();
		$styles   = $settings['styles'] ?? array();
	}

	$styles = sha_factorial_jobs_normalize_styles( $styles );

	return ! empty( $styles['show_card'] );
}

/**
 * @param array<string, string|bool|int> $styles Style settings.
 */
function sha_factorial_jobs_get_styles_css( array $styles = array() ): string {
	if ( empty( $styles ) ) {
		$settings = sha_factorial_jobs_get_settings();
		$styles   = $settings['styles'] ?? array();
	}

	$variables = sha_factorial_jobs_get_style_variables( $styles );

	if ( empty( $variables ) ) {
		return '';
	}

	$declarations = array();

	foreach ( $variables as $name => $value ) {
		$declarations[] = "\t" . $name . ': ' . $value . ';';
	}

	return ".sfj-jobs,\n.sfj-job-single {\n" . implode( "\n", $declarations ) . "\n}";
}

/**
 * @return array<string, array{label: string, hint: string, default: bool}>
 */
function sha_factorial_jobs_get_card_field_definitions(): array {
	return array(
		'category'      => array(
			'label'   => __( 'Category', 'sha-factorial-jobs' ),
			'hint'    => __( 'Badge with the professional area (Factorial enum).', 'sha-factorial-jobs' ),
			'default' => true,
		),
		'contract_type' => array(
			'label'   => __( 'Contract type', 'sha-factorial-jobs' ),
			'hint'    => __( 'Permanent, temporary, internship, etc.', 'sha-factorial-jobs' ),
			'default' => true,
		),
		'workplace_type' => array(
			'label'   => __( 'Workplace type', 'sha-factorial-jobs' ),
			'hint'    => __( 'On-site, remote, or hybrid.', 'sha-factorial-jobs' ),
			'default' => true,
		),
		'schedule_type' => array(
			'label'   => __( 'Schedule', 'sha-factorial-jobs' ),
			'hint'    => __( 'Full-time or part-time.', 'sha-factorial-jobs' ),
			'default' => true,
		),
		'remote'        => array(
			'label'   => __( 'Remote', 'sha-factorial-jobs' ),
			'hint'    => __( 'Label when the job allows remote work.', 'sha-factorial-jobs' ),
			'default' => false,
		),
		'location'      => array(
			'label'   => __( 'Location', 'sha-factorial-jobs' ),
			'hint'    => __( 'Name resolved from Factorial locations.', 'sha-factorial-jobs' ),
			'default' => true,
		),
		'team'          => array(
			'label'   => __( 'Team', 'sha-factorial-jobs' ),
			'hint'    => __( 'Department or team (teams lookup).', 'sha-factorial-jobs' ),
			'default' => false,
		),
		'legal_entity'  => array(
			'label'   => __( 'Legal entity', 'sha-factorial-jobs' ),
			'hint'    => __( 'Useful on pages with region="all".', 'sha-factorial-jobs' ),
			'default' => false,
		),
		'salary'        => array(
			'label'   => __( 'Salary', 'sha-factorial-jobs' ),
			'hint'    => __( 'Respects Factorial hide_salary.', 'sha-factorial-jobs' ),
			'default' => true,
		),
		'published_at'  => array(
			'label'   => __( 'Published date', 'sha-factorial-jobs' ),
			'hint'    => __( 'When the job posting was published.', 'sha-factorial-jobs' ),
			'default' => false,
		),
		'detail_link'   => array(
			'label'   => __( 'View detail link', 'sha-factorial-jobs' ),
			'hint'    => __( 'Only when the job posting has a description.', 'sha-factorial-jobs' ),
			'default' => true,
		),
		'apply_link'    => array(
			'label'   => __( 'Apply button', 'sha-factorial-jobs' ),
			'hint'    => __( 'Public job posting URL.', 'sha-factorial-jobs' ),
			'default' => true,
		),
	);
}

/**
 * @param array<string, bool> $stored Stored card field toggles.
 * @return array<string, bool>
 */
function sha_factorial_jobs_normalize_card_fields( array $stored ): array {
	$normalized = array();

	foreach ( sha_factorial_jobs_get_card_field_definitions() as $field => $definition ) {
		$normalized[ $field ] = isset( $stored[ $field ] )
			? (bool) $stored[ $field ]
			: (bool) $definition['default'];
	}

	return $normalized;
}

/**
 * @param string              $field       Field key.
 * @param array<string, bool> $card_fields Card field toggles.
 */
function sha_factorial_jobs_card_field_enabled( string $field, array $card_fields ): bool {
	return ! empty( $card_fields[ $field ] );
}

/**
 * Maps client-side filter dimensions to card field toggles.
 *
 * @return array<string, string>
 */
function sha_factorial_jobs_filter_dimension_card_field_map(): array {
	return array(
		'region'          => 'location',
		'location_id'     => 'location',
		'legal_entity_id' => 'legal_entity',
		'team_id'         => 'team',
		'category'        => 'category',
		'contract_type'   => 'contract_type',
		'workplace_type'  => 'workplace_type',
		'schedule_type'   => 'schedule_type',
	);
}

/**
 * Whether a filter dimension is allowed for the current card field configuration.
 *
 * @param array<string, bool> $card_fields Card field toggles.
 */
function sha_factorial_jobs_filter_dimension_enabled( string $dimension, array $card_fields ): bool {
	$map = sha_factorial_jobs_filter_dimension_card_field_map();

	if ( ! isset( $map[ $dimension ] ) ) {
		return false;
	}

	return sha_factorial_jobs_card_field_enabled( $map[ $dimension ], $card_fields );
}

/**
 * @param Sha_Factorial_Lookup_Repository|null $lookups        Lookup repository.
 * @param string                               $region         Region slug.
 * @param string                               $type           Lookup type.
 * @param string                               $id             Entity ID.
 * @param array<string, array<string, string>> $lookup_labels  Optional label overrides.
 */
function sha_factorial_jobs_resolve_lookup_name( ?Sha_Factorial_Lookup_Repository $lookups, string $region, string $type, string $id, array $lookup_labels = array() ): string {
	if ( '' === $id ) {
		return '';
	}

	if ( isset( $lookup_labels[ $type ][ $id ] ) ) {
		return (string) $lookup_labels[ $type ][ $id ];
	}

	if ( null === $lookups ) {
		return '';
	}

	return $lookups->get_name( $region, $type, $id );
}

/**
 * @return array<string, mixed>
 */
function sha_factorial_jobs_get_preview_job(): array {
	$jobs = sha_factorial_jobs_get_preview_jobs();

	return $jobs[0];
}

/**
 * @return array<int, array<string, mixed>>
 */
function sha_factorial_jobs_get_preview_jobs(): array {
	$jobs = array(
		array(
			'id'                          => 'preview-1',
			'_region'                     => 'es',
			'title'                       => __( 'Senior Physiotherapist', 'sha-factorial-jobs' ),
			'description'                 => __( 'Full job description with requirements, benefits, and hiring process.', 'sha-factorial-jobs' ),
			'category'                    => 'nursing_and_therapy',
			'contract_type'               => 'indefinite',
			'workplace_type'              => 'hybrid',
			'schedule_type'               => 'full_time',
			'remote'                      => true,
			'location_id'                 => '1',
			'team_id'                     => '1',
			'legal_entity_id'             => '1',
			'salary_format'               => 'range',
			'salary_from_amount_in_cents' => 3200000,
			'salary_to_amount_in_cents'   => 3800000,
			'salary_period'               => 'annual',
			'hide_salary'                 => false,
			'published_at'                => gmdate( 'c', strtotime( '-5 days' ) ),
			'url'                         => 'https://factorialhr.com/',
		),
		array(
			'id'                          => 'preview-2',
			'_region'                     => 'es',
			'title'                       => __( 'Clinical Nurse', 'sha-factorial-jobs' ),
			'description'                 => __( 'Clinical profile for an integrative medicine unit.', 'sha-factorial-jobs' ),
			'category'                    => 'nursing_and_therapy',
			'contract_type'               => 'indefinite',
			'workplace_type'              => 'onsite',
			'schedule_type'               => 'full_time',
			'remote'                      => false,
			'location_id'                 => '1',
			'team_id'                     => '1',
			'legal_entity_id'             => '1',
			'salary_format'               => 'fixed_amount',
			'salary_from_amount_in_cents' => 2800000,
			'salary_to_amount_in_cents'   => 0,
			'salary_period'               => 'annual',
			'hide_salary'                 => false,
			'published_at'                => gmdate( 'c', strtotime( '-12 days' ) ),
			'url'                         => 'https://factorialhr.com/',
		),
		array(
			'id'                          => 'preview-3',
			'_region'                     => 'mx',
			'title'                       => __( 'Sports Nutritionist', 'sha-factorial-jobs' ),
			'description'                 => '',
			'category'                    => 'sciences_and_research',
			'contract_type'               => 'temporary',
			'workplace_type'              => 'remote',
			'schedule_type'               => 'part_time',
			'remote'                      => true,
			'location_id'                 => '2',
			'team_id'                     => '2',
			'legal_entity_id'             => '2',
			'salary_format'               => 'range',
			'salary_from_amount_in_cents' => 45000000,
			'salary_to_amount_in_cents'   => 55000000,
			'salary_period'               => 'monthly',
			'hide_salary'                 => false,
			'published_at'                => gmdate( 'c', strtotime( '-2 days' ) ),
			'url'                         => 'https://factorialhr.com/',
		),
	);

	return sha_factorial_jobs_sort_jobs_by_published_at_desc( $jobs );
}

/**
 * @return array<string, array<string, string>>
 */
function sha_factorial_jobs_get_preview_lookup_labels(): array {
	return array(
		'locations'      => array(
			'1' => __( 'SHA Wellness Clinic, Alicante', 'sha-factorial-jobs' ),
			'2' => __( 'SHA Mexico, CDMX', 'sha-factorial-jobs' ),
		),
		'teams'          => array(
			'1' => __( 'Therapies & Wellness', 'sha-factorial-jobs' ),
			'2' => __( 'Sports Nutrition', 'sha-factorial-jobs' ),
		),
		'legal_entities' => array(
			'1' => __( 'SHA Spain', 'sha-factorial-jobs' ),
			'2' => __( 'SHA Mexico', 'sha-factorial-jobs' ),
		),
	);
}

/**
 * @param array<string, bool>              $card_fields   Card field toggles.
 * @param array<string, string|bool|int> $styles        Optional style settings.
 * @param bool                             $preview_mode  Show all configured fields in preview.
 */
function sha_factorial_jobs_render_admin_preview_list( array $card_fields, array $styles = array(), bool $preview_mode = true ): string {
	$lookup_labels = sha_factorial_jobs_get_preview_lookup_labels();
	$jobs          = sha_factorial_jobs_get_preview_jobs();
	$has_styles   = ! empty( $styles );
	$style_attr   = $has_styles ? sha_factorial_jobs_get_styles_inline_style( $styles ) : '';
	$preview_attr  = $has_styles ? ' data-sfj-style-preview' : ' data-sfj-card-preview';
	$show_card     = $has_styles ? sha_factorial_jobs_should_show_card( $styles ) : true;
	$wrapper_class = 'sfj-jobs sfj-jobs--list' . ( $show_card ? '' : ' sfj-jobs--no-card' );

	ob_start();
	?>
	<div class="<?php echo esc_attr( $wrapper_class ); ?>"<?php echo $preview_attr; ?><?php echo '' !== $style_attr ? ' style="' . esc_attr( $style_attr ) . '"' : ''; ?>>
		<?php if ( $has_styles ) : ?>
			<?php
			echo sha_factorial_jobs_render_template(
				'partials/filters.php',
				array(
					'jobs'          => $jobs,
					'lookups'       => null,
					'lookup_labels' => $lookup_labels,
					'card_fields'   => $card_fields,
				)
			);
			?>
		<?php endif; ?>
		<div class="sfj-jobs__items">
			<?php foreach ( $jobs as $job ) : ?>
				<?php
				echo sha_factorial_jobs_render_template(
					'partials/job-card.php',
					array(
						'job'           => $job,
						'card_fields'   => $card_fields,
						'lookups'       => null,
						'lookup_labels' => $lookup_labels,
						'preview_mode'  => $preview_mode,
					)
				);
				?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param array<string, mixed> $settings Plugin settings.
 */
function sha_factorial_jobs_update_settings( array $settings ): bool {
	return update_option( 'sha_factorial_jobs_settings', $settings );
}

/**
 * @param string $region Region slug.
 * @return bool
 */
function sha_factorial_jobs_is_valid_region( string $region ): bool {
	return in_array( $region, array( 'es', 'mx', 'all' ), true );
}

/**
 * Whether the request is a job detail view (?sfj_job= on the jobs page).
 */
function sha_factorial_jobs_is_job_detail_request(): bool {
	if ( is_admin() ) {
		return false;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$job_id = isset( $_GET['sfj_job'] ) ? sanitize_text_field( wp_unslash( $_GET['sfj_job'] ) ) : '';

	return '' !== $job_id;
}

/**
 * Whether the current front request is the job listing (page with [sha_factorial_jobs], not detail).
 */
function sha_factorial_jobs_is_jobs_listing_view(): bool {
	if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
		return false;
	}

	if ( sha_factorial_jobs_is_job_detail_request() ) {
		return false;
	}

	if ( ! is_singular() ) {
		return false;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	return has_shortcode( $post->post_content, 'sha_factorial_jobs' );
}

/**
 * HTML attribute to discourage search snippets on volatile shortcode output.
 *
 * Not a guaranteed noindex for in-page HTML; see readme-dev SEO section.
 */
function sha_factorial_jobs_markup_snippet_exclusion_attr(): string {
	if ( ! apply_filters( 'sha_factorial_jobs_exclude_shortcode_from_snippets', true ) ) {
		return '';
	}

	return ' data-nosnippet';
}

/**
 * rel attribute for links to job detail URLs (?sfj_job=).
 */
function sha_factorial_jobs_detail_link_rel_attr(): string {
	$rels = array();

	if ( apply_filters( 'sha_factorial_jobs_detail_link_nofollow', true ) ) {
		$rels[] = 'nofollow';
	}

	if ( array() === $rels ) {
		return '';
	}

	return ' rel="' . esc_attr( implode( ' ', $rels ) ) . '"';
}

/**
 * Base URL of the page that contains the jobs shortcode.
 */
function sha_factorial_jobs_get_page_base_url(): string {
	if ( is_singular() ) {
		$permalink = get_permalink();

		if ( is_string( $permalink ) && '' !== $permalink ) {
			return $permalink;
		}
	}

	return home_url( add_query_arg( array() ) );
}

/**
 * @param string $region Region slug (es|mx).
 * @param string $job_id Job posting ID.
 */
function sha_factorial_jobs_get_job_detail_url( string $region, string $job_id ): string {
	return add_query_arg(
		array(
			'sfj_job'    => $job_id,
			'sfj_region' => $region,
		),
		sha_factorial_jobs_get_page_base_url()
	);
}

/**
 * URL to return to the jobs list (clears detail query args).
 */
function sha_factorial_jobs_get_list_url(): string {
	return remove_query_arg(
		array( 'sfj_job', 'sfj_region' ),
		sha_factorial_jobs_get_page_base_url()
	);
}

/**
 * Whether the job has content worth showing on the detail view.
 *
 * @param array<string, mixed> $job Job posting from API.
 */
function sha_factorial_jobs_job_has_detail( array $job ): bool {
	$description = trim( wp_strip_all_tags( (string) ( $job['description'] ?? '' ) ) );

	return '' !== $description;
}

/**
 * Human-readable region label for cards and filters.
 */
function sha_factorial_jobs_get_region_label( string $region ): string {
	$settings = sha_factorial_jobs_get_settings();
	$default  = 'es' === $region
		? __( 'Spain', 'sha-factorial-jobs' )
		: __( 'Mexico', 'sha-factorial-jobs' );

	return (string) ( $settings['connections'][ $region ]['label'] ?? $default );
}

/**
 * Tooltip text for a labelled field value.
 */
function sha_factorial_jobs_field_tooltip( string $field, string $value, array $field_definitions = array() ): string {
	if ( array() === $field_definitions ) {
		$field_definitions = sha_factorial_jobs_get_card_field_definitions();
	}

	$label = (string) ( $field_definitions[ $field ]['label'] ?? $field );

	return $label . ': ' . $value;
}

/**
 * @param array<int, array<string, mixed>> $jobs Job postings.
 * @return array<int, array<string, mixed>>
 */
function sha_factorial_jobs_sort_jobs_by_published_at_desc( array $jobs ): array {
	usort(
		$jobs,
		static function ( $a, $b ): int {
			$time_a = 0;
			$time_b = 0;

			if ( is_array( $a ) && ! empty( $a['published_at'] ) ) {
				$time_a = (int) strtotime( (string) $a['published_at'] );
			}

			if ( is_array( $b ) && ! empty( $b['published_at'] ) ) {
				$time_b = (int) strtotime( (string) $b['published_at'] );
			}

			return $time_b <=> $time_a;
		}
	);

	return $jobs;
}

/**
 * Compact numeric published date for cards (no month names).
 *
 * @param int $timestamp Unix timestamp.
 */
function sha_factorial_jobs_format_published_date( int $timestamp ): string {
	$format = apply_filters( 'sha_factorial_jobs_published_date_format', 'd/m/Y' );

	return wp_date( $format, $timestamp );
}

/**
 * @param array<string, mixed> $job Job posting from API.
 */
function sha_factorial_jobs_format_salary( array $job ): string {
	if ( ! empty( $job['hide_salary'] ) ) {
		return '';
	}

	$from = isset( $job['salary_from_amount_in_cents'] ) ? (int) $job['salary_from_amount_in_cents'] : 0;
	$to   = isset( $job['salary_to_amount_in_cents'] ) ? (int) $job['salary_to_amount_in_cents'] : 0;

	if ( $from <= 0 && $to <= 0 ) {
		return '';
	}

	$format_from = number_format_i18n( $from / 100, 0 );
	$format_to   = number_format_i18n( $to / 100, 0 );
	$period      = isset( $job['salary_period'] ) ? (string) $job['salary_period'] : 'annual';

	$period_label = sha_factorial_jobs_enum_label( 'salary_period', $period );

	if ( 'range' === ( $job['salary_format'] ?? '' ) && $to > $from ) {
		return sprintf(
			/* translators: 1: min salary, 2: max salary, 3: period */
			__( '%1$s – %2$s (%3$s)', 'sha-factorial-jobs' ),
			$format_from,
			$format_to,
			$period_label
		);
	}

	return sprintf(
		/* translators: 1: salary amount, 2: period */
		__( '%1$s (%2$s)', 'sha-factorial-jobs' ),
		$format_from,
		$period_label
	);
}

/**
 * Whether the current site locale is RTL (e.g. Arabic).
 */
function sha_factorial_jobs_is_rtl(): bool {
	return is_rtl();
}

/**
 * @param string $class Existing class string.
 */
function sha_factorial_jobs_direction_class( string $class = '' ): string {
	if ( ! sha_factorial_jobs_is_rtl() ) {
		return trim( $class );
	}

	return trim( $class . ' sfj-rtl' );
}

/**
 * HTML dir attribute for frontend markup.
 */
function sha_factorial_jobs_direction_attr(): string {
	return sha_factorial_jobs_is_rtl() ? ' dir="rtl"' : '';
}

/**
 * @param string $field Enum field name.
 * @param string $slug  Enum slug from API.
 */
function sha_factorial_jobs_enum_label( string $field, string $slug ): string {
	$registry = new Sha_Factorial_Enum_Registry();
	return $registry->get_label( $field, $slug );
}

/**
 * @param string               $template Template path relative to templates/.
 * @param array<string, mixed> $vars     Variables for template.
 */
function sha_factorial_jobs_render_template( string $template, array $vars = array() ): string {
	$loader = new Sha_Factorial_Template_Loader();
	$path   = $loader->locate( $template );

	if ( ! file_exists( $path ) ) {
		return '';
	}

	ob_start();
	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract( $vars, EXTR_SKIP );
	include $path;
	return (string) ob_get_clean();
}
