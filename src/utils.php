<?php

/**
 * Sanitize a surrogate key to only allow hash-like keys.
 *
 * This function will allow surrogate keys to be a-z, A-Z, 0-9, -, and _. This will hopefully ward off any weird issues
 * that might occur with unusual characters.
 *
 * @param  string $key The key to sanitize.
 * @return string            The sanitized key.
 */
function purgely_sanitize_surrogate_key($key)
{
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
}

/**
 * Get an individual settings value.
 *
 * @param  string $name The name of the option to retrieve.
 * @return string       The option value.
 */
function purgely_get_option($name)
{
    $value = '';
    $options = purgely_get_options();

    if (isset($options[$name])) {
        $value = $options[$name];
    }

    return $value;
}

/**
 * Get all of the Purgely options.
 * Gets the options set by the user and falls back to the constant configuration if the value is not set in options.
 *
 * @return array Array of all Purgely options.
 */
function purgely_get_options()
{
    $option_keys = array(
        'fastly_api_key',
        'fastly_service_id',
        'fastly_log_purges',
        'fastly_debug_mode',
        'fastly_vcl_version',
        'allow_purge_all',
        'fastly_api_hostname',
        'enable_stale_while_revalidate',
        'stale_while_revalidate_ttl',
        'enable_stale_if_error',
        'stale_if_error_ttl',
        'surrogate_control_ttl',
        'cache_control_ttl',
        'default_purge_type',
    );

    $options = array();

    foreach ($option_keys as $key) {
        $constant = 'PURGELY_' . strtoupper($key);

        if (defined($constant)) {
            $options[$key] = constant($constant);
        }
    }

    $options = get_option('fastly-settings-general', $options);
    $options = array_merge($options, get_option('fastly-settings-advanced', $options));
    $options = array_merge($options, get_option('fastly-settings-webhooks', $options));

    return $options;
}

/**
 * Sanitize a Fastly Service ID or API Key.
 * Restricts a value to only a-z, A-Z, and 0-9.
 *
 * @param  string $key Unsantizied key.
 * @return string      Sanitized key.
 */
function purgely_sanitize_key($key)
{
    return preg_replace('/[^a-zA-Z0-9]/', '', $key);
}

/**
 * Callback function for sanitizing a checkbox setting.
 *
 * @param  mixed $value Unsanitized setting.
 * @return bool         Whether or not value is valid.
 */
function purgely_sanitize_checkbox($value)
{
    return (in_array($value, array('1', 1, 'true', true), true));
}

/**
 * Function for testing Fastly API connection
 * @param $hostname
 * @param $service_id
 * @param $api_key
 * @return array
 */
function test_fastly_api_connection($hostname, $service_id, $api_key)
{

    if (empty($hostname) || empty($service_id) || empty($api_key)) {
        return array('status' => false, 'message' => __('Please enter credentials first'));
    }

    $url = $hostname . '/service/' . $service_id;
    $headers = array(
        'Fastly-Key' => $api_key,
        'Accept' => 'application/json'
    );
    try {
        $response = Requests::get($url, $headers);

        if ($response->success) {
            $response_body = json_decode($response->body);
            $service_name = $response_body->name;
            $message = __('Connection Successful on service *' . $service_name . "*");
        } else {
            handle_logging($response);
            $message = json_decode($response->body);
            $message = $message->msg;
        }
        return array('status' => $response->success, 'message' => $message);
    } catch (Exception $e) {
        return array('status' => false, 'message' => $e->getMessage());
    }
}

/**
 * Sends message to slack via webhooks
 * @param $message
 */
function send_web_hook($message)
{

    if (!Purgely_Settings::get_setting('webhooks_activate')) {
        return;
    }

    $webhook_url = Purgely_Settings::get_setting('webhooks_url_endpoint');
    $username = Purgely_Settings::get_setting('webhooks_username');
    $channel = Purgely_Settings::get_setting('webhooks_channel');

    $headers = array('Content-type: application/json');
    $data = json_encode(
        array(
            'text' => $message,
            'username' => $username,
            'channel' => '#' . $channel,
            'icon_emoji' => ':airplane:'
        )
    );

    try {
        $response = Requests::request($webhook_url, $headers, $data, Requests::POST);
        if (!$response->success) {
            if (Purgely_Settings::get_setting('fastly_debug_mode')) {
                error_log("Webhooks request failed, error: " . json_decode($response->body));
            }
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

/**
 * Test slack webhooks connection in admin
 * @return array
 */
function test_web_hook()
{

    $webhook_url = Purgely_Settings::get_setting('webhooks_url_endpoint');
    $username = Purgely_Settings::get_setting('webhooks_username');
    $channel = Purgely_Settings::get_setting('webhooks_channel');

    $headers = array('Content-type: application/json');
    $data = json_encode(
        array(
            'text' => 'Connection successful!',
            'username' => $username,
            'channel' => '#' . $channel,
            'icon_emoji' => ':airplane:'
        )
    );

    try {
        $response = Requests::request($webhook_url, $headers, $data, Requests::POST);
        $message = $response->success ? __('Connection Successful!') : __($response->body);

        if (Purgely_Settings::get_setting('fastly_debug_mode')) {
            error_log('Webhooks - test connection: ' . $response->body);
        }

        return array('status' => $response->success, 'message' => $message);
    } catch (Exception $e) {
        if (Purgely_Settings::get_setting('fastly_debug_mode')) {
            error_log('Webhooks - test connection: ' . $e->getMessage());
        }
        return array('status' => false, 'message' => $e->getMessage());
    }
}

/**
 * Do logging where needed
 * @param Requests_Response $response
 * @param $message
 */
function handle_logging(Requests_Response $response, $message = false)
{
    $debug_mode = Purgely_Settings::get_setting('fastly_debug_mode');
    $log_purges = Purgely_Settings::get_setting('fastly_log_purges');
    $log_slack = Purgely_Settings::get_setting('webhooks_activate');

    if ($debug_mode || $log_purges || $log_slack) {
        $msg = get_message_by_status_code($response->status_code);
        if ($message) {
            $msg = $msg . ' - ' . $message;
        }
    } else {
        return;
    }

    // Log purges in logs, don't log twice
    if ($log_purges || $debug_mode) {
        if ($log_purges) {
            error_log($msg);
        } elseif ($debug_mode) {
            if (!$response->success) {
                error_log($msg);
            }
        }
    }

    // Log message in Slack via Webhooks
    if ($log_slack) {
        send_web_hook($msg);
    }
}

/**
 * Returns response message based on status code
 * @param $code
 * @return string
 */
function get_message_by_status_code($code)
{
    switch ($code) {
        case 200:
            $msg = __($code . ' - OK');
            break;
        case 203:
            $msg = __($code . ' - Non-Authoritative Information');
            break;
        case 300:
            $msg = __($code . ' - Multiple Choices');
            break;
        case 301:
            $msg = __($code . ' - Moved Permanently');
            break;
        case 302:
            $msg = __($code . ' - Moved Temporarily');
            break;
        case 401:
            $msg = __($code . ' - Unauthorized');
            break;
        case 404:
            $msg = __($code . ' - Not Found');
            break;
        case 410:
            $msg = __($code . ' - Gone');
            break;
        default:
            $msg = __('Error occurred, turn on debugging options and check your logs.');
            break;
    }
    return $msg;
}

/**
 * Determine if the first arg is a URL.
 *
 * @param  string $thing The first argument passed to the function.
 * @return bool                True if the thing is a URL, false if not.
 */
function is_url($thing)
{
    return 0 === strpos($thing, 'http') && esc_url_raw($thing) === $thing;
}
