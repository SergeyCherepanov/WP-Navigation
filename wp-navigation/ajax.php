<?php
/**
 * Navigation Manager - Wordpress Plugin.
 *
 * @copyright:    Copyright 2008 Sergey Cherepanov. (http://www.cherepanov.org.ua)
 * @author:       Sergey Cherepanov (sergey@cherepanov.org.ua)
 * @license:      http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE v3.0
 * @date          16.12.08
 */

if(!class_exists('wpdb')){
    include_once('../../../wp-config.php');
}
include_once(ABSPATH . '/wp-admin/admin.php');

if(!current_user_can('level_8')){
    exit('Acces denied!');
}

global $wpdb, $navigation;
switch($_REQUEST['action']):
    case('usejs'):
        if ($_REQUEST['group_ID']) {
            eval('$use = '.$_REQUEST['value'].';');
            if($use){
                $value = $_REQUEST['method'];
            }else{
                $value = 0;
            }
            $wpdb->update($wpdb->prefix.'navigation_groups', array('usejs'=>(int)$value), array('ID'=>(int)$_REQUEST['group_ID']));
        }
        break;
    case ('add_group'):
        if ($_REQUEST['group_name']) {
            $wpdb->insert($wpdb->prefix.'navigation_groups', array('name'=>$_REQUEST['group_name']));
            $navigation->init_groups(true);
        }
        
        
        if (is_array($navigation->groups) && !empty($navigation->groups)) {
            echo '{group_id:'.$wpdb->insert_id.',html:\''.$navigation->list_groups().'\'}';
        }
        break;
    case ('delete_group'):
        if($navigation->delete_group($_REQUEST['group_id'])){
            echo $_REQUEST['group_id'];
        }
        break;
    case ('add_item'): case('edit_item'):
        switch($_REQUEST['item']['type']){
            case('page'):
                
                if(!$_REQUEST['item']['page']){
                    exit;
                }
                
                $page = get_page($_REQUEST['item']['page']);
                $item = array(
                    'name'            => ($_REQUEST['item']['custom_title'] ? $_REQUEST['item']['custom_title'] : $page->post_title),
                    'value'            => $page->ID,
                    'custom_title'    => ($_REQUEST['item']['custom_title'] ? 1 : 0),
                    'type'            => 'page',
                    'group'            => $_REQUEST['item']['group'],
                    'parent'        => $_REQUEST['item']['parent'],
                    'show_child'    => $_REQUEST['item']['show_child']
                );
                break;
            case('category'):
                if(!$_REQUEST['item']['category']){
                    exit;
                }
                $category = get_category($_REQUEST['item']['category']);
                $item = array(
                    'name'            => ($_REQUEST['item']['custom_title'] ? $_REQUEST['item']['custom_title'] : $category->name),
                    'value'            => $category->term_id,
                    'custom_title'    => ($_REQUEST['item']['custom_title'] ? 1 : 0),
                    'type'            => 'category',
                    'group'            => $_REQUEST['item']['group'],
                    'parent'        => $_REQUEST['item']['parent'],
                    'show_child'    => $_REQUEST['item']['show_child']
                );
                break;
            case('custom'):
                $item = array(
                    'name'            => ($_REQUEST['item']['custom_title'] ? $_REQUEST['item']['custom_title'] : 'untitled'),
                    'value'            => $_REQUEST['item']['custom'],
                    'custom_title'    => 1,
                    'type'            => 'custom',
                    'group'            => $_REQUEST['item']['group'],
                    'parent'        => $_REQUEST['item']['parent'],
                    'show_child'    => 0
                );
                break;
        }
        if ($_REQUEST['item']['extends']) {
            $item['extends'] = json_encode($_REQUEST['item']['extends']);
        }
        
        if ($item['name'] && $item['group']) {
            if ($_REQUEST['action'] == 'add_item') {
                if($insert_item = $navigation->add_item($item)){
                    echo '{action:\'add\',item:\'<li class="navigation-item" id="navigation-item-', $insert_item['ID'], '"><a href="#" onclick="selectitem(', $insert_item['ID'], ');return false">',$insert_item['name'],'</a></li>\',parents:\''.$navigation->dropdawn_items('show_select_tag=0&group='.$insert_item['group'].'&name=item[parent]&show_option_none=None', false).'\'}';
                }
            } else if($_REQUEST['action'] == 'edit_item' && ($item['parent'] != $_REQUEST['ID'])) {
                if ( $result = $navigation->update_item($item, array('ID'=>$_REQUEST['ID']))) {
                    $result['ID']    = $_REQUEST['ID'];
                    $result['action']    = 'update';
                    $result['parents'] = $navigation->dropdawn_items('show_select_tag=0&group='.$item['group'].'&name=item[parent]&show_option_none=None', false);
                    echo json_encode(array_merge($item, $result));
                }
                
            }
        }
        break;
    case ('get_items'):
        $usejs = $wpdb->get_var('SELECT `usejs` FROM '.$wpdb->prefix.'navigation_groups WHERE `ID` = '.(int)$_REQUEST['group']);
        echo '{usejs:'.((int)$usejs).',items:\''.addslashes($navigation->list_items('group='.$_REQUEST['group'], false)).'\',parents:\''.addslashes($navigation->dropdawn_items('show_select_tag=0&group='.$_REQUEST['group'].'&name=item[parent]&show_option_none=None', false)).'\'}';
        break;
    case ('delete_item'):
        if ($navigation->delete_items($_REQUEST['item_id'])) {
            $result = array();
            $result['ID']        = $_REQUEST['item_id'];
            $result['parents']    = $navigation->dropdawn_items('show_select_tag=0&group='.$_REQUEST['group_id'].'&name=item[parent]&show_option_none=None', false);
            echo json_encode($result);
        }
        break;
    case ('item_to_edit'):
        $item = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'navigation WHERE `ID` = '.$_REQUEST['item_id']);
        if (!($item->extends = json_decode(stripslashes($item->extends)))) {
            $item->extends = '';
        }
        echo json_encode($item);
        break;
    case 'item_up':
        $current    = $wpdb->get_row('SELECT `ID`, `order`, `group`, `parent` FROM '.$wpdb->prefix.'navigation WHERE `ID`='.$_REQUEST['item_id']);
        $upper        = $wpdb->get_row('SELECT `ID`, `order` FROM '.$wpdb->prefix.'navigation WHERE `parent` = '.$current->parent.' AND `group` = '.$current->group.' AND `order`<'.$current->order.' ORDER BY `order` DESC LIMIT 1');
        /* ------------------ */
        if(!empty($upper)){
            $order = $upper->order;
        }else{
            break;
        }
        $result = $wpdb->update($wpdb->prefix.'navigation', array('order'=>(int)$order), array('ID'=>$current->ID));
        /* ------------------ */
        if($result){
            $ID = $upper->ID;
            $order = $current->order;
            $result = $wpdb->update($wpdb->prefix.'navigation', array('order'=>(int)$order), array('ID'=>$ID));
        }
        $parents = $navigation->dropdawn_items('show_select_tag=0&group='.$current->group.'&name=item[parent]&show_option_none=None', false);
        /* ------------------ */
        echo json_encode($parents);
        break;
    case 'item_down':
        $current    = $wpdb->get_row('SELECT `ID`, `order`, `group`, `parent` FROM '.$wpdb->prefix.'navigation WHERE `ID`='.$_REQUEST['item_id']);
        $lower        = $wpdb->get_row('SELECT `ID`, `order` FROM '.$wpdb->prefix.'navigation WHERE `parent` = '.$current->parent.' AND `group` = '.$current->group.' AND `order`>'.$current->order.' ORDER BY `order` ASC LIMIT 1');
        /* ------------------ */
        $ID = $current->ID;
        if(!empty($lower)){
            $order = $lower->order;
        }else{
            break;
        }
        $result = $wpdb->update($wpdb->prefix.'navigation', array('order'=>(int)$order), array('ID'=>$ID));
        /* ------------------ */
        if($result){
            $ID = $lower->ID;
            $order = $current->order;
            $result = $wpdb->update($wpdb->prefix.'navigation', array('order'=>(int)$order), array('ID'=>$ID));
        }
        $parents = $navigation->dropdawn_items('show_select_tag=0&group='.$current->group.'&name=item[parent]&show_option_none=None', false);
        /* ------------------ */
        echo json_encode($parents);
        break;
    default:
        echo 'false';
        break;
endswitch;

