<?php
/**
 * Connection testing via AJAX.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tests Factorial API credentials and returns diagnostics.
 */
final class Sha_Factorial_Connection_Tester {

	private Sha_Factorial_Credential_Store $credentials;

	/**
	 * @param Sha_Factorial_Credential_Store $credentials Credential store.
	 */
	public function __construct( Sha_Factorial_Credential_Store $credentials ) {
		$this->credentials = $credentials;
	}

	/**
	 * Register AJAX handlers.
	 */
	public function register(): void {
		add_action( 'wp_ajax_sha_factorial_test_connection', array( $this, 'handle_test' ) );
		add_action( 'wp_ajax_sha_factorial_clear_cache', array( $this, 'handle_clear_cache' ) );
	}

	/**
	 * AJAX: test connection.
	 */
	public function handle_test(): void {
		check_ajax_referer( 'sha_factorial_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'sha-factorial-jobs' ) ), 403 );
		}

		$region  = sanitize_key( wp_unslash( $_POST['region'] ?? '' ) );
		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

		if ( ! in_array( $region, array( 'es', 'mx' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid region.', 'sha-factorial-jobs' ) ) );
		}

		if ( '' === $api_key ) {
			$settings = sha_factorial_jobs_get_settings();
			$api_key  = $this->credentials->decrypt( (string) ( $settings['connections'][ $region ]['api_key'] ?? '' ) );
		}

		$result = $this->test( $region, $api_key );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
					'code'    => $result->get_error_code(),
				)
			);
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: clear cache.
	 */
	public function handle_clear_cache(): void {
		check_ajax_referer( 'sha_factorial_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'sha-factorial-jobs' ) ), 403 );
		}

		$cache   = new Sha_Factorial_Transient_Cache();
		$deleted = $cache->purge_all();

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of cache entries removed */
					__( 'Cache cleared: %d entries removed.', 'sha-factorial-jobs' ),
					$deleted
				),
				'deleted' => $deleted,
			)
		);
	}

	/**
	 * @param string $region  Region slug.
	 * @param string $api_key API key.
	 * @return array<string, mixed>|WP_Error
	 */
	public function test( string $region, string $api_key ) {
		$client = new Sha_Factorial_Api_Client( $api_key );

		$credentials = $client->get( 'resources/api_public/credentials' );

		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}

		$identity = isset( $credentials['data'] ) && is_array( $credentials['data'] )
			? $credentials['data']
			: $credentials;

		$published_count = 0;
		$page_query      = array(
			'status' => 'published',
			'limit'  => 100,
		);

		do {
			$jobs = $client->get( 'resources/ats/job_postings', $page_query );

			if ( is_wp_error( $jobs ) ) {
				break;
			}

			$batch = isset( $jobs['data'] ) && is_array( $jobs['data'] ) ? $jobs['data'] : array();
			$published_count += count( $batch );

			$meta = isset( $jobs['meta'] ) && is_array( $jobs['meta'] ) ? $jobs['meta'] : array();

			if ( empty( $meta['has_next_page'] ) || empty( $meta['end_cursor'] ) ) {
				break;
			}

			$page_query['after_id'] = $meta['end_cursor'];
		} while ( true );

		return array(
			'region'          => $region,
			'identity'        => $identity,
			'published_count' => $published_count,
			'api_version'     => SHA_FACTORIAL_JOBS_API_VERSION,
			'message'         => __( 'Successfully connected to Factorial.', 'sha-factorial-jobs' ),
		);
	}
}
