<?php
/**
    Plugin Name: Navigation Manager
    Plugin URI:  http://www.wp-admin.org.ua
    Description: Navigation Manager for wordpress.
    Author:      Sergey Cherepanov
    Version:     1.0
    Author URI:  http://www.cherepanov.org.ua
*/
/**
 * Navigation Manager - Wordpress Plugin.
 *
 * @copyright:    Copyright 2008 Sergey Cherepanov. (http://www.cherepanov.org.ua)
 * @author:       Sergey Cherepanov (sergey@cherepanov.org.ua)
 * @license:      http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE v3.0
 * @date          16.12.08
 */

if (!class_exists('navigation')) {
    
    class navigation_item
    {
        private $parent_item = null;
        private $level       = null;
        public  $child       = null;

        public function level()
        {
            if($this->level !== null){
                return $this->level;
            }
            return $this->level = $this->get_level();
        }
        
        public function get_level($lvl = 0)
        {
            if($this->parent){
                $lvl = $this->parent_item->get_level($lvl+1);
            }
            return $lvl;
        }

        public function get_parent_by_level($lvl = 0)
        {
            $result = null;
            if ($lvl <= $this->level()) {
                if ($this->level() == $lvl) {
                    $result = $this;
                } else if ($this->parent) {
                    $result = $this->parent_item->get_parent_by_level($lvl);
                }
            }
            return $result;
        }

        public function __construct($item)
        {
            foreach ($item as $var_name=>$var_value) {
                $this->{$var_name} = $var_value;
            }
        }

        public function set_child($children = null)
        {
            if (is_object($children))
            {
                if (!$this->child) {
                    $this->child    = array();
                }
                $this->child[]    = &$children;
                $children->set_parent($this);
            }
        }

        public function set_parent($parent = null)
        {
            $this->parent_item = &$parent;
        }

        public function get_ancestors($ancestors = array())
        {
            if (is_object($this->parent_item)) {
                $ancestors[] = $this->parent_item->ID;
                $ancestors = $this->parent_item->get_ancestors($ancestors);
            }
            return $ancestors;
        }
    }
    
    class navigation
    {
        private $options = null;

        public static $version    = '0.3.5';
        public static $abspath    = '';
        public static $url        = '';

        public $groups             = array();
        public $items              = array();
        public $groupVisibleCount  = array();
        public $includedJavaScript = array();
        public $currentItem        = null;
        
        public static function upgrade()
        {
            include_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            global $wpdb, $charset_collate;
            
            $table_name = $wpdb->prefix.'navigation';
            
            if(!$wpdb->get_var("DESC $table_name 'extends'")){
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN `extends` text NOT NULL DEFAULT ''");
            }
            
            $table_name = $wpdb->prefix.'navigation_groups';
            
            if(!$wpdb->get_var("DESC $table_name 'usejs'")){
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN `usejs` tinyint(1) NOT NULL default '0'");
            }
            update_option('navigation_plugin_version', navigation::$version);
        }
        
        public static function install()
        {
            include_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            global $wpdb, $charset_collate;
            $table_name = $wpdb->prefix.'navigation';
            if($wpdb->get_var('SHOW TABLES LIKE "'.$table_name.'"') != $table_name){
                $sql = "CREATE TABLE " . $table_name . " (
                  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NOT NULL,
                  `value` VARCHAR(1024) NOT NULL,
                  `custom_title` int(9),
                  `type` VARCHAR(255),
                  `group` mediumint(9) NOT NULL,
                  `parent` mediumint(9) NOT NULL default '0',
                  `show_child` tinyint(1) NOT NULL default '0',
                  `exclude` VARCHAR(1024),
                  `depth` tinyint(1) NOT NULL default '0',
                  `order` mediumint(9) NOT NULL default '0',
                  `extends` text,
                  UNIQUE KEY ID (ID)
                ) $charset_collate;";
                dbDelta($sql);
            }
            
                
            $table_name = $wpdb->prefix.'navigation_groups';
            if($wpdb->get_var('SHOW TABLES LIKE "'.$table_name.'"') != $table_name){
                $sql = "CREATE TABLE " . $table_name . " (
                  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NOT NULL,
                  `usejs` tinyint(1) NOT NULL default '0',
                  UNIQUE KEY ID (ID)
                ) $charset_collate;";
                dbDelta($sql);
            }
            $table_name = $wpdb->prefix.'navigation_permalinks';
            if($wpdb->get_var('SHOW TABLES LIKE "'.$table_name.'"') != $table_name){
                $sql = "CREATE TABLE " . $table_name . " (
                  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
                  `url` VARCHAR(2083) NOT NULL,
                  `type` VARCHAR(64) NOT NULL,
                  `object_ID` mediumint(9) NOT NULL,
                  UNIQUE KEY ID (ID)
                ) $charset_collate;";
                dbDelta($sql);
            }
            update_option('navigation_plugin_version', navigation::$version);
        }
        
        public function __construct()
        {
            if(get_option('navigation_plugin_version') < navigation::$version){
                navigation::upgrade();
            }
            if((bool) get_option('need_fix_permalinks') || $_POST['need_fix_permalinks']){
                add_action('init', array('permalinks_optimizer', 'rebuild_permalinks'));
                update_option("need_fix_permalinks", 0);
            }
            $this->init_groups();
        }

        public function init_groups($disable_cache = false)
        {
            if(empty($this->groups) || $disable_cache){
                global $wpdb;
                $this->groups  = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'navigation_groups');
            }
        }

        public function list_groups($active = false)
        {
            $this->init_groups();
            if(!$active){
                $active = $this->groups[0]->ID;
            }
            
            $result = '';
            if (is_array($this->groups) && !empty($this->groups)) {
                foreach ($this->groups as $key => $group) {
                    $result .= '<li class="navigation-group '.($group->ID==$active ? 'active' : '').'" id="navigation-group-'.$group->ID.'"><a href="#selectgroup" onclick="selectNavigationGroup('.$group->ID.'); return false;">'.$group->name.' [id:'.$group->ID.']</a><span onclick="return deleteNavigationGroup('.$group->ID.')">'.__('Delete').'</span></li>';
                }
                reset($this->groups);
            }
            return $result;
        }

        public function dropdown_groups($args = '')
        {
            $result   = '';
            $defaults = array(
                'name'        => 0,
                'as_value'    => 'ID',
                'show_option_none' => __('None'),
                'selected'  => 0
            );
            $args     = wp_parse_args( $args, $defaults );

            if(empty($this->groups)){
                $this->init_groups();
            }
            

            
            if (is_array($this->groups) && !empty($this->groups)) {
                foreach($this->groups as $key=>$group) {
                    $result .= '<option '.($args['selected'] == $group->{$args['as_value']} ? ' selected="selected" ' : '').' value='.$group->{$args['as_value']}.'>'.$group->name.'</option>';
                }
                reset($this->groups);
                $result = '<select name="'.$args['name'].'"><option value="">'.$args['show_option_none'].'</option>'.$result.'</select>';
            }
            return $result;
        }

        public function delete_group ($ID = '')
        {
            global $wpdb;
            $result = null;
            if($ID){
                $result = $wpdb->query('DELETE FROM '.$wpdb->prefix.'navigation_groups WHERE `ID` = '.$ID);
                
                if($result){
                    $this->init_groups();
                    $wpdb->query('DELETE FROM '.$wpdb->prefix.'navigation WHERE `group` = '.$ID);
                }
            }
            return $result;
        }
        
        public function init_menu()
        {
            add_management_page(__('Navigation'), __('Navigation'), 'level_8', navigation::$abspath.'/settings.php');
        }
        
        public function admin_head()
        {
            echo '<link type="text/css" rel="stylesheet" href="'.navigation::$url.'/style.css" />';
        }

        public function ajax_request()
        {
            if(isset($_REQUEST['nav_request'])){
                include(navigation::$abspath.'/ajax.php');
                exit();
            }
        }

        private function get_ancestors($item, $ancestors = array())
        {
            if ($item->parent) {
            }
        }

        public function get_group($group)
        {
            $this->init_groups();
            $result    = null;
            $search_by = '';
            
            if ($search_group = intval($group)) {
                $search_by = 'ID';
            } elseif($search_group = trim(strval($group))) {
                $search_by = 'name';
            }
            if ($search_by && is_array($this->groups) && !empty($this->groups)) {
                foreach ($this->groups as $group) {
                    if ($group->{$search_by} == $search_group) {
                        $result = $group;
                    }
                }
                reset($this->groups);
            }
            return $result;
        }

        public function get_items($args = '')
        {
            global $wpdb;
            $defaults = array(
                'group'        => (int) $this->groups[0]->ID,
                'show_inner'=> 0,
                'level'        => 0,
                'depth'        => 0
            );
            
            $args          = wp_parse_args( $args, $defaults );
            $args['group'] = $this->get_group($args['group'])->ID;

            $q = '
                SELECT *,
                CASE WHEN `custom_title` != 1 THEN  
                    CASE 
                        WHEN 
                            `type` = "category" THEN (SELECT name FROM '.$wpdb->terms.' WHERE term_id = `value`) 
                        WHEN
                            `type` = "page" THEN (SELECT post_title FROM '.$wpdb->posts.' WHERE ID = `value`) 
                    END
                ELSE `name`
                END 
                AS `display_name`,
                CASE 
                    WHEN 
                        `type` = "category" OR `type` = "page" THEN (SELECT `url` FROM '.$wpdb->prefix.'navigation_permalinks links WHERE `object_ID` = `value` AND links.`type` = items.`type` LIMIT 1) 
                    ELSE
                        `value`
                END
                AS `url`
                FROM '.$wpdb->prefix.'navigation items
                WHERE `group` IN ('.$args['group'].')
                ORDER BY `order`, `name`
            ';

            $result = $wpdb->get_results($q);
            $children_elements = array();
            if ($args['show_inner']):
                foreach ($result as $key=>$item):
                    if ($item->show_child) {
                        switch ($item->type) {
                            case('page'):
                                $sub_pages = get_pages('child_of='.$item->value.'&exclude='.$item->exclude);
                                foreach ($sub_pages as $page) {
                                    $new_item                 = new stdClass();
                                    $new_item->ID            = $page->ID.'-inner-page-item';
                                    $new_item->name            = $page->post_title;
                                    $new_item->display_name    = $page->post_title;
                                    $new_item->value        = $page->ID;
                                    $new_item->custom_title = 0;
                                    $new_item->type            = $item->type;
                                    $new_item->group        = $item->group;
                                    $new_item->show_child    = 0;
                                    $new_item->order        = 0;
                                    
                                    if ($item->value != $page->post_parent) {
                                        $new_item->parent    = $page->post_parent.'-inner-page-item';
                                    } else {
                                        $new_item->parent    = $item->ID;
                                    }
                                    $children_elements[]    = $new_item;
                                }
                                break;
                            case('category'):
                                $sub_categories = get_categories('child_of='.$item->value.'&exclude='.$item->exclude);
                                foreach($sub_categories as $category){
                                    
                                    $new_item = null;
                                    $new_item->ID            = $category->term_id.'-inner-cat-item';
                                    $new_item->name            = $category->name;
                                    $new_item->display_name    = $category->name;
                                    $new_item->value        = $category->term_id;
                                    $new_item->custom_title = 0;
                                    $new_item->type            = $item->type;
                                    $new_item->group        = $item->group;
                                    $new_item->parent        = $item->ID;
                                    $new_item->show_child    = 0;
                                    $new_item->order        = 0;
                                    
                                    $children_elements[]    = $new_item;
                                }
                                break;
                        }
                    }
                endforeach;
            endif;

            $result     = array_merge($result, $children_elements);
            $collection = &$this->items[$args['group']];
            $collection = array();

            foreach ($result as $key=>$item) {
                if (!isset($collection[$item->ID])) {
                    $collection[$item->ID] = new navigation_item($item);
                    $collection[$item->ID]->extends = json_decode(stripslashes($result[$key]->extends));
                }
                
            }
            $out = array();
            foreach ($collection as $ID=>$item) {
                if ($item->parent) {
                    $collection[$item->parent]->set_child($item);
                } else {
                    $out[] = &$collection[$ID];
                }
            }
            return $out;
        }

        public function list_items($args = '', $echo = true, $depth = 0, $items_array = null)
        {
            if ($items_array) {
                $items = $items_array;
            } else {
                $items = $this->get_items($args);
            }
            $html      = '';
            foreach ($items as $item) {
                $html .= '<li class="navigation-item" id="navigation-item-'.$item->ID.'">'.(!empty($item->child) ? '<span class="handler" onclick="openclose(this);">&nbsp;</span>' : '' ).'<a href="#selectitem" onclick="selectitem('.$item->ID.')">'.$item->display_name.'</a>';
                if (!empty($item->child)) {
                    $html .= '<ul class="sub-level level-'.($depth+1).'">'.$this->list_items($args, false, ($depth+1), $item->child).'</ul>';
                }
                $html .= '</li>';
            }
            if ($echo) {
                echo $html;
                return null;
            }
            return $html;
        }
        
        public function dropdawn_items($args = '', $echo = true, $level = 0, $items_array = null, $parent_css_class = array())
        {
            $defaults = array(
                'group'        => $this->groups[0]->ID,
                'name'        => 'item',
                'as_value'    => 'ID',
                'show_option_none' => '',
                'show_select_tag' => 1,
                'exclude' => ''
            );
            
            if ($items_array) {
                $items = $items_array;
            } else {
                $items = $this->get_items($args);
            }
            $parse_args    = wp_parse_args( $args, $defaults );
            $exclude_items = explode(',', str_replace(' ', '', $parse_args['exclude']));
            $html          = '';

            if (!$level && $parse_args['show_option_none']) {
                $html    .= '<option value="">'.$parse_args['show_option_none'].'</option>';
            }

            foreach ($items as $item) {
                if (in_array($item->ID, $exclude_items)) {
                    continue;
                }
                $html .= '<option ' . ( empty($parent_css_class) ? '' : 'class="child-of-'.implode('-', $parent_css_class).'-"' ) . ' value="'.$item->{$parse_args['as_value']}.'">'.str_repeat('&mdash;', $level).' '.str_replace("'", '&prime;', $item->display_name).'</option>';
                if (!empty($item->child)) {
                    $html .= $this->dropdawn_items($args, false, $level+1, $item->child, array_merge(array($item->ID), $parent_css_class));
                }
            }

            if (!$level && $parse_args['show_select_tag']) {
                $html = '<select id="'.preg_replace('/[^\w\d_\-]/', '', $parse_args['name']).'" name="'.$parse_args['name'].'">'.$html.'</select>';
            } else if ($level) {
                //$html = '<optgroup class="child" id="child-'.$item->parent.'">'.$html.'</optgroup>';
            }
            if ($echo) {
                echo $html;
                return null;
            }
            return $html;
        }

        public function delete_items($IDs)
        {
            global $wpdb;
            if ($result = $wpdb->query('DELETE FROM '.$wpdb->prefix.'navigation WHERE `ID` IN('.$IDs.')')) {
                $childs = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'navigation WHERE `parent` IN('.$IDs.')');
                if (!empty($childs)) {
                    $childIDs = array();
                    foreach ($childs as $child) {
                        $childIDs[] = $child->ID;
                    }
                    $this->delete_items(implode(',', $childIDs));
                }
            }
            return $result;
        }

        public function delete_item_by_value($value = '', $type = '')
        {
            if($value && (string) $type){
                global $wpdb;
                $items     = $wpdb->get_results('SELECT `ID` FROM '.$wpdb->prefix.'navigation WHERE `value` = "'.$value.'" AND `type` = "'.$type.'"');
                $to_delete = array();
                if (!empty($items)):
                    foreach ($items as $item) {
                        $to_delete[] = $item->ID;
                    }
                    return $this->delete_items(implode(',', $to_delete));
                endif;
            }
            return false;
        }

        private function prepare_item($item = array())
        {
            $types = array(
                'name'            =>'string',
                'value'            =>'string',
                'custom_title'    =>'int',
                'type'            =>'string',
                'group'            =>'int',
                'parent'        =>'int',
                'show_child'    =>'int',
                'exclude'        =>'string',
                'depth'            =>'int',
                'order'            =>'int',
                'extends'        =>'string'
            );
            
            foreach ($item as $field=>$value) {
                if (isset($types[$field])) {
                    switch ($types[$field]) {
                        case('string'):
                            $item[$field] = trim(addslashes( (string) $value));
                            break;
                        case('int'):
                            $item[$field] = (int)    $value;
                            break;
                        case('float'):
                            $item[$field] = (float) $value;
                            break;
                    }
                }
            }
            return $item;
        }

        public function add_item($item)
        {
            global $wpdb;
            $item = $this->prepare_item($item);
            $item['order'] = (int) 1 + $wpdb->get_var('SELECT MAX(`order`) FROM '.$wpdb->prefix.'navigation WHERE `parent` = '.($item['parent'] ? $item['parent'] : 0).' AND `group` = '.$item['group']);
            
            if ($wpdb->insert($wpdb->prefix.'navigation', $item)) {
                $item['ID'] = $wpdb->insert_id;
                switch($item['type']):
                    case('page'):
                        permalinks_optimizer::update_page_link($item['value']);
                        break;
                    case('category'):
                        permalinks_optimizer::update_category_link($item['value']);
                        break;
                endswitch;
                return $item;
            }
            return false;
        }

        public function update_item($item, $where = array())
        {
            global $wpdb;
            $item            = $this->prepare_item($item);
            $previous_parent = $wpdb->get_var('SELECT `parent` FROM '.$wpdb->prefix.'navigation WHERE `ID` = '.$_REQUEST['ID']);
            if ((int) $previous_parent != (int) $item['parent']) {
                $item['order'] = 1 + $wpdb->get_var('SELECT MAX(`order`) FROM '.$wpdb->prefix.'navigation WHERE `parent` = '.($item['parent'] ? $item['parent'] : 0).' AND `group` = '.$item['group']);
            }

            if ($wpdb->update($wpdb->prefix.'navigation', $item, $where)) {
                switch($item['type']):
                    case('page'):
                        permalinks_optimizer::update_page_link($item['value']);
                        break;
                    case('category'):
                        permalinks_optimizer::update_category_link($item['value']);
                        break;
                endswitch;
                return $item;
            }
            return false;
        }
    }

    navigation::$abspath    = dirname(__FILE__);
    navigation::$url        = get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__));

    $navigation = new navigation();
}
    
    class permalinks_optimizer
    {
        public static $page_updated        = array();
        public static $category_updated = array();
        
        function __construct(){
            
        }

        static function update_page_link($page_ID)
        {
            global $wpdb;
            $pages_to_update = array($page_ID);
            $sub_pages = get_pages('child_of='.$page_ID);
            if (!empty($sub_pages)) {
                foreach ($sub_pages as $page) {
                    $pages_to_update[] = $page->ID;
                }
            }
            foreach ($pages_to_update as $page_ID) {
                if (!isset(permalinks_optimizer::$page_updated[$page_ID]) && !permalinks_optimizer::$page_updated[$page_ID]) {
                    if ($wpdb->get_var('SELECT COUNT(*) FROM '.$wpdb->prefix.'navigation_permalinks WHERE `type`="page" AND `object_ID` = '.$page_ID)) {
                        $wpdb->update($wpdb->prefix.'navigation_permalinks', array('url'=>get_page_link($page_ID)), array('type' => 'page', 'object_ID'=>$page_ID));
                    } else {
                        $wpdb->insert($wpdb->prefix.'navigation_permalinks', array('url'=>get_page_link($page_ID), 'type' => 'page', 'object_ID'=>$page_ID));
                    }
                    permalinks_optimizer::$page_updated[$page_ID] = true;
                }
            }
        }

        static function update_category_link($category_ID)
        {
            global $wpdb;
            $categories_to_update    = array($category_ID);
            $sub_categories          = get_categories('hide_empty=0&child_of='.$category_ID);
            
            if (!empty($sub_categories)) {
                foreach ($sub_categories as $category) {
                    $categories_to_update[] = $category->term_id;
                }
            }

            foreach ($categories_to_update as $cat_ID) {
                if (!isset(permalinks_optimizer::$category_updated[$cat_ID]) && !permalinks_optimizer::$category_updated[$cat_ID]) {
                    if ($wpdb->get_var('SELECT COUNT(*) FROM '.$wpdb->prefix.'navigation_permalinks WHERE type="category" AND `object_ID` = '.$cat_ID)) {
                        $wpdb->update($wpdb->prefix.'navigation_permalinks', array('url'=>get_category_link($cat_ID)), array('type' => 'category', 'object_ID'=>$cat_ID));
                    } else {
                        $wpdb->insert($wpdb->prefix.'navigation_permalinks', array('url'=>get_category_link($cat_ID), 'type' => 'category', 'object_ID'=>$cat_ID));
                    }
                    permalinks_optimizer::$category_updated[$cat_ID] = true;
                }
            }
        }

        static function delete_permalinks($IDs)
        {
            global $wpdb;
            return $wpdb->query('DELETE FROM '.$wpdb->prefix.'navigation_permalinks WHERE `object_ID` IN('.$IDs.')');
        }

        static function on_save_page($page_ID)
        {
            if ($_POST['post_type'] == 'page') {
                permalinks_optimizer::update_page_link($page_ID);
            }
        }

        static function rebuild_permalinks()
        {
            global $wpdb;
            wp_cache_delete( 'permalinks_optimizer', 'options' );
            $pages = get_all_page_ids();
            
            foreach ($pages as $page) {
                permalinks_optimizer::update_page_link($page);
            }

            $categories = get_all_category_ids();
            foreach ($categories as $category) {
                permalinks_optimizer::update_category_link($category);
            }
            permalinks_optimizer::$page_updated        = array();
            permalinks_optimizer::$category_updated = array();
        }
        
        static function delete_page_permalink($page_IDs)
        {
            global $wpdb;
            $result = $wpdb->query('DELETE FROM '.$wpdb->prefix.'navigation_permalinks WHERE `object_ID` IN ('.$page_IDs.') AND `type` = "page"');
            if ($result) {
                if (class_exists('navigation')) {
                    global $navigation;
                    $navigation->delete_item_by_value($page_IDs, 'page');
                }
            }
            return $result;
        }

        static function delete_category_permalink($cat_IDs)
        {
            global $wpdb;
            $result = $wpdb->query('DELETE FROM '.$wpdb->prefix.'navigation_permalinks WHERE `object_ID` IN ('.$cat_IDs.') AND `type` = "category"');
            if ($result) {
                if (class_exists('navigation')) {
                    global $navigation;
                    $navigation->delete_item_by_value($cat_IDs, 'category');
                }
            }
            return $result;
        }
    }
    
    if (!is_admin()) {
        include(navigation::$abspath.'/client.functions.php');
    }
    
    include (navigation::$abspath.'/widget.php');
    
    
    function navigation_css()
    {
        echo '<link rel="stylesheet" href="'.navigation::$url.'/style-fronted.css'.'" type="text/css" media="screen" />';
    }
    
    // Future-friendly json_encode
    if( !function_exists('json_encode') ) {
        include_once(navigation::$abspath.'/lib/JSON.php');
        function json_encode($data) {
            $json = new Services_JSON();
            return( $json->encode($data) );
        }
    }

    // Future-friendly json_decode
    if( !function_exists('json_decode') ) {
        include_once(navigation::$abspath.'/lib/JSON.php');
        function json_decode($data) {
            $json = new Services_JSON();
            return( $json->decode($data) );
        }
    }
    register_activation_hook(__FILE__, array('navigation', 'install'));
    register_activation_hook(__FILE__, array('permalinks_optimizer', 'rebuild_permalinks'));
    
    add_action('admin_menu', array('navigation', 'init_menu'));
    add_action('admin_init', array('navigation', 'ajax_request'));
    add_action('admin_head', array('navigation', 'admin_head'));
    add_action('update_option_permalink_structure', create_function('', 'update_option("need_fix_permalinks", 1);'));
    add_action('update_option_home', create_function('', 'update_option("need_fix_permalinks", 1);'));
    add_action('save_post', array('permalinks_optimizer', 'on_save_page'));
    add_action('delete_post', array('permalinks_optimizer', 'delete_page_permalink'));
    add_action('created_category', array('permalinks_optimizer', 'update_category_link'));
    add_action('edited_category', array('permalinks_optimizer', 'update_category_link'));
    add_action('delete_category', array('permalinks_optimizer', 'delete_category_permalink'));
    add_action('wp_head', 'navigation_css');
    
    $locale = get_locale();
    $mofile = navigation::$abspath . "/langs/$locale.mo";

    load_textdomain('default', $mofile);
