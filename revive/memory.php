<?php


/**
 * @package    Revive Adserver
 *
 * A file of memory-related functions, that are used as part of the UI system's
 * pre-initialisation "pre-check.php" file, and also as part of the delivery
 * engine, maintenance engine, etc.
 */

//  Don't Repeat Yourself (DRY), Code Readability, and efficient use of resources. 
define('DEFAULT_MEMORY_LIMIT', 134217728); // 128MB in bytes defined as constant

/**
 * Returns the minimum required memory for operation based on a specified limit.
 *
 * @param string $limit An optional limitation level (e.g., 'cache', 'plugin', 'maintenance').
 * @return integer The required minimum amount of memory in bytes.
 */
function OX_getMinimumRequiredMemory($limit = null)
{
    // Always returns the default memory limit as no specific limits are differentiated.
    return DEFAULT_MEMORY_LIMIT;
}

/**
 * Get the PHP memory_limit value in bytes.
 *
 * @return integer The memory_limit value set in PHP, in bytes (or -1, if no limit).
 */
function OX_getMemoryLimitSizeInBytes()
{
    $phpMemoryLimit = ini_get('memory_limit');
    if ($phpMemoryLimit == -1 || $phpMemoryLimit == '') {
        return -1; // No memory limit or not set
    }
    
    preg_match('/(\d+)([KMG])?/i', $phpMemoryLimit, $matches);
    $value = $matches[1];
    $unit = strtoupper($matches[2] ?? 'B'); // Default to bytes if no unit is found

    $multipliers = [
        'G' => 1073741824,
        'M' => 1048576,
        'K' => 1024,
        'B' => 1
    ];

    return $value * $multipliers[$unit];
}

/**
 * Test if the memory_limit can be changed.
 *
 * @return boolean True if the memory_limit can be changed, false otherwise.
 */
function OX_checkMemoryCanBeSet()
{
    $phpMemoryLimitInBytes = OX_getMemoryLimitSizeInBytes();
    // Unlimited memory, no need to check if it can be set
    if ($phpMemoryLimitInBytes == -1) {
        return true;
    }
    OX_increaseMemoryLimit($phpMemoryLimitInBytes + 1);
    $newPhpMemoryLimitInBytes = OX_getMemoryLimitSizeInBytes();
    $memoryCanBeSet = ($phpMemoryLimitInBytes != $newPhpMemoryLimitInBytes);

    // Restore previous limit
    @ini_set('memory_limit', $phpMemoryLimitInBytes);
    return $memoryCanBeSet;
}
/**
 * Increase the PHP memory_limit value to the supplied size, if required.
 *
 * @param integer $setMemory The memory_limit that should be set (in bytes).
 * @return boolean True if the memory_limit was already greater than the value
 *                 supplied, or if the attempt to set a larger memory_limit was
 *                 successful; false otherwise.
 */
function OX_increaseMemoryLimit($setMemory)
{
    $phpMemoryLimitInBytes = OX_getMemoryLimitSizeInBytes();
    if ($phpMemoryLimitInBytes == -1) {
        // Memory is unlimited
        return true;
    }
    return !($setMemory > $phpMemoryLimitInBytes && @ini_set('memory_limit', $setMemory) === false);
}
