<?php

// Core delivery engine functions for Revive Adserver
include_once('delivery_engine_utilities.php');  // assuming utility functions are encapsulated here

function parseDeliveryIniFile($configPath = null, $configFile = null, $sections = true)
{
    // Set default path for the configuration if not specified
    $configPath = $configPath ?? MAX_PATH . '/var';
    $configFile = $configFile ? '.' . $configFile : '';

    // Compose the configuration file name based on the host
    $host = OX_getHostName();
    $configFileName = "$configPath/$host$configFile.conf.php";

    // Attempt to load the configuration
    $conf = loadConfig($configFileName, $sections);

    // Handle specific cases for plugin or default configuration
    if ($conf === null) {
        $conf = handleSpecialConfigs($configPath, $configFile, $sections);
    }

    // Check and handle installation status
    checkInstallationStatus($conf, $configPath, $configFile);

    return $conf;
}

/**
 * Attempts to load a configuration file and apply database type fix
 * @param string $configFileName Path to the configuration file
 * @param bool $sections Whether to process sections
 * @return array|null Configuration array or null if not found
 */
function loadConfig($configFileName, $sections)
{
    $conf = @parse_ini_file($configFileName, $sections);
    return $conf ? fixMysqli($conf) : null;
}

/**
 * Handles cases for plugins and defaults when main config fails
 * @param string $configPath Base configuration path
 * @param string $configFile Suffix for the configuration file
 * @param bool $sections Whether to process sections
 * @return array Configuration data or exit on failure
 */
function handleSpecialConfigs($configPath, $configFile, $sections)
{
    if ($configFile === '.plugin') {
        // Special handling for plugin configuration
        return handlePluginConfig($configPath, $sections);
    }

    // Attempt to load default configuration if none found
    $defaultConfigFileName = "$configPath/default$configFile.conf.php";
    $conf = @parse_ini_file($defaultConfigFileName, $sections);
    return $conf ?: exitWithError("No configuration file was found.");
}

/**
 * Ensures that the installation status is verified before proceeding
 * @param array|null $conf Configuration array
 * @param string $configPath Base configuration path
 * @param string $configFile Suffix for the configuration file
 */
function checkInstallationStatus($conf, $configPath, $configFile)
{
    if ($conf === null && file_exists(MAX_PATH . '/var/INSTALLED')) {
        exitWithError("Revive Adserver has been installed, but no configuration file was found.");
    } elseif ($conf === null) {
        exitWithError("Revive Adserver has not been installed yet -- please read the INSTALL.txt file.");
    }
}

/**
 * Properly exits and logs an error message
 * @param string $message Error message to display
 */
function exitWithError($message)
{
    echo $message;
    exit(1);
}
