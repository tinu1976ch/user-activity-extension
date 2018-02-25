<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

if (defined('AAM_KEY') && !defined('AAM_USER_ACTIVITY')) {
    $config = require(dirname(__FILE__) . '/config.php');
    
    //define extension constant as it's version #
    define('AAM_USER_ACTIVITY', $config['version']);

    //register activate and extension classes
    AAM_Autoloader::add('AAM_UserActivity', $config['basedir'] . '/UserActivity.php');
    AAM_Autoloader::add('AAM_UserActivity_List_Table', $config['basedir'] . '/Table.php');
    AAM_Autoloader::add('AAM_UserActivity_Activation', $config['basedir'] . '/Activation.php');
    
    if (class_exists('AAM_UserActivity_Activation')) {
        AAM_UserActivity_Activation::run();
    }
    
    if (version_compare(AAM_Core_API::version(), '5.0') === -1) {
        AAM_Core_Console::add(
                '[User Activity] extension requires AAM 5.0 or higher.', 'b'
        );
    }

    AAM_UserActivity::bootstrap();
}