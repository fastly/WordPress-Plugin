<?

/**
 * Fastly API for PHP.
 * @package Fastly
 * @version 0.2b
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
  function FastlyAPI($api_key='', $host='api.fastly.com', $port='80') {
    $this->api_key = $api_key;
    $this->host = $host;
    $this->port = $port;
  }
  
  /**
   * Sends a request to the fastly API server.
   * @param $request HTTP request content to send.
   */
  function send($request) {
    $fp = fsockopen($this->host, $this->port, $errno, $errstr, 10);
		if (!$fp) {
		  return -1;
		}
		else {
			fwrite($fp, $request);
			$response = '';
	    while (!feof($fp)) {
	        $response .= fgets($fp, 128);
	    }
	    fclose($fp);
		}
		
		$lines = explode("\n", $response);
		
		// TODO Handle this better...
    preg_match("#HTTP/1.1 (\\d+)#", array_shift($lines), $matches);
    $code = $matches[1];    
    $body = array_pop($lines);
    
    return array(
      'code' => $code,
      'body' => $body
    );
  }

  /**
   * Sends a purge request to the Fastly API.
   * @param $uri URI to purge.
   */
  function purge($uri) {
    // TODO How can we handle this more elegantly?
    if (!$this->api_key)
      return;
    
    $server = $_SERVER['SERVER_NAME'];
    $key = $this->api_key;
    
    $request = "PURGE " . $uri . " HTTP/1.1\r\n"
      . "User-Agent: Fastly API Adapter\r\n"
			. "Host: " . $server . "\r\n"
			. "Accept: */*\r\n"
			. "Proxy-Connection: Keep-Alive\r\n"
			. "X-Fastly-Key: " . $key . "\r\n"
			. "\r\n";
    return $this->send($request);
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
    // Construct post body
    $body = array();
    foreach ($data as $k => $v) {
      $body[] = $k . '=' . urlencode($v);
    }
    $body = implode('&', $body);
    $content_length = strlen($body);
    
    // Construct post header
    $header = array(
      "POST " . $path . " HTTP/1.1",
      "User-Agent: FastlyAPI Adapter",
      "Accept: */*",
      "Connection: close",
      "Content-Length: " . $content_length,
    );

    if ($this->api_key) {
      $header[] = "X-Fastly-Key: " . $this->api_key;
    }
    
    // Construct and send the request
    $request = implode("\r\n", $header) . "\r\n\r\n" . $body . "\r\n"; 
		return $this->send($request);
  }
} 

// "WHITE LIGHT, doo-doo doo-doo doo, WHITE LIGHT" -- Gorillaz
?>