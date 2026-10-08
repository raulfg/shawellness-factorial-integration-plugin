<?php
/**
 * Uninstall handler for SHA Factorial Jobs.
 *
 * @package ShaFactorialJobs
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'sha_factorial_jobs_settings' );

global $wpdb;

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_sha_factorial_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_sha_factorial_' ) . '%'
	)
);
