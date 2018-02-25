<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM user activity extension
 *
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_UserActivity {

    /**
     * Instance of itself
     * 
     * @var AAM_UserActivity 
     * 
     * @access private
     */
    private static $_instance = null;

    /**
     *
     * @var type 
     */
    protected $screen = null;
    
    /**
     *
     * @var type 
     */
    protected $table;
    
    /**
     *
     * @var type 
     */
    protected $hooks = null;

    /**
     * Initialize the extension
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        //manager Admin Menu
        if (is_multisite() && is_network_admin()) {
            //register AAM in the network admin panel
            add_action('network_admin_menu', array($this, 'adminMenu'), 1000);
            add_action('network_admin_notices', array($this, 'mainNotice'), 1);
        } else {
            add_action('admin_menu', array($this, 'adminMenu'), 1000);
            add_action('admin_notices', array($this, 'mainNotice'), 1);
        }
        
        add_action('aam-rejected-action', array($this, 'trackRejected'), 10, 2);
        
        //add post extensions load hook
        if (AAM_Core_Config::get('track-activity', true)) {
            add_action('aam-post-extensions-load', array($this, 'postExtensionsLoad'));
        }
    }
    
     /**
     * Main Dashboard notification
     * 
     * @return void
     * 
     * @access public
     */
    public function mainNotice() {
        $screen = get_current_screen();
        
        if (is_object($screen) && ($screen->id == $this->screen) && !$this->installed()) {
            $style = 'padding: 10px; letter-spacing:0.5px;';
            echo '<div class="updated notice"><p style="' . $style . '">';
            echo 'Install free <b>User Activity Hooks</b> plugin to track more activities. ';
            echo '<a href="https://github.com/aamplugin/user-activity-hooks" target="_blank">Download plugin.</a>';
            echo '</p></div>';
        }
    }
    
    /**
     * Get plugin's status
     * 
     * @return string
     * 
     * @access protected
     * @static
     */
    protected function installed($name = 'AAM User Activity Helper') {
        $status = false;

        if (file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (function_exists('get_plugin_data')) {
            foreach(get_plugins() as $plugin) {
                if ($plugin['Name'] == $name) {
                    $status = true;
                    break;
                }
            }
        }

        return $status;
    }
    
    /**
     * 
     */
    public function postExtensionsLoad() {
        foreach($this->getHooks() as $hook => $config) {
            if (isset($config['hookCallback'])) {
                $callback = $config['hookCallback'];
            } else {
                $callback = array($this, 'hookCallback');
            }
            
            add_filter($hook, $callback, 1, 99);
        }
    }
    
    /**
     * 
     * @return type
     */
    public function getHooks() {
        if (is_null($this->hooks)) {
            $config  = AAM_Core_Config::get('activity.hook', array());
            $default = require dirname(__FILE__) . '/config.php';
            $custom  = apply_filters('aam-user-activity-hooks-filter', array());

            $this->hooks = array_merge(
                    $default['markups'], $custom, is_array($config) ? $config : array()
            );
        }
        
        return $this->hooks;
    }
    
    /**
     * 
     */
    public function hookCallback() {
        $this->save(current_filter(), func_get_args());
    }
    
    /**
     * 
     * @param type $area
     * @param type $args
     */
    public function trackRejected($area, $args) {
        $hooks = $this->getHooks();
        
        if (isset($args['hook']) && (isset($hooks[$args['hook']]))) {
            $this->save($args['hook'], array($area, $args));
        }
    }
    
    /**
     * 
     * @param type $hook
     * @param type $metadata
     * @param type $user
     */
    public function save($hook, $metadata, $user = null) {
        global $wpdb;
        
        $wpdb->insert(
                "{$wpdb->prefix}aam_user_activity", 
                array(
                    'user'     => ($user ? $user : get_current_user_id()),
                    'created'  => date('Y-m-d H:i:s'),
                    'location' => AAM_Core_Request::server('REMOTE_ADDR'),
                    'hook'     => $hook,
                    'metadata' => serialize($metadata)
                )
        );
    }
    
    /**
     * 
     * @global type $wpdb
     */
    public function flush() {
        global $wpdb;
        
        $wpdb->query("TRUNCATE {$wpdb->prefix}aam_user_activity");
    }
    
    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public function count() {
        global $wpdb;

        $s    = AAM_Core_Request::request('s');
        $args = array();
        
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}aam_user_activity AS ua";
        
        if (!empty($s)) {
            $sql .= " LEFT JOIN {$wpdb->users} AS u ON (ua.user = u.ID) ";
            $sql .= 'WHERE u.user_login = %s || u.user_nicename = %s || ';
            $sql .= 'u.user_email = %s || u.display_name = %s || ua.hook = %s';
            $args = array($s, $s, $s, $s, $s);
        }

        return $wpdb->get_var((count($args) ? $wpdb->prepare($sql, $args) : $sql));
    }
    
        /**
     * Retrieve customer’s data from the database
     *
     * @param int $limit
     * @param int $offset
     *
     * @return mixed
     */
    public function getList($limit = 10, $offset = 1) {
        global $wpdb;

        $orderby = AAM_Core_Request::request('orderby');
        $order   = AAM_Core_Request::request('order', 'ASC');
        $s       = AAM_Core_Request::request('s');
        $args    = array();
        
        $sql = "SELECT ua.* FROM {$wpdb->prefix}aam_user_activity AS ua";
        
        if (!empty($s)) {
            $sql .= " LEFT JOIN {$wpdb->users} AS u ON (ua.user = u.ID) ";
            $sql .= 'WHERE u.user_login = %s || u.user_nicename = %s || ';
            $sql .= 'u.user_email = %s || u.display_name = %s || ua.hook = %s';
            $args = array($s, $s, $s, $s, $s);
        }
        
        if (!empty($orderby)) {
            $sql .= ' ORDER BY %s %s' . esc_sql($orderby);
            $args = array_merge($args, array($orderby, $order));
        }

        if ($limit != -1) {
            $sql .= " LIMIT %d OFFSET %d";
            $args = array_merge($args, array($limit, ( $offset - 1 ) * $limit));
        }

        return $wpdb->get_results($wpdb->prepare($sql, $args), 'ARRAY_A');
    }

    /**
     * Register subadmin menu
     *
     * @return void
     *
     * @access public
     */
    public function adminMenu() {
        //register the menu
        $this->screen = add_submenu_page(
                'aam', 
                'User Activity', 
                'User Activity', 
                AAM_Core_Config::get('activity.capability', 'administrator'), 
                'aamua', 
                array($this, 'renderPage')
        );

        add_action("load-{$this->screen}", array($this, 'screenOptions'));
    }

    /**
     * 
     */
    public function renderPage() {
        require dirname(__FILE__) . '/phtml/list.phtml';
    }

    /**
     * 
     * @return type
     */
    public function screenOptions() {
        $screen = get_current_screen();
        
        // get out of here if we are not on our settings page
        if (is_object($screen) && ($screen->id == $this->screen)) {
            $args = array(
                'label'   => __('Number of events per page:', AAM_KEY),
                'default' => 10,
                'option'  => 'events_per_page'
            );
            add_screen_option('per_page', $args);

            $this->table = new AAM_UserActivity_List_Table;
        }
    }
    
    /**
     * Get extension base URL
     * 
     * @param string $path
     * 
     * @return string
     * 
     * @access public
     */
    public function getBaseurl($path = '') {
        $contentDir = str_replace('\\', '/', WP_CONTENT_DIR);
        $baseDir    = str_replace('\\', '/', dirname(__FILE__));
        
        $relative = str_replace($contentDir, '', $baseDir);
        
        return content_url() . $relative . $path;
    }
    
    /**
     * 
     * @return type
     */
    public function getTable() {
        return $this->table;
    }

    /**
     * Bootstrap the extension
     * 
     * @return AAM_UserActivity
     * 
     * @access public
     */
    public static function bootstrap() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }
    
    /**
     * 
     * @return type
     */
    public static function getInstance() {
        return self::bootstrap();
    }

}
