<?php
/**
 * Define the endpoint for the API.
 */
if (!defined('PURGELY_API_ENDPOINT')) {
    define('PURGELY_API_ENDPOINT', 'https://api.fastly.com/');
}

/**
 * Define the user API key.
 */
if (!defined('PURGELY_FASTLY_KEY')) {
    define('PURGELY_FASTLY_KEY', '');
}

/**
 * Define the service ID.
 */
if (!defined('PURGELY_FASTLY_SERVICE_ID')) {
    define('PURGELY_FASTLY_SERVICE_ID', '');
}

/**
 * Allow plugin to issue full purges or not.
 */
if (!defined('PURGELY_ALLOW_PURGE_ALL')) {
    define('PURGELY_ALLOW_PURGE_ALL', false);
}

/**
 * Turn stale-while-revalidate on or off.
 */
if (!defined('PURGELY_ENABLE_STALE_WHILE_REVALIDATE')) {
    define('PURGELY_ENABLE_STALE_WHILE_REVALIDATE', true);
}

/**
 * Set the default stale-while-revalidate TTL.
 */
if (!defined('PURGELY_STALE_WHILE_REVALIDATE_TTL')) {
    define('PURGELY_STALE_WHILE_REVALIDATE_TTL', 60 * 60 * 24); // 24 hours
}

/**
 * Turn stale-if-error on or off.
 */
if (!defined('PURGELY_ENABLE_STALE_IF_ERROR')) {
    define('PURGELY_ENABLE_STALE_IF_ERROR', true);
}

/**
 * Set the default stale-if-error TTL.
 */
if (!defined('PURGELY_STALE_IF_ERROR_TTL')) {
    define('PURGELY_STALE_IF_ERROR_TTL', 60 * 60 * 24); // 24 hours
}

/**
 * Set the default surrogate control TTL.
 */
if (!defined('PURGELY_SURROGATE_CONTROL_TTL')) {
    define('PURGELY_SURROGATE_CONTROL_TTL', 86400); // 24 hours
}

/**
 * Set the default cache control TTL.
 */
if (!defined('PURGELY_CACHE_CONTROL_TTL')) {
    define('PURGELY_CACHE_CONTROL_TTL', 0); // 0 minutes
}

/**
 * Set the default purge type for all purges.
 * The currently supported values are "soft" and "instant".
 */
if (!defined('PURGELY_DEFAULT_PURGE_TYPE')) {
    define('PURGELY_DEFAULT_PURGE_TYPE', 'soft');
}

/**
 * Set the default purges logging
 * The currently supported values are "true" and "false".
 */
if (!defined('PURGELY_FASTLY_LOG_PURGES')) {
    define('PURGELY_FASTLY_LOG_PURGES', false);
}

/**
 * Set the default purges logging
 * The currently supported values are "true" and "false".
 */
if (!defined('PURGELY_FASTLY_DEBUG_MODE')) {
    define('PURGELY_FASTLY_DEBUG_MODE', false);
}

/**
 * Set the default purges logging
 * The currently supported values are "true" and "false".
 */
if (!defined('PURGELY_FASTLY_VCL_VERSION')) {
    define('PURGELY_FASTLY_VCL_VERSION', false);
}

/**
 * Set the default webhooks url endpoint
 */
if (!defined('PURGELY_WEBHOOKS_URL_ENDPOINT')) {
    define('PURGELY_WEBHOOKS_URL_ENDPOINT', 'https://hooks.slack.com/services/');
}

/**
 * Set the default webhooks username
 */
if (!defined('PURGELY_WEBHOOKS_USERNAME')) {
    define('PURGELY_WEBHOOKS_USERNAME', 'wordpress-bot');
}

/**
 * Set the default webhooks channel
 */
if (!defined('PURGELY_WEBHOOKS_CHANNEL')) {
    define('PURGELY_WEBHOOKS_CHANNEL', 'general');
}

/**
 * Webhooks activation
 */
if (!defined('PURGELY_WEBHOOKS_ACTIVATE')) {
    define('PURGELY_WEBHOOKS_ACTIVATE', false);
}
