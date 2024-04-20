<?php

/**
 * Improved version of the ReviveAdserver configuration file parser function.
 *
 * This function reads and parses configuration files with enhanced error handling and improved code readability.
 *
 * @param string $configPath The directory to load the config file from (default is Revive Adserver's /var directory).
 * @param string $configFile The configuration file name suffix (e.g., "geotargeting" for geotargeting.conf.php).
 * @param bool $sections Whether to process sections in the ini file.
 * @param string $type File extension type (default is ".php").
 * @return array|void Configuration array or void on error.
 */
function parseIniFile($configPath = null, $configFile = null, $sections = true, $type = '.php')
{
    $fixMysqli = function ($conf) {
        if (isset($conf['database']['type']) && 0 === stripos($conf['database']['type'], 'mysql')) {
            $conf['database']['type'] = 'mysqli';
            $conf['table']['type'] = $conf['table']['type'] ?? 'InnoDB';
        }
        return $conf;
    };

    $configPath = $configPath ?? MAX_PATH . '/var';
    $configFile = $configFile ? '.' . $configFile : '';
    $fileType = '.conf' . $type;

    // Handle command line execution cases
    if (!isset($_SERVER['SERVER_NAME'])) {
        $_SERVER['HTTP_HOST'] = handleCommandLine();
    }

    $configFilePath = constructFilePath($configPath, $_SERVER['HTTP_HOST'], $configFile, $fileType);
    if (file_exists($configFilePath)) {
        $conf = @parse_ini_file($configFilePath, $sections);
        return isset($conf['realConfig']) ? handleRealConfig($conf, $configPath, $configFile, $fileType, $fixMysqli) : $fixMysqli($conf);
    }

    return handleNoConfigFound($configPath, $configFile, $fileType, $sections, $fixMysqli);
}

/**
 * Constructs the full path to a configuration file.
 *
 * @param string $path Base directory path.
 * @param string $host Host name.
 * @param string $file File name prefix.
 * @param string $type File extension.
 * @return string Full path to the configuration file.
 */
function constructFilePath($path, $host, $file, $type) {
    return $path . '/' . $host . $file . $type;
}

/**
 * Handles command line execution environment specifics.
 *
 * @return string Host name from command line arguments or default test host.
 */
function handleCommandLine() {
    if (defined('TEST_ENVIRONMENT_RUNNING')) {
        return 'test';
    }
    if (empty($GLOBALS['argv'][1])) {
        echo PRODUCT_NAME . " was called via the command line but had no host parameter.\n";
        exit(1);
    }
    return trim($GLOBALS['argv'][1]);
}

/**
 * Handles cases where a real configuration file is referenced by another configuration.
 *
 * @param array $conf Parsed configuration array.
 * @param string $path Base directory path.
 * @param string $file File name prefix.
 * @param string $type File extension.
 * @param callable $fixMysqli Function to adjust database configuration entries.
 * @return array Adjusted configuration array after merging and fixing database settings.
 */
function handleRealConfig($conf, $path, $file, $type, $fixMysqli) {
    $realConfigPath = $path . '/' . $conf['realConfig'] . $file . $type;
    if (file_exists($realConfigPath)) {
        $realConfig = @parse_ini_file($realConfigPath, true);
        $mergedConf = mergeConfigFiles($realConfig, $conf);
        return isset($mergedConf['realConfig']) ? $mergedConf : $fixMysqli($mergedConf);
    }
    return [];
}

/**
 * Handles scenarios where no configuration file is found.
 *
 * @param string $path Base directory path.
 * @param string $file File name prefix.
 * @param string $type File extension.
 * @param bool $sections Whether to process sections in the ini file.
 * @param callable $fixMysqli Function to adjust database configuration entries.
 * @return array|void Returns configuration array or exits on critical error.
 */
function handleNoConfigFound($path, $file, $type, $sections, $fixMysqli) {
    $defaultConfigPath = $path . '/default' . $file . $type;
    if (file_exists($defaultConfigPath)) {
        $conf = @parse_ini_file($defaultConfigPath, $sections);
        return isset($conf['realConfig']) ? $conf : $fixMysqli($conf);
    }
    checkInstallationStatus();
}

/**
 * Checks and handles installation status for critical errors.
 */
function checkInstallationStatus() {
    global $installing;
    if ($installing) {
        return; // Further checks or error messages could be handled here.
    }
    echo PRODUCT_NAME . " configuration not found and software appears to be installed.\n";
    exit(1);
}
