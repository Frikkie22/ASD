<?php
// Global context variable accessible across different scopes
global $phpAds_context;

// Check to prevent duplicate inclusion of this setup
if (!defined('PHPADSNEW_INCLUDED')) {
    // Define MAX_PATH based on the directory of the current file
    define('MAX_PATH', dirname(__FILE__));

    // Include essential files for delivery initialization
    require MAX_PATH . '/init-delivery.php';
    require MAX_PATH . '/lib/max/Delivery/adSelect.php';

    // Function to fetch an ad without processing its output
    function view_raw($what, $clientid = 0, $target = '', $source = '', $withtext = 0, $context = 0, $richmedia = true) {
        // Directly call the ad selection function with minimal parameters
        return MAX_adSelect($what, $clientid, $target, $source, $withtext, '', $context, $richmedia, '', '', '');
    }

    // Function to display an ad and return its identifier
    function view($what, $clientid = 0, $target = '', $source = '', $withtext = 0, $context = 0, $richmedia = true) {
        $output = view_raw($what, $clientid, $target, $source, $withtext, $context, $richmedia);
        if (is_array($output)) {
            echo $output['html'];  
            return $output['bannerid'];  
        }
        return false;  // Return false if output is not an array
    }

    // Set a flag to indicate this file has been included
    define('PHPADSNEW_INCLUDED', true);
}
?>
