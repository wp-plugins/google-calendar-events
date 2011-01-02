<?php
/*
Plugin Name: Google Calendar Events
Plugin URI: http://www.rhanney.co.uk/plugins/google-calendar-events
Description: Parses Google Calendar feeds and displays the events as a calendar grid or list on a page, post or widget.
Version: 0.4
Author: Ross Hanney
Author URI: http://www.rhanney.co.uk
License: GPL2

---

Copyright 2010 Ross Hanney (email: rosshanney@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define('GCE_PLUGIN_NAME', str_replace('.php', '', basename(__FILE__)));
define('GCE_TEXT_DOMAIN', 'google-calendar-events');
define('GCE_OPTIONS_NAME', 'gce_options');
define('GCE_GENERAL_OPTIONS_NAME', 'gce_general');

require_once 'widget/gce-widget.php';
require_once 'inc/gce-parser.php';

if(!class_exists('Google_Calendar_Events')){
	class Google_Calendar_Events{
		//PHP 4 constructor
		function Google_Calendar_Events(){
			$this->__construct();
		}

		//PHP 5 constructor
		function __construct(){
			add_action('activate_google-calendar-events/google-calendar-events.php', array($this, 'activate_plugin'));
			add_action('init', array($this, 'init_plugin'));
			add_action('admin_menu', array($this, 'setup_admin'));
			add_action('admin_init', array($this, 'init_admin'));
			add_action('wp_print_styles', array($this, 'add_styles'));
			add_action('wp_print_scripts', array($this, 'add_scripts'));
			add_action('widgets_init', create_function('', 'return register_widget("GCE_Widget");'));
			add_action('wp_ajax_gce_ajax', array($this, 'gce_ajax'));
			add_action('wp_ajax_nopriv_gce_ajax', array($this, 'gce_ajax'));
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
			add_shortcode('google-calendar-events', array($this, 'shortcode_handler'));
		}

		//If any new options have been added between versions, this will update any saved feeds with defaults for new options (shouldn't overwrite anything saved)
		//Will do the same for general options
		function activate_plugin(){
			add_option(GCE_OPTIONS_NAME);
			add_option(GCE_GENERAL_OPTIONS_NAME);

			//Get feed options
			$options = get_option(GCE_OPTIONS_NAME);

			if(!empty($options)){
				foreach($options as $key => $saved_feed_options){
					$defaults = array(
						'id' => 1, 
						'title' => '',
						'url' => '',
						'show_past_events' => 'false',
						'max_events' => 25,
						'day_limit' => '',
						'date_format' => '',
						'time_format' => '',
						'timezone' => 'default',
						'cache_duration' => 43200,
						'multiple_day' => 'false',
						'display_start' => 'time',
						'display_end' => 'time-date',
						'display_location' => '',
						'display_desc' => '',
						'display_link' => 'on',
						'display_start_text' => 'Starts:',
						'display_end_text' => 'Ends:',
						'display_location_text' => 'Location:',
						'display_desc_text' => 'Description:',
						'display_desc_limit' => '',
						'display_link_text' => 'More details',
						'display_link_target' => '',
						'display_separator' => ', '
					);

					//Update old display_start / display_end values
					if(!isset($saved_feed_options['display_start']))
						$saved_feed_options['display_start'] = 'none';
					elseif($saved_feed_options['display_start'] == 'on')
						$saved_feed_options['display_start'] = 'time';

					if(!isset($saved_feed_options['display_end']))
						$saved_feed_options['display_end'] = 'none';
					elseif($saved_feed_options['display_end'] == 'on')
						$saved_feed_options['display_end'] = 'time-date';

					//Update old show past events value
					if($saved_feed_options['show_past_events'] == 'false')
						$saved_feed_options['show_past_event'] = 'none';
					elseif($saved_feed_options['show_past_events'] == 'true')
						$saved_feed_options['show_past_events'] = 'month';

					//Merge saved options with defaults
					foreach($saved_feed_options as $option_name => $option){
						$defaults[$option_name] = $saved_feed_options[$option_name];
					}

					$options[$key] = $defaults;
				}
			}

			//Save feed options
			update_option(GCE_OPTIONS_NAME, $options);

			//Get general options
			$options = get_option(GCE_GENERAL_OPTIONS_NAME);

			$defaults = array(
				'stylesheet' => '',
				'javascript' => false,
				'loading' => 'Loading...'
			);

			$old_stylesheet_option = get_option('gce_stylesheet');

			//If old custom stylesheet options was set, add it to general options, then delete old option
			if($old_stylesheet_option !== false){
				$defaults['stylesheet'] = $old_stylesheet_option;
				delete_option('gce_stylesheet');
			}elseif(isset($options['stylesheet'])){
				$defaults['stylesheet'] = $options['stylesheet'];
			}

			if(isset($options['javascript'])) $defaults['javascript'] = $options['javascript'];
			if(isset($options['loading'])) $defaults['loading'] = $options['loading'];

			//Save general options
			update_option(GCE_GENERAL_OPTIONS_NAME, $defaults);
		}

		function init_plugin(){
			//Load text domain for i18n
			load_plugin_textdomain(GCE_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
			if(get_option('timezone_string') != '') date_default_timezone_set(get_option('timezone_string'));
		}

		//Adds 'Settings' link to main WordPress Plugins page
		function add_settings_link($links){
			array_unshift($links, '<a href="options-general.php?page=google-calendar-events.php">' . __('Settings', GCE_TEXT_DOMAIN) . '</a>');
			return $links;
		}

		//Setup admin settings page
		function setup_admin(){
			if(function_exists('add_options_page')) add_options_page('Google Calendar Events', 'Google Calendar Events', 'manage_options', basename(__FILE__), array($this, 'admin_page'));
		}

		//Prints admin settings page
		function admin_page(){
			//Add correct updated message (added / edited / deleted)
			if(isset($_GET['updated'])){
				switch($_GET['updated']){
					case 'added':
						?><div class="updated"><p><strong><?php _e('New Feed Added Successfully.', GCE_TEXT_DOMAIN); ?></strong></p></div><?php
						break;
					case 'edited':
						?><div class="updated"><p><strong><?php _e('Feed Details Updated Successfully.', GCE_TEXT_DOMAIN); ?></strong></p></div><?php
						break;
					case 'deleted':
						?><div class="updated"><p><strong><?php _e('Feed Deleted Successfully.', GCE_TEXT_DOMAIN); ?></strong></p></div><?php
				}
			}?>

			<div class="wrap">
				<div id="icon-options-general" class="icon32"><br /></div>

				<h2><?php _e('Google Calendar Events', GCE_TEXT_DOMAIN); ?></h2>
				<form method="post" action="options.php" id="test-form">
					<?php
					if(isset($_GET['action'])){
						switch($_GET['action']){
							//Add feed section
							case 'add':
								settings_fields('gce_options');
								do_settings_sections('add_feed');
								do_settings_sections('add_display');
								?><p class="submit"><input type="submit" class="button-primary submit" value="<?php _e('Add Feed', GCE_TEXT_DOMAIN); ?>" /></p>
								<p><a href="<?php echo admin_url('options-general.php?page=' . GCE_PLUGIN_NAME . '.php'); ?>" class="button-secondary"><?php _e('Cancel', GCE_TEXT_DOMAIN); ?></a></p><?php
								break;
							//Edit feed section
							case 'edit':
								settings_fields('gce_options');
								do_settings_sections('edit_feed');
								do_settings_sections('edit_display');
								?><p class="submit"><input type="submit" class="button-primary submit" value="<?php _e('Save Changes', GCE_TEXT_DOMAIN); ?>" /></p>
								<p><a href="<?php echo admin_url('options-general.php?page=' . GCE_PLUGIN_NAME . '.php'); ?>" class="button-secondary"><?php _e('Cancel', GCE_TEXT_DOMAIN); ?></a></p><?php
								break;
							//Delete feed section
							case 'delete':
								settings_fields('gce_options');
								do_settings_sections('delete_feed');
								?><p class="submit"><input type="submit" class="button-primary submit" name="gce_options[submit_delete]" value="<?php _e('Delete Feed', GCE_TEXT_DOMAIN); ?>" /></p>
								<p><a href="<?php echo admin_url('options-general.php?page=' . GCE_PLUGIN_NAME . '.php'); ?>" class="button-secondary"><?php _e('Cancel', GCE_TEXT_DOMAIN); ?></a></p><?php
						}
					}else{
						//Main admin section
						settings_fields('gce_general');
						require_once 'admin/main.php';
					}
					?>
				</form>
			</div>
		<?php
		}

		//Initialize admin stuff
		function init_admin(){
			register_setting('gce_options', 'gce_options', array($this, 'validate_feed_options'));
			register_setting('gce_general', 'gce_general', array($this, 'validate_general_options'));

			require_once 'admin/add.php';
			require_once 'admin/edit.php';
			require_once 'admin/delete.php';
		}

		//Check / validate submitted feed options data before being stored
		function validate_feed_options($input){
			//Get saved options
			$options = get_option(GCE_OPTIONS_NAME);

			if(isset($input['submit_delete'])){
				//If delete button was clicked, delete feed from options array
				unset($options[$input['id']]);
			}else{
				//Otherwise, validate options and add / update them

				//Check id is positive integer
				$id = absint($input['id']);
				//Escape title text
				$title = esc_html($input['title']);
				//Escape feed url
				$url = esc_url($input['url']);
				//Make sure show past events is either true of false
				$show_past_events = (isset($input['show_past_events']) ? 'true' : 'false');
				//Check max events is a positive integer. If absint returns 0, reset to default (25)
				$max_events = (absint($input['max_events']) == 0 ? 25 : absint($input['max_events']));
				//Check day limit is a positive integer. If not (or 0) set to ''
				$day_limit = absint($input['day_limit']) == 0 ? '' : absint($input['day_limit']);

				$date_format = wp_filter_kses($input['date_format']);
				$time_format = wp_filter_kses($input['time_format']);

				//Escape timezone
				$timezone = esc_html($input['timezone']);

				//Make sure cache duration is a positive integer or 0. If user has typed 0, leave as 0 but if 0 is returned from absint, set to default (43200)
				$cache_duration = $input['cache_duration'];
				if($cache_duration != '0') $cache_duration = (absint($cache_duration) == 0 ? 43200 : absint($cache_duration));

				$multiple_day = (isset($input['multiple_day']) ? 'true' : 'false');

				$display_start = esc_html($input['display_start']);
				$display_end = esc_html($input['display_end']);

				//Display options must be 'on' or null
				$display_location = (isset($input['display_location']) ? 'on' : null);
				$display_desc = (isset($input['display_desc']) ? 'on' : null);
				$display_link = (isset($input['display_link']) ? 'on' : null);
				$display_link_target = (isset($input['display_link_target']) ? 'on' : null);

				//Filter display text
				$display_start_text = wp_filter_kses($input['display_start_text']);
				$display_end_text = wp_filter_kses($input['display_end_text']);
				$display_location_text = wp_filter_kses($input['display_location_text']);
				$display_desc_text = wp_filter_kses($input['display_desc_text']);
				$display_link_text = wp_filter_kses($input['display_link_text']);

				$display_separator = wp_filter_kses($input['display_separator']);

				$display_desc_limit = absint($input['display_desc_limit']) == 0 ? '' : absint($input['display_desc_limit']);

				//Fill options array with validated values
				$options[$id] = array(
					'id' => $id, 
					'title' => $title,
					'url' => $url,
					'show_past_events' => $show_past_events,
					'max_events' => $max_events,
					'day_limit' => $day_limit,
					'date_format' => $date_format,
					'time_format' => $time_format,
					'timezone' => $timezone,
					'cache_duration' => $cache_duration,
					'multiple_day' => $multiple_day,
					'display_start' => $display_start,
					'display_end' => $display_end,
					'display_location' => $display_location,
					'display_desc' => $display_desc,
					'display_link' => $display_link,
					'display_start_text' => $display_start_text,
					'display_end_text' => $display_end_text,
					'display_location_text' => $display_location_text,
					'display_desc_text' => $display_desc_text,
					'display_desc_limit' => $display_desc_limit,
					'display_link_text' => $display_link_text,
					'display_link_target' => $display_link_target,
					'display_separator' => $display_separator
				);
			}

			return $options;
		}

		//Validate submitted general options
		function validate_general_options($input){
			$options = get_option(GCE_GENERAL_OPTIONS_NAME);

			$options['stylesheet'] = esc_url($input['stylesheet']);
			$options['javascript'] = (isset($input['javascript']) ? true : false);
			$options['loading'] = esc_html($input['loading']);

			return $options;
		}

		//Handles the shortcode stuff
		function shortcode_handler($atts){
			$options = get_option(GCE_OPTIONS_NAME);

			//Check that any feeds have been added
			if(is_array($options) && !empty($options)){
				extract(shortcode_atts(array(
					'id' => '1',
					'type' => 'grid',
					'title' => false,
					'max' => 0
				), $atts));

				//Break comma delimited list of feed ids into array
				$feed_ids = explode(',', str_replace(' ', '', $id));

				//Check each id is an integer, if not, remove it from the array
				foreach($feed_ids as $key => $feed_id){
					if(absint($feed_id) == 0) unset($feed_ids[$key]);
				}

				$no_feeds_exist = true;

				//If at least one of the feed ids entered exists, set no_feeds_exist to false
				foreach($feed_ids as $feed_id){
					if(isset($options[$feed_id])) $no_feeds_exist = false;
				}

				$max_events = absint($max);

				//Check that at least one valid feed id has been entered
				if(count((array)$feed_ids) == 0 || $no_feeds_exist){
					return __('No valid Feed IDs have been entered for this shortcode. Please check that you have entered the IDs correctly and that the Feeds have not been deleted.', GCE_TEXT_DOMAIN);
				}else{
					//Turnd feed_ids back into string or feed ids delimited by '-' ('1-2-3-4' for example)
					$feed_ids = implode('-', $feed_ids);

					//If title has been omitted from shortcode, set title_text to null, otherwise set to title (even if empty string)
					$title_text = ($title === false ? null : $title);

					switch($type){
						case 'grid': return gce_print_grid($feed_ids, $title_text, $max_events);
						case 'ajax': return gce_print_grid($feed_ids, $title_text, $max_events, true);
						case 'list': return gce_print_list($feed_ids, $title_text, $max_events);
						case 'list-grouped': return gce_print_list($feed_ids, $title_text, $max_events, true);
					}
				}
			}else{
				return __('No feeds have been added yet. You can add a feed in the Google Calendar Events settings.', GCE_TEXT_DOMAIN);
			}
		}

		//Adds the required CSS
		function add_styles(){
			//Don't add styles if on admin screens
			if(!is_admin()){
				//If user has entered a URL to a custom stylesheet, use it. Otherwise use the default
				$options = get_option(GCE_GENERAL_OPTIONS_NAME);
				if(isset($options['stylesheet']) && ($options['stylesheet'] != '')){
					wp_enqueue_style('gce_styles', $options['stylesheet']);
				}else{
					wp_enqueue_style('gce_styles', WP_PLUGIN_URL . '/' . GCE_PLUGIN_NAME . '/css/gce-style.css');
				}
			}
		}

		//Adds the required scripts
		function add_scripts(){
			//Don't add scripts if on admin screens
			if(!is_admin()){
				$options = get_option(GCE_GENERAL_OPTIONS_NAME);
				$add_to_footer = (bool)$options['javascript'];

				wp_enqueue_script('jquery');
				wp_enqueue_script('gce_jquery_qtip', WP_PLUGIN_URL . '/' . GCE_PLUGIN_NAME . '/js/jquery-qtip.js', array('jquery'), null, $add_to_footer);
				wp_enqueue_script('gce_scripts', WP_PLUGIN_URL . '/' . GCE_PLUGIN_NAME . '/js/gce-script.js', array('jquery'), null, $add_to_footer);
				wp_localize_script('gce_scripts', 'GoogleCalendarEvents', array(
					'ajaxurl' => admin_url('admin-ajax.php'),
					'loading' => $options['loading']
				));
			}
		}

		//AJAX stuffs
		function gce_ajax(){
			if(isset($_GET['gce_feed_ids'])){
				if($_GET['gce_type'] == 'page'){
					//The page grid markup to be returned via AJAX
					echo gce_print_grid($_GET['gce_feed_ids'], $_GET['gce_title_text'], $_GET['max_events'], true, $_GET['gce_month'], $_GET['gce_year']);
				}elseif($_GET['gce_type'] == 'widget'){
					//The widget grid markup to be returned via AJAX
					gce_widget_content_grid($_GET['gce_feed_ids'], $_GET['gce_title_text'], $_GET['max_events'], $_GET['gce_widget_id'], true, $_GET['gce_month'], $_GET['gce_year']);
				}
				die();
			}
		}
	}
}

function gce_print_list($feed_ids, $title_text, $max_events, $grouped = false){
	//Create new GCE_Parser object, passing array of feed id(s)
	$list = new GCE_Parser(explode('-', $feed_ids), $title_text, $max_events);

	//If the feed(s) parsed ok, return the list markup, otherwise return an error message
	if(count($list->get_errors()) == 0){
		return '<div class="gce-page-list">' . $list->get_list($grouped) . '</div>';
	}else{
		return sprintf(__('The following feeds were not parsed successfully: %s. Please check that the feed URLs are correct and that the feeds have public sharing enabled.'), implode(', ', $list->get_errors()));
	}
}

function gce_print_grid($feed_ids, $title_text, $max_events, $ajaxified = false, $month = null, $year = null){
	//Create new GCE_Parser object, passing array of feed id(s) returned from gce_get_feed_ids()
	$grid = new GCE_Parser(explode('-', $feed_ids), $title_text, $max_events);

	//If the feed(s) parsed ok, return the grid markup, otherwise return an error message
	if(count($grid->get_errors()) == 0){
		$markup = '<div class="gce-page-grid" id="gce-page-grid-' . $feed_ids .'">';

		//Add AJAX script if required
		if($ajaxified) $markup .= '<script type="text/javascript">jQuery(document).ready(function($){gce_ajaxify("gce-page-grid-' . $feed_ids . '", "' . $feed_ids . '", "' . $title_text . '", "page");});</script>';

		return $markup . $grid->get_grid($year, $month, $ajaxified) . '</div>';
	}else{
		return sprintf(__('The following feeds were not parsed successfully: %s. Please check that the feed URLs are correct and that the feeds have public sharing enabled.'), implode(', ', $grid->get_errors()));
	}
}

$gce = new Google_Calendar_Events();
?>