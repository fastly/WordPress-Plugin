<?

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
  function FastlyAPI($api_key='', $host='https://api.fastly.com', $port=null) {
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
      $this->post('/purge/' . $uri);
    }
  }

  /**
   * Sends a purge all request to the Fastly API.
   */
  function purgeAll($service_id) {
    return $this->post('/service/' . $service_id . '/purge_all');
  }

  /** 
   * Sends a post request to the Fastly API.
   * @param $path Path to call on the remote host.
   * @param $data Data for the body for the post request.
   * @return The response from the server or -1 if an error occurred.
   */
  function post($path, $data=array()) {

    $headers = array("Host: ".$this->host_name, "Accept: */*"); 
    if ($this->api_key) {
      $headers[] = "X-Fastly-Key: " . $this->api_key;
    }

    $url = $this->host;
    if (!is_null($this->port) || $this->port == "") {
      $url .= ":" . $this->port; 
    } 
    $url .= $path;
    $ch  = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return !!$response;
    
  }
} 

// "WHITE LIGHT, doo-doo doo-doo doo, WHITE LIGHT" -- Gorillaz
?>