<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

return array(
    'version' => '1.4.2',
    'id'      => 'AAM_USER_ACTIVITY',
    'basedir' => dirname(__FILE__),
    'markups' => array(
        'post_read' => 'Access denied to {$1.action} for post <b>{$1.post.post_title}</b> <small>(ID: {$1.post.ID})</small>',
        'post_edit' => 'Access denied to {$1.action} post <b>{$1.post.post_title}</b> <small>(ID: {$1.post.ID})</small>',
        'access_backend_menu' => 'Access denied to menu {$1.id}',
        'access_dashboard' => 'Access denied to dashboard',
        'media_read' => 'Access denied to view or read media asset {$1.post.post_title} <small>(ID: {$1.post.ID})</small>',
        'term_edit' => 'Access denied to {$1.action} for {$1.taxonomy} with ID term {$1.term}',
        'term_browse' => 'Access denied to {$1.action} <b>{$1.term.name}</b> <small>(ID: {$1.term.term_id})</small>',
        'blog_access' => 'Access denied',
        'ip_access'   => 'Access denied for the IP {$1.target.ip}'
    ),
    'requires' => array(
        'aam'  => '5.6.1.1'
    )
);