<?php
/**
 * Job postings repository.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fetches and caches job postings.
 */
final class Sha_Factorial_Job_Repository {

	private Sha_Factorial_Cache_Interface $cache;

	private Sha_Factorial_Credential_Store $credentials;

	/**
	 * @param Sha_Factorial_Cache_Interface  $cache       Cache service.
	 * @param Sha_Factorial_Credential_Store $credentials Credential store.
	 */
	public function __construct(
		Sha_Factorial_Cache_Interface $cache,
		Sha_Factorial_Credential_Store $credentials
	) {
		$this->cache       = $cache;
		$this->credentials = $credentials;
	}

	/**
	 * @param string               $region Region slug.
	 * @param array<string, mixed> $query  Query parameters.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public function list( string $region, array $query = array() ) {
		if ( 'all' === $region ) {
			return $this->list_all_regions( $query );
		}

		if ( ! in_array( $region, array( 'es', 'mx' ), true ) ) {
			return new WP_Error( 'sha_factorial_invalid_region', __( 'Invalid region.', 'sha-factorial-jobs' ) );
		}

		return $this->list_for_region( $region, $query );
	}

	/**
	 * @param string $region Region slug.
	 * @param string $id     Job posting ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public function get( string $region, string $id ) {
		$settings = sha_factorial_jobs_get_settings();
		$api_key  = $this->get_api_key_for_region( $settings, $region );

		if ( '' === $api_key ) {
			return new WP_Error( 'sha_factorial_missing_key', __( 'API key not configured.', 'sha-factorial-jobs' ) );
		}

		$cache_key = 'job_' . $region . '_' . $id;
		$cached    = $this->cache->get( $cache_key );

		if ( null !== $cached ) {
			return $cached;
		}

		$client   = new Sha_Factorial_Api_Client( $api_key );
		$response = $client->get( 'resources/ats/job_postings/' . rawurlencode( $id ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$job = isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : $response;

		if ( 'published' !== (string) ( $job['status'] ?? '' ) ) {
			return new WP_Error(
				'sha_factorial_job_not_available',
				__( 'This job posting is not available.', 'sha-factorial-jobs' )
			);
		}

		$job['_region'] = $region;

		$this->cache->set( $cache_key, $job, (int) $settings['cache_ttl'] );

		return $job;
	}

	/**
	 * @param string               $region Region slug.
	 * @param array<string, mixed> $query  Query parameters.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public function list_for_region( string $region, array $query = array() ) {
		$query['status'] = 'published';

		$settings = sha_factorial_jobs_get_settings();
		$api_key  = $this->get_api_key_for_region( $settings, $region );

		if ( '' === $api_key ) {
			return new WP_Error( 'sha_factorial_missing_key', __( 'API key not configured.', 'sha-factorial-jobs' ) );
		}

		$cache_key = 'jobs_' . $region . '_' . md5( wp_json_encode( $query ) );
		$cached    = $this->cache->get( $cache_key );

		if ( null !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$client = new Sha_Factorial_Api_Client( $api_key );
		$jobs   = $this->fetch_all_pages( $client, $query );

		if ( is_wp_error( $jobs ) ) {
			return $jobs;
		}

		foreach ( $jobs as $index => $job ) {
			$jobs[ $index ]['_region'] = $region;
		}

		$this->cache->set( $cache_key, $jobs, (int) $settings['cache_ttl'] );

		return $jobs;
	}

	/**
	 * @param array<string, mixed> $query Query parameters.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	private function list_all_regions( array $query ) {
		$merged = array();

		foreach ( array( 'es', 'mx' ) as $region ) {
			$result = $this->list_for_region( $region, $query );

			if ( is_wp_error( $result ) ) {
				continue;
			}

			$merged = array_merge( $merged, $result );
		}

		return $merged;
	}

	/**
	 * @param Sha_Factorial_Api_Client $client API client.
	 * @param array<string, mixed>     $query  Query parameters.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	private function fetch_all_pages( Sha_Factorial_Api_Client $client, array $query ) {
		$all_jobs       = array();
		$query['limit'] = min( 100, max( 1, (int) ( $query['limit'] ?? 100 ) ) );

		do {
			$response = $client->get( 'resources/ats/job_postings', $query );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$batch = isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : array();
			$all_jobs = array_merge( $all_jobs, $batch );

			$meta = isset( $response['meta'] ) && is_array( $response['meta'] ) ? $response['meta'] : array();

			if ( empty( $meta['has_next_page'] ) ) {
				break;
			}

			$query['after_id'] = $meta['end_cursor'] ?? null;

			if ( empty( $query['after_id'] ) ) {
				break;
			}
		} while ( true );

		return $all_jobs;
	}

	/**
	 * @param array<string, mixed> $settings Plugin settings.
	 * @param string               $region   Region slug.
	 */
	private function get_api_key_for_region( array $settings, string $region ): string {
		$encrypted = $settings['connections'][ $region ]['api_key'] ?? '';

		if ( empty( $settings['connections'][ $region ]['enabled'] ) ) {
			return '';
		}

		return $this->credentials->decrypt( (string) $encrypted );
	}
}
