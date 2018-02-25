<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM user activity activation
 *
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_UserActivity_Activation {

    /**
     * Run the activation hook
     * 
     * Add capabilities related to commenting feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function run() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'aam_user_activity';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta(
                str_replace(
                        '%', 
                        $wpdb->prefix, 
                        file_get_contents(dirname(__FILE__) . '/db.sql')
                )
            );
        }
        
        //clean-up
        @unlink(__FILE__);
        @unlink(dirname(__FILE__) . '/db.sql');
    }

}