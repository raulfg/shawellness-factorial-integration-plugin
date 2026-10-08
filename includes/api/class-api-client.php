<?php
/**
 * Factorial HTTP client.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Low-level API transport.
 */
final class Sha_Factorial_Api_Client {

	private string $api_key;

	/**
	 * @param string $api_key Factorial API key.
	 */
	public function __construct( string $api_key ) {
		$this->api_key = trim( $api_key );
	}

	/**
	 * @param string               $resource API resource path.
	 * @param array<string, mixed> $query    Query parameters.
	 * @return array<string, mixed>|WP_Error
	 */
	public function get( string $resource, array $query = array() ) {
		if ( '' === $this->api_key ) {
			return new WP_Error(
				'sha_factorial_missing_key',
				__( 'No API key configured for this connection.', 'sha-factorial-jobs' )
			);
		}

		$url = trailingslashit( SHA_FACTORIAL_JOBS_API_BASE ) . ltrim( $resource, '/' );

		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Accept'    => 'application/json',
					'x-api-key' => $this->api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 204 === $code || '' === trim( $body ) ) {
			return new WP_Error(
				'sha_factorial_empty_response',
				__( 'Empty response from Factorial. Check that the API key matches the production environment.', 'sha-factorial-jobs' ),
				array( 'status' => $code )
			);
		}

		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			return new WP_Error(
				'sha_factorial_invalid_json',
				__( 'Factorial response is not valid JSON.', 'sha-factorial-jobs' ),
				array( 'status' => $code )
			);
		}

		if ( $code >= 400 ) {
			$message = isset( $data['message'] ) ? (string) $data['message'] : __( 'Factorial API error.', 'sha-factorial-jobs' );

			return new WP_Error(
				'sha_factorial_api_error',
				$message,
				array(
					'status' => $code,
					'data'   => $data,
				)
			);
		}

		return $data;
	}
}
