<?php
/**
 * Plugin Name:       SHA Factorial Jobs
 * Plugin URI:        https://shawellness.com
 * Description:       Displays Factorial HR job postings for SHA Wellness (Spain and Mexico).
 * Version:           0.5.17
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Shawellness
 * Text Domain:       sha-factorial-jobs
 * Domain Path:       /languages
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

define( 'SHA_FACTORIAL_JOBS_VERSION', '0.5.17' );
define( 'SHA_FACTORIAL_JOBS_FILE', __FILE__ );
define( 'SHA_FACTORIAL_JOBS_PATH', plugin_dir_path( __FILE__ ) );
define( 'SHA_FACTORIAL_JOBS_URL', plugin_dir_url( __FILE__ ) );
define( 'SHA_FACTORIAL_JOBS_STYLE_EDITOR_ENABLED', false );
define( 'SHA_FACTORIAL_JOBS_API_VERSION', '2026-10-01' );
define( 'SHA_FACTORIAL_JOBS_API_BASE', 'https://api.factorialhr.com/api/' . SHA_FACTORIAL_JOBS_API_VERSION );

require_once SHA_FACTORIAL_JOBS_PATH . 'includes/class-activator.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/cache/class-cache-interface.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/cache/class-transient-cache.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/security/class-credential-store.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/api/class-api-client.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/api/class-enum-registry.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/api/class-job-repository.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/api/class-lookup-repository.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/public/class-template-loader.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/public/class-jobs-service.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/public/class-shortcodes.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/public/class-public-seo.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/admin/class-connection-tester.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/admin/class-settings-page.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/helpers.php';
require_once SHA_FACTORIAL_JOBS_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'Sha_Factorial_Activator', 'activate' ) );

/**
 * Bootstrap the plugin.
 */
function sha_factorial_jobs_init(): void {
	Sha_Factorial_Plugin::instance()->init();
}

add_action( 'plugins_loaded', 'sha_factorial_jobs_init' );
