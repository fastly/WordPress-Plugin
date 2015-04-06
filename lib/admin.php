<?php

/**
 * Fastly Admin Panel.
 * @package Fastly
 * @author Ryan Sandor Richards
 * @copyright 2011 Fastly.com, All Rights Reserved
 */
class FastlyAdmin {
  /**
   * Initializes the fastly admin panel.
   */
  function FastlyAdmin() {
    // Setup admin interface
    add_action('admin_menu', array(&$this, 'adminPanel'));
    add_action('admin_init', array(&$this, 'adminInit'));
    
    // Register scripts and styles
    add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    
    // Ajax Actions
    add_action('wp_ajax_set_page', array(&$this, 'ajaxSetPage'));
    add_action('wp_ajax_sign_up', array(&$this, 'ajaxSignUp'));
    
    #update_option('fastly_page', 'welcome');
    update_option('fastly_page', 'configure');
    
    /* Point to CI API Server
    update_option('fastly_api_hostname', '184.106.66.217');
    update_option('fastly_api_port', 5500);
    //*/
    
    /* Point to Dev API Server
    update_option('fastly_api_hostname', '10.235.5.18');
    update_option('fastly_api_port', 80);
    //*/
    
    // Grab an instance of the API adapter
    $this->api = new FastlyAPI(
      get_option('fastly_api_key'),
      get_option('fastly_api_hostname'),
      get_option('fastly_api_port'),
      get_option('fastly_api_soft')
    );
  }

  /**
   * Register scripts and styles needed for the admin option page.
   *
   * @return void
   */
  function admin_enqueue_scripts( $hook_suffix ) {
    if ('settings_page_fastly-admin-panel' !== $hook_suffix) {
      return;
    }

    // Add scripts and styles
    wp_register_style('fastly.css', $this->resource('fastly.css'));
    wp_enqueue_style('fastly.css');

    wp_register_script('fastly.js', $this->resource('fastly.js'));
    // Expose a WP CSRF nonce to the fastly.js script
    $nonce = wp_create_nonce('fastly-admin');
    wp_localize_script('fastly.js', 'fastlyNonce', $nonce);
    wp_enqueue_script('fastly.js');
  }
  
  /**
   * @param $p Page name to test.
   * @return True if the given page is valid, false otherwise.
   */
  function validPage($p) {
    return in_array($p, array_keys($this->templates));
  }
  
  /**
   * Set the user's default page.
   */
  function ajaxSetPage() {
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'fastly-admin')) {
      wp_die('Bad CSRF Nonce.');
    }

    if (isset($_REQUEST['page']) && $this->validPage($_REQUEST['page'])) {
      update_option('fastly_page', esc_sql($_REQUEST['page']));
      die(1);
    }
    die();
  }
  
  /**
   * Make a sign up request to teh fastly API.
   */
  function ajaxSignUp() {
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'fastly-admin')) {
      wp_die('Bad CSRF Nonce.');
    }

    unset($_REQUEST['action']);
    $_REQUEST['wizard'] = 'wordpress';
    
    $response = $this->api->post('/signup', $_REQUEST);
        
    if ($response === -1)
      wp_die('Could not connect to Fastly API.');
      
    $code = $response['code'];
    $body = $this->decode($response['body']);
    $defaultError = "An error occurred while connecting to the fastly API, please try your request again.";
    
    switch ($code) {
      case 200:
        update_option('fastly_api_key', esc_sql($body['api_key']));
        update_option('fastly_service_id', esc_sql($body['service_id']));
        update_option('fastly_page', 'configure');
        
        // Update internal host name
        $parts = explode('/', $_REQUEST['website_address']);
        if (count($parts) >= 3) {
          update_option('fastly_hostname', esc_sql($parts[2]));
        }
        
        $response = array('status' => 'success');
        break;
      case 400:
        if ($body['class'] == "Customer")
          $msg = "A customer with that name already exists.";
        else if ($body['class'] == "User") {
          if (!empty($body['errors']['login']))
            $msg = "Invalid e-mail address.";
          else
            $msg = "A user with the given e-mail address already exists.";
        }
        else
          $msg = $defaultError;
        $response = array('status' => 'error', 'msg' => $msg);
        break;
      case 404:
        $response = array('status' => 'error', 'msg' => $defaultError);
        break;
    }
    
    die($this->encode($response));
  }
  
  
  /**
   * Called when admin is initialized by wordpress.
   */
  function adminInit() {
    // Config form group
    register_setting('fastly-group', 'fastly_hostname');
    register_setting('fastly-group', 'fastly_api_hostname');
    register_setting('fastly-group', 'fastly_api_port');
    register_setting('fastly-group', 'fastly_api_key');
    register_setting('fastly-group', 'fastly_service_id');
    register_setting('fastly-group', 'fastly_log_purges');
    register_setting('fastly-group', 'fastly_api_soft');
    
    // Page change group
    register_setting('fastly-page-group', 'fastly_page');
    
    // Generate front-end templates
    $this->templates = array(
      'welcome' => $this->welcome(),
      'configure' => $this->configure(),
    );

    // Get the current page
    $this->page = get_option('fastly_page');
    if (!$this->validPage($this->page)) {
      $this->page = 'welcome';
      update_option('fastly_page', 'welcome');
    }
  }
  
  /**
   * Adds the admin panel for the plugin.
   */
  function adminPanel() {
    add_options_page('Configure Fastly', 'Fastly', 'manage_options', 'fastly-admin-panel', array(&$this, 'render'));
  }
  
  /**
   * Fetches various static resources for the fastly plugin (js, css, images, etc.)
   * @param $name Name of the resource to fetch.
   * @return The URL to the resource in the plugin directory.
   */
  function resource($name='') {
    return FASTLY_PLUGIN_URL . 'static/' . $name;
  }
  
  /**
   * Backwards compatible JSON encoder.
   * @param $obj Object to encode.
   * @return The JSON encoding of the given object.
   */
  function encode($obj) {
    if (function_exists('json_encode')) {
      return json_encode($obj);
    }
    else {
      $json = new Services_JSON();
      return $json->encode($obj);
    }
  }

  /**
   * Backwards compatible JSON decode.
   * @param $str A json string.
   * @return The object represented by the json.
   */
  function decode($str) {
    if (function_exists('json_decode')) {
      return json_decode($str, true);
    }
    else {
      $json = new Services_JSON();
      return $json->decode($str);
    }
  }
  
  /**
   * @return Sign Up / Welcome page markup.
   */
  function welcome() {
    $customer = get_bloginfo('name');
    $address = $_SERVER['SERVER_ADDR'];
    $website_address = get_bloginfo('wpurl');
      
    return '
      <div class="signup fastly-admin-page">
        <h2>Sign Up</h2>
        <p class="error-flash"></p>
        <p>To create your free Fastly account enter your information, click the checkbox, and press the &quot;Sign Up&quot; button.</p>
        
        <fieldset>
          <p><b>Blog Name</b></p>
          <p><input class="text" id="customer" type="text" value="' . esc_attr($customer) . '"></p>
          <p><b>Your Name</b></p>
          <p><input class="text" id="name" type="text"></p>
          <p><b>Email Address</b></p>
          <p><input class="text" id="email" type="text"></p>
        </fieldset>
        
        <br>
        
        <fieldset>
          <p><b>Blog Address</b></p>
          <p><input class="text" id="website_address" type="text" value="' . esc_url($website_address) . '"></p>
          <p><b>Server Address</b></p>
          <p><input class="text" id="address" type="text" value="' . esc_attr($address) . '"></p>
        </fieldset>
        
        <p><label id="agree_tos_label" for="agree_tos"><input id="agree_tos" type="checkbox"> I agree to the
          <a href="#" target="_blank">terms of service</a></label></p>
        
        <p class="button-row"><a href="#" class="button submit">Sign Up</a> <img class="loading" src="' . $this->resource('loading.gif') . '"></p>
      </div>
      
      <div class="welcome fastly-admin-page">
        <br>
        <p><a href="#" class="configure">Click here if you already have a Fastly account for your site.</a></p>
      </div>
    ';
  }
  
  /**
   * @return Configuration page markup.
   */
  function configure() {
    // TODO NEEDS TEH: $_SERVER['SERVER_ADDR']
    ob_start();    
    echo '
      <div class="configure fastly-admin-page">
        <h2>Configure</h2>
        <form method="post" action="options.php">
    ';
    
    settings_fields('fastly-group');
    $parts = parse_url( get_bloginfo('wpurl') );
    $testUrl = 'http://' . $parts['host'] . '.global.prod.fastly.net';
    if( !empty($parts['path']) ) {
      $testUrl .= $parts['path'];
    }
    
    echo '
          <fieldset>
            <p><b>Fastly API Key</b></p>
            <p><input class="text" type="text" name="fastly_api_key" value="' . esc_attr(get_option('fastly_api_key')) . '"></p>
            <p><b>Service Id</b></p>
            <p><input class="text" type="text" name="fastly_service_id" value="' . esc_attr(get_option('fastly_service_id')) . '"></p>
          </fieldset>
      
          <p><a href="#" class="advanced">Advanced Configuration</a></p>
      
          <fieldset class="advanced">
            <p><b>Fastly API Hostname</b></p>
            <p><input class="text" name="fastly_api_hostname" type="text" value="' . esc_url(get_option('fastly_api_hostname')) . '"></p>
            <p><b>Fastly API Port</b></p>
            <p><input class="text" name="fastly_api_port" type="text" value="' . esc_attr(get_option('fastly_api_port')) . '"></p>
            <p><input class="checkbox" name="fastly_log_purges" type="checkbox" value="1" ' . ((int)get_option('fastly_log_purges')?"checked='checked'":"") . '> <b>Log purges to PHP errorlog</b></p>
            <p><input class="checkbox" name="fastly_api_soft" type="checkbox" value="1" ' . ((int)get_option('fastly_api_soft')?"checked='checked'":"") . '> <b>Enable soft purges</b></p>
            
            <!--
            <p><b></b></p>
            <p><input type="text" value=""></p>
            <p><b></b></p>
            <p><input type="text" value=""></p>
            -->
          </fieldset>
      
          <p><input type="submit" class="button" value="Save Settings"></p>
        </form>
      </div>
      <p>Test your site: <a href="' . esc_url($testUrl) . '">' . esc_url($testUrl) . '</a></p>
    ';
    
    $form = ob_get_contents();
    ob_end_clean();
    
    return $form;
  }
  
  /**
   * Initializes the JS for the page.
   */
  function initJS() {
    echo '<script type="text/javascript">
      Fastly.init("' . $this->page . '", ' . $this->encode($this->templates) . ');
    </script>';
  }
  
  /**
   * Renders the admin panel for the plugin.
   */
  function render() {
    if (!current_user_can('manage_options'))  {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    
    echo '<div id="fastly-admin" class="wrap">';
      echo '<h1><img alt="fastly" src="' . $this->resource('logo_white.gif') . '"><br><span style="font-size: x-small;">version: '. FASTLY_VERSION .'</span></h1>';
      echo '<div class="content">' . $this->templates[$this->page] . '</div>';
    echo '</div>';
    
    $this->initJS();
  }
}

// "Some people call me the space cowboy, yeah | Some call me the gangster of love | Some people call me Maurice" -- Steve Miller

?>
