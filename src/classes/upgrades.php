<?php

/**
 * Class Upgrades
 *
 * Contains new schema upgrades that run automatically on version update, and some manual upgrade functions
 */
class Upgrades
{

    protected $_main_instance;

    /**
     * Upgrades constructor.
     *
     * Sets up main settings object for paths and version
     *
     * @param $object
     */
    public function __construct($object)
    {
        $this->_main_instance = $object;
    }


    /**
     * Check version and run new upgrades if there are any
     */
    public function check_and_run_upgrades()
    {
        // Upgrade to 1.1.1
        if (version_compare($this->_main_instance->current_version, '1.1.1', '<')) {
            $this->upgrade1_1_1();
        }
    }

    /**
     * Upgrades to 1.1.1 version
     *
     * @return void
     */
    protected function upgrade1_1_1()
    {
        // Convert old fastly credentials to new storing type
        $data_general = array();
        $data_advanced = array();
        $data_general['fastly_api_hostname'] = get_option('fastly_api_hostname', false);
        $data_general['fastly_api_key'] = get_option('fastly_api_key', false);
        $data_general['fastly_service_id'] = get_option('fastly_service_id', false);
        $data_advanced['fastly_log_purges'] = get_option('fastly_log_purges', false);

        foreach ($data_general as $k => $single) {
            if ($single === false || empty($single)) {
                unset($data_general[$k]);
            }
        }

        foreach ($data_advanced as $k => $single) {
            if ($single === false || empty($single)) {
                unset($data_advanced[$k]);
            }
        }

        // Update data
        update_option('fastly-settings-general', $data_general);
        update_option('fastly-settings-advanced', $data_advanced);

        // Update version
        update_option("fastly-schema-version", '1.1.1');
    }

    /**
     * Manual update of vcl, conditions and settings to 1.1.1 version
     * @param bool
     * @return bool|array
     */
    public function vcl_upgrade_1_1_1($activate)
    {
        // Update VCL
        $vcl_dir = $this->_main_instance->vcl_dir;
        $data = array(
            'vcl' => array(
                array(
                    'vcl_dir' => $vcl_dir,
                    'type' => 'recv'
                ),
                array(
                    'vcl_dir' => $vcl_dir,
                    'type' => 'deliver',
                ),
                array(
                    'vcl_dir' => $vcl_dir,
                    'type' => 'error',
                ),
                array(
                    'vcl_dir' => $vcl_dir,
                    'type' => 'fetch',
                )
            ),
            'condition' => array(
                array(
                    'name' => 'wordpressplugin_request1',
                    'statement' => 'req.http.x-pass',
                    'type' => 'REQUEST',
                    'priority' => 90
                )
            ),
            'setting' => array(
                array(
                    'name' => 'wordpressplugin_setting1',
                    'action' => 'pass',
                    'request_condition' => 'wordpressplugin_request1'
                )
            )
        );

        $errors = array();

        $vcl = new Vcl_Handler($data);
        if (!$vcl->execute($activate)) {
            //Log if enabled
            if (Purgely_Settings::get_setting('fastly_debug_mode')) {
                foreach ($vcl->get_errors() as $error) {
                    error_log($error);
                }
            }

            $errors = array_merge($errors, $vcl->get_errors());
        }

        if (!empty($errors)) {
            return $errors;
        }

        return true;
    }
}
