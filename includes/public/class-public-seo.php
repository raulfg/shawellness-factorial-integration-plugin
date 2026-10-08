<?php
/**
 * Frontend SEO for volatile job list/detail views.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Marks job listing and detail URLs as noindex; indexable careers landing should be a separate page.
 */
final class Sha_Factorial_Public_Seo {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'wp_robots', array( $this, 'filter_wp_robots' ) );
		add_action( 'wp_head', array( $this, 'output_canonical_link' ), 1 );
		add_action( 'template_redirect', array( $this, 'send_robots_header' ) );
	}

	/**
	 * @param array<string, bool|string> $robots Robots directives.
	 * @return array<string, bool|string>
	 */
	public function filter_wp_robots( array $robots ): array {
		if ( ! $this->should_noindex_current_view() ) {
			return $robots;
		}

		$robots['noindex'] = true;

		return $robots;
	}

	/**
	 * Canonical for detail views points at the listing URL without query args.
	 */
	public function output_canonical_link(): void {
		if ( ! $this->should_noindex_current_view() ) {
			return;
		}

		$canonical = $this->get_canonical_url();

		if ( '' === $canonical ) {
			return;
		}

		printf(
			'<link rel="canonical" href="%s" />' . "\n",
			esc_url( $canonical )
		);
	}

	/**
	 * Reinforce noindex for crawlers that read response headers.
	 */
	public function send_robots_header(): void {
		if ( ! $this->should_noindex_current_view() ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		header( 'X-Robots-Tag: noindex, follow', true );
	}

	private function should_noindex_current_view(): bool {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return false;
		}

		if ( sha_factorial_jobs_is_job_detail_request() ) {
			/**
			 * Whether job detail URLs (?sfj_job=) should be noindex.
			 *
			 * @param bool $noindex Default true.
			 */
			return (bool) apply_filters( 'sha_factorial_jobs_job_detail_noindex', true );
		}

		if ( sha_factorial_jobs_is_jobs_listing_view() ) {
			/**
			 * Whether the whole WordPress page that contains [sha_factorial_jobs] should be noindex.
			 *
			 * Default false: the page (hero, copy) can stay indexable; shortcode output uses data-nosnippet.
			 */
			return (bool) apply_filters( 'sha_factorial_jobs_listing_noindex', false );
		}

		return false;
	}

	private function get_canonical_url(): string {
		if ( ! sha_factorial_jobs_is_job_detail_request() ) {
			return '';
		}

		return sha_factorial_jobs_get_list_url();
	}
}
