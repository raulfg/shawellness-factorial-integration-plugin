<?php
/**
 * Shortcodes.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders frontend shortcodes.
 */
final class Sha_Factorial_Shortcodes {

	private Sha_Factorial_Jobs_Service $service;

	private bool $assets_enqueued = false;

	/**
	 * @param Sha_Factorial_Jobs_Service $service Jobs service.
	 */
	public function __construct( Sha_Factorial_Jobs_Service $service ) {
		$this->service = $service;
	}

	/**
	 * Register shortcodes.
	 */
	public function register(): void {
		add_shortcode( 'sha_factorial_jobs', array( $this, 'render_jobs_list' ) );
		add_shortcode( 'sha_factorial_jobs_demo', array( $this, 'render_demo_cards' ) );
	}

	/**
	 * Renders sample cards with every available field enabled.
	 */
	public function render_demo_cards(): string {
		$this->enqueue_assets();

		$jobs          = sha_factorial_jobs_get_preview_jobs();
		$lookup_labels = sha_factorial_jobs_get_preview_lookup_labels();
		$card_fields   = sha_factorial_jobs_normalize_card_fields(
			array_fill_keys( array_keys( sha_factorial_jobs_get_card_field_definitions() ), true )
		);
		$wrapper_class = sha_factorial_jobs_direction_class( 'sfj-jobs sfj-jobs--grid sfj-jobs--demo' );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>"<?php echo sha_factorial_jobs_direction_attr(); ?>>
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
							'preview_mode'  => true,
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
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function render_jobs_list( $atts ): string {
		$atts = shortcode_atts(
			array(
				'region'          => 'all',
				'ids'             => '',
				'category'        => '',
				'contract_type'   => '',
				'workplace_type'  => '',
				'schedule_type'   => '',
				'team_id'         => '',
				'location_id'     => '',
				'legal_entity_id' => '',
				'remote'          => '',
				'limit'           => '',
				'layout'          => '',
				'show_filters'    => '',
				'show_salary'     => '',
			),
			(array) $atts,
			'sha_factorial_jobs'
		);

		$this->enqueue_assets();

		$detail = $this->maybe_render_job_detail( $atts );

		if ( null !== $detail ) {
			return $detail;
		}

		$result = $this->service->get_jobs_for_display( $atts );

		if ( is_wp_error( $result ) ) {
			return $this->render_error( $result );
		}

		return sha_factorial_jobs_render_template(
			'jobs-list.php',
			$result
		);
	}

	/**
	 * Renders job detail when sfj_job is present in the query string.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 */
	private function maybe_render_job_detail( array $atts ): ?string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$job_id = isset( $_GET['sfj_job'] ) ? sanitize_text_field( wp_unslash( $_GET['sfj_job'] ) ) : '';

		if ( '' === $job_id ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$job_region = isset( $_GET['sfj_region'] ) ? sanitize_key( wp_unslash( $_GET['sfj_region'] ) ) : '';

		$shortcode_region = sanitize_key( $atts['region'] ?? 'all' );

		if ( '' === $job_region ) {
			if ( in_array( $shortcode_region, array( 'es', 'mx' ), true ) ) {
				$job_region = $shortcode_region;
			} else {
				return $this->render_error(
					new WP_Error(
						'sha_factorial_missing_region',
						__( 'Missing sfj_region parameter in the URL.', 'sha-factorial-jobs' )
					)
				);
			}
		}

		if ( ! in_array( $job_region, array( 'es', 'mx' ), true ) ) {
			return $this->render_error(
				new WP_Error(
					'sha_factorial_invalid_region',
					__( 'Invalid region.', 'sha-factorial-jobs' )
				)
			);
		}

		if ( 'all' !== $shortcode_region && $shortcode_region !== $job_region ) {
			return $this->render_error(
				new WP_Error(
					'sha_factorial_region_mismatch',
					__( 'This job posting does not belong to this page.', 'sha-factorial-jobs' )
				)
			);
		}

		$result = $this->service->get_job_for_display( $job_region, $job_id );

		if ( is_wp_error( $result ) ) {
			return $this->render_error( $result );
		}

		$result['list_url'] = sha_factorial_jobs_get_list_url();

		return sha_factorial_jobs_render_template(
			'job-single.php',
			$result
		);
	}

	/**
	 * Enqueue frontend assets once.
	 */
	private function enqueue_assets(): void {
		if ( $this->assets_enqueued ) {
			return;
		}

		wp_enqueue_style(
			'sha-factorial-jobs-public',
			SHA_FACTORIAL_JOBS_URL . 'assets/css/public.css',
			array(),
			SHA_FACTORIAL_JOBS_VERSION
		);

		$inline_css = sha_factorial_jobs_get_styles_css();

		if ( '' !== $inline_css ) {
			wp_add_inline_style( 'sha-factorial-jobs-public', $inline_css );
		}

		wp_enqueue_style(
			'sha-factorial-jobs-sha-wellness',
			SHA_FACTORIAL_JOBS_URL . 'assets/css/sha-wellness.css',
			array( 'sha-factorial-jobs-public' ),
			SHA_FACTORIAL_JOBS_VERSION
		);

		wp_enqueue_script(
			'sha-factorial-jobs-filters',
			SHA_FACTORIAL_JOBS_URL . 'assets/js/filters.js',
			array(),
			SHA_FACTORIAL_JOBS_VERSION,
			true
		);

		$this->assets_enqueued = true;
	}

	/**
	 * @param WP_Error $error Error object.
	 */
	private function render_error( WP_Error $error ): string {
		$class = esc_attr( sha_factorial_jobs_direction_class( 'sfj-error' ) );
		$dir   = sha_factorial_jobs_direction_attr();

		if ( current_user_can( 'manage_options' ) ) {
			return '<div class="' . $class . ' sfj-error--admin"' . $dir . '>' . esc_html( $error->get_error_message() ) . '</div>';
		}

		return '<div class="' . $class . '"' . $dir . '>' . esc_html__( 'No job postings available at this time.', 'sha-factorial-jobs' ) . '</div>';
	}
}
