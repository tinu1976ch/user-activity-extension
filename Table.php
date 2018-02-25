<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM user activity table
 *
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_UserActivity_List_Table extends WP_List_Table {

    /**
     * Constructor
     * 
     * @reeturn void
     * 
     * @access public
     */
    public function __construct() {
        parent::__construct([
            'singular' => __('User Activity', AAM_KEY),
            'plural'   => __('User Activities', AAM_KEY),
            'ajax'     => false
        ]);
    }
    
    /**
     * 
     */
    public function no_items() {
        _e('No events found.', AAM_KEY);
    }
    
    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function column_name($item) {
        if ($item['user']) {
            $user = get_user_by('ID', $item['user']);
            $args = array('extra_attr' => 'style="float:left; margin-right: 5px;"');

            $html  = '<a href="' . get_edit_user_link($item['user']) . '" target=';
            $html .= '"_blank">' . get_avatar($item['user'], 40, '', '', $args);
            $html .= '<strong>' . ucfirst($user->display_name) . '</strong>';
            $html .= '</a>';
            $html .= '<small> ' . translate_user_role($user->roles[0]). '</small>';
            $html .= '<br/>' . $user->user_email;
        } else {
            $url   = AAM_UserActivity::getInstance()->getBaseurl('/media/none.svg');
            $html  = '<img src="' . $url . '" ';
            $html .= 'class="avatar avatar-40 photo" width="40" height="40" ';
            $html .= 'style="float:left; margin-right: 5px;" />';
            $html .= '<strong>Visitor</strong><br/>Anonymous';
        }
        
        return $html;
    }
    
    /**
     * Render a column when no column specific method exists.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'created':
            case 'location':
                $response = $item[$column_name];
                break;
            
            case 'event':
                $response = $this->formatEvent($item);
                break;
            
            default:
                break;
        }
        
        return $response;
    }
    
    /**
     * 
     * @param type $event
     * @return type
     */
    protected function formatEvent($event) {
        $hooks    = AAM_UserActivity::getInstance()->getHooks();
        $config   = (isset($hooks[$event['hook']]) ? $hooks[$event['hook']] : null);
        $response = $event['hook'];
        
        if (!is_null($config)) {
            if (is_string($config)) {
                $response = $this->replaceMarkers(
                        $config, unserialize($event['metadata'])
                );
            } elseif(isset($config['messageCallback'])) {
                $response = call_user_func($config['messageCallback'], $event);
            } elseif (isset($config['message'])) {
                $response = $this->replaceMarkers(
                        $config['message'], unserialize($event['metadata'])
                );
            }
        }
        
        return "<b>{$event['hook']}</b><br/>" . $response;
    }
    
    /**
     * 
     * @param type $str
     * @param type $metadata
     * @return type
     */
    protected function replaceMarkers($str, $metadata) {
        if (preg_match_all('/{\$([^\s,\t:]+)}/', $str, $match)) {
            foreach($match[1] as $marker) {
                $str = str_replace(
                        '{$' . $marker . '}', 
                        $this->find(explode('.', $marker), $metadata), 
                        $str
                );
            }
        }
        
        return $str;
    }
    
    /**
     * 
     * @param type $marker
     * @param type $metadata
     * @return type
     */
    protected function find($marker, $metadata) {
        $result = null;
        
        if (is_array($marker) && count($marker)) {
            $position = array_shift($marker);
            
            if (is_array($metadata) && isset($metadata[$position])) {
                $result = $metadata[$position];
            } elseif (is_object($metadata) && isset($metadata->{$position})) {
                $result = $metadata->{$position};
            }
            
            if (count($marker)) {
                $result = $this->find($marker, $result);
            }
        }
        
        return $result;
    }
    
    /**
     *  Associative array of columns
     *
     * @return array
     */
    public function get_columns() {
        $columns = array (
            'name'     => __('User', AAM_KEY),
            'created'  => __('Occured', AAM_KEY),
            'location' => __('Location', AAM_KEY),
            'event'    => __('Event', AAM_KEY)
        );

        return $columns;
    }
    
    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array (
            'name'    => array('user', true),
            'created' => array('created', true)
        );

        return $sortable_columns;
    }
    
    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = array(
            'clear'  => 'Clear Log'
        );

        return $actions;
    }
    
    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {
        $this->triggerBulkAction();
        
        $limit  = $this->get_items_per_page('events_per_page', 10);
        $offset = $this->get_pagenum();
        $total  = AAM_UserActivity::getInstance()->count();

        $this->set_pagination_args([
            'total_items' => $total,
            'per_page'    => $limit 
        ]);

        $this->items = AAM_UserActivity::getInstance()->getList($limit, $offset);
    }
    
    /**
     * 
     */
    protected function triggerBulkAction() {
        switch(AAM_Core_Request::request('action')) {
            case 'clear':
                AAM_UserActivity::getInstance()->flush();
                break;
            
            default:
                break;
        }
    }
    
    /**
     * 
     * @return type
     */
    protected function get_column_info() {
        return array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
            'name'
        );
    }
    
}