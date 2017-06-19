<?php

/**
 * Singleton for setting up Purgely settings.
 */
class Purgely_Settings_Page
{

    /**
     * Size of input fields in admin
     */
    const INPUT_SIZE = 35;

    /**
     * The one instance of Purgely_Settings_Page.
     *
     * @var Purgely_Settings_Page
     */
    private static $instance;

    /**
     * Instantiate or return the one Purgely_Settings_Page instance.
     *
     * @return Purgely_Settings_Page
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initiate actions.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('wp_ajax_test_fastly_connection', array($this, 'test_fastly_connection_callback'));
        add_action('wp_ajax_fastly_vcl_update_ok', array($this, 'fastly_vcl_update_ok_callback'));
        add_action('wp_ajax_purge_by_url', array($this, 'fastly_purge_by_url_callback'));
        add_action('wp_ajax_test_fastly_webhooks_connection', array($this, 'test_fastly_webhooks_connection_callback'));
        add_action('wp_ajax_purge_all', array($this, 'purge_all_callback'));
    }

    /**
     * Setup the configuration screen for the plugin.
     *
     * @return void
     */
    function add_admin_menu()
    {
        add_menu_page(
            __('Fastly General', 'purgely'),
            __('Fastly', 'purgely'),
            'manage_options',
            'fastly',
            array($this, 'options_page')
        );

        add_submenu_page(
            'fastly',
            __('Fastly General Options', 'purgely'),
            __('General', 'purgely'),
            'manage_options',
            'fastly',
            array($this, 'options_page')
        );

        add_submenu_page(
            'fastly',
            __('Fastly Advanced Options', 'purgely'),
            __('Advanced', 'purgely'),
            'manage_options',
            'fastly-advanced',
            array($this, 'options_page_advanced')
        );

        add_submenu_page(
            'fastly',
            __('Fastly Webhooks', 'purgely'),
            __('Webhooks', 'purgely'),
            'manage_options',
            'fastly-webhooks',
            array($this, 'options_page_webhooks')
        );
    }


    /**
     * Initialize all of the settings.
     *
     * @return void
     */
    function settings_init()
    {
        // Set up the option name, "fastly-settings-general". All general values will be in this array.
        register_setting(
            'fastly-settings-general',
            'fastly-settings-general',
            array($this, 'sanitize_settings')
        );

        // Set up the option name, "fastly-settings-advanced". All advanced values will be in this array.
        register_setting(
            'fastly-settings-advanced',
            'fastly-settings-advanced',
            array($this, 'sanitize_settings')
        );

        // Set up the option name, "fastly-settings-webhooks". All webhooks values will be in this array.
        register_setting(
            'fastly-settings-webhooks',
            'fastly-settings-webhooks',
            array($this, 'sanitize_settings')
        );

        // Set up the settings section.
        add_settings_section(
            'purgely-fastly_settings',
            __('Fastly settings', 'purgely'),
            array($this, 'fastly_settings_callback'),
            'fastly-settings-general'
        );

        add_settings_section(
            'purgely-fastly_settings',
            __('General settings', 'purgely'),
            array($this, 'fastly_settings_newcomers'),
            'fastly-settings-general'
        );

        // Register all of the GENERAL settings.
        add_settings_field(
            'fastly_api_key',
            __('Fastly API token', 'purgely'),
            array($this, 'fastly_api_key_render'),
            'fastly-settings-general',
            'purgely-fastly_settings'
        );

        add_settings_field(
            'fastly_service_id',
            __('Service ID', 'purgely'),
            array($this, 'fastly_service_id_render'),
            'fastly-settings-general',
            'purgely-fastly_settings'
        );

        add_settings_field(
            'fastly_api_hostname',
            __('API Endpoint', 'purgely'),
            array($this, 'fastly_api_hostname_render'),
            'fastly-settings-general',
            'purgely-fastly_settings'
        );

        // Execute only if first time, or if requires updating
        $purgely_instance = get_purgely_instance();
        if (version_compare(get_option('fastly_vcl_version'), $purgely_instance->vcl_last_version, '<')) {
            add_settings_field(
                'fastly_update_vcl',
                __('Update VCL', 'purgely'),
                array($this, 'fastly_vcl_update_render'),
                'fastly-settings-general',
                'purgely-fastly_settings'
            );
        }

        add_settings_field(
            'fastly_test_connection',
            __('', 'purgely'),
            array($this, 'fastly_test_connection_render'),
            'fastly-settings-general',
            'purgely-fastly_settings'
        );

        // Register all of the ADVANCED settings.
        add_settings_section(
            'purgely-advanced_settings',
            __('Advanced settings', 'purgely'),
            array($this, 'general_settings_callback'),
            'fastly-settings-advanced'
        );

        add_settings_field(
            'surrogate_control_ttl',
            __('Surrogate Cache TTL (in Seconds)', 'purgely'),
            array($this, 'surrogate_control_render'),
            'fastly-settings-advanced',
            'purgely-advanced_settings'
        );

        add_settings_field(
            'cache_control_ttl',
            __('Cache TTL (in Seconds)', 'purgely'),
            array($this, 'cache_control_render'),
            'fastly-settings-advanced',
            'purgely-advanced_settings'
        );

        add_settings_field(
            'default_purge_type',
            __('Default Purge Type', 'purgely'),
            array($this, 'default_purge_type_render'),
            'fastly-settings-advanced',
            'purgely-advanced_settings'
        );

        add_settings_field(
            'allow_purge_all',
            __('Allow Full Cache Purges', 'purgely'),
            array($this, 'allow_purge_all_render'),
            'fastly-settings-advanced',
            'purgely-advanced_settings'
        );

        add_settings_field(
            'fastly_log_purges',
            __('Log purges in error log', 'purgely'),
            array($this, 'fastly_log_purges_render'),
            'fastly-settings-advanced',
            'purgely-advanced_settings'
        );

        add_settings_field(
            'fastly_debug_mode',
            __('Debug mode', 'purgely'),
            array($this, 'fastly_debug_mode_render'),
            'fastly-settings-advanced',
            'purgely-advanced_settings'
        );

        // Set up the stale content settings.
        add_settings_section(
            'purgely-stale_settings',
            __('Content revalidation settings', 'purgely'),
            array($this, 'stale_settings_callback'),
            'fastly-settings-advanced'
        );

        add_settings_field(
            'enable_stale_while_revalidate',
            __('Enable Stale while Revalidate', 'purgely'),
            array($this, 'enable_stale_while_revalidate_render'),
            'fastly-settings-advanced',
            'purgely-stale_settings'
        );

        add_settings_field(
            'stale_while_revalidate_ttl',
            __('Stale while Revalidate TTL (in Seconds)', 'purgely'),
            array($this, 'stale_while_revalidate_ttl_render'),
            'fastly-settings-advanced',
            'purgely-stale_settings'
        );

        add_settings_field(
            'enable_stale_if_error',
            __('Enable Stale if Error', 'purgely'),
            array($this, 'enable_stale_if_error_render'),
            'fastly-settings-advanced',
            'purgely-stale_settings'
        );

        add_settings_field(
            'stale_if_error_ttl',
            __('Stale if Error TTL (in Seconds)', 'purgely'),
            array($this, 'stale_if_error_ttl_render'),
            'fastly-settings-advanced',
            'purgely-stale_settings'
        );

        add_settings_field(
            'purge_by_url',
            __('Purge by URL', 'purgely'),
            array($this, 'purge_by_url_render'),
            'fastly-settings-advanced',
            'purgely-stale_settings'
        );

        add_settings_field(
            'purge_all',
            __('', 'purgely'),
            array($this, 'purge_all_render'),
            'fastly-settings-advanced',
            'purgely-stale_settings'
        );

        // Set up the webhooks settings.
        add_settings_section(
            'purgely-webhooks_settings',
            __('Webhooks settings', 'purgely'),
            array($this, 'webhooks_settings_callback'),
            'fastly-settings-webhooks'
        );

        // Register all of the webhooks settings
        add_settings_field(
            'webhooks_url_endpoint',
            __('Webhooks URL Endpoint', 'purgely'),
            array($this, 'webhooks_url_endpoint_render'),
            'fastly-settings-webhooks',
            'purgely-webhooks_settings'
        );

        add_settings_field(
            'webhooks_username',
            __('Webhooks Username', 'purgely'),
            array($this, 'webhooks_username_render'),
            'fastly-settings-webhooks',
            'purgely-webhooks_settings'
        );

        add_settings_field(
            'webhooks_channel',
            __('Webhooks Channel', 'purgely'),
            array($this, 'webhooks_channel_render'),
            'fastly-settings-webhooks',
            'purgely-webhooks_settings'
        );

        add_settings_field(
            'webhooks_activate',
            __('Activate for purging', 'purgely'),
            array($this, 'webhooks_activate_render'),
            'fastly-settings-webhooks',
            'purgely-webhooks_settings'
        );

        add_settings_field(
            'webhooks_test_connection',
            __('', 'purgely'),
            array($this, 'webhooks_test_connection_render'),
            'fastly-settings-webhooks',
            'purgely-webhooks_settings'
        );
    }

    /**
     * Print the sign up and documentation link on the settings page.
     *
     * @return void
     */
    public function fastly_settings_newcomers()
    {
        echo __("New to Fastly? <a href='https://docs.fastly.com/guides/basic-setup/sign-up-and-create-your-first-service' target='_blank'>Sign up and get started!</a>", 'purgely');
    }

    /**
     * Print the description for the settings page.
     *
     * @return void
     */
    public function fastly_settings_callback()
    {
        esc_html_e('Please enter details related to your Fastly account. A Fastly API token and service ID are required for some operations (e.g., surrogate key and full cache purges). ', 'purgely');
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function fastly_api_key_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='text' name='fastly-settings-general[fastly_api_key]'
               value='<?php echo esc_attr($options['fastly_api_key']); ?>' size="<?php echo self::INPUT_SIZE ?>">
        <em><strong><?php esc_html_e('Required for surrogate key and full cache purges', 'purgely'); ?></strong></em>
        <p class="description">
            <?php esc_html_e('API token for the Fastly account associated with this site.', 'purgely'); ?>
            <?php
            printf(
                esc_html__('Please see Fastly\'s documentation for %s.', 'purgely'),
                sprintf(
                    '<a href="%1$s" target="_blank">%2$s</a>',
                    'https://docs.fastly.com/guides/account-management-and-security/finding-and-managing-your-account-info#finding-and-regenerating-your-api-key',
                    esc_html__('more information on finding your API token', 'purgely')
                )
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function fastly_service_id_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='text' name='fastly-settings-general[fastly_service_id]'
               value='<?php echo esc_attr($options['fastly_service_id']); ?>' size="<?php echo self::INPUT_SIZE ?>">
        <em><strong><?php esc_html_e('Required for surrogate key and full cache purges', 'purgely'); ?></strong></em>
        <p class="description">
            <?php esc_html_e('Fastly service ID for this site.', 'purgely'); ?>
            <?php
            printf(
                esc_html__('Please see Fastly\'s documentation for %s.', 'purgely'),
                sprintf(
                    '<a href="%1$s" target="_blank">%2$s</a>',
                    'https://docs.fastly.com/guides/account-management-and-security/finding-and-managing-your-account-info#finding-your-service-id',
                    esc_html__('more information on finding your service ID', 'purgely')
                )
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function fastly_api_hostname_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='text' name='fastly-settings-general[fastly_api_hostname]'
               value='<?php echo esc_attr($options['fastly_api_hostname']); ?>' size="<?php echo self::INPUT_SIZE ?>">
        <p class="description">
            <?php esc_html_e('API endpoint for this service.', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Render the vcl update button.
     *
     * @return void
     */
    public function fastly_vcl_update_render()
    {
        add_thickbox();
        $message = __('Make sure you have proper credentials.');
        $service_id = false;
        $vcl = new Vcl_Handler(array());

        if (!is_null($vcl->_last_active_version_num) && !is_null($vcl->_next_cloned_version_num)) {
            $service_id = purgely_get_option('fastly_service_id');
            $message = __("You are about to clone active version
                        {$vcl->_last_active_version_num} for service <b>{$service_id}</b>.
                        We'll upload VCL snippets to version {$vcl->_next_cloned_version_num}");
        } else {
            $errors = $vcl->get_errors();
            if (!empty($errors)) {
                $errors = $vcl->get_errors();
                $message = $errors[0];
            }
        }
        ?>
        <div id="vcl-popup-wrapper" style="display:none;">
            <div id="vcl-main-ui" style="relative;">
                <p style="margin-bottom:0;padding-bottom:0;"><?php echo $message; ?></p>
                <?php if ($service_id) : ?>
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th>
                                <label for="vcl_activate_new">
                                    <?php echo __('Activate new version'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="checkbox" id="vcl_activate_new" name="vcl_activate_new" value="1" checked>

                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <div style="margin-bottom:.5em;" id="vcl-response-msg"></div>
                    <div>
                        <input type="button" id="vcl-update-btn-ok" class="button button-primary"
                               style="margin-right: 1em;" onclick="vcl_step2()"
                               value="<?php echo __('Save Changes'); ?>"/>
                        <input type="button" class="button button-secondary" onclick="vcl_step_cancel()"
                               value="<?php echo __('Cancel'); ?>"/>
                    </div>
                    <span class="spinner" id="vcl-popup-spinner"
                          style="position: absolute; top:0;left:0;width:100%;height:100%;margin:0; background-color: #fff; background-position:center;"></span>
                <?php else: ?>
                    <p>
                        <input type="button" class="button button-secondary" onclick="vcl_step_cancel()"
                               value="<?php echo __('Close'); ?>"/>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <div id="vcl-wrapper">
            <a href="#TB_inline?&inlineId=vcl-popup-wrapper&<?php if ($service_id) : ?>width=420&height=180<?php else : ?>width=320&height=120<?php endif; ?>"
               name="Confirm VCL Update" class='button button-secondary thickbox' id="vcl-update-btn">VCL UPDATE</a>
            <em class="description" id="vcl-update-response">
                <strong style="padding-top: 5px; display: inline-block">
                    <?php echo __('Make sure to save before proceeding'); ?>
                </strong>
            </em>
        </div>

        <script type='text/javascript'>
            var url = '<?php echo admin_url('admin-ajax.php'); ?>';
            var activate = 0;
            var vcl_response_msg = document.getElementById('vcl-response-msg');
            var spinner = jQuery('#vcl-popup-spinner');
            /** Proceed to step 2 vcl update **/
            function vcl_step2() {
                activate = jQuery('#vcl_activate_new').is(":checked") ? 1 : 0;
                spinner.toggleClass('is-active');
                jQuery.ajax({
                    method: 'GET',
                    url: url,
                    data: {
                        action: 'fastly_vcl_update_ok',
                        activate: activate
                    },
                    success: function (response) {
                        spinner.toggleClass('is-active');
                        if (response.status) {
                            // Final stage
                            if (activate === 1) {
                                var button_elem = jQuery('#vcl-update-btn');
                                var button_ok_elem = jQuery('#vcl-update-btn-ok');
                                button_elem.hide();
                                button_ok_elem.hide();
                            }

                            vcl_response_msg.innerHTML = response.message;
                        }
                        document.getElementById('vcl-update-response').innerHTML = response.message;
                    },
                    dataType: 'json'
                });
            }

            /** Hide vcl update popup **/
            function vcl_step_cancel() {
                jQuery('#TB_closeWindowButton').click();
            }
        </script>
        <?php
    }

    /**
     * Render the test connection button.
     *
     * @return void
     */
    public function fastly_test_connection_render()
    {
        ?>
        <input type='button' class='button button-secondary' id="test-connection-btn" value="TEST CONNECTION"/>
        <em class="description" id="test-connection-response">
            <strong style="padding-top: 5px; display: inline-block">
                <?php echo __('Make sure to save before proceeding'); ?>
            </strong>
        </em>
        <script type='text/javascript'>
            var url = '<?php echo admin_url('admin-ajax.php'); ?>';
            jQuery(document).ready(function ($) {
                jQuery('#test-connection-btn').click(function () {
                    $.ajax({
                        method: 'GET',
                        url: url,
                        data: {
                            action: 'test_fastly_connection'
                        },
                        success: function (response) {
                            document.getElementById('test-connection-response').innerHTML = '';
                            if (response.status) {
                                var button_elem = jQuery('#test-connection-btn');
                                if (button_elem.hasClass('button-secondary')) {
                                    button_elem.toggleClass('button-secondary');
                                    button_elem.toggleClass('button-primary');
                                }
                            }
                            document.getElementById('test-connection-response').innerHTML = response.message;
                        },
                        dataType: 'json'
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Render the test connection button.
     *
     * @return void
     */
    public function webhooks_test_connection_render()
    {
        ?>
        <input type='button' class='button button-secondary' id="test-webhooks-connection-btn" value="TEST CONNECTION"/>
        <em class="description" id="test-connection-response">
            <strong>
                <?php echo __('Make sure to save before proceeding'); ?>
            </strong>
        </em>
        <script type='text/javascript'>
            var url = '<?php echo admin_url('admin-ajax.php'); ?>';
            jQuery(document).ready(function ($) {
                jQuery('#test-webhooks-connection-btn').click(function () {
                    $.ajax({
                        method: 'GET',
                        url: url,
                        data: {
                            action: 'test_fastly_webhooks_connection'
                        },
                        success: function (response) {
                            document.getElementById('test-connection-response').innerHTML = '';
                            if (response.status) {
                                var button_elem = jQuery('#test-webhooks-connection-btn');
                                if (button_elem.hasClass('button-secondary')) {
                                    button_elem.toggleClass('button-secondary');
                                    button_elem.toggleClass('button-primary');
                                }
                            }
                            document.getElementById('test-connection-response').innerHTML = response.message;
                        },
                        dataType: 'json'
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Render the test connection button.
     *
     * @return void
     */
    public function purge_all_render()
    {
        ?>
        <input type='button' class='button button-secondary' id="test-connection-btn" value="Purge All"/>
        <div id="purge-all-response"></div>
        <script type='text/javascript'>
            var url = '<?php echo admin_url('admin-ajax.php'); ?>';
            jQuery(document).ready(function ($) {
                jQuery('#test-connection-btn').click(function () {
                    $.ajax({
                        method: 'GET',
                        url: url,
                        data: {
                            action: 'purge_all'
                        },
                        success: function (response) {
                            document.getElementById('purge-all-response').innerHTML = '';
                            if (response.status) {
                                var button_elem = jQuery('#test-connection-btn');
                                if (button_elem.hasClass('button-secondary')) {
                                    button_elem.toggleClass('button-secondary');
                                    button_elem.toggleClass('button-primary');
                                }
                            }
                            document.getElementById('purge-all-response').innerHTML = response.message;
                        },
                        dataType: 'json'
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Test connection callback
     */
    function test_fastly_connection_callback()
    {
        $hostname = Purgely_Settings::get_setting('fastly_api_hostname');
        $service_id = Purgely_Settings::get_setting('fastly_service_id');
        $api_key = Purgely_Settings::get_setting('fastly_api_key');

        $result = test_fastly_api_connection($hostname, $service_id, $api_key);
        echo json_encode($result);

        die();
    }

    /**
     * Vcl update callback
     */
    function fastly_vcl_update_ok_callback()
    {
        $purgely_instance = get_purgely_instance();
        $upgrades = new Upgrades($purgely_instance);
        $activate = false;
        $result = array();

        if (isset($_GET['activate']) && $_GET['activate'] === '1') {
            $activate = true;
        }

        // Upgrades for version 1.1.1
        if (version_compare(get_option('fastly_vcl_version'), $purgely_instance->vcl_last_version, '<')) {
            $result = $upgrades->vcl_upgrade_1_1_1($activate);

            if ($result === true && $activate) {
                $message = __('Successfully upgraded!');
                // Update vcl_version
                update_option("fastly_vcl_version", $purgely_instance->vcl_last_version);

            } elseif ($result === true && !$activate) {
                $message = __('VCL upgraded, new version NOT activated!');
            }

            if (is_array($result)) {
                $result = array('status' => false, 'message' => __($result[0]));
            } else {
                $result = array('status' => true, 'message' => $message);
            }
        }
        echo json_encode($result);
        die();
    }

    /**
     * Purge by URL callback
     */
    function fastly_purge_by_url_callback()
    {
        $purge_url = $_GET['purge_url'];
        $url = !empty($purge_url) ? $purge_url : false;
        $result = array('status' => false, 'message' => __('Enter url you want to purge first.'));

        if ($url) {
            $purgely = new Purgely_Purge();
            $response = $purgely->purge(Purgely_Purge::URL, $url);
            if ($response) {
                $result = array('status' => true, 'message' => __('Successfully purged!'));
            } else {
                $result = array('status' => false, 'message' => __('Error while purging, check logs.'));
            }
        }

        echo json_encode($result);
        die();
    }

    /**
     * Test webhooks connection callback
     */
    function test_fastly_webhooks_connection_callback()
    {
        $result = test_web_hook();
        echo json_encode($result);
        die();
    }

    /**
     * Test webhooks connection callback
     */
    function purge_all_callback()
    {

        if (!Purgely_Settings::get_setting('allow_purge_all')) {
            echo json_encode(array('status' => false, 'message' => __('Allow Full Cache Purges first.')));
            die();
        }

        $purgely = new Purgely_Purge();
        $result = $purgely->purge(Purgely_Purge::ALL);

        if ($result === true) {
            $result = array('status' => true, 'message' => __('Successfully purged!'));
        } else {
            $result = array('status' => false, 'message' => __('Purging failed, check logs!'));
        }
        echo json_encode($result);
        die();
    }

    /**
     * Print the description general settings section.
     *
     * @return void
     */
    public function general_settings_callback()
    {
        esc_html_e('This section allows you to configure general cache settings. Note that changes to these settings can cause destabilization to your site if misconfigured. The default setting should be sufficient for most sites.', 'purgely');
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function surrogate_control_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='text' name='fastly-settings-advanced[surrogate_control_ttl]'
               value='<?php echo esc_attr($options['surrogate_control_ttl']); ?>' size="<?php echo self::INPUT_SIZE ?>">
        <p class="description">
            <?php esc_html_e('This setting controls the "surrogate-control" header\'s "max-age" value. It defines the cache duration for all pages on the site.', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function cache_control_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='text' name='fastly-settings-advanced[cache_control_ttl]'
               value='<?php echo esc_attr($options['cache_control_ttl']); ?>' size="<?php echo self::INPUT_SIZE ?>">
        <p class="description">
            <?php esc_html_e('This setting controls the "cache-control" header\'s "max-age" value. It specifies how long end users/browsers should cache pages', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function default_purge_type_render()
    {
        $options = Purgely_Settings::get_settings();

        $purge_types = array(
            'soft' => __('Soft', 'purgely'),
            'instant' => __('Instant', 'purgely'),
        );
        ?>
        <select name="fastly-settings-advanced[default_purge_type]">
            <?php foreach ($purge_types as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>"<?php selected($options['default_purge_type'], $key); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php
            printf(
                esc_html__('The purge type setting controls the manner in which the cache is purged. Instant purging causes the cached object(s) to be purged immediately. Soft purging causes the origin to revalidate the cache and Fastly will serve stale content until revalidation is completed. For more information, please see %s.', 'purgely'),
                sprintf(
                    '<a href="%1$s" target="_blank">%2$s</a>',
                    'https://docs.fastly.com/guides/purging/soft-purges',
                    esc_html__('Fastly\'s documentation for more information on soft purging', 'purgely')
                )
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function allow_purge_all_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='radio'
               name='fastly-settings-advanced[allow_purge_all]' <?php checked(isset($options['allow_purge_all']) && true === $options['allow_purge_all']); ?>
               value='true'>Yes&nbsp;
        <input type='radio'
               name='fastly-settings-advanced[allow_purge_all]' <?php checked(isset($options['allow_purge_all']) && false === $options['allow_purge_all']); ?>
               value='false'>No
        <p class="description">
            <?php esc_html_e('The full cache purging behavior available to WP CLI must be explicitly enabled in order for it to work. Purging the entire cache can cause significant site stability issues and is disabled by default.', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function fastly_log_purges_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='radio'
               name='fastly-settings-advanced[fastly_log_purges]' <?php checked(isset($options['fastly_log_purges']) && true === $options['fastly_log_purges']); ?>
               value='true'>Yes&nbsp;
        <input type='radio'
               name='fastly-settings-advanced[fastly_log_purges]' <?php checked(isset($options['fastly_log_purges']) && false === $options['fastly_log_purges']); ?>
               value='false'>No
        <p class="description">
            <?php esc_html_e('Log all purges in error_log', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function fastly_debug_mode_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='radio'
               name='fastly-settings-advanced[fastly_debug_mode]' <?php checked(isset($options['fastly_debug_mode']) && true === $options['fastly_debug_mode']); ?>
               value='true'>Yes&nbsp;
        <input type='radio'
               name='fastly-settings-advanced[fastly_debug_mode]' <?php checked(isset($options['fastly_debug_mode']) && false === $options['fastly_debug_mode']); ?>
               value='false'>No
        <p class="description">
            <?php esc_html_e('Log all setting update requests in error_log', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Print the description for the stale content settings.
     *
     * @return void
     */
    public function stale_settings_callback()
    {
        esc_html_e('This section allows you to configure how content is handled as it is revalidated. It is important that proper consideration is given to how content is regenerated after it expires from cache. The default settings take a conservative approach by allowing stale content to be served while new content is regenerated in the background.', 'purgely');
    }

    /**
     * Print the description for the webhooks settings.
     *
     * @return void
     */
    public function webhooks_settings_callback()
    {
        esc_html_e('This section allows you to configure webhooks for slack.', 'purgely');
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function enable_stale_while_revalidate_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='radio'
               name='fastly-settings-advanced[enable_stale_while_revalidate]' <?php checked(isset($options['enable_stale_while_revalidate']) && true === $options['enable_stale_while_revalidate']); ?>
               value='true'>Yes&nbsp;
        <input type='radio'
               name='fastly-settings-advanced[enable_stale_while_revalidate]' <?php checked(isset($options['enable_stale_while_revalidate']) && false === $options['enable_stale_while_revalidate']); ?>
               value='false'>No
        <p class="description">
            <?php
            printf(
                esc_html__('Turn the "stale while revalidate" behavior on or off. The stale while revalidate behavior allows stale content to be served while content is regenerated in the background. Please see %s', 'purgely'),
                sprintf(
                    '<a href="%1$s" target="_blank">%2$s</a>',
                    'https://www.fastly.com/blog/stale-while-revalidate',
                    esc_html__('Fastly\'s documentation for more information on stale while revalidate', 'purgely')
                )
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function stale_while_revalidate_ttl_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='text' name='fastly-settings-advanced[stale_while_revalidate_ttl]'
               value='<?php echo esc_attr($options['stale_while_revalidate_ttl']); ?>'
               size="<?php echo self::INPUT_SIZE ?>">
        <p class="description">
            <?php esc_html_e('This setting determines the amount of time that stale content will be served while new content is generated.', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function enable_stale_if_error_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='radio'
               name='fastly-settings-advanced[enable_stale_if_error]' <?php checked(isset($options['enable_stale_if_error']) && true === $options['enable_stale_if_error']); ?>
               value='true'>Yes&nbsp;
        <input type='radio'
               name='fastly-settings-advanced[enable_stale_if_error]' <?php checked(isset($options['enable_stale_if_error']) && false === $options['enable_stale_if_error']); ?>
               value='false'>No
        <p class="description">
            <?php
            printf(
                esc_html__('Turn the "stale if error" behavior on or off. The stale if error behavior allows stale content to be served while the origin is returning an error state. Please see %s', 'purgely'),
                sprintf(
                    '<a href="%1$s" target="_blank">%2$s</a>',
                    'https://www.fastly.com/blog/stale-while-revalidate',
                    esc_html__('Fastly\'s documentation for more information on stale if error', 'purgely')
                )
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function stale_if_error_ttl_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='text' name='fastly-settings-advanced[stale_if_error_ttl]'
               value='<?php echo esc_attr($options['stale_if_error_ttl']); ?>' size="<?php echo self::INPUT_SIZE ?>">
        <p class="description">
            <?php esc_html_e('This setting determines the amount of time that stale content will be served while the origin is returning an error state.', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function purge_by_url_render()
    {
        ?>
        <span class="spinner" id="vcl-popup-spinner"
              style="position: absolute; top:0;left:0;width:100%;height:100%;margin:0; background-color: #fff; background-position:center;"></span>
        <input type='text' id="purge_by_url" name='purge_by_url'
               placeholder="<?php echo __('https://example.com/test'); ?>" value=''
               size="<?php echo self::INPUT_SIZE ?>">
        <input type="button" class="button button-secondary" id="purge-by-url" value="<?php echo __('Purge URL'); ?>">
        <p id="purge-by-url-status"></p>
        <p class="description">
            <?php esc_html_e('Paste the url you want to purge and click Purge URL button', 'purgely'); ?>
        </p>
        <script type='text/javascript'>
            var url = '<?php echo admin_url('admin-ajax.php'); ?>';
            var purge_url_status = document.getElementById('purge-by-url-status');
            var spinner = jQuery('#vcl-popup-spinner');
            var purge_url_value;

            jQuery(document).ready(function ($) {
                jQuery('#purge-by-url').click(function () {
                    spinner.toggleClass('is-active');
                    purge_url_value = document.getElementById('purge_by_url').value;

                    $.ajax({
                        method: 'GET',
                        url: url,
                        data: {
                            action: 'purge_by_url',
                            purge_url: purge_url_value
                        },
                        success: function (response) {
                            console.log(response);
                            spinner.toggleClass('is-active');
                            purge_url_status.innerHTML = response.message;
                        },
                        dataType: 'json'
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function webhooks_url_endpoint_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='text' name='fastly-settings-webhooks[webhooks_url_endpoint]'
               value='<?php echo esc_attr($options['webhooks_url_endpoint']); ?>' size="<?php echo self::INPUT_SIZE ?>">
        <p class="description">
            <?php esc_html_e('Slack URL endpoint.', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function webhooks_username_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='text' name='fastly-settings-webhooks[webhooks_username]'
               value='<?php echo esc_attr($options['webhooks_username']); ?>' size="<?php echo self::INPUT_SIZE ?>">
        <p class="description">
            <?php esc_html_e('Slack username.', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function webhooks_channel_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        #<input type='text' name='fastly-settings-webhooks[webhooks_channel]'
                value='<?php echo esc_attr($options['webhooks_channel']); ?>' size="<?php echo self::INPUT_SIZE ?>">
        <p class="description">
            <?php esc_html_e('Slack channel.', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Render the setting input.
     *
     * @return void
     */
    public function webhooks_activate_render()
    {
        $options = Purgely_Settings::get_settings();
        ?>
        <input type='radio'
               name='fastly-settings-webhooks[webhooks_activate]' <?php checked(isset($options['webhooks_activate']) && true === $options['webhooks_activate']); ?>
               value='true'>Yes&nbsp;
        <input type='radio'
               name='fastly-settings-webhooks[webhooks_activate]' <?php checked(isset($options['webhooks_activate']) && false === $options['webhooks_activate']); ?>
               value='false'>No
        <p class="description">
            <?php esc_html_e('Log purging messages in your slack channel.', 'purgely'); ?>
        </p>
        <?php
    }

    /**
     * Print the general settings page.
     *
     * @return void
     */
    public function options_page()
    {
        ?>
        <div class="wrap">
            <form action='options.php' method='post'>
                <div id="fastly-admin" class="wrap">
                    <h1><img alt="fastly" src="<?php echo FASTLY_PLUGIN_URL . 'static/logo_white.gif'; ?>"><br><span
                                style="font-size: x-small;">version: <?php echo FASTLY_VERSION; ?></span></h1>
                </div>
                <?php
                settings_fields('fastly-settings-general');
                do_settings_sections('fastly-settings-general');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Print the advanced settings page.
     *
     * @return void
     */
    public function options_page_advanced()
    {
        ?>
        <div class="wrap">
            <form action='options.php' method='post'>
                <div id="fastly-admin" class="wrap">
                    <h1><img alt="fastly" src="<?php echo FASTLY_PLUGIN_URL . 'static/logo_white.gif'; ?>"><br><span
                                style="font-size: x-small;">version: <?php echo FASTLY_VERSION; ?></span></h1>
                </div>
                <?php
                settings_fields('fastly-settings-advanced');
                do_settings_sections('fastly-settings-advanced');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Print the webhooks settings page.
     *
     * @return void
     */
    public function options_page_webhooks()
    {
        ?>
        <div class="wrap">
            <form action='options.php' method='post'>
                <div id="fastly-admin" class="wrap">
                    <h1><img alt="fastly" src="<?php echo FASTLY_PLUGIN_URL . 'static/logo_white.gif'; ?>"><br><span
                                style="font-size: x-small;">version: <?php echo FASTLY_VERSION; ?></span></h1>
                </div>
                <?php
                settings_fields('fastly-settings-webhooks');
                do_settings_sections('fastly-settings-webhooks');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize all of the setting.
     *
     * @param  array $settings The unsanitized settings.
     * @return array           The sanitized settings.
     */
    public function sanitize_settings($settings)
    {
        $clean_settings = array();
        $registered_settings = Purgely_Settings::get_registered_settings();

        foreach ($settings as $key => $value) {
            if (isset($registered_settings[$key])) {
                $clean_settings[$key] = call_user_func($registered_settings[$key]['sanitize_callback'], $value);
            }
        }

        return $settings;
    }
}

/**
 * Instantiate or return the one Purgely_Settings_Page instance.
 *
 * @return Purgely_Settings_Page
 */
function get_purgely_settings_page_instance()
{
    return Purgely_Settings_Page::instance();
}

get_purgely_settings_page_instance();
