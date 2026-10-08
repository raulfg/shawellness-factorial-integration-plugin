<?php
/**
 * Cache interface.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Contract for plugin cache backends.
 */
interface Sha_Factorial_Cache_Interface {

	/**
	 * @param string $key Cache key.
	 * @return mixed|null
	 */
	public function get( string $key );

	/**
	 * @param string $key   Cache key.
	 * @param mixed  $value Value to store.
	 * @param int    $ttl   Time to live in seconds.
	 */
	public function set( string $key, $value, int $ttl ): bool;

	/**
	 * @param string $key Cache key.
	 */
	public function delete( string $key ): bool;

	/**
	 * @return int Number of deleted entries.
	 */
	public function purge_all(): int;
}
