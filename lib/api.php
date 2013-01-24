<?php

/**
 * Fastly API for PHP.
 * @package Fastly
 * @author Ryan Sandor Richards
 * @copyright 2011 Fastly.com, All Rights Reserved
 */
class FastlyAPI {  
  /**
   * Default constructor.
   * @param $api_key Fastly API key.
   * @param $host Hostname of the API server.
   * @param $port Port for the API server.
   */
  function FastlyAPI($api_key='', $host='https://app.fastly.com', $port=null) {
    $this->api_key   = $api_key;
    $this->host      = $host;
    $this->port      = $port;
    $this->host_name = preg_replace('/^(ssl|https?):\/\//', '', $host);
  }
  
  /**
   * Sends a purge request to the Fastly API.
   * @param $uri URI to purge.
   */
  function purge($uris) {
    // TODO How can we handle this more elegantly?
    if (!$this->api_key)
      return;
    
    if (!is_array($uris)) {
      $uris = array($uris);
    }
    // TODO - change this to a curl_multi_exec at some point
    foreach ($uris as $uri) {
      #$uri = preg_replace("/^https?:\/\//", '', $uri); 
      error_log("Purging " . $uri);
      $this->post($uri);
    }
  }

  /**
   * Sends a purge all request to the Fastly API.
   */
  function purgeAll($service_id) {
    $url = $this->host;
    if (!is_null($this->port) && is_numeric($this->port)) {
      $url .= ":" . $this->port; 
    } 
    $url .= '/service/' . $service_id . '/purge_all';
      
    return $this->post($url, true);
  }

  /** 
   * Sends a post request to the Fastly API.
   * @param $path Path to call on the remote host.
   * @param $data Data for the body for the post request.
   * @return The response from the server or -1 if an error occurred.
   */
  function post($url, $do_post = true) {

    if ($this->api_key) {
      $headers[] = "X-Fastly-Key: " . $this->api_key;
    }

    $ch  = curl_init();
    # Temporary workaround to fix purging. Use POST instead of PURGE method.
    # Strip off protocol
    $url = "https://app.fastly.com/purge/" . preg_replace("/^http(s?):\/\//",'', $url);
    error_log("Purging using POST for " . $url);
    curl_setopt($ch, CURLOPT_URL, $url );
    if ($do_post) {
      curl_setopt($ch, CURLOPT_POST, 1);
    } else {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PURGE");      
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return !!$response;
    
  }
} 

// "WHITE LIGHT, doo-doo doo-doo doo, WHITE LIGHT" -- Gorillaz
?>
