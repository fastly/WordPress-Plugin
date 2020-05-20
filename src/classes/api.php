<?php

class Fastly_Api
{
    private static $instance;
    
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
            'version',
        ]);
        
        $this->get_active_version();
    }

    /**
     * @return Fastly_Api
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_active_version()
    {
        if (is_null($this->active_version)) {
            $response = Requests::get($this->base_url, $this->headers_get);
            foreach (json_decode($response->body) as $version) {
                if ($version->active) {
                    $this->active_version = $version;
                    break;
                }
            }
        }
        return $this->active_version;
    }

    public function get_all_snippets()
    {
        $url = $this->base_url . "/{$this->active_version->number}/snippet";
        return json_decode(Requests::get($url, $this->headers_get)->body);
    }
}

/**
 * @return Fastly_Api
 */
function fastly_api()
{
    return Fastly_Api::getInstance();
}
