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
<div class="wrap">
<h2><?php _e('Navigation manager')?></h2>
<script type="text/javascript" src="<?php echo navigation::$url . '/common.js.php';?>"></script>
<script type="text/javascript" src="<?php echo navigation::$url . '/shift.js';?>"></script>
<script type="text/javascript" src="<?php echo navigation::$url . '/openclose.js';?>"></script>
<script type="text/javascript" src="<?php echo get_option('siteurl')?>/wp-includes/js/jquery/jquery.form.js"></script>
<form class="clear" action="" method="post" style="margin-bottom:0.5em">
    <input type="hidden" name="need_fix_permalinks" value="1"/>
    <input type="submit" class="button" value="<?php _e('Rebuild navigation links');?>" title="Please use if links not correct displayed, after any changes"/>
</form>
<div class="nav-info">
    <p class="indicator alignleft"><img align="absmiddle" src="<?php echo navigation::$url;?>/images/indicator.gif" alt="" /><?php _e('Loading');?>.</p>
    <p class="message alignright">&nbsp;</p>
</div>

<div class="navigation-manager-container clear">
<table class="navigation-manager" border="0" cellspacing="0" cellpadding="0">
    <tbody valign="top">
        <tr>
            <td width="33%" class="first">
                <h3><?php _e('Navigation groups')?></h3>
                <div class="options">
                    <button class="button" onclick="return addNavigationGroup(prompt('<?php _e('Group Name');?>:'));"><?php _e('add new')?></button>
                </div>
                <ul id="navigation-groups">
                <?php echo $navigation->list_groups();?>
                </ul>
            </td>
            <td width="33%" class="group_options">
                <h3><?php _e('Group options')?></h3>
                <ul class="tabs">
                    <li><a href="#group_items"><?php _e('Items');?></a></li>
                    <li>&nbsp;|&nbsp;</li>
                    <li><a href="#group_settings"><?php _e('Settings');?></a></li>
                </ul>
                <div id="group_items" class="section act">
                    
                    <div class="options">
                        <button class="button addNewItem"><?php _e('add new')?></button>
                    </div>
                    <ul id="navigation-items">
                        <?php $navigation->list_items();?>
                    </ul>
                </div>
                <div id="group_settings"  class="section">
                    <?php $method = intval($wpdb->get_var('SELECT `usejs` FROM '.$wpdb->prefix.'navigation_groups WHERE `ID` = '.$navigation->groups[0]->ID));?>
                    <p>
                        <label>
                            <input type="checkbox" <?php $method ? print 'checked="checked"' : ''?> name="usejs" /> 
                            <span><?php _e('Use JavaScript to open/close tree, in client section.');?></span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <span class="alignleft">Method:&nbsp;</span>
                            <select name="usejs_method">
                                <option value="1" <?php $method == 1 ? print 'selected="selected"' : '';?>><?php _e('Tree');?></option>
                                <option value="2" <?php $method == 2 ? print 'selected="selected"' : '';?>><?php _e('Drop down');?></option>
                            </select>
                        </label>
                    </p>
                </div>
            </td>
            <td width="33%" class="last">
                <h3 class="form-title"><?php _e('Add new item')?></h3>
                <?php include(navigation::$abspath.'/edit-form.php');?>
            </td>
        </tr>
    </tbody>
</table>
</div>
</div>