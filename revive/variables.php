<?php



/**
 * @package    OpenX
 * @author     Chris Nutting <chris.nutting@openx.org>
 * @author     Andrew Hill <andrew.hill@openx.org>
 * @author     Radek Maciaszek <radek.maciaszek@openx.org>
 *
 * A file to set up the environment for the delivery engine.
 *
 * Both opcode and PHP by itself slow things down when we require many
 * files. Therefore maintainability has been sacrificed in order to
 * speed up a delivery:
 * - We are not using classes (if possible) in delivery;
 * - We have as few as possible includes and add new code into
 *   existing files.
 */

/**
 * Polyfills
 */
if (!function_exists('each')) {
    function each(&$array)
    {
        $key = key($array);

        if (null === $key) {
            return false;
        }

        $value = current($array);
        next($array);

        return [
            0 => $key,
            1 => $value,
            'key' => $key,
            'value' => $value,
        ];
    }
}

// Setup common configuration variables used in both delivery and admin parts
function setupConfigVariables()
{
    // Define global configuration settings
    $GLOBALS['_MAX'] = [
        'MAX_DELIVERY_MULTIPLE_DELIMITER' => '|',
        'MAX_COOKIELESS_PREFIX'           => '__',
        'thread_id'                       => uniqid(),
        'SSL_REQUEST'                     => isSSLRequest(), 
        'MAX_RAND'                        => $GLOBALS['_MAX']['CONF']['priority']['randmax'] ?? 2147483647,
        'NOW_ms'                          => round(1000 * (float)microtime(true)) /
    ];

    // Set server timezone for auto-maintenance
    if (!isInstalling()) {
        $GLOBALS['serverTimezone'] = date_default_timezone_get();
        OA_setTimeZoneUTC();
    }
}

// Check if the installation script is running
function isInstalling() {
    return substr($_SERVER['SCRIPT_NAME'], -11) === 'install.php';
}

// Determine if the current request is over SSL
function isSSLRequest() {
    return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') ||
           ($_SERVER['SERVER_PORT'] === $GLOBALS['_MAX']['CONF']['openads']['sslPort']) ||
           (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ||
           (strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on') ||
           (strtolower($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '') === 'on') ||
           (strtolower($_SERVER['FRONT-END-HTTPS'] ?? '') === 'on');
}

// Setup delivery-specific configuration variables
function setupDeliveryConfigVariables()
{
    defineConstants(['MAX_PATH', 'OX_PATH', 'RV_PATH', 'LIB_PATH']);
    if (!isset($GLOBALS['_MAX']['CONF'])) {
        $GLOBALS['_MAX']['CONF'] = parseDeliveryIniFile();
    }
    setupConfigVariables(); // Ensure common variables are set up
}

// Define multiple constants if not already defined
function defineConstants($constants)
{
    foreach ($constants as $constant) {
        if (!defined($constant)) {
            define($constant, dirname(__FILE__));
        }
    }
    if (!defined('LIB_PATH')) {
        define('LIB_PATH', MAX_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'OX');
    }
}








/**
 * Set a timezone
 *
 * @param string $timezone
 */
function OA_setTimeZone($timezone)
{
    // Set the new time zone
    date_default_timezone_set($timezone);

    // Set PEAR::Date_TimeZone default as well
    //
    // Ideally this should be a Date_TimeZone::setDefault() call, but for optimization
    // purposes, we just override the global variable
    $GLOBALS['_DATE_TIMEZONE_DEFAULT'] = $timezone;
}

/**
 * Set the current default timezone to UTC
 *
 * @see OA_setTimeZone()
 */
function OA_setTimeZoneUTC()
{
    OA_setTimeZone('UTC');
}

/**
 * Set the current default timezone to local
 *
 * @see OA_setTimeZone()
 */
function OA_setTimeZoneLocal()
{
    $tz = empty($GLOBALS['_MAX']['PREF']['timezone']) ? 'UTC' : $GLOBALS['_MAX']['PREF']['timezone'];
    OA_setTimeZone($tz);
}

/**
 * Returns the hostname the script is running under.
 *
 * @return string containing the hostname (with port number stripped).
 */
function OX_getHostName()
{
    if (!empty($_SERVER['HTTP_HOST'])) {
        $host = explode(':', $_SERVER['HTTP_HOST']);
        $host = $host[0];
    } elseif (!empty($_SERVER['SERVER_NAME'])) {
        $host = explode(':', $_SERVER['SERVER_NAME']);
        $host = $host[0];
    }
    return $host;
}

/**
 * Returns the hostname (with port) the script is running under.
 *
 * @return string containing the hostname with port
 */
function OX_getHostNameWithPort()
{
    if (!empty($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } elseif (!empty($_SERVER['SERVER_NAME'])) {
        $host = $_SERVER['SERVER_NAME'];
    }
    return $host;
}

/**
 * A function to define the PEAR include path in a separate method,
 * as it is required by delivery only in exceptional circumstances.
 */
function setupIncludePath()
{
    static $checkIfAlreadySet;
    if (isset($checkIfAlreadySet)) {
        return;
    }
    $checkIfAlreadySet = true;

    $oxPearPath = MAX_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pear';
    $oxZendPath = MAX_PATH . DIRECTORY_SEPARATOR . 'lib';

    set_include_path($oxPearPath . PATH_SEPARATOR . $oxZendPath . PATH_SEPARATOR . get_include_path());
}

/**
 * @return \Psr\Container\ContainerInterface
 */
function RV_getContainer()
{
    return $GLOBALS['_MAX']['DI'];
}
