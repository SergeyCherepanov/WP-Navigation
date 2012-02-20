=== Plugin Name ===
Contributors: markjaquith, mdawaffe
Donate link: http://example.com/
Tags: Navigation, pages, categories, links
Requires at least: 1.0
Tested up to: 2.5
Stable tag: 1.0

Navigation manager for wordpress 2.5 and higher

== Description ==

The plugin allows you to create custom navigation to your blog. 


== Installation ==


1. Upload folder `navigation` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Choose `Manage -> navigation` to create you navigation group
4. Place `<?php wp_navigation(); ?>` in your templates

== Usage ==

<?php wp_navigation('arguments'); ?> 

Example:

<?php 
	if(function_exists('wp_navigation')){
		wp_navigation('group=1&title_li=My navigation&echo=1'); 
	}
?>
Parameters:
'group'		- Name or ID navigation group.
'title_li'	- Set the text and style of the Page list's heading.
'echo'		- Toggles the display of the generated list of links or return the list as an HTML text string to be used in PHP.