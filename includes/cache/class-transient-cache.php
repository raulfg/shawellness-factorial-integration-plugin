<?php
/**
 * WordPress transient cache.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Transient-based cache implementation.
 */
final class Sha_Factorial_Transient_Cache implements Sha_Factorial_Cache_Interface {

	private const PREFIX = 'sha_factorial_';

	/**
	 * @param string $key Cache key without prefix.
	 * @return mixed|null
	 */
	public function get( string $key ) {
		$value = get_transient( self::PREFIX . $key );

		return false === $value ? null : $value;
	}

	/**
	 * @param string $key   Cache key without prefix.
	 * @param mixed  $value Value to store.
	 * @param int    $ttl   Time to live in seconds.
	 */
	public function set( string $key, $value, int $ttl ): bool {
		return (bool) set_transient( self::PREFIX . $key, $value, $ttl );
	}

	/**
	 * @param string $key Cache key without prefix.
	 */
	public function delete( string $key ): bool {
		return delete_transient( self::PREFIX . $key );
	}

	/**
	 * @return int Number of deleted entries.
	 */
	public function purge_all(): int {
		global $wpdb;

		$like         = $wpdb->esc_like( '_transient_' . self::PREFIX ) . '%';
		$timeout_like = $wpdb->esc_like( '_transient_timeout_' . self::PREFIX ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like,
				$timeout_like
			)
		);

		return is_numeric( $deleted ) ? (int) $deleted : 0;
	}
}
