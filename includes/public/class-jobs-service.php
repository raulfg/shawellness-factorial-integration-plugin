<?php
/**
 * Jobs service.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrates repositories and shortcode attribute parsing.
 */
final class Sha_Factorial_Jobs_Service {

	private Sha_Factorial_Job_Repository $jobs;

	private Sha_Factorial_Lookup_Repository $lookups;

	/**
	 * @param Sha_Factorial_Job_Repository    $jobs    Job repository.
	 * @param Sha_Factorial_Lookup_Repository $lookups Lookup repository.
	 */
	public function __construct(
		Sha_Factorial_Job_Repository $jobs,
		Sha_Factorial_Lookup_Repository $lookups
	) {
		$this->jobs    = $jobs;
		$this->lookups = $lookups;
	}

	/**
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return array<string, mixed>|WP_Error
	 */
	public function get_jobs_for_display( array $atts ) {
		$settings = sha_factorial_jobs_get_settings();
		$defaults = $settings['defaults'];

		$region = sanitize_key( $atts['region'] ?? 'all' );
		if ( ! sha_factorial_jobs_is_valid_region( $region ) ) {
			$region = 'all';
		}

		$query = array();

		foreach ( array( 'team_id', 'location_id', 'legal_entity_id' ) as $field ) {
			if ( ! empty( $atts[ $field ] ) ) {
				$query[ $field ] = sanitize_text_field( $atts[ $field ] );
			}
		}

		if ( ! empty( $atts['ids'] ) ) {
			$query['ids'] = array_map( 'sanitize_text_field', explode( ',', $atts['ids'] ) );
		}

		$jobs = $this->jobs->list( $region, $query );

		if ( is_wp_error( $jobs ) ) {
			return $jobs;
		}

		$client_filters = array(
			'category'       => $atts['category'] ?? '',
			'contract_type'  => $atts['contract_type'] ?? '',
			'workplace_type' => $atts['workplace_type'] ?? '',
			'schedule_type'  => $atts['schedule_type'] ?? '',
			'remote'         => $atts['remote'] ?? '',
		);

		$jobs = $this->apply_client_filters( $jobs, $client_filters );
		$jobs = sha_factorial_jobs_sort_jobs_by_published_at_desc( $jobs );

		$limit = (int) ( $atts['limit'] ?? 0 );
		if ( $limit > 0 ) {
			$jobs = array_slice( $jobs, 0, min( $limit, 100 ) );
		}

		$card_fields = $settings['card_fields'] ?? sha_factorial_jobs_normalize_card_fields( array() );

		if ( isset( $atts['show_salary'] ) && '' !== $atts['show_salary'] ) {
			$card_fields['salary'] = $this->to_bool( $atts['show_salary'] );
		}

		return array(
			'jobs'         => $jobs,
			'region'       => $region,
			'layout'       => sanitize_key( $atts['layout'] ?? $defaults['layout'] ?? 'list' ),
			'show_filters' => $this->to_bool( $atts['show_filters'] ?? $defaults['show_filters'] ?? false ),
			'card_fields'  => $card_fields,
			'show_card'    => sha_factorial_jobs_should_show_card( $settings['styles'] ?? array() ),
			'lookups'      => $this->lookups,
		);
	}

	/**
	 * @param string               $region Region slug.
	 * @param string               $id     Job ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public function get_job_for_display( string $region, string $id ) {
		$job = $this->jobs->get( $region, $id );

		if ( is_wp_error( $job ) ) {
			return $job;
		}

		$settings = sha_factorial_jobs_get_settings();

		return array(
			'job'       => $job,
			'region'    => $region,
			'show_card' => sha_factorial_jobs_should_show_card( $settings['styles'] ?? array() ),
			'lookups'   => $this->lookups,
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $jobs    Jobs list.
	 * @param array<string, string>            $filters Client filters.
	 * @return array<int, array<string, mixed>>
	 */
	private function apply_client_filters( array $jobs, array $filters ): array {
		return array_values(
			array_filter(
				$jobs,
				function ( $job ) use ( $filters ) {
					if ( ! is_array( $job ) ) {
						return false;
					}

					foreach ( $filters as $field => $value ) {
						if ( '' === $value ) {
							continue;
						}

						if ( 'remote' === $field ) {
							$expected = in_array( strtolower( $value ), array( '1', 'true', 'yes' ), true );
							if ( (bool) ( $job['remote'] ?? false ) !== $expected ) {
								return false;
							}
							continue;
						}

						if ( (string) ( $job[ $field ] ?? '' ) !== (string) $value ) {
							return false;
						}
					}

					return true;
				}
			)
		);
	}

	/**
	 * @param mixed $value Raw value.
	 */
	private function to_bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
	}
}
