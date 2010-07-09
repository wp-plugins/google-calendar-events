<?php
/*
Plugin Name: Google Calendar Events
Plugin URI: http://www.rhanney.co.uk/plugins/google-calendar-events
Description: Parses Google Calendar feeds and displays the events as a calendar grid or list on a page, post or widget.
Version: 0.2
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
			add_action('activate_google-calendar-events/google-calendar-events.php', array($this, 'init_plugin'));
			add_action('admin_menu', array($this, 'setup_admin'));
			add_action('admin_init', array($this, 'init_admin'));

			add_action('widgets_init', create_function('', 'return register_widget("GCE_Widget");'));

			add_shortcode('google-calendar-events', array($this, 'shortcode_handler'));

			add_action('wp_print_styles', array($this, 'add_styles'));
			add_action('wp_print_scripts', array($this, 'add_scripts'));
		}

		//If any new options have been added between versions, this will update any saved feeds with defaults for new options (shouldn't overwrite anything saved)
		function init_plugin(){
			add_option(GCE_OPTIONS_NAME);

			$options = get_option(GCE_OPTIONS_NAME);

			if(!empty($options)){
				foreach($options as $key => $saved_feed_options){
					$defaults = array(
						'id' => 1, 
						'title' => '',
						'url' => '',
						'show_past_events' => 'false',
						'max_events' => 25,
						'date_format' => '',
						'time_format' => '',
						//'offset' => 0,
						'cache_duration' => 43200,
						'display_title' => 'on',
						'display_start' => 'on',
						'display_end' => '',
						'display_location' => '',
						'display_desc' => '',
						'display_link' => 'on',
						'display_title_text' => '',
						'display_start_text' => 'Starts:',
						'display_end_text' => 'Ends:',
						'display_location_text' => 'Location:',
						'display_desc_text' => 'Description:',
						'display_link_text' => 'More details'
					);

					//Merge saved options with defaults
					foreach($saved_feed_options as $option_name => $option){
						$defaults[$option_name] = $saved_feed_options[$option_name];
					}

					$options[$key] = $defaults;
				}
			}

			update_option(GCE_OPTIONS_NAME, $options);
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
						?><div class="updated"><p><strong>New Feed Added Successfully.</strong></p></div><?php
						break;
					case 'edited':
						?><div class="updated"><p><strong>Feed Details Updated Successfully.</strong></p></div><?php
						break;
					case 'deleted':
						?><div class="updated"><p><strong>Feed Deleted Successfully.</strong></p></div><?php
						break;
				}
			}?>

			<div class="wrap">
				<div id="icon-options-general" class="icon32"><br /></div>

				<h2>Google Calendar Events</h2>
				<form method="post" action="options.php" id="test-form">
					<?php
					settings_fields('gce_options_group');

					if(isset($_GET['action'])){
						switch($_GET['action']){
							//Add feed section
							case 'add':
								do_settings_sections('add_feed');
								do_settings_sections('add_display');
								?><p class="submit"><input type="submit" class="button-primary submit" value="<?php esc_attr_e('Add Feed', GCE_TEXT_DOMAIN); ?>" /></p>
								<p><a href="<?php echo admin_url() . 'options-general.php?page=' . GCE_PLUGIN_NAME . '.php'; ?>" class="button-secondary">Cancel</a></p><?php
								break;
							//Edit feed section
							case 'edit':
								do_settings_sections('edit_feed');
								do_settings_sections('edit_display');
								?><p class="submit"><input type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', GCE_TEXT_DOMAIN); ?>" /></p>
								<p><a href="<?php echo admin_url() . 'options-general.php?page=' . GCE_PLUGIN_NAME . '.php'; ?>" class="button-secondary">Cancel</a></p><?php
								break;
							//Delete feed section
							case 'delete':
								do_settings_sections('delete_feed');
								?><p class="submit"><input type="submit" class="button-primary" name="gce_options[submit_delete]" value="<?php esc_attr_e('Delete Feed', GCE_TEXT_DOMAIN); ?>" /></p>
								<p><a href="<?php echo admin_url() . 'options-general.php?page=' . GCE_PLUGIN_NAME . '.php'; ?>" class="button-secondary">Cancel</a></p><?php
								break;
						}
					}else{
						//Main admin section
						settings_fields('gce_stylesheet');
						require_once 'admin/main.php';
					}
					?>
				</form>
			</div>
		<?php
		}

		//Initialize admin stuff
		function init_admin(){
			register_setting('gce_options_group', 'gce_options', array($this, 'validate_options'));
			register_setting('gce_stylesheet', 'gce_stylesheet', 'esc_url');

			//Setup add feed admin section and all fields
			require_once 'admin/add.php';
			add_settings_section('gce_add', __('Add a Feed', GCE_TEXT_DOMAIN), 'gce_add_main_text', 'add_feed');
			//Unique ID                                          //Title                                                         //Function                        //Page      //Section ID
			add_settings_field('gce_add_id_field',               __('Feed ID', GCE_TEXT_DOMAIN),                                 'gce_add_id_field',               'add_feed', 'gce_add');
			add_settings_field('gce_add_title_id_field',         __('Feed Title', GCE_TEXT_DOMAIN),                              'gce_add_title_field',            'add_feed', 'gce_add');
			add_settings_field('gce_add_url_field',              __('Feed URL', GCE_TEXT_DOMAIN),                                'gce_add_url_field',              'add_feed', 'gce_add');
			add_settings_field('gce_add_show_past_events_field', __('Retrieve past events for current month?', GCE_TEXT_DOMAIN), 'gce_add_show_past_events_field', 'add_feed', 'gce_add');
			add_settings_field('gce_add_max_events_field',       __('Maximum number of events to retrieve', GCE_TEXT_DOMAIN),    'gce_add_max_events_field',       'add_feed', 'gce_add');
			add_settings_field('gce_add_date_format_field',      __('Date format', GCE_TEXT_DOMAIN),                             'gce_add_date_format_field',      'add_feed', 'gce_add');
			add_settings_field('gce_add_time_format_field',      __('Time format', GCE_TEXT_DOMAIN),                             'gce_add_time_format_field',      'add_feed', 'gce_add');
			//add_settings_field('gce_add_offset_field',           __('Timezone offset', GCE_TEXT_DOMAIN),                         'gce_add_offset_field',           'add_feed', 'gce_add');
			add_settings_field('gce_add_cache_duration_field',   __('Cache duration', GCE_TEXT_DOMAIN),                          'gce_add_cache_duration_field',   'add_feed', 'gce_add');

			add_settings_section('gce_add_display', __('Display Options', GCE_TEXT_DOMAIN), 'gce_add_display_main_text', 'add_display');
			add_settings_field('gce_add_display_title_field',    __('Display title?', GCE_TEXT_DOMAIN),                          'gce_add_display_title_field',    'add_display', 'gce_add_display');
			add_settings_field('gce_add_display_start_field',    __('Display start time?', GCE_TEXT_DOMAIN),                     'gce_add_display_start_field',    'add_display', 'gce_add_display');
			add_settings_field('gce_add_display_end_field',      __('Display end time and date?', GCE_TEXT_DOMAIN),              'gce_add_display_end_field',      'add_display', 'gce_add_display');
			add_settings_field('gce_add_display_location_field', __('Display location?', GCE_TEXT_DOMAIN),                       'gce_add_display_location_field', 'add_display', 'gce_add_display');
			add_settings_field('gce_add_display_desc_field',     __('Display description?', GCE_TEXT_DOMAIN),                    'gce_add_display_desc_field',     'add_display', 'gce_add_display');
			add_settings_field('gce_add_display_link_field',     __('Display link to event?', GCE_TEXT_DOMAIN),                  'gce_add_display_link_field',     'add_display', 'gce_add_display');

			//Setup edit feed admin section and all fields
			require_once 'admin/edit.php';
			add_settings_section('gce_edit', __('Edit Feed', GCE_TEXT_DOMAIN), 'gce_edit_main_text', 'edit_feed');
			//Unique ID                                           //Title                                                         //Function                         //Page       //Section ID
			add_settings_field('gce_edit_id_field',               __('Feed ID', GCE_TEXT_DOMAIN),                                 'gce_edit_id_field',               'edit_feed', 'gce_edit');
			add_settings_field('gce_edit_title_field',            __('Feed Title', GCE_TEXT_DOMAIN),                              'gce_edit_title_field',            'edit_feed', 'gce_edit');
			add_settings_field('gce_edit_url_field',              __('Feed URL', GCE_TEXT_DOMAIN),                                'gce_edit_url_field',              'edit_feed', 'gce_edit');
			add_settings_field('gce_edit_show_past_events_field', __('Retrieve past events for current month?', GCE_TEXT_DOMAIN), 'gce_edit_show_past_events_field', 'edit_feed', 'gce_edit');
			add_settings_field('gce_edit_max_events_field',       __('Maximum number of events to retrieve', GCE_TEXT_DOMAIN),    'gce_edit_max_events_field',       'edit_feed', 'gce_edit');
			add_settings_field('gce_edit_date_format_field',      __('Date format', GCE_TEXT_DOMAIN),                             'gce_edit_date_format_field',      'edit_feed', 'gce_edit');
			add_settings_field('gce_edit_time_format_field',      __('Time format', GCE_TEXT_DOMAIN),                             'gce_edit_time_format_field',      'edit_feed', 'gce_edit');
			//add_settings_field('gce_edit_offset_field',           __('Timezone offset', GCE_TEXT_DOMAIN),                         'gce_edit_offset_field',           'edit_feed', 'gce_edit');
			add_settings_field('gce_edit_cache_duration_field',   __('Cache duration', GCE_TEXT_DOMAIN),                          'gce_edit_cache_duration_field',   'edit_feed', 'gce_edit');

			add_settings_section('gce_edit_display', __('Display Options', GCE_TEXT_DOMAIN), 'gce_edit_display_main_text', 'edit_display');
			add_settings_field('gce_edit_display_title_field',    __('Display title?', GCE_TEXT_DOMAIN),                          'gce_edit_display_title_field',    'edit_display', 'gce_edit_display');
			add_settings_field('gce_edit_display_start_field',    __('Display start time?', GCE_TEXT_DOMAIN),                     'gce_edit_display_start_field',    'edit_display', 'gce_edit_display');
			add_settings_field('gce_edit_display_end_field',      __('Display end time and date?', GCE_TEXT_DOMAIN),              'gce_edit_display_end_field',      'edit_display', 'gce_edit_display');
			add_settings_field('gce_edit_display_location_field', __('Display location?', GCE_TEXT_DOMAIN),                       'gce_edit_display_location_field', 'edit_display', 'gce_edit_display');
			add_settings_field('gce_edit_display_desc_field',     __('Display description?', GCE_TEXT_DOMAIN),                    'gce_edit_display_desc_field',     'edit_display', 'gce_edit_display');
			add_settings_field('gce_edit_display_link_field',     __('Display link to event?', GCE_TEXT_DOMAIN),                  'gce_edit_display_link_field',     'edit_display', 'gce_edit_display');

			//Setup delete feed admin section and all fields
			require_once 'admin/delete.php';
			add_settings_section('gce_delete', __('Delete Feed', GCE_TEXT_DOMAIN), 'gce_delete_main_text', 'delete_feed');
			//Unique ID                                  //Title                            //Function                //Page         //Section ID
			add_settings_field('gce_delete_id_field',    __('Feed ID', GCE_TEXT_DOMAIN),    'gce_delete_id_field',    'delete_feed', 'gce_delete');
			add_settings_field('gce_delete_title_field', __('Feed Title', GCE_TEXT_DOMAIN), 'gce_delete_title_field', 'delete_feed', 'gce_delete');
		}

		//Check / validate submitted data before being stored
		function validate_options($input){
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
				$show_past_events = ($input['show_past_events'] == 'true' ? 'true' : 'false');
				//Check max events is a positive integer. If absint returns 0, reset to default (25)
				$max_events = (absint($input['max_events']) == 0 ? 25 : absint($input['max_events']));

				$date_format = wp_filter_kses($input['date_format']);
				$time_format = wp_filter_kses($input['time_format']);

				//Check timezone offset is an integer
				//$offset = (int)$input['offset'];

				//Make sure cache duration is a positive integer or 0. If user has typed 0, leave as 0 but if 0 is returned from absint, set to default (43200)
				$cache_duration = $input['cache_duration'];
				if($cache_duration != '0'){
					$cache_duration = (absint($cache_duration) == 0 ? 43200 : absint($cache_duration));
				}

				//Dooltip options must be 'on' or null
				$display_title = ($input['display_title'] == 'on' ? 'on' : null);
				$display_start = ($input['display_start'] == 'on' ? 'on' : null);
				$display_end = ($input['display_end'] == 'on' ? 'on' : null);
				$display_location = ($input['display_location'] == 'on' ? 'on' : null);
				$display_desc = ($input['display_desc'] == 'on' ? 'on' : null);
				$display_link = ($input['display_link'] == 'on' ? 'on' : null);

				//Escape display text
				$display_title_text = wp_filter_kses($input['display_title_text']);
				$display_start_text = wp_filter_kses($input['display_start_text']);
				$display_end_text = wp_filter_kses($input['display_end_text']);
				$display_location_text = wp_filter_kses($input['display_location_text']);
				$display_desc_text = wp_filter_kses($input['display_desc_text']);
				$display_link_text = wp_filter_kses($input['display_link_text']);

				//Fill options array with validated values
				$options[$id] = array(
					'id' => $id, 
					'title' => $title,
					'url' => $url,
					'show_past_events' => $show_past_events,
					'max_events' => $max_events,
					'date_format' => $date_format,
					'time_format' => $time_format,
					//'offset' => $offset,
					'cache_duration' => $cache_duration,
					'display_title' => $display_title,
					'display_start' => $display_start,
					'display_end' => $display_end,
					'display_location' => $display_location,
					'display_desc' => $display_desc,
					'display_link' => $display_link,
					'display_title_text' => $display_title_text,
					'display_start_text' => $display_start_text,
					'display_end_text' => $display_end_text,
					'display_location_text' => $display_location_text,
					'display_desc_text' => $display_desc_text,
					'display_link_text' => $display_link_text
				);
			}

			return $options;
		}

		//Handles the shortcode stuff
		function shortcode_handler($atts){
		$options = get_option(GCE_OPTIONS_NAME);

			//Check that any feeds have been added
			if(is_array($options) && !empty($options)){
				extract(shortcode_atts(array(
					'id' => '1',
					'type' => 'grid'
				), $atts));

				switch($type){
					case 'grid':
						return gce_print_grid($id);
						break;
					case 'ajax':
						return gce_print_grid($id, true);
						break;
					case 'list':
						return gce_print_list($id);
						break;
				}
			}else{
				return 'No feeds have been added yet. You can add a feed in the Google Calendar Events settings.';
			}
		}

		//Adds the required CSS
		function add_styles(){
			//Don't add styles if on admin screens
			if(!is_admin()){
				//If user has entered a URL to a custom stylesheet, use it. Otherwise use the default
				if((get_option('gce_stylesheet') != false) && (get_option('gce_stylesheet') != '')){
					wp_enqueue_style('gce_styles', get_option('gce_stylesheet'));
				}else{
					wp_enqueue_style('gce_styles', WP_PLUGIN_URL . '/' . GCE_PLUGIN_NAME . '/css/gce-style.css');
				}
			}
		}

		//Adds the required scripts
		function add_scripts(){
			//Don't add scripts if on admin screens
			if(!is_admin()){
				wp_enqueue_script('jquery');
				wp_enqueue_script('gce_jquery_qtip', WP_PLUGIN_URL . '/' . GCE_PLUGIN_NAME . '/js/jquery-qtip.js');
				wp_enqueue_script('gce_scripts', WP_PLUGIN_URL . '/' . GCE_PLUGIN_NAME . '/js/gce-script.js');
				
			}
		}
	}
}

function gce_print_list($feed_id){
	//Get saved feed options
	$options = get_option(GCE_OPTIONS_NAME);

	//Set time and date formats to WordPress defaults if not set by user
	$df = $options[$feed_id]['date_format'];
	$tf = $options[$feed_id]['time_format'];
	if($df == '') $df = get_option('date_format');
	if($tf == '') $tf = get_option('time_format');

	$display_options = array();

	//Add display options text to array if they have been turned on (if turned off, don't add them)
	if($options[$feed_id]['display_title'] == 'on') $display_options['title'] = $options[$feed_id]['display_title_text'];
	if($options[$feed_id]['display_start'] == 'on') $display_options['start'] = $options[$feed_id]['display_start_text'];
	if($options[$feed_id]['display_end'] == 'on') $display_options['end'] = $options[$feed_id]['display_end_text'];
	if($options[$feed_id]['display_location'] == 'on') $display_options['location'] = $options[$feed_id]['display_location_text'];
	if($options[$feed_id]['display_desc'] == 'on') $display_options['desc'] = $options[$feed_id]['display_desc_text'];
	if($options[$feed_id]['display_link'] == 'on') $display_options['link'] = $options[$feed_id]['display_link_text'];

	//Creates a new GCE_Parser object for $feed_id
	$feed_data = new GCE_Parser(
		$options[$feed_id]['url'],
		$options[$feed_id]['show_past_events'],
		$options[$feed_id]['max_events'],
		$options[$feed_id]['cache_duration'],
		$df,
		$tf,
		//$options[$feed_id]['offset'],
		null,
		$display_options
	);

	//If the feed parsed ok
	if($feed_data->parsed_ok()){
		$markup = '<div class="gce-page-list">' . $feed_data->get_list() . '</div>';

		return $markup;
	}else{
		return 'The Google Calendar feed was not parsed successfully, please check that the feed URL is correct.';
	}
}

function gce_print_grid($feed_id, $ajaxified = false, $month = null, $year = null){
	//Get saved feed options
	$options = get_option(GCE_OPTIONS_NAME);

	//Set time and date formats to WordPress defaults if not set by user
	$df = $options[$feed_id]['date_format'];
	$tf = $options[$feed_id]['time_format'];
	if($df == '') $df = get_option('date_format');
	if($tf == '') $tf = get_option('time_format');

	$display_options = array();

	//Add display options text to array if they have been turned on (if turned off, don't add them)
	if($options[$feed_id]['display_title'] == 'on') $display_options['title'] = $options[$feed_id]['display_title_text'];
	if($options[$feed_id]['display_start'] == 'on') $display_options['start'] = $options[$feed_id]['display_start_text'];
	if($options[$feed_id]['display_end'] == 'on') $display_options['end'] = $options[$feed_id]['display_end_text'];
	if($options[$feed_id]['display_location'] == 'on') $display_options['location'] = $options[$feed_id]['display_location_text'];
	if($options[$feed_id]['display_desc'] == 'on') $display_options['desc'] = $options[$feed_id]['display_desc_text'];
	if($options[$feed_id]['display_link'] == 'on') $display_options['link'] = $options[$feed_id]['display_link_text'];

	//Creates a new GCE_Parser object for $feed_id
	$feed_data = new GCE_Parser(
		$options[$feed_id]['url'],
		$options[$feed_id]['show_past_events'],
		$options[$feed_id]['max_events'],
		$options[$feed_id]['cache_duration'],
		$df,
		$tf,
		//$options[$feed_id]['offset'],
		get_option('start_of_week'),
		$display_options
	);

	//If the feed parsed ok
	if($feed_data->parsed_ok()){
		$markup = '<div class="gce-page-grid" id="gce-page-grid-' . $feed_id .'">';

		//Add AJAX script if required
		if($ajaxified){
			$markup .= '<script type="text/javascript">jQuery(document).ready(function($){gce_ajaxify("gce-page-grid-' . $feed_id . '", "' . $feed_id . '", "page");});</script>';
		}

		$markup .= $feed_data->get_grid($year, $month, $ajaxified);

		$markup .= '</div>';
		return $markup;
	}else{
		return 'The Google Calendar feed was not parsed successfully, please check that the feed URL is correct.';
	}
}

function gce_handle_ajax($feed_id, $month = null, $year = null){
	echo gce_print_grid($feed_id, true, $month, $year);
}

if(isset($_GET['gce_type']) && $_GET['gce_type'] == 'page'){
	if(isset($_GET['gce_feed_id'])){
		gce_handle_ajax($_GET['gce_feed_id'], $_GET['gce_month'], $_GET['gce_year']);
		die();
	}
}

$gce = new Google_Calendar_Events();
?>