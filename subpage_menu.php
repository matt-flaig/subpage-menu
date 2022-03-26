<?php
/*
Plugin Name: Subpage Menu (WPBakery Page Builder)
Plugin Shortname: subpage_menu
Description: Displays a menu of a parent pages' subpages, or a child pages' siblings.
Version: 0.1
License: GPLv2 or later
*/

// don't load directly
if (!defined('ABSPATH'))
	die('-1');

class VCSubpageMenuAddonClass
{
	function __construct()
	{
		// We safely integrate with VC with this hook
		add_action('init', array(
			$this,
			'integrateWithVC'
		));
		
		// Use this when creating a shortcode addon
		add_shortcode('bartag', array(
			$this,
			'renderMyBartag'
		));
		
		// Register CSS and JS
		add_action('wp_enqueue_scripts', array(
			$this,
			'loadCssAndJs'
		));
	}
	
	public function integrateWithVC()
	{
		global $uncode_colors, $uncode_colors_w_transp, $uncode_post_types;

		// Check if WPBakery Page Builder is installed
		if (!defined('WPB_VC_VERSION')) {
			// Display notice that WPBakery Page Builder is required
			add_action('admin_notices', array(
				$this,
				'showVcVersionNotice'
			));
			return;
		}
		
		if(!$uncode_colors){
			$uncode_colors = [["", "No colors loaded…"]];
		}
		
		vc_map(array(
			"name" => __("Subpage Menu", 'subpage_menu'),
			"description" => __("Displays a menu of a parent pages' subpages, or a child pages' siblings.", 'subpage_menu'),
			"base" => "bartag",
			"class" => "subpage_menu",
			"controls" => "full",
			"icon" => plugins_url('assets/th.png', __FILE__), // or css class name which you can reffer in your css file later. Example: "subpage_menu_my_class"
			"category" => __('Content', 'js_composer'),
			"params" => array(
				array(
					"type" => "dropdown",
					"heading" => esc_html__("Menu Background Color", 'subpage_menu') ,
					"param_name" => "menu_background_color",
					"description" => esc_html__("Specify a background color for the menu.", 'subpage_menu') ,
					"class" => 'uncode_colors',
					"value" => $uncode_colors,
					'group' => esc_html__('Module', 'subpage_menu') ,
				),
				array(
					"type" => "dropdown",
					"heading" => esc_html__("Text Color", 'subpage_menu') ,
					"param_name" => "text_color",
					"description" => esc_html__("Specify a color for the text links.", 'subpage_menu') ,
					"class" => 'uncode_colors',
					"value" => $uncode_colors,
					'group' => esc_html__('Module', 'subpage_menu') ,
				),
			)
		));
	}
	
	/*
	Shortcode logic how it should be rendered
	*/
	public function renderMyBartag($atts, $content = null)
	{
		global $pagename;
		
		// unpack shortcodes values
		extract( shortcode_atts( array(
			'menu_background_color' => '',
			'text_color' => '',
			'link_hover_color' => ''
		), $atts ) );

		/*query for child pages*/
		global $post;
		
		// check if this is a top level page
		if ($post->post_parent == 0) {
			$requestPostParentID = $post->ID;
		} else {
			$requestPostParentID = $post->post_parent;
		}
		
		query_posts(array(
			'post_parent' => $requestPostParentID,
			'post_type' => 'page',
			'posts_per_page' => -1
		));
		
		// note this was hack-programmed in a couple of minutes. Not something I would recommend using long-term.
		if (have_posts()) {
			$output = '<ul class="subpage_menu' . ($menu_background_color !== '' ?  ' style-'.$menu_background_color.'-bg' : '' ) . '">';
			
			// insert parent page here
			if($post->post_parent == 0){
				$output .= '<li class="subpage_menu_item"><a style="opacity: 1" href="' . get_page_link() . '">' . get_the_title($post) . '</a></li>';
			}else{
				$output .= '<li class="subpage_menu_item"><a href="' . get_page_link($post->post_parent) . '">' . get_the_title($post->post_parent) . '</a></li>';
			}
			
			while (have_posts()) {
				
				the_post();
				$id = get_the_ID();
				$title = get_the_title();
				$link = get_page_link();
				
				if(!empty($post->post_password)){
					continue;
				}
				
				$output .= '<li class="subpage_menu_item"><a ' . ((basename(get_permalink()) == $pagename) ? 'style="opacity: 1"' : '' ) . ' href="' . $link . '">' . $title . '</a></li>';
			}
			
			$output .= '</ul>';
			
			wp_reset_query();
		}
		
		
		
		return $output;
	}
	
	/*
	Load plugin css and javascript files which you may need on front end of your site
	*/
	public function loadCssAndJs()
	{
		wp_register_style('subpage_menu_style', plugins_url('assets/subpage_menu.css', __FILE__));
		wp_enqueue_style('subpage_menu_style');
	}
	
	/*
	Show notice if your plugin is activated but WPBakery Page Builder is not
	*/
	public function showVcVersionNotice()
	{
		$plugin_data = get_plugin_data(__FILE__);
		echo '
				<div class="updated">
					<p>' . sprintf(__('<strong>%s</strong> requires <strong><a href="http://bit.ly/vcomposer" target="_blank">WPBakery Page Builder</a></strong> plugin to be installed and activated on your site.', 'subpage_menu'), $plugin_data['Name']) . '</p>
				</div>';
	}
}
// Finally initialize code
new VCSubpageMenuAddonClass();
