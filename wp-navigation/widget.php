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
 * @param array $args
 * @param int $widget_args
 * @return mixed
 */
function wp_widget_navigation($args, $widget_args = 1)
{
    include_once(navigation::$abspath.'/client.functions.php');
    extract( $args, EXTR_SKIP );
    if ( is_numeric($widget_args) )
        $widget_args = array( 'number' => $widget_args );
    $widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
    extract( $widget_args, EXTR_SKIP );

    $options = get_option('widget_navigation');

    if ( !isset($options[$number]) )
        return;

    $title = apply_filters('widget_title', $options[$number]['title']);
    $html = '<div id="navigation-widget-'.$number.'" class="navigation-widget '.$options[$number]['class'].'">'.wp_navigation('group='.$options[$number]['group'].'&echo=0').'</div>';
    
    
    echo $before_widget, $before_title, $title, $after_title, $html, $after_widget;
    
}

/**
 * @param $widget_args
 */
function wp_widget_navigation_control($widget_args)
{
    global $wp_registered_widgets, $navigation;;
    static $updated = false;

    if ( is_numeric($widget_args) )
        $widget_args = array( 'number' => $widget_args );
    $widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
    extract( $widget_args, EXTR_SKIP );

    $options = get_option('widget_navigation');
    if ( !is_array($options) )
        $options = array();

    if ( !$updated && !empty($_POST['sidebar']) ) {
        $sidebar = (string) $_POST['sidebar'];

        $sidebars_widgets = wp_get_sidebars_widgets();
        if ( isset($sidebars_widgets[$sidebar]) )
            $this_sidebar =& $sidebars_widgets[$sidebar];
        else
            $this_sidebar = array();

        foreach ( $this_sidebar as $_widget_id ) {
            if ( 'wp_widget_navigation' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
                $widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
                if ( !in_array( "navigation-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
                    unset($options[$widget_number]);
            }
        }

        foreach ( (array) $_POST['widget-navigation'] as $widget_number => $widget_navigation ) {
            if ( !isset($widget_navigation['group']) && isset($options[$widget_number]) ) // user clicked cancel
                continue;
            $title = strip_tags(stripslashes($widget_navigation['title']));
            $class = strip_tags(stripslashes($widget_navigation['class']));
            if ( current_user_can('unfiltered_html') ){
                $group = stripslashes( $widget_navigation['group'] );
            }
            else{
                $group = stripslashes(wp_filter_post_kses( $widget_navigation['group'] ));
            }
            $options[$widget_number] = compact( 'title', 'group', 'class');
        }

        update_option('widget_navigation', $options);
        $updated = true;
    }

    if ( -1 == $number ) {
    
        $title = '';
        $group = current($navigation->groups)->ID;
        $number = '%i%';
        $css_class = '';
    } else {
        $title = attribute_escape($options[$number]['title']);
        $group = format_to_edit($options[$number]['group']);
        $css_class = $options[$number]['class'];
    }
?>
        <p>
            <label><?php _e('Title');?></label>
            <input class="widefat" id="navigation-title-<?php echo $number; ?>" name="widget-navigation[<?php echo $number; ?>][title]" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label><?php _e('CSS class');?></label>
            <input class="widefat" id="navigation-class-<?php echo $number; ?>" name="widget-navigation[<?php echo $number; ?>][class]" type="text" value="<?php echo $css_class; ?>" />
        </p>
        <p>
            <label><?php _e('Navigation group');?></label>
            <?php echo $navigation->dropdown_groups('show_option_none=Please select group&name=widget-navigation['.$number.'][group]&selected='.$group);?>
        </p>
<?php
}

function wp_widget_navigation_register() {
    if ( !$options = get_option('widget_navigation') )
        $options = array();
    $widget_ops = array('classname' => 'widget_navigation', 'description' => __('Replace standard navigation widget with this one to add custom navigation '));
    $control_ops = array('width' => 400, 'height' => 350, 'id_base' => 'navigation');
    $name = __('Custom navigation');

    $id = false;
    foreach ( array_keys($options) as $o ) {
        // Old widgets can have null values for some reason
        if ( !isset($options[$o]['title']) || !isset($options[$o]['group']) )
            continue;
        $id = "navigation-$o"; // Never never never translate an id
        wp_register_sidebar_widget($id, $name, 'wp_widget_navigation', $widget_ops, array( 'number' => $o ));
        wp_register_widget_control($id, $name, 'wp_widget_navigation_control', $control_ops, array( 'number' => $o ));
    }

    // If there are none, we register the widget's existance with a generic template
    if ( !$id ) {
        wp_register_sidebar_widget( 'navigation-1', $name, 'wp_widget_navigation', $widget_ops, array( 'number' => -1 ) );
        wp_register_widget_control( 'navigation-1', $name, 'wp_widget_navigation_control', $control_ops, array( 'number' => -1 ) );
    }
}
add_action('widgets_init', 'wp_widget_navigation_register');