<?php
/**
 * Class to control the VCL handling.
 */
class Vcl_Handler {

    /** VCL data to be processed */
    protected $_vcl_data;

    /** Condition data to be processed */
    protected $_condition_data;

    /** Setting data to be processed */
    protected $_setting_data;


    /** Fastly API endpoint */
    protected $_hostname;

    /** Fastly API Key */
    protected $_api_key;

    /** Fastly Service ID */
    protected $_service_id;

    /** Fastly API URL version base */
    protected $_version_base_url;

    /** Headers used for GET requests */
    protected $_headers_get;

    /** Headers used for POST, PUT requests */
    protected $_headers_post;


    /** Last active version data */
    protected $_last_version_data;

    /** Last cloned version number */
    protected $_last_cloned_version;


    /** Errors */
    protected $_errors = array();

    /**
     * Sets data to be processed, sets Credentials
     * Vcl_Handler constructor.
     */
    public function __construct( $data) {
        $this->_vcl_data = !empty($data['vcl']) ? $data['vcl'] : false;
        $this->_condition_data = !empty($data['condition']) ? $data['condition'] : false;
        $this->_setting_data = !empty($data['setting']) ? $data['setting'] : false;

        $this->_hostname = purgely_get_option('fastly_api_hostname');
        $this->_service_id = purgely_get_option('fastly_service_id');
        $this->_api_key = purgely_get_option('fastly_api_key');

        $connection = test_fastly_api_connection($this->_hostname, $this->_service_id, $this->_api_key);
        if(!$connection['status']) {
            $this->add_error(__($connection['message']));
            return;
        }

        // Set credentials based data (API url, headers, last version)
        $this->_version_base_url = $this->_hostname . '/service/' . $this->_service_id . '/version';
        $this->_headers_get = array(
            'Fastly-Key' => $this->_api_key,
            'Accept' => 'application/json'
        );
        $this->_headers_post = array(
            'Fastly-Key' => $this->_api_key,
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded'
        );

        $this->_last_version_data = $this->get_last_version();

        return;
    }

    /**
     * Main execute function, takes values inserted into constructor, builds requests and sends them via Fastly API
     * @return bool
     */
    public function execute() {
        // Check if there are connection errors from construct
        if(!empty($this->get_errors())) {
            return false;
        }

        // Check if last version is fetched
        if($this->_last_version_data === false) {
            $this->add_error(__('Last version does not exist'));
            return false;
        }

        // Check if any of the data is set
        if(!($this->_vcl_data || $this->_condition_data || $this->_setting_data)) {
            $this->add_error(__('No update data set, please specify, vcl, condition or setting data'));
            return false;
        }

        try {
            if(false === $this->clone_last_active_version()) {
                $this->add_error(__('Unable to clone last version'));
                return false;
            }

            $requests = array();


            if(!empty($this->_vcl_data)) {
                $requests[] = $this->prepare_vcl();
            }

            if(!empty($this->_condition_data)) {
                $requests[] = $this->prepare_condition();
            }

            if(!empty($this->_setting_data)) {
                $requests[] = $this->prepare_setting();
            }

            if(!$this->validate_version()) {
                $this->add_error(__('Version not validated'));
                return false;
            }

            $requests[] = $this->prepare_activate_version();

            // Set Request Headers
            foreach($requests as $key => $request) {
                if(in_array($request['type'], array(Requests::POST, Requests::PUT))) {
                    $requests[$key]['headers'] = $this->_headers_post;
                } else {
                    $requests[$key]['headers'] = $this->_headers_get;
                }
            }

            // Send Requests
            $responses = Requests::request_multiple($requests);

            $pass = true;
            foreach($responses as $response) {
                if(!$response->success) {
                    $pass = false;
                    $this->add_error(__('Some of the API requests failed, enable debugging and check logs for more information.'));
                    if(Purgely_Settings::get_setting( 'fastly_debug_mode' )) {
                        error_log(json_decode($response->body));
                    }
                }
            }
        } catch (Exception $e) {
            $this->add_error(__('Some of the API requests failed, enable debugging and check logs for more information.'));
            if(Purgely_Settings::get_setting( 'fastly_debug_mode' )) {
                error_log($e->getMessage());
            }
            return false;
        }

        return $pass;
    }

    /**
     * Prepares VCL request
     * @return array|bool
     */
    public function prepare_vcl() {
        // Prepare VCL data content
        if(!empty($this->_vcl_data['type'])) {
            $this->_vcl_data['name'] = 'wordpress_' . $this->_vcl_data['type'];
            $this->_vcl_data['dynamic'] = 0;
            $this->_vcl_data['priority'] = 50;
            if(file_exists($this->_vcl_data['vcl_dir'] . '/' . $this->_vcl_data['type'] . '.vcl')) {
                $this->_vcl_data['content'] = file_get_contents($this->_vcl_data['vcl_dir'] . '/' . $this->_vcl_data['type'] . '.vcl');
                unset($this->_vcl_data['vcl_dir']);
            } else {
                $this->add_error(__('VCL file does not exist.'));
                return false;
            }
        } else {
            $this->add_error(__('VCL type not set.'));
            return false;
        }

        if($this->check_if_vcl_exists()) {
            return $this->prepare_update_vcl();
        } else {
            return $this->prepare_insert_vcl();
        }
    }

    /**
     * Checks if VCL exists
     * @return bool
     */
    public function check_if_vcl_exists() {
        if(empty($this->_last_version_data)) {
            return false;
        }

        $url = $this->_version_base_url . '/' . $this->_last_cloned_version . '/snippet/' . $this->_vcl_data['name'];
        $response = Requests::get($url, $this->_headers_get);

        return $response->success;
    }

    /**
     * Prepares request for updating existing VCL
     * @return array
     */
    public function prepare_update_vcl() {
        $url = $this->_version_base_url . '/' . $this->_last_cloned_version . '/snippet/' . $this->_vcl_data['name'];

        $request = array(
            'url' => $url,
            'data' => $this->_vcl_data,
            'type' => Requests::PUT
        );

        return $request;
    }

    /**
     * Prepare request for inserting new VCL
     * @return array
     */
    public function prepare_insert_vcl() {
        $url = $this->_version_base_url . '/' . $this->_last_cloned_version . '/snippet';

        $request = array(
            'url' => $url,
            'data' => $this->_vcl_data,
            'type' => Requests::POST
        );

        return $request;
    }

    /**
     * Fetch last service version
     * @return bool|int
     */
    public function get_last_version() {
        $url = $this->_version_base_url;
        $response = Requests::get($url, $this->_headers_get);
        $response_data = json_decode($response->body);

        foreach($response_data as $key => $version_data) {
            if($version_data->active) {
                return $version_data;
            }
        }
        return false;
    }


    /**
     * Creates and returns cloned version number
     * @return bool
     */
    public function clone_last_active_version() {
        if(empty($this->_last_version_data)) {
            return false;
        }

        $version_number = $this->_last_version_data->number;
        $url = $this->_version_base_url . '/' . $version_number . '/clone';
        $response = Requests::put($url, $this->_headers_post);

        $response_data = json_decode($response->body);
        $cloned_version_number = isset($response_data->number) ? $response_data->number : false;
        $this->_last_cloned_version = $cloned_version_number;

        return $cloned_version_number;
    }

    /**
     * Prepares condition for insertion
     * @return array|bool
     */
    public function prepare_condition() {
        // Prepare condition content
        foreach(array('name', 'statement', 'type', 'priority') as $item) {
            if(empty($this->_condition_data[$item])) {
                $this->add_error(__('Condition data not properly set.'));
                return false;
            }
        }

        if($this->get_condition()) {
            return $this->prepare_update_condition();
        } else {
            return $this->prepare_insert_condition();
        }
    }

    /**
     * Fetches condition by condition name
     * @return bool
     */
    public function get_condition() {
        $url = $this->_version_base_url . '/' . $this->_last_cloned_version . '/condition/' . $this->_condition_data['name'];
        $response = Requests::get($url, $this->_headers_get);
        return $response->success;
    }

    /**
     * Prepare condition for insert
     * @return array
     */
    public function prepare_insert_condition() {
        $url = $this->_version_base_url . '/' . $this->_last_cloned_version . '/condition';

        $request = array(
            'url' => $url,
            'data' => $this->_condition_data,
            'type' => Requests::POST
        );

        return $request;
    }

    /**
     * Prepare condition for update
     * @return array
     */
    public function prepare_update_condition() {
        $url = $this->_version_base_url . '/' . $this->_last_cloned_version . '/condition/' . $this->_condition_data['name'];

        $request = array(
            'url' => $url,
            'data' => $this->_condition_data,
            'type' => Requests::PUT
        );

        return $request;
    }

    /**
     * Prepares setting for insertion
     * @return array|bool
     */
    public function prepare_setting() {
        // Prepare setting content
        foreach(array('name', 'action', 'request_condition') as $item) {
            if(empty($this->_setting_data[$item])) {
                $this->add_error(__('Setting data not properly set.'));
                return false;
            }
        }

        if($this->get_setting()) {
            return $this->prepare_update_setting();
        } else {
            return $this->prepare_insert_setting();
        }
    }

    /**
     * Fetches setting by condition name
     * @return bool
     */
    public function get_setting() {
        $url = $this->_version_base_url . '/' . $this->_last_cloned_version . '/request_settings/' . $this->_setting_data['name'];
        $response = Requests::get($url, $this->_headers_get);
        return $response->success;
    }

    /**
     * Prepares Insert setting data
     * @return array
     */
    public function prepare_insert_setting() {
        $url = $this->_version_base_url . '/' . $this->_last_cloned_version . '/request_settings';

        $request = array(
            'url' => $url,
            'data' => $this->_setting_data,
            'type' => Requests::POST
        );

        return $request;
    }

    /**
     * Prepares update setting data
     * @return array
     */
    public function prepare_update_setting() {
        $url = $this->_version_base_url . '/' . $this->_last_cloned_version . '/request_settings/' . $this->_setting_data['name'];

        $request = array(
            'url' => $url,
            'data' => $this->_setting_data,
            'type' => Requests::PUT
        );

        return $request;
    }

    /**
     * Validates last cloned version
     * @return bool
     */
    public function validate_version() {
        $url = $this->_version_base_url . '/' . $this->_last_cloned_version . '/validate';
        $response = Requests::get($url, $this->_headers_get);
        return $response->success;
    }

    /**
     * Activates last cloned version
     * @return array
     */
    public function prepare_activate_version() {
        $url = $this->_version_base_url . '/' . $this->_last_cloned_version . '/activate';

        $request = array(
            'url' => $url,
            'type' => Requests::PUT
        );

        return $request;
    }

    /**
     * Adds new error to error array
     * @param $message
     */
    public function add_error($message) {
        $this->_errors[] = $message;
    }

    /**
     * Fetches logged errors
     */
    public function get_errors() {
        return $this->_errors;
    }
}