<?php
/**
 * Factorial enum registry.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * OpenAPI enum slugs and human-readable labels.
 */
final class Sha_Factorial_Enum_Registry {

	/**
	 * @var array<string, array<int, string>>
	 */
	private array $enums = array(
		'category'       => array(
			'administration_and_secretariat',
			'design_and_architecture',
			'education_and_social_policy',
			'engineering',
			'finance',
			'hr',
			'it',
			'legal',
			'management_and_consulting',
			'marketing_and_communication',
			'nursing_and_therapy',
			'physicians',
			'public_sector',
			'purchasing_materials_administration_and_logistics',
			'sales',
			'sciences_and_research',
			'service_industry_and_manufacturing',
			'sports_art_and_creative_jobs',
			'other',
		),
		'contract_type'  => array(
			'indefinite', 'temporary', 'intern', 'training', 'freelance', 'vendor_contractor',
			'volunteer', 'per_hour', 'other', 'alternant', 'interim', 'minijob', 'werkstudent',
			'apprenticeship', 'pj', 'clt', 'jovem_aprendiz', 'a_termo_incerto', 'a_termo_certo',
			'de_curta_duracao', 'de_muita_curta_duracao', 'promessa_de_trabalho', 'a_tempo_parcial',
			'com_pluralidade_de_empregadores', 'teletrabalho', 'pre_reforma', 'recibos_verdes',
			'estagio', 'sem_termo', 'apprentissage', 'fixed_discontinued', 'apprendistato',
		),
		'workplace_type' => array( 'onsite', 'remote', 'hybrid' ),
		'schedule_type'  => array( 'full_time', 'part_time' ),
		'status'         => array( 'draft', 'published', 'unlisted', 'archived', 'cancelled', 'deleted' ),
		'salary_format'  => array( 'fixed_amount', 'range' ),
		'salary_period'  => array( 'annual', 'monthly', 'daily' ),
	);

	/**
	 * @param string $field Enum field.
	 * @param string $slug  Enum value.
	 */
	public function get_label( string $field, string $slug ): string {
		if ( '' === $slug ) {
			return '';
		}

		$readable = str_replace( '_', ' ', $slug );
		return ucwords( $readable );
	}

	/**
	 * @param string $field Enum field.
	 * @return array<int, string>
	 */
	public function get_values( string $field ): array {
		return $this->enums[ $field ] ?? array();
	}

	/**
	 * @param string $field Enum field.
	 * @param string $slug  Enum value.
	 */
	public function is_valid( string $field, string $slug ): bool {
		return in_array( $slug, $this->get_values( $field ), true );
	}
}
