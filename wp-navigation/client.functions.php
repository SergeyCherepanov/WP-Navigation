<?php
/**
 * Navigation Manager - Wordpress Plugin.
 *
 * @copyright:    Copyright 2008 Sergey Cherepanov. (http://www.cherepanov.org.ua)
 * @author:       Sergey Cherepanov (sergey@cherepanov.org.ua)
 * @license:      http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE v3.0
 * @date          16.12.08
 */

/**
 * Retreive list of navigation items
 *
 * @param array $items
 * @param array $path
 * @param int $level
 * @param bool $usejs
 * @param int $depth
 * @param int $current_depth
 * @return string
 */
function wp_navigation_list_items($items = array(), $path = array(), $level = 0, $usejs = false, $depth = 0, $current_depth = 1)
{
    global $navigation;
    $output    = '';
    $curr_item = null;

    if (is_object($items)) {
        $curr_item = $items;
        $items = $items->child;

    }
    if (is_array($items)) {
        $i = 0;
        foreach ($items as $item) {
            $i++;
            $itemlink = '';
            switch ($item->type) {
                case('page'):
                    if(!$item->url){
                        $itemlink = get_page_link($item->value);
                    }else{
                        $itemlink = $item->url;
                    }

                break;
                case('category'):
                    if(!$item->url){
                        $itemlink = get_category_link($item->value);
                    }else{
                        $itemlink = $item->url;
                    }
                break;
                case('custom'):

                    $itemlink = $item->value;

                break;
            }



            $class_name = ($item->ID == end($path) ? 'current_page_item' : (in_array($item->ID, $path) ? 'current_page_ancestor' : ''));

            if($item === $items[0]){
                $class_name .= ' first ';
            }
            if($item === end($items)){
                $class_name .= ' last ';
            }


            switch(intval($usejs)):
                case(1):
                    $output .= '<li id="navigation-item-'.$item->ID.(($viscount = $navigation->groupVisibleCount[$item->group]) > 1 ? '-'.$viscount : '').'" class="navigation-item '.$class_name.' item-'.$i.' ' .$item->extends->css. '">'.(empty($item->child) ? '' : '<span class="handler '.(in_array($item->ID, $path) ? ' open ' : ' close ').'" onclick="openclose(this)">&nbsp;</span>');
                break;
                case(2):
                    $output .= '<li id="navigation-item-'.$item->ID.(($viscount = $navigation->groupVisibleCount[$item->group]) > 1 ? '-'.$viscount : '').'" class="navigation-item '.$class_name.' item-'.$i.' ' .$item->extends->css. '" '.(empty($item->child) ? '' : ' onmouseover="openclose(this)" onmouseout="openclose(this)" ').'>';
                break;
                default:
                    $output .= '<li id="navigation-item-'.$item->ID.(($viscount = $navigation->groupVisibleCount[$item->group]) > 1 ? '-'.$viscount : '').'" class="navigation-item '.$class_name.' item-'.$i.' ' .$item->extends->css. '">';
                break;

            endswitch;

            $output .= '<a title="'.$item->extends->link_title.'" href="'.$itemlink.'">'.stripslashes($item->display_name).'</a>';

            if(isset($item->child) && !empty($item->child)){
                if($depth && $current_depth >= $depth){
                    $output .= '</li>';
                    continue;
                }
                $output .= wp_navigation_list_items($item, $path, $level+1, $usejs, $depth, $current_depth+1);
            }
            $output .= '</li>';
        }
        if ($level) {
            $output = '<ul class="sub-navigation-group level-'.$level.' '.(($usejs == 0 || $usejs == 1) && in_array($curr_item->ID, $path) ? ' open ' : ' close ').'">'.$output.'</ul>';
        }
    }
    return $output;
}

function wp_navigation($args = '')
{
    global $navigation, $wp_query, $wpdb;

    $defaults = array(
        'group'        => (int) $navigation->groups[0]->ID,
        'title_li'    => '',
        'show_inner'=> 1,
        'echo'         => 1,
        'usejs'     => null,
        'level'        => 0,
        'depth'        => 0
    );

    $parse_args = wp_parse_args($args, $defaults);

    if ($parse_args['usejs'] === null) {
        $parse_args['usejs'] = $wpdb->get_var('SELECT `usejs` FROM '.$wpdb->prefix.'navigation_groups WHERE `name` = "'.$parse_args['group'].'" OR `ID` = '.((int)$parse_args['group']).' LIMIT 1');
    }

    if ($parse_args['usejs']) {
        $usejs = $parse_args['usejs'];
    } else {
        $usejs = false;
    }

    $level = (int) $parse_args['level'];
    $depth = (int) $parse_args['depth'];

    $parse_args['group'];
    $current_group    = (int) $navigation->get_group($parse_args['group'])->ID;

    $output = '';
    $items  = $navigation->get_items($parse_args);
    $url    = $_SERVER['REQUEST_URI'];

    if (!($current_item = $wpdb->get_var('SELECT `ID` FROM '.$wpdb->prefix.'navigation WHERE `value` LIKE "'.$url.'" AND `type` = "custom"  AND  `group` IN ('.$current_group.') ORDER BY `order`, `name` LIMIT 1'))) {
        switch(true){
            case (is_page()):
                $q = 'SELECT `ID` FROM '.$wpdb->prefix.'navigation WHERE `value` = "'.$wp_query->queried_object->ID.'" AND `type` = "page"  AND  `group` IN ('.$current_group.') ORDER BY `order`, `name` LIMIT 1';
                $current_item = $wpdb->get_var($q);
                if (!$current_item && isset($navigation->items[$current_group][$wp_query->queried_object->ID.'-inner-page-item'])) {
                    $current_item = $wp_query->queried_object->ID.'-inner-page-item';
                }
            break;
            case (is_category()):
                $current_item = $wpdb->get_var('SELECT `ID` FROM '.$wpdb->prefix.'navigation WHERE `value` = "'.$wp_query->query_vars['cat'].'" AND `type` = "category"  AND  `group` IN ('.$current_group.') ORDER BY `order`, `name` LIMIT 1');
                if(!$current_item && isset($navigation->items[$current_group][$wp_query->query_vars['cat'].'-inner-cat-item'])){
                    $current_item = $wp_query->query_vars['cat'].'-inner-cat-item';
                }
            break;
        }
    }

    if (isset($navigation->groupVisibleCount[$current_group])) {
        $navigation->groupVisibleCount[$current_group]++ ;
    } else {
        $navigation->groupVisibleCount[$current_group] = 1;
    }

    $path   = array();
    $path[] = $current_item;
    $ID     = $current_item;

    while ($ID = $navigation->items[$current_group][$ID]->parent) {
        $path[]    = $ID;
    }

    $path = array_reverse($path);

    if ($level) {
        if ($current_item) {
            $items = ($navigation->items[$current_group][$current_item]->get_parent_by_level($level-1)->child);
        } else {
            return '';
        }
    }
    switch (intval($usejs)):
        case(1):
        case(2):
            if (!in_array('openclose', $navigation->includedJavaScript)) {
                $output .= '<script type="text/javascript" src="'. navigation::$url . '/openclose.js"></script>';
                $navigation->includedJavaScript[] = 'openclose';
            }
            if ($usejs == 1) {
                $navigation_type = 'tree';
            } else {
                $navigation_type = 'dropdown';
            }
        break;
        default:
            $output .= '';
            $navigation_type = 'default';
        break;
    endswitch;

    $output  .= '<ul id="navigation-group-'.$current_group.(($viscount = $navigation->groupVisibleCount[$current_group]) > 1 ? '-'.$viscount : '').'" class="nav-group nav-group-'.$current_group.' '.$navigation_type.'">%s</ul>';
    $contents = wp_navigation_list_items($items, $path, 0, $usejs, $depth);

    if ($contents) {
        $output = sprintf($output, $contents);
        if($parse_args['title_li']){
            $output = '<li class="wp-navigation">'.$parse_args['title_li'].$output.'</li>';
        }
    } else {
        $output = '';
    }

    if ($parse_args['echo']) {
        return print $output;
    } else {
        return $output;
    }
}
