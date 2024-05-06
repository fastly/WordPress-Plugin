<?php

class Fastly_Api
{
    private static $instance;

    protected $error_message = '';

    protected $headers_get;
    
    protected $headers_post;
    
    protected $base_url;

    protected $active_version;

    protected function __clone(){}

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }
    
    protected function __construct()
    {
        $this->headers_get = [
            'Fastly-Key' => purgely_get_option('fastly_api_key'),
            'Accept' => 'application/json'
        ];
        $this->headers_post = [
            'Fastly-Key' => purgely_get_option('fastly_api_key'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $this->base_url = implode('', [
            trailingslashit(purgely_get_option('fastly_api_hostname')),
            trailingslashit('service'),
            trailingslashit(purgely_get_option('fastly_service_id')),
        ]);
    }

    /**
     * @return Fastly_Api
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            $instance = new self();
            $instance->validate();
            self::$instance = $instance;
        }
        return self::$instance;
    }

    protected function validate()
    {
        try {
            $this->get_active_version();
        } catch (\Exception $e) {
            wp_die("<div class=\"error notice\">
                <p>Unable to connect to Fastly. Please go to General settings and review your settings.</p>
            </div>");
        }
    }

    public function get_active_version()
    {
        if (is_null($this->active_version)) {
            $response = Requests::get($this->base_url.'version', $this->headers_get);
            foreach (json_decode($response->body) as $version) {
                if ($version->active) {
                    $this->active_version = $version;
                    break;
                }
            }
        }
        return $this->active_version;
    }

    public function clone_active_version()
    {
        return $this->clone_version($this->get_active_version()->number);
    }

    public function clone_version($version)
    {
        $url = $this->base_url . "version/{$version}/clone";
        return json_decode(Requests::put($url, $this->headers_post)->body);
    }


    public function validate_version($version)
    {
        $url = $this->base_url . "version/{$version}/validate";
        $result = json_decode(Requests::get($url, $this->headers_get)->body);
        if ($result->status === 'error') {
            $this->show_error($result->msg);
            return false;
        }
        return true;
    }

    public function activate_version($version)
    {
        $url = $this->base_url . "version/{$version}/activate";
        $this->active_version = json_decode(Requests::put($url, $this->headers_post)->body);
        return $this->active_version;
    }

    public function get_all_snippets($version = null)
    {
        $v = 0;
        if(!$version){
            $activeVersion   = $this->get_active_version();
            if($activeVersion){
                $v = $this->get_active_version()->number;
            }
        }else{
            $v = $version;
        }

        if($v){
            $url = $this->base_url . "version/{$v}/snippet";
            $response = Requests::get($url, $this->headers_get)->body;
        }else{
            $response = '{}';
        }
        return json_decode($response);
    }

    public function get_snippet($name, $version = null)
    {
        $v = is_null($version) ? $this->get_active_version()->number : $version;
        $url = $this->base_url . "version/{$v}/snippet/{$name}";
        return json_decode(Requests::get($url, $this->headers_get)->body);
    }

    public function snippet_exists($name, $version = null)
    {
        $result = $this->get_snippet($name, $version);
        return (bool) $result->id;
    }

    public function upload_snippet($version, $snippet)
    {
        $url = $this->base_url . "version/{$version}/snippet";
        if (!$this->snippet_exists($snippet['name'], $version)) {
            $verb = Requests::POST;
        } else {
            $verb = Requests::PUT;
            if (!isset($snippet['dynamic']) || $snippet['dynamic'] != 1) {
                $url .= '/'.$snippet['name'];
                unset($snippet['name'], $snippet['type'], $snippet['dynamic'], $snippet['priority']);
            } else {
                $snippet['name'] = $this->get_snippet($snippet['name'], $version)->id;
                $url = $this->base_url . "snippet/{$snippet['name']}";
            }
        }

        $result = json_decode(Requests::request($url, $this->headers_post, $snippet, $verb)->body);
        if (!isset($result->id) || !$result->id) {
            $this->show_error($result->detail);
            return false;
        }
        return true;
    }

    public function delete_snippet($version, $name)
    {
        $url = $this->base_url . "version/{$version}/snippet/{$name}";
        $result =  json_decode(Requests::delete($url, $this->headers_get)->body);
        if ($result->status !== 'ok') {
            $this->show_error($result->detail);
            return false;
        }
        return true;
    }

    public function get_all_acls()
    {
        $activeVersion = $this->get_active_version();
        if($activeVersion){
            $url = $this->base_url . "version/{$activeVersion->number}/acl";
            $data = Requests::get($url, $this->headers_get)->body;
        }else{
            $data = '{}';
        }
        return json_decode($data);
    }

    public function get_all_dictionaries()
    {
        $url = $this->base_url . "version/{$this->get_active_version()->number}/dictionary";
        return json_decode(Requests::get($url, $this->headers_get)->body);
    }

    public function show_error($message)
    {
        $this->error_message = $message;
        add_action('admin_notices', array($this, 'error_notice'));
    }

    public function error_notice()
    {
        ?>
        <div class="error notice">
            <p><?php esc_html( $this->error_message ); ?></p>
        </div>
        <?php
    }
}

/**
 * @return Fastly_Api
 */
function fastly_api()
{
    return Fastly_Api::getInstance();
}
