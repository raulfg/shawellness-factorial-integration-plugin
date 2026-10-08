<?php
/**
 * Admin settings page.
 *
 * @package ShaFactorialJobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the plugin admin UI.
 */
final class Sha_Factorial_Settings_Page {

	private Sha_Factorial_Credential_Store $credentials;

	/**
	 * @param Sha_Factorial_Credential_Store $credentials Credential store.
	 */
	public function __construct( Sha_Factorial_Credential_Store $credentials ) {
		$this->credentials = $credentials;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_menu(): void {
		add_menu_page(
			__( 'Factorial Jobs', 'sha-factorial-jobs' ),
			__( 'Factorial Jobs', 'sha-factorial-jobs' ),
			'manage_options',
			'sha-factorial-jobs',
			array( $this, 'render_page' ),
			'dashicons-id-alt',
			58
		);
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_sha-factorial-jobs' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'sha-factorial-jobs-public',
			SHA_FACTORIAL_JOBS_URL . 'assets/css/public.css',
			array(),
			SHA_FACTORIAL_JOBS_VERSION
		);

		wp_enqueue_style(
			'sha-factorial-jobs-sha-wellness',
			SHA_FACTORIAL_JOBS_URL . 'assets/css/sha-wellness.css',
			array( 'sha-factorial-jobs-public' ),
			SHA_FACTORIAL_JOBS_VERSION
		);

		wp_enqueue_style(
			'sha-factorial-jobs-admin',
			SHA_FACTORIAL_JOBS_URL . 'assets/css/admin.css',
			array( 'dashicons', 'sha-factorial-jobs-sha-wellness' ),
			SHA_FACTORIAL_JOBS_VERSION
		);

		wp_enqueue_script(
			'sha-factorial-jobs-admin',
			SHA_FACTORIAL_JOBS_URL . 'assets/js/admin.js',
			array(),
			SHA_FACTORIAL_JOBS_VERSION,
			true
		);

		wp_localize_script(
			'sha-factorial-jobs-admin',
			'shaFactorialAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'sha_factorial_admin' ),
				'i18n'    => array(
					'testing'     => __( 'Testing connection…', 'sha-factorial-jobs' ),
					'success'     => __( 'Connection verified', 'sha-factorial-jobs' ),
					'error'       => __( 'Could not connect', 'sha-factorial-jobs' ),
					'clearing'    => __( 'Clearing cache…', 'sha-factorial-jobs' ),
					'copied'      => __( 'Shortcode copied', 'sha-factorial-jobs' ),
					'copy'        => __( 'Copy', 'sha-factorial-jobs' ),
					'test'        => __( 'Test connection', 'sha-factorial-jobs' ),
					'jobsFound'   => __( 'published job postings', 'sha-factorial-jobs' ),
					'apiVersion'  => __( 'API', 'sha-factorial-jobs' ),
					'resetStyles' => __( 'Restore defaults', 'sha-factorial-jobs' ),
					'resetFields' => __( 'Restore fields', 'sha-factorial-jobs' ),
					'resetDone'   => __( 'Values restored. Save changes to apply them.', 'sha-factorial-jobs' ),
				),
			)
		);
	}

	/**
	 * Handle settings form save.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['sha_factorial_save_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'sha_factorial_save_settings' );

		$settings = sha_factorial_jobs_get_settings();

		foreach ( array( 'es', 'mx' ) as $region ) {
			$label_key   = 'connection_' . $region . '_label';
			$key_field   = 'connection_' . $region . '_api_key';
			$enabled_key = 'connection_' . $region . '_enabled';

			if ( isset( $_POST[ $label_key ] ) ) {
				$settings['connections'][ $region ]['label'] = sanitize_text_field( wp_unslash( $_POST[ $label_key ] ) );
			}

			$settings['connections'][ $region ]['enabled'] = isset( $_POST[ $enabled_key ] );

			$new_key = isset( $_POST[ $key_field ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $key_field ] ) ) ) : '';

			if ( '' !== $new_key ) {
				$settings['connections'][ $region ]['api_key'] = $this->credentials->encrypt( $new_key );
			}
		}

		$settings['cache_ttl'] = max( 300, min( 86400, (int) ( $_POST['cache_ttl'] ?? 1800 ) ) );

		unset( $settings['defaults']['status'] );

		$settings['defaults']['layout']       = sanitize_key( wp_unslash( $_POST['default_layout'] ?? 'list' ) );
		$settings['defaults']['show_filters'] = isset( $_POST['default_show_filters'] );

		unset( $settings['defaults']['show_salary'] );

		$card_fields = array();
		foreach ( array_keys( sha_factorial_jobs_get_card_field_definitions() ) as $field ) {
			$card_fields[ $field ] = isset( $_POST[ 'card_field_' . $field ] );
		}
		$settings['card_fields'] = sha_factorial_jobs_normalize_card_fields( $card_fields );

		if ( sha_factorial_jobs_style_editor_enabled() ) {
			$raw_styles = array();
			foreach ( sha_factorial_jobs_get_style_definitions() as $key => $definition ) {
				if ( 'checkbox' === $definition['type'] ) {
					$raw_styles[ $key ] = isset( $_POST[ 'style_' . $key ] );
					continue;
				}

				if ( isset( $_POST[ 'style_' . $key ] ) ) {
					$raw_styles[ $key ] = wp_unslash( $_POST[ 'style_' . $key ] );
				}
			}
			$settings['styles'] = sha_factorial_jobs_normalize_styles( $raw_styles );
		}

		sha_factorial_jobs_update_settings( $settings );

		add_settings_error(
			'sha_factorial_jobs',
			'settings_saved',
			__( 'Settings saved successfully.', 'sha-factorial-jobs' ),
			'success'
		);
	}

	/**
	 * Render admin page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = sha_factorial_jobs_get_settings();
		$weak     = $this->credentials->uses_weak_storage();

		settings_errors( 'sha_factorial_jobs' );
		?>
		<div class="wrap sfj-admin" id="sfj-admin">
			<header class="sfj-admin-header">
				<h1><?php esc_html_e( 'Factorial Jobs', 'sha-factorial-jobs' ); ?></h1>
			</header>

			<?php if ( $weak ) : ?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'OpenSSL is not available. API keys are stored with basic encoding. Enable OpenSSL in PHP for better security.', 'sha-factorial-jobs' ); ?></p>
				</div>
			<?php endif; ?>

			<nav class="sfj-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'sha-factorial-jobs' ); ?>">
				<button type="button" class="sfj-tab is-active" data-tab="connections" role="tab" aria-selected="true">
					<?php esc_html_e( 'Connections', 'sha-factorial-jobs' ); ?>
				</button>
				<button type="button" class="sfj-tab" data-tab="display" role="tab" aria-selected="false">
					<?php esc_html_e( 'Display', 'sha-factorial-jobs' ); ?>
				</button>
				<?php if ( sha_factorial_jobs_style_editor_enabled() ) : ?>
					<button type="button" class="sfj-tab" data-tab="styles" role="tab" aria-selected="false">
						<?php esc_html_e( 'Styles', 'sha-factorial-jobs' ); ?>
					</button>
				<?php endif; ?>
				<button type="button" class="sfj-tab" data-tab="shortcodes" role="tab" aria-selected="false">
					<?php esc_html_e( 'Shortcodes', 'sha-factorial-jobs' ); ?>
				</button>
				<button type="button" class="sfj-tab" data-tab="about" role="tab" aria-selected="false">
					<?php esc_html_e( 'About', 'sha-factorial-jobs' ); ?>
				</button>
			</nav>

			<form method="post" action="" class="sfj-form">
				<?php wp_nonce_field( 'sha_factorial_save_settings' ); ?>
				<input type="hidden" name="sha_factorial_save_settings" value="1" />

				<div class="sfj-panel is-active" data-panel="connections">
					<div class="sfj-grid sfj-grid--connections">
						<?php $this->render_connection_card( 'es', $settings ); ?>
						<?php $this->render_connection_card( 'mx', $settings ); ?>
					</div>

					<div class="sfj-card sfj-card--cache">
						<h2><?php esc_html_e( 'Cache', 'sha-factorial-jobs' ); ?></h2>
						<p class="sfj-card__desc"><?php esc_html_e( 'Reduces calls to the Factorial API. Clear the cache after publishing new job postings.', 'sha-factorial-jobs' ); ?></p>
						<div class="sfj-field-row">
							<label for="cache_ttl"><?php esc_html_e( 'TTL (seconds)', 'sha-factorial-jobs' ); ?></label>
							<input type="number" id="cache_ttl" name="cache_ttl" value="<?php echo esc_attr( (string) $settings['cache_ttl'] ); ?>" min="300" max="86400" step="60" class="small-text" />
							<button type="button" class="button button-secondary" id="sfj-clear-cache">
								<?php esc_html_e( 'Clear cache', 'sha-factorial-jobs' ); ?>
							</button>
						</div>
					</div>
				</div>

				<div class="sfj-panel" data-panel="display" hidden>
					<?php $this->render_card_fields_settings( $settings ); ?>

					<div class="sfj-card">
						<h2><?php esc_html_e( 'Default shortcode values', 'sha-factorial-jobs' ); ?></h2>
						<p class="sfj-card__desc"><?php esc_html_e( 'Only published job postings from Factorial are shown.', 'sha-factorial-jobs' ); ?></p>
						<div class="sfj-field-grid">
							<div class="sfj-field">
								<label for="default_layout"><?php esc_html_e( 'Layout', 'sha-factorial-jobs' ); ?></label>
								<select id="default_layout" name="default_layout" class="sfj-input">
									<option value="list" <?php selected( $settings['defaults']['layout'], 'list' ); ?>><?php esc_html_e( 'List', 'sha-factorial-jobs' ); ?></option>
									<option value="grid" <?php selected( $settings['defaults']['layout'], 'grid' ); ?>><?php esc_html_e( 'Grid', 'sha-factorial-jobs' ); ?></option>
								</select>
							</div>
							<div class="sfj-field sfj-field--checkbox">
								<label><input type="checkbox" name="default_show_filters" <?php checked( $settings['defaults']['show_filters'] ); ?> /> <?php esc_html_e( 'Show filters', 'sha-factorial-jobs' ); ?></label>
							</div>
						</div>
					</div>
				</div>

				<?php if ( sha_factorial_jobs_style_editor_enabled() ) : ?>
					<div class="sfj-panel" data-panel="styles" hidden>
						<?php $this->render_styles_settings( $settings ); ?>
					</div>
				<?php endif; ?>

				<div class="sfj-panel" data-panel="shortcodes" hidden>
					<?php $this->render_shortcodes_reference(); ?>
				</div>

				<footer class="sfj-footer">
					<?php submit_button( __( 'Save changes', 'sha-factorial-jobs' ), 'primary', 'submit', false ); ?>
				</footer>
			</form>

			<div class="sfj-panel" data-panel="about" hidden>
				<?php $this->render_about_tab(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * @param string               $region   Region slug.
	 * @param array<string, mixed> $settings Plugin settings.
	 */
	private function render_connection_card( string $region, array $settings ): void {
		$conn       = $settings['connections'][ $region ];
		$has_key    = '' !== $this->credentials->decrypt( (string) ( $conn['api_key'] ?? '' ) );
		$flag         = 'es' === $region ? '🇪🇸' : '🇲🇽';
		$region_label = 'es' === $region ? __( 'Spain', 'sha-factorial-jobs' ) : __( 'Mexico', 'sha-factorial-jobs' );
		?>
		<article class="sfj-connection" data-region="<?php echo esc_attr( $region ); ?>">
			<header class="sfj-connection__header">
				<div class="sfj-connection__title">
					<span class="sfj-connection__flag" aria-hidden="true"><?php echo esc_html( $flag ); ?></span>
					<div>
						<h2><?php echo esc_html( $region_label ); ?></h2>
						<p class="sfj-connection__slug"><?php echo esc_html( strtoupper( $region ) ); ?></p>
					</div>
				</div>
				<span class="sfj-status sfj-status--idle" data-status-badge>
					<span class="sfj-status__dot"></span>
					<span class="sfj-status__text"><?php echo $has_key ? esc_html__( 'Not tested', 'sha-factorial-jobs' ) : esc_html__( 'No API key', 'sha-factorial-jobs' ); ?></span>
				</span>
			</header>

			<div class="sfj-connection__body">
				<div class="sfj-field">
					<label for="connection_<?php echo esc_attr( $region ); ?>_label"><?php esc_html_e( 'Label', 'sha-factorial-jobs' ); ?></label>
					<input type="text" class="regular-text sfj-input" id="connection_<?php echo esc_attr( $region ); ?>_label" name="connection_<?php echo esc_attr( $region ); ?>_label" value="<?php echo esc_attr( (string) $conn['label'] ); ?>" />
				</div>

				<div class="sfj-field">
					<label for="connection_<?php echo esc_attr( $region ); ?>_api_key"><?php esc_html_e( 'API Key', 'sha-factorial-jobs' ); ?></label>
					<div class="sfj-input-group">
						<input type="password" class="large-text code sfj-input" id="connection_<?php echo esc_attr( $region ); ?>_api_key" name="connection_<?php echo esc_attr( $region ); ?>_api_key" value="" placeholder="<?php echo $has_key ? esc_attr( $this->credentials->mask( (string) $conn['api_key'] ) ) : esc_attr__( 'Paste your Factorial API key', 'sha-factorial-jobs' ); ?>" autocomplete="off" data-has-key="<?php echo $has_key ? '1' : '0'; ?>" />
						<button type="button" class="button button-secondary sfj-toggle-key" aria-label="<?php esc_attr_e( 'Show/hide', 'sha-factorial-jobs' ); ?>">
							<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
						</button>
					</div>
					<p class="sfj-hint"><?php esc_html_e( 'Leave empty to keep the current key.', 'sha-factorial-jobs' ); ?></p>
				</div>

				<div class="sfj-field sfj-field--checkbox">
					<label>
						<input type="checkbox" name="connection_<?php echo esc_attr( $region ); ?>_enabled" <?php checked( ! empty( $conn['enabled'] ) ); ?> />
						<?php esc_html_e( 'Active connection', 'sha-factorial-jobs' ); ?>
					</label>
				</div>

				<div class="sfj-test-result" data-test-result hidden></div>

				<button type="button" class="button button-secondary sfj-btn--test" data-test-connection data-region="<?php echo esc_attr( $region ); ?>">
					<span class="sfj-btn__spinner" hidden></span>
					<span class="sfj-btn__label"><?php esc_html_e( 'Test connection', 'sha-factorial-jobs' ); ?></span>
				</button>
			</div>
		</article>
		<?php
	}

	/**
	 * Card field toggles and live preview.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 */
	private function render_card_fields_settings( array $settings ): void {
		$card_fields = $settings['card_fields'] ?? sha_factorial_jobs_normalize_card_fields( array() );
		$definitions = sha_factorial_jobs_get_card_field_definitions();
		?>
		<div class="sfj-card sfj-card--display">
			<div class="sfj-card__head">
				<div>
					<h2><?php esc_html_e( 'Job card fields', 'sha-factorial-jobs' ); ?></h2>
					<p class="sfj-card__desc"><?php esc_html_e( 'Enable or disable which Factorial data appears on each job posting. The title is always visible.', 'sha-factorial-jobs' ); ?></p>
				</div>
				<button type="button" class="button button-secondary" id="sfj-reset-card-fields">
					<?php esc_html_e( 'Restore fields', 'sha-factorial-jobs' ); ?>
				</button>
			</div>

			<div class="sfj-display-layout">
				<div class="sfj-card-fields" data-sfj-card-fields>
					<ul class="sfj-card-fields__list">
						<?php foreach ( $definitions as $field => $definition ) : ?>
							<li class="sfj-card-fields__item">
								<label class="sfj-card-fields__toggle">
									<input
										type="checkbox"
										name="<?php echo esc_attr( 'card_field_' . $field ); ?>"
										value="1"
										data-sfj-card-field-toggle="<?php echo esc_attr( $field ); ?>"
										data-sfj-card-field-default="<?php echo ! empty( $definition['default'] ) ? '1' : '0'; ?>"
										<?php checked( ! empty( $card_fields[ $field ] ) ); ?>
									/>
									<span class="sfj-card-fields__label"><?php echo esc_html( $definition['label'] ); ?></span>
								</label>
								<p class="sfj-card-fields__hint"><?php echo esc_html( $definition['hint'] ); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="sfj-card-preview sfj-card-preview--sticky">
					<p class="sfj-card-preview__label"><?php esc_html_e( 'Preview (3 sample job postings)', 'sha-factorial-jobs' ); ?></p>
					<div class="sfj-card-preview__frame">
						<?php echo sha_factorial_jobs_render_admin_preview_list( $card_fields ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Style settings and live preview.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 */
	private function render_styles_settings( array $settings ): void {
		$styles       = $settings['styles'] ?? sha_factorial_jobs_normalize_styles( array() );
		$definitions  = sha_factorial_jobs_get_style_definitions();
		$group_labels = sha_factorial_jobs_get_style_group_labels();
		$all_fields   = sha_factorial_jobs_normalize_card_fields(
			array_fill_keys( array_keys( sha_factorial_jobs_get_card_field_definitions() ), true )
		);
		?>
		<div class="sfj-card sfj-card--styles">
			<div class="sfj-card__head">
				<div>
					<h2><?php esc_html_e( 'Frontend styles', 'sha-factorial-jobs' ); ?></h2>
					<p class="sfj-card__desc"><?php esc_html_e( 'Customize cards, filters, and list spacing. Changes apply to listings and the detail view.', 'sha-factorial-jobs' ); ?></p>
				</div>
				<button type="button" class="button button-secondary" id="sfj-reset-styles">
					<?php esc_html_e( 'Restore defaults', 'sha-factorial-jobs' ); ?>
				</button>
			</div>

			<div class="sfj-display-layout sfj-display-layout--styles">
				<div class="sfj-style-fields" data-sfj-style-fields>
					<?php foreach ( $group_labels as $group => $group_label ) : ?>
						<section class="sfj-style-group<?php echo in_array( $group, array( 'colors', 'filters' ), true ) ? ' sfj-style-group--colors' : ''; ?>">
							<h3><?php echo esc_html( $group_label ); ?></h3>
							<?php foreach ( $definitions as $key => $definition ) : ?>
								<?php if ( ( $definition['group'] ?? '' ) !== $group ) : continue; endif; ?>
								<div class="sfj-style-field<?php echo in_array( $key, array( 'show_card', 'filter_button_style' ), true ) ? ' sfj-style-field--full' : ''; ?>">
									<?php if ( 'checkbox' === $definition['type'] ) : ?>
										<label class="sfj-style-field__checkbox">
											<input
												type="checkbox"
												name="<?php echo esc_attr( 'style_' . $key ); ?>"
												value="1"
												data-sfj-style-input
												data-sfj-style-key="<?php echo esc_attr( $key ); ?>"
												data-sfj-style-type="checkbox"
												data-sfj-style-var="<?php echo esc_attr( $definition['css_var'] ); ?>"
												data-sfj-style-on="<?php echo esc_attr( (string) ( $definition['on'] ?? '' ) ); ?>"
												data-sfj-style-off="<?php echo esc_attr( (string) ( $definition['off'] ?? 'none' ) ); ?>"
												data-sfj-style-default="<?php echo ! empty( $definition['default'] ) ? '1' : '0'; ?>"
												<?php checked( ! empty( $styles[ $key ] ) ); ?>
											/>
											<span><?php echo esc_html( $definition['label'] ); ?></span>
										</label>
									<?php elseif ( 'color' === $definition['type'] ) : ?>
										<label class="sfj-style-field__label" for="<?php echo esc_attr( 'style_' . $key ); ?>"><?php echo esc_html( $definition['label'] ); ?></label>
										<input
											type="color"
											class="sfj-style-color"
											id="<?php echo esc_attr( 'style_' . $key ); ?>"
											name="<?php echo esc_attr( 'style_' . $key ); ?>"
											value="<?php echo esc_attr( (string) ( $styles[ $key ] ?? $definition['default'] ) ); ?>"
											data-sfj-style-input
											data-sfj-style-key="<?php echo esc_attr( $key ); ?>"
											data-sfj-style-type="color"
											data-sfj-style-var="<?php echo esc_attr( $definition['css_var'] ); ?>"
											data-sfj-style-default="<?php echo esc_attr( (string) $definition['default'] ); ?>"
										/>
									<?php else : ?>
										<label class="sfj-style-field__label" for="<?php echo esc_attr( 'style_' . $key ); ?>">
											<?php echo esc_html( $definition['label'] ); ?>
											<span class="sfj-style-field__value" data-sfj-style-value="<?php echo esc_attr( $key ); ?>">
												<?php
												echo esc_html(
													(string) ( $styles[ $key ] ?? $definition['default'] ) . ( $definition['unit'] ?? '' )
												);
												?>
											</span>
										</label>
										<input
											type="range"
											class="sfj-style-range"
											id="<?php echo esc_attr( 'style_' . $key ); ?>"
											name="<?php echo esc_attr( 'style_' . $key ); ?>"
											min="<?php echo esc_attr( (string) ( $definition['min'] ?? 0 ) ); ?>"
											max="<?php echo esc_attr( (string) ( $definition['max'] ?? 100 ) ); ?>"
											value="<?php echo esc_attr( (string) ( $styles[ $key ] ?? $definition['default'] ) ); ?>"
											data-sfj-style-input
											data-sfj-style-key="<?php echo esc_attr( $key ); ?>"
											data-sfj-style-type="range"
											data-sfj-style-var="<?php echo esc_attr( $definition['css_var'] ); ?>"
											data-sfj-style-unit="<?php echo esc_attr( (string) ( $definition['unit'] ?? '' ) ); ?>"
											data-sfj-style-scale="<?php echo ! empty( $definition['scale'] ) ? '1' : '0'; ?>"
											data-sfj-style-default="<?php echo esc_attr( (string) $definition['default'] ); ?>"
										/>
									<?php endif; ?>
									<?php if ( ! empty( $definition['hint'] ) ) : ?>
										<p class="sfj-style-field__hint"><?php echo esc_html( $definition['hint'] ); ?></p>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</section>
					<?php endforeach; ?>
				</div>

				<div class="sfj-card-preview sfj-card-preview--styles sfj-card-preview--sticky">
					<p class="sfj-card-preview__label"><?php esc_html_e( 'Preview (filters + 3 job postings)', 'sha-factorial-jobs' ); ?></p>
					<div class="sfj-card-preview__frame">
						<?php echo sha_factorial_jobs_render_admin_preview_list( $all_fields, $styles ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Shortcodes reference tab.
	 */
	private function render_shortcodes_reference(): void {
		$examples = array(
			array(
				'title' => __( 'Spain listing', 'sha-factorial-jobs' ),
				'code'  => '[sha_factorial_jobs region="es"]',
			),
			array(
				'title' => __( 'Mexico listing', 'sha-factorial-jobs' ),
				'code'  => '[sha_factorial_jobs region="mx"]',
			),
			array(
				'title' => __( 'Both regions with filters', 'sha-factorial-jobs' ),
				'code'  => '[sha_factorial_jobs region="all" show_filters="true" layout="grid"]',
			),
		);

		$attrs = array(
			'region'          => 'es | mx | all',
			'category'        => 'Factorial category slug',
			'contract_type'   => 'Factorial contract_type slug',
			'workplace_type'  => 'onsite | remote | hybrid',
			'schedule_type'   => 'full_time | part_time',
			'team_id'         => __( 'Team ID (API)', 'sha-factorial-jobs' ),
			'location_id'     => __( 'Location ID (API)', 'sha-factorial-jobs' ),
			'legal_entity_id' => __( 'Legal entity ID (API)', 'sha-factorial-jobs' ),
			'remote'          => 'true | false',
			'limit'           => '1–100',
			'layout'          => 'list | grid',
			'show_filters'    => 'true | false',
			'show_salary'     => 'true | false',
		);
		?>
		<div class="sfj-card">
			<h2><?php esc_html_e( 'Ready-to-copy shortcodes', 'sha-factorial-jobs' ); ?></h2>
			<p class="sfj-card__desc"><?php esc_html_e( 'Each job detail opens on the same page using URL parameters (?sfj_job=ID&sfj_region=es|mx). No separate shortcode is required.', 'sha-factorial-jobs' ); ?></p>
			<?php foreach ( $examples as $example ) : ?>
				<div class="sfj-shortcode">
					<div class="sfj-shortcode__head">
						<strong><?php echo esc_html( $example['title'] ); ?></strong>
						<button type="button" class="button button-small sfj-copy" data-copy="<?php echo esc_attr( $example['code'] ); ?>"><?php esc_html_e( 'Copy', 'sha-factorial-jobs' ); ?></button>
					</div>
					<code class="sfj-shortcode__code"><?php echo esc_html( $example['code'] ); ?></code>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="sfj-card">
			<h2><?php esc_html_e( 'Attributes (Factorial API mapping)', 'sha-factorial-jobs' ); ?></h2>
			<table class="sfj-table widefat striped">
				<thead><tr><th><?php esc_html_e( 'Attribute', 'sha-factorial-jobs' ); ?></th><th><?php esc_html_e( 'Values', 'sha-factorial-jobs' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $attrs as $name => $values ) : ?>
						<tr><td><code><?php echo esc_html( $name ); ?></code></td><td><?php echo esc_html( $values ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * About tab content.
	 */
	private function render_about_tab(): void {
		?>
		<div class="sfj-about">
			<div class="sfj-card">
				<h2><?php esc_html_e( 'SHA Factorial Jobs', 'sha-factorial-jobs' ); ?></h2>
				<p class="sfj-about__meta">
					<?php
					printf(
						/* translators: %s: plugin version */
						esc_html__( 'Version %s', 'sha-factorial-jobs' ),
						esc_html( SHA_FACTORIAL_JOBS_VERSION )
					);
					?>
				</p>
				<p class="sfj-card__desc">
					<?php esc_html_e( 'Custom integration between Factorial HR and WordPress for SHA Wellness. Displays published job postings on your site via shortcodes, with configurable cache, frontend filters, and field and style customization from this panel.', 'sha-factorial-jobs' ); ?>
				</p>
				<ul class="sfj-about__list">
					<li><?php esc_html_e( 'Connection to the Factorial API by region.', 'sha-factorial-jobs' ); ?></li>
					<li><?php esc_html_e( 'Job listing and detail on the same page.', 'sha-factorial-jobs' ); ?></li>
					<li><?php esc_html_e( 'Configurable visible fields, layout, filters, and styles.', 'sha-factorial-jobs' ); ?></li>
				</ul>
				<p class="sfj-about__credits">
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: 1: developer name, 2: client name */
							__( 'Developed by %1$s for %2$s.', 'sha-factorial-jobs' ),
							'<strong>Clink Web Value</strong>',
							'<strong>SHA Wellness</strong>'
						)
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}
}
