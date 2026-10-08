<?php
/**
 * Encrypted credential storage.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Encrypts and decrypts API keys at rest.
 */
final class Sha_Factorial_Credential_Store {

	private const CIPHER = 'AES-256-CBC';

	/**
	 * @param string $plain Plaintext API key.
	 */
	public function encrypt( string $plain ): string {
		$plain = trim( $plain );

		if ( '' === $plain ) {
			return '';
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return 'b64:' . base64_encode( $plain );
		}

		$iv_length = openssl_cipher_iv_length( self::CIPHER );
		$iv        = openssl_random_pseudo_bytes( $iv_length );
		$encrypted = openssl_encrypt( $plain, self::CIPHER, $this->get_key(), 0, $iv );

		if ( false === $encrypted ) {
			return 'b64:' . base64_encode( $plain );
		}

		return 'enc:' . base64_encode( $iv . $encrypted );
	}

	/**
	 * @param string $stored Stored credential.
	 */
	public function decrypt( string $stored ): string {
		$stored = trim( $stored );

		if ( '' === $stored ) {
			return '';
		}

		if ( str_starts_with( $stored, 'b64:' ) ) {
			$decoded = base64_decode( substr( $stored, 4 ), true );
			return false === $decoded ? '' : $decoded;
		}

		if ( ! str_starts_with( $stored, 'enc:' ) || ! function_exists( 'openssl_decrypt' ) ) {
			return $stored;
		}

		$payload = base64_decode( substr( $stored, 4 ), true );

		if ( false === $payload ) {
			return '';
		}

		$iv_length = openssl_cipher_iv_length( self::CIPHER );
		$iv        = substr( $payload, 0, $iv_length );
		$cipher    = substr( $payload, $iv_length );
		$decrypted = openssl_decrypt( $cipher, self::CIPHER, $this->get_key(), 0, $iv );

		return false === $decrypted ? '' : $decrypted;
	}

	/**
	 * @param string $stored Stored credential.
	 */
	public function mask( string $stored ): string {
		$plain = $this->decrypt( $stored );

		if ( '' === $plain ) {
			return '';
		}

		$length = strlen( $plain );

		if ( $length <= 8 ) {
			return str_repeat( '•', $length );
		}

		return substr( $plain, 0, 4 ) . str_repeat( '•', max( 8, $length - 8 ) ) . substr( $plain, -4 );
	}

	/**
	 * @return bool
	 */
	public function uses_weak_storage(): bool {
		return ! function_exists( 'openssl_encrypt' );
	}

	/**
	 * @return string
	 */
	private function get_key(): string {
		return hash( 'sha256', wp_salt( 'auth' ), true );
	}
}
