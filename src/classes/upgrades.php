<?php

/**
 * Class Upgrades
 *
 * Contains new schema upgrades that run automatically on version update, and some manual upgrade functions
 */
class Upgrades {

    protected $_main_instance;

    /**
     * Upgrades constructor.
     *
     * Sets up main settings object for paths and version
     *
     * @param $object
     */
    public function __construct( $object )
    {
        $this->_main_instance = $object;
    }


    /**
     * Check version and run new upgrades if there are any
     */
    public function check_and_run_upgrades() {
        // Upgrade to 1.1.1
        if(version_compare($this->_main_instance->current_version, '1.1.1', '<')) {
            $this->upgrade1_1_1();
        }
    }

    /**
     * Upgrades to 1.1.1 version
     *
     * @since 1.1.1.
     *
     * @return void
     */
    protected function upgrade1_1_1()
    {
        // Convert old fastly credentials to new storing type
        $data = array();
        $data['fastly_hostname'] = get_option( 'fastly_hostname', false );
        $data['fastly_api_hostname'] = get_option( 'fastly_api_hostname', false );
        $data['fastly_api_port'] = get_option( 'fastly_api_port', false );
        $data['fastly_page'] = get_option( 'fastly_page', false ); // TODO needed? for welcome page with hostname and port
        $data['fastly_log_purges'] = get_option( 'fastly_log_purges', false );
        $data['fastly_api_soft'] = get_option( 'fastly_api_soft', false );
        $data['fastly_api_key'] = get_option( 'fastly_api_key', false );
        $data['fastly_service_id'] = get_option( 'fastly_service_id', false );

        foreach($data as $k => $single){
            if($single === false || empty($single)) {
                unset($data[$k]);
            }
        }

        // Update data
        update_option('fastly-settings', $data);

        // Update version
        update_option( "fastly-schema-version", '1.1.1' );
    }

    /**
     * Manual update of vcl, conditions and settings to 1.1.1 version
     * @return bool|array
     */
    public function vcl_upgrade_1_1_1() {
        // Update VCL
        $vcl_dir = $this->_main_instance->vcl_dir;
        $data = array(
            'vcl' => array(
                'vcl_dir' => $vcl_dir,
                'type' => 'recv',

            ),
            'condition' => array(
                'name' => 'wordpress_request1',
                'statement' => 'req.http.x-pass',
                'type' => 'REQUEST',
                'priority' => 90
            ),
            'setting' => array(
                'name' => 'wordpress_setting1',
                'action' => 'pass',
                'request_condition' => 'wordpress_request1'
            )
        );

        $vcl = new Vcl_Handler( $data );
        if(!$vcl->execute()) {
            //Log if enabled
            if(Purgely_Settings::get_setting( 'fastly_debug_mode' )) {
                foreach($vcl->get_errors() as $error) {
                    error_log($error);
                }
            }
            return $vcl->get_errors();
        }
        return true;
    }
}