<?php

/**
 * @package    Max
 *
 * A file to set up the environment for the administration interface.
 */

require_once 'pre-check.php';
require_once 'init-parse.php';
require_once 'variables.php';
require_once 'constants.php';

/**
 * The environment initialisation function for the administration interface.
 *
 * @TODO Should move the user authentication, loading of preferences into this
 *       file, and out of the /www/admin/config.php file.
 */
/**
 * Initializes the ad server environment, configures settings, and manages redirection if necessary.
 */
function init()
{
    clearGlobalVariables();  
    initializeEnvironment(); 

    if (shouldRedirect()) {  
        handleRedirect();    
    }

    configureErrorHandler(); 
    adjustMemoryLimits();    
}

/**
 * Clears global variables to prevent them from being exploited or overwritten unintentionally.
 */
function clearGlobalVariables()
{
    unset($GLOBALS['_MAX'], $GLOBALS['_OX']);
}

/**
 * Sets up environment configurations, constants, and autoloads necessary classes.
 */
function initializeEnvironment()
{
    setupServerVariables();  
    setupConstants();         
    setupConfigVariables();   

    require MAX_PATH . '/lib/vendor/autoload.php';  
    $GLOBALS['_MAX']['DI'] = new \RV\Container($GLOBALS['_MAX']['CONF']);  

    error_reporting(E_ALL & ~(E_NOTICE | E_WARNING | E_DEPRECATED | E_STRICT));  /
    defineInstallationStatus();  
}

/**
 * Defines the installation status based on the configuration and file existence.
 */
function defineInstallationStatus()
{
    if ((!isset($GLOBALS['_MAX']['CONF']['openads']['installed'])) || (!$GLOBALS['_MAX']['CONF']['openads']['installed'])) {
        define('OA_INSTALLATION_STATUS', OA_INSTALLATION_STATUS_NOTINSTALLED);
    } elseif ($GLOBALS['_MAX']['CONF']['openads']['installed'] && file_exists(MAX_PATH . '/var/UPGRADE')) {
        define('OA_INSTALLATION_STATUS', OA_INSTALLATION_STATUS_UPGRADING);
    } elseif ($GLOBALS['_MAX']['CONF']['openads']['installed'] && file_exists(MAX_PATH . '/var/INSTALLED')) {
        define('OA_INSTALLATION_STATUS', OA_INSTALLATION_STATUS_INSTALLED);
    }
}

/**
 * Determines whether a redirect to the installation process is necessary based on the current script and installation status.
 */
function shouldRedirect()
{
    global $installing;
    return (!$installing && PHP_SAPI != 'cli' && OA_INSTALLATION_STATUS !== OA_INSTALLATION_STATUS_INSTALLED);
}

/**
 * Performs redirection if the system is not fully installed or upgrading.
 */
function handleRedirect()
{
    redirectIfNeeded();  // Perform the actual redirection to the installation script.
}

/**
 * Configures and starts the custom error handler to manage PHP errors more effectively.
 */
function configureErrorHandler()
{
    include_once MAX_PATH . '/lib/max/ErrorHandler.php';
    $eh = new MAX_ErrorHandler();
    $eh->startHandler();
}

/**
 * Adjusts the PHP memory limit to ensure the application has sufficient resources to operate.
 */
function adjustMemoryLimits()
{
    $GLOBALS['_OX']['ORIGINAL_MEMORY_LIMIT'] = OX_getMemoryLimitSizeInBytes();
    OX_increaseMemoryLimit(OX_getMinimumRequiredMemory());
}


// Run the init() function
init();

require_once 'PEAR.php';

// Set $conf
$conf = $GLOBALS['_MAX']['CONF'];
