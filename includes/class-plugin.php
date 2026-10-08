<?php
/**
 * Main plugin class.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bootstraps plugin services.
 */
final class Sha_Factorial_Plugin {

	private static ?Sha_Factorial_Plugin $instance = null;

	private Sha_Factorial_Credential_Store $credentials;

	private Sha_Factorial_Transient_Cache $cache;

	private Sha_Factorial_Job_Repository $jobs;

	private Sha_Factorial_Lookup_Repository $lookups;

	private Sha_Factorial_Jobs_Service $jobs_service;

	/**
	 * Singleton instance.
	 */
	public static function instance(): Sha_Factorial_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize plugin.
	 */
	public function init(): void {
		load_plugin_textdomain(
			'sha-factorial-jobs',
			false,
			dirname( plugin_basename( SHA_FACTORIAL_JOBS_FILE ) ) . '/languages'
		);

		$this->credentials  = new Sha_Factorial_Credential_Store();
		$this->cache        = new Sha_Factorial_Transient_Cache();
		$this->jobs         = new Sha_Factorial_Job_Repository( $this->cache, $this->credentials );
		$this->lookups      = new Sha_Factorial_Lookup_Repository( $this->cache, $this->credentials );
		$this->jobs_service = new Sha_Factorial_Jobs_Service( $this->jobs, $this->lookups );

		if ( is_admin() ) {
			$settings = new Sha_Factorial_Settings_Page( $this->credentials );
			$settings->register();

			$tester = new Sha_Factorial_Connection_Tester( $this->credentials );
			$tester->register();
		}

		$shortcodes = new Sha_Factorial_Shortcodes( $this->jobs_service );
		$shortcodes->register();

		if ( ! is_admin() ) {
			$public_seo = new Sha_Factorial_Public_Seo();
			$public_seo->register();
		}
	}
}
