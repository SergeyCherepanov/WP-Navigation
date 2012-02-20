<?php
/**
 * Navigation Manager - Wordpress Plugin.
 *
 * @copyright:    Copyright 2008 Sergey Cherepanov. (http://www.cherepanov.org.ua)
 * @author:       Sergey Cherepanov (sergey@cherepanov.org.ua)
 * @license:      http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE v3.0
 * @date          16.12.08
 */
?>
<script type="text/javascript">
jQuery(
    function($){
        $(document).ready(function() {
                $('#edititem').ajaxForm(function(result) {
                    submitItem(result);
            });
        });
    }
);
</script>
<form action="" method="post" id="edititem" name="edititem">
    <ul class="item-types">
        <li class="page-type">
            <label><input <?php (!isset($item->type) ? print 'checked="checked"' : $item->type == 'page' ? print 'checked="checked"' : '');?> type="radio" name="item[type]" value="page" />
            <?php _e('Page');?>:</label>
            <?php wp_dropdown_pages('selected='.($item->type == 'page' ? $item->value : '').'&name=item[page]&show_option_none='.__('Please select'))?>
        </li>
        <li class="category-type">
            <label><input <?php ($item->type == 'category'  ? print 'checked="checked"' : '');?> type="radio" name="item[type]" value="category" />
            <?php _e('Category');?>:</label>
            <?php wp_dropdown_categories('hide_empty=0&orderby=name&hierarchical=1&selected='.($item->type == 'category' ? $item->value : '').'&name=item[category]&show_option_none='.__('Please select'))?>
            <br class="clear"/>
        </li>
        <li class="custom-type">
            <label><input <?php ($item->type == 'custom' ? print 'checked="checked"' : '');?> type="radio" name="item[type]" value="custom"/>
            <?php _e('Custom link');?>:</label>
            <input class="text" type="text" name="item[custom]" value="<?php ($item->type == 'category' ? print $item->value : '');?>" />
        </li>
    </ul>
    <p class="show-child">
        <label>
            <input type="checkbox" value="1" name="item[show_child]" <?php if($item->show_child){echo ' checked="checked" ';}?> />
            <?php _e('Show inner elements')?>
        </label>
    </p>
    <p class="custom-field custom-title">
        <label><?php _e('Custom title');?>:</label><input class="text" type="text" name="item[custom_title]" value="<?php ( $item->custom_title ? print $item->name : '');?>" /></p>
    <p class="custom-field css-class">
        <label><?php _e('CSS class');?>:</label><input class="text" type="text" name="item[extends][css]" value="<?php echo $item->extends['css'];?>" /></p>
    <p class="custom-field link-title">
        <label><?php _e('Link title');?>:</label><input class="text" type="text" name="item[extends][link_title]" value="<?php echo $item->extends['link_title'];?>" /></p>
    <p class="custom-field parent">
        <label><?php _e('Parent')?>:</label>
        <?php $navigation->dropdawn_items('name=item[parent]&show_option_none='.__('None'));?>
    </p>

    <input type="hidden" name="nav_request" value="1" />
    <input type="hidden" name="action" id="action" value="add_item" />
    <input type="hidden" name="ID" id="ID" value="<?php echo ($item->ID ? $item->ID : '')?>" />
    <input type="hidden" name="item[group]" id="itemgroup" value="<?php echo ($item->group ? $item->group : current($navigation->groups)->ID);?>" />

    <p class="btn edit" style="display:none">
        <button class="button-secondary up"><?php _e('Up')?></button>
        <button class="button-secondary down"><?php _e('Down')?></button>
        <input class="button save" type="submit" value="<?php _e('Save')?>" />
        <input class="button delete" type="button" value="<?php _e('Delete')?>" />
    </p>
    <p class="btn add">
        <input class="button" type="submit" value="<?php _e('Add new item')?>" />
    </p>
</form>