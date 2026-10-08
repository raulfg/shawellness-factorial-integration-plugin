<?php
/**
 * Template loader.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Locates plugin templates with theme override support.
 */
final class Sha_Factorial_Template_Loader {

	/**
	 * @param string $template Template filename.
	 */
	public function locate( string $template ): string {
		$theme_path = trailingslashit( get_stylesheet_directory() ) . 'sha-factorial-jobs/' . ltrim( $template, '/' );

		if ( file_exists( $theme_path ) ) {
			return $theme_path;
		}

		return SHA_FACTORIAL_JOBS_PATH . 'templates/' . ltrim( $template, '/' );
	}
}
