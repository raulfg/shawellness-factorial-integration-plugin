<?php
/**
 * Plugin activation.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles activation defaults.
 */
final class Sha_Factorial_Activator {

	/**
	 * @return array<string, mixed>
	 */
	public static function get_default_settings(): array {
		return array(
			'connections' => array(
				'es' => array(
					'label'   => __( 'Spain', 'sha-factorial-jobs' ),
					'api_key' => '',
					'enabled' => true,
				),
				'mx' => array(
					'label'   => __( 'Mexico', 'sha-factorial-jobs' ),
					'api_key' => '',
					'enabled' => true,
				),
			),
			'cache_ttl'   => 1800,
			'card_fields' => self::get_default_card_fields(),
			'styles'      => self::get_default_styles(),
			'defaults'    => array(
				'layout'       => 'list',
				'show_filters' => false,
			),
		);
	}

	/**
	 * @return array<string, bool>
	 */
	public static function get_default_card_fields(): array {
		return array(
			'category'       => true,
			'contract_type'    => true,
			'workplace_type'   => true,
			'schedule_type'    => true,
			'remote'           => false,
			'location'         => true,
			'team'             => false,
			'legal_entity'     => false,
			'salary'           => true,
			'published_at'     => false,
			'detail_link'      => true,
			'apply_link'       => true,
		);
	}

	/**
	 * @return array<string, string|bool|int>
	 */
	public static function get_default_styles(): array {
		return array(
			'accent_color'       => '#2271b1',
			'accent_text_color'  => '#ffffff',
			'link_color'         => '#135e96',
			'badge_background'   => '#e8f2fc',
			'badge_text'         => '#135e96',
			'muted_text_color'   => '#5c6b7a',
			'show_card'          => true,
			'card_background'    => '#ffffff',
			'card_border_color'  => '#e4ebe8',
			'card_border_radius' => 16,
			'card_padding'       => 24,
			'card_shadow'        => true,
			'grid_gap'              => 20,
			'title_font_size'       => 125,
			'filter_button_style' => true,
			'filter_label_size'   => 100,
			'filter_select_size'  => 100,
			'filter_background'     => '#ffffff',
			'filter_border_color'   => '#e4ebe8',
			'filter_text_color'     => '#5c6b7a',
			'filter_border_radius'  => 10,
			'filter_gap'            => 12,
			'filter_spacing_bottom' => 20,
		);
	}

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		if ( false === get_option( 'sha_factorial_jobs_settings', false ) ) {
			add_option( 'sha_factorial_jobs_settings', self::get_default_settings() );
		}
	}
}
