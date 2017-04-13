<?php
/**
 * Singleton for setting up Purgely settings.
 */
class Purgely_Settings_Page {
	/**
	 * The one instance of Purgely_Settings_Page.
	 *
	 * @since 1.0.0.
	 *
	 * @var Purgely_Settings_Page
	 */
	private static $instance;

	/**
	 * Instantiate or return the one Purgely_Settings_Page instance.
	 *
	 * @since 1.0.0.
	 *
	 * @return Purgely_Settings_Page
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initiate actions.
	 *
	 * @since 1.0.0.
	 *
	 * @return Purgely_Settings_Page
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
	}

	/**
	 * Setup the configuration screen for the plugin.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	function add_admin_menu() {
		add_submenu_page(
			'options-general.php',
			__( 'Fastly', 'purgely' ),
			__( 'Fastly', 'purgely' ),
			'manage_options',
			'fastly',
			array( $this, 'options_page' )
		);
	}

	/**
	 * Initialize all of the settings.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	function settings_init() {
		// Set up the option name, "fastly-settings". All values will be in this array.
		register_setting(
			'fastly-settings',
			'fastly-settings',
			array( $this, 'sanitize_settings' )
		);

		// Set up the settings section.
		add_settings_section(
			'purgely-fastly_settings',
			__( 'Fastly settings', 'purgely' ),
			array( $this, 'fastly_settings_callback' ),
			'fastly-settings'
		);

        add_settings_section(
            'purgely-fastly_settings',
            __( 'Fastly settings', 'purgely' ),
            array( $this, 'fastly_settings_newcomers' ),
            'fastly-settings'
        );

		// Register all of the individual settings.
		add_settings_field(
			'fastly_api_key',
			__( 'API Key', 'purgely' ),
			array( $this, 'fastly_api_key_render' ),
			'fastly-settings',
			'purgely-fastly_settings'
		);

		add_settings_field(
			'fastly_service_id',
			__( 'Service ID', 'purgely' ),
			array( $this, 'fastly_service_id_render' ),
			'fastly-settings',
			'purgely-fastly_settings'
		);

		add_settings_field(
			'fastly_api_hostname',
			__( 'API Endpoint', 'purgely' ),
			array( $this, 'fastly_api_hostname_render' ),
			'fastly-settings',
			'purgely-fastly_settings'
		);

		// Set up the general settings.
		add_settings_section(
			'purgely-general_settings',
			__( 'General settings', 'purgely' ),
			array( $this, 'general_settings_callback' ),
			'fastly-settings'
		);

		add_settings_field(
			'surrogate_control_ttl',
			__( 'Cache TTL (in Seconds)', 'purgely' ),
			array( $this, 'surrogate_control_render' ),
			'fastly-settings',
			'purgely-general_settings'
		);

		add_settings_field(
			'default_purge_type',
			__( 'Default Purge Type', 'purgely' ),
			array( $this, 'default_purge_type_render' ),
			'fastly-settings',
			'purgely-general_settings'
		);

		add_settings_field(
			'allow_purge_all',
			__( 'Allow Full Cache Purges', 'purgely' ),
			array( $this, 'allow_purge_all_render' ),
			'fastly-settings',
			'purgely-general_settings'
		);

        add_settings_field(
            'fastly_log_purges',
            __( 'Log purges in error log', 'purgely' ),
            array( $this, 'fastly_log_purges_render' ),
            'fastly-settings',
            'purgely-general_settings'
        );

		// Set up the stale content settings.
		add_settings_section(
			'purgely-stale_settings',
			__( 'Content revalidation settings', 'purgely' ),
			array( $this, 'stale_settings_callback' ),
			'fastly-settings'
		);

		add_settings_field(
			'enable_stale_while_revalidate',
			__( 'Enable Stale while Revalidate', 'purgely' ),
			array( $this, 'enable_stale_while_revalidate_render' ),
			'fastly-settings',
			'purgely-stale_settings'
		);

		add_settings_field(
			'stale_while_revalidate_ttl',
			__( 'Stale while Revalidate TTL (in Seconds)', 'purgely' ),
			array( $this, 'stale_while_revalidate_ttl_render' ),
			'fastly-settings',
			'purgely-stale_settings'
		);

		add_settings_field(
			'enable_stale_if_error',
			__( 'Enable Stale if Error', 'purgely' ),
			array( $this, 'enable_stale_if_error_render' ),
			'fastly-settings',
			'purgely-stale_settings'
		);

		add_settings_field(
			'stale_if_error_ttl',
			__( 'Stale if Error TTL (in Seconds)', 'purgely' ),
			array( $this, 'stale_if_error_ttl_render' ),
			'fastly-settings',
			'purgely-stale_settings'
		);
	}

    /**
     * Print the sign up and documentation link on the settings page.
     *
     * @since 1.1.1.
     *
     * @return void
     */
    public function fastly_settings_newcomers() {
        echo __("New to Fastly? <a href='https://docs.fastly.com/guides/basic-setup/sign-up-and-create-your-first-service' target='_blank'>Sign up and get started!</a>", 'purgely');
    }

	/**
	 * Print the description for the settings page.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function fastly_settings_callback() {
		esc_html_e( 'Please enter details related to your Fastly account. A Fastly API key and service ID are required for some operations (e.g., surrogate key and full cache purges). ', 'purgely' );
	}

	/**
	 * Render the setting input.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function fastly_api_key_render() {
		$options = Purgely_Settings::get_settings();
		?>
		<input type='text' name='fastly-settings[fastly_api_key]' value='<?php echo esc_attr( $options['fastly_api_key'] ); ?>'>
		<em><strong><?php esc_html_e( 'Required for surrogate key and full cache purges', 'purgely' ); ?></strong></em>
		<p class="description">
			<?php esc_html_e( 'API key for the Fastly account associated with this site.', 'purgely' ); ?>
			<?php
			printf(
				esc_html__( 'Please see Fastly\'s documentation for %s.', 'purgely' ),
				sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					'https://docs.fastly.com/guides/account-management-and-security/finding-and-managing-your-account-info#finding-and-regenerating-your-api-key',
					esc_html__( 'more information on finding your API key', 'purgely' )
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the setting input.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function fastly_service_id_render() {
		$options = Purgely_Settings::get_settings();
		?>
		<input type='text' name='fastly-settings[fastly_service_id]' value='<?php echo esc_attr( $options['fastly_service_id'] ); ?>'>
		<em><strong><?php esc_html_e( 'Required for surrogate key and full cache purges', 'purgely' ); ?></strong></em>
		<p class="description">
			<?php esc_html_e( 'Fastly service ID for this site.', 'purgely' ); ?>
			<?php
			printf(
				esc_html__( 'Please see Fastly\'s documentation for %s.', 'purgely' ),
				sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					'https://docs.fastly.com/guides/account-management-and-security/finding-and-managing-your-account-info#finding-your-service-id',
					esc_html__( 'more information on finding your service ID', 'purgely' )
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the setting input.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function fastly_api_hostname_render() {
		$options = Purgely_Settings::get_settings();
		?>
		<input type='text' name='fastly-settings[fastly_api_hostname]' value='<?php echo esc_attr( $options['fastly_api_hostname'] ); ?>'>
		<p class="description">
			<?php esc_html_e( 'API endpoint for this service.', 'purgely' ); ?>
		</p>
		<?php
	}

	/**
	 * Print the description general settings section.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function general_settings_callback() {
		esc_html_e( 'This section allows you to configure general cache settings. Note that changes to these settings can cause destabilization to your site if misconfigured. The default setting should be sufficient for most sites.', 'purgely' );
	}

	/**
	 * Render the setting input.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function surrogate_control_render() {
		$options = Purgely_Settings::get_settings();
		?>
		<input type='text' name='fastly-settings[surrogate_control_ttl]' value='<?php echo esc_attr( $options['surrogate_control_ttl'] ); ?>'>
		<p class="description">
			<?php esc_html_e( 'This setting controls the "surrogate-control" header\'s "max-age" value. It defines the cache duration for all pages on the site.', 'purgely' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the setting input.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function default_purge_type_render() {
		$options = Purgely_Settings::get_settings();

		$purge_types = array(
			'soft'    => __( 'Soft', 'purgely' ),
			'instant' => __( 'Instant', 'purgely' ),
		);
		?>
		<select name="fastly-settings[default_purge_type]">
			<?php foreach ( $purge_types as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $options['default_purge_type'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php
			printf(
				esc_html__( 'The purge type setting controls the manner in which the cache is purged. Instant purging causes the cached object(s) to be purged immediately. Soft purging causes the origin to revalidate the cache and Fastly will serve stale content until revalidation is completed. For more information, please see %s.', 'purgely' ),
				sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					'https://docs.fastly.com/guides/purging/soft-purges',
					esc_html__( 'Fastly\'s documentation for more information on soft purging', 'purgely' )
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the setting input.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function allow_purge_all_render() {
		$options = Purgely_Settings::get_settings();
		?>
		<input type='radio' name='fastly-settings[allow_purge_all]' <?php checked( isset( $options['allow_purge_all'] ) && true === $options['allow_purge_all'] ); ?> value='true'>Yes&nbsp;
		<input type='radio' name='fastly-settings[allow_purge_all]' <?php checked( isset( $options['allow_purge_all'] ) && false === $options['allow_purge_all'] ); ?> value='false'>No
		<p class="description">
			<?php esc_html_e( 'The full cache purging behavior available to WP CLI must be explicitly enabled in order for it to work. Purging the entire cache can cause significant site stability issues and is disable by default.', 'purgely' ); ?>
		</p>
		<?php
	}

    /**
     * Render the setting input.
     *
     * @since 1.0.0.
     *
     * @return void
     */
    public function fastly_log_purges_render() {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='radio' name='fastly-settings[fastly_log_purges]' <?php checked( isset( $options['fastly_log_purges'] ) && true === $options['fastly_log_purges'] ); ?> value='true'>Yes&nbsp;
        <input type='radio' name='fastly-settings[fastly_log_purges]' <?php checked( isset( $options['fastly_log_purges'] ) && false === $options['fastly_log_purges'] ); ?> value='false'>No
        <p class="description">
            <?php esc_html_e( 'Log all purges in error_log', 'purgely' ); ?>
        </p>
        <?php
    }

	/**
	 * Print the description for the stale content settings.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function stale_settings_callback() {
		esc_html_e( 'This section allows you to configure how content is handled as it is revalidated. It is important that proper consideration is given to how content is regenerated after it expires from cache. The default settings take a conservative approach by allowing stale content to be served while new content is regenerated in the background.', 'purgely' );
	}

	/**
	 * Render the setting input.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function enable_stale_while_revalidate_render() {
		$options = Purgely_Settings::get_settings();
		?>
		<input type='radio' name='fastly-settings[enable_stale_while_revalidate]' <?php checked( isset( $options['enable_stale_while_revalidate'] ) && true === $options['enable_stale_while_revalidate'] ); ?> value='true'>Yes&nbsp;
		<input type='radio' name='fastly-settings[enable_stale_while_revalidate]' <?php checked( isset( $options['enable_stale_while_revalidate'] ) && false === $options['enable_stale_while_revalidate'] ); ?> value='false'>No
		<p class="description">
			<?php
			printf(
				esc_html__( 'Turn the "stale while revalidate" behavior on or off. The stale while revalidate behavior allows stale content to be served while content is regenerated in the background. Please see %s', 'purgely' ),
				sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					'https://www.fastly.com/blog/stale-while-revalidate',
					esc_html__( 'Fastly\'s documentation for more information on stale while revalidate', 'purgely' )
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the setting input.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function stale_while_revalidate_ttl_render() {
		$options = Purgely_Settings::get_settings();
		?>
		<input type='text' name='fastly-settings[stale_while_revalidate_ttl]' value='<?php echo esc_attr( $options['stale_while_revalidate_ttl'] ); ?>'>
		<p class="description">
			<?php esc_html_e( 'This setting determines the amount of time that stale content will be served while new content is generated.', 'purgely' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the setting input.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function enable_stale_if_error_render() {
		$options = Purgely_Settings::get_settings();
		?>
		<input type='radio' name='fastly-settings[enable_stale_if_error]' <?php checked( isset( $options['enable_stale_if_error'] ) && true === $options['enable_stale_if_error'] ); ?> value='true'>Yes&nbsp;
		<input type='radio' name='fastly-settings[enable_stale_if_error]' <?php checked( isset( $options['enable_stale_if_error'] ) && false === $options['enable_stale_if_error'] ); ?> value='false'>No
		<p class="description">
			<?php
			printf(
				esc_html__( 'Turn the "stale if error" behavior on or off. The stale if error behavior allows stale content to be served while the origin is returning an error state. Please see %s', 'purgely' ),
				sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					'https://www.fastly.com/blog/stale-while-revalidate',
					esc_html__( 'Fastly\'s documentation for more information on stale if error', 'purgely' )
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the setting input.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function stale_if_error_ttl_render() {
		$options = Purgely_Settings::get_settings();
		?>
		<input type='text' name='fastly-settings[stale_if_error_ttl]' value='<?php echo esc_attr( $options['stale_if_error_ttl'] ); ?>'>
		<p class="description">
			<?php esc_html_e( 'This setting determines the amount of time that stale content will be served while the origin is returning an error state.', 'purgely' ); ?>
		</p>
		<?php
	}

	/**
	 * Print the settings page.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function options_page() {
		?>
		<div class="wrap">
			<form action='options.php' method='post'>
                <div id="fastly-admin" class="wrap">
                    <h1><img alt="fastly" src="<?php echo FASTLY_PLUGIN_URL .'static/logo_white.gif'; ?>"><br><span style="font-size: x-small;">version: <?php echo FASTLY_VERSION; ?></span></h1>
                </div>
				<?php
				settings_fields( 'fastly-settings' );
				do_settings_sections( 'fastly-settings' );
				submit_button();
				?>

			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize all of the setting.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $settings The unsanitized settings.
	 * @return array           The sanitized settings.
	 */
	public function sanitize_settings( $settings ) {
		$clean_settings = array();
		$registered_settings = Purgely_Settings::get_registered_settings();

		foreach ( $settings as $key => $value ) {
			if ( isset( $registered_settings[ $key ] ) ) {
				$clean_settings[ $key ] = call_user_func( $registered_settings[ $key ]['sanitize_callback'], $value );
			}
		}

		return $settings;
	}
}

/**
 * Instantiate or return the one Purgely_Settings_Page instance.
 *
 * @since 1.0.0.
 *
 * @return Purgely_Settings_Page
 */
function get_purgely_settings_page_instance() {
	return Purgely_Settings_Page::instance();
}

get_purgely_settings_page_instance();