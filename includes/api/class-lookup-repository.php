<?php
/**
 * Lookup data repository.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Teams, locations and legal entities from Factorial.
 */
final class Sha_Factorial_Lookup_Repository {

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
	 * @param string $region Region slug.
	 * @param string $type   Lookup type.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public function get_all( string $region, string $type ) {
		$endpoints = array(
			'teams'          => 'resources/teams/teams',
			'locations'      => 'resources/locations/locations',
			'legal_entities' => 'resources/companies/legal_entities',
		);

		if ( ! isset( $endpoints[ $type ] ) ) {
			return new WP_Error( 'sha_factorial_invalid_lookup', __( 'Invalid lookup type.', 'sha-factorial-jobs' ) );
		}

		$settings = sha_factorial_jobs_get_settings();
		$api_key  = $this->get_api_key_for_region( $settings, $region );

		if ( '' === $api_key ) {
			return array();
		}

		$cache_key = 'lookups_' . $type . '_' . $region;
		$cached    = $this->cache->get( $cache_key );

		if ( null !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$client   = new Sha_Factorial_Api_Client( $api_key );
		$response = $client->get( $endpoints[ $type ], array( 'limit' => 100 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : array();
		$this->cache->set( $cache_key, $data, (int) $settings['cache_ttl'] );

		return $data;
	}

	/**
	 * @param string $region Region slug.
	 * @param string $type   Lookup type.
	 * @param string $id     Entity ID.
	 */
	public function get_name( string $region, string $type, string $id ): string {
		if ( '' === $id ) {
			return '';
		}

		$items = $this->get_all( $region, $type );

		if ( is_wp_error( $items ) ) {
			return '';
		}

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( (string) ( $item['id'] ?? '' ) === (string) $id ) {
				return (string) ( $item['name'] ?? $item['title'] ?? $item['legal_name'] ?? $id );
			}
		}

		return $id;
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
