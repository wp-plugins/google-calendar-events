<?php
/*
Plugin Name: Google Calendar Events
Plugin URI: http://www.rhanney.co.uk/plugins/google-calendar-events
Description: Parses Google Calendar feeds and displays the events as a calendar grid or list on a page, post or widget.
Version: 0.6
Author: Ross Hanney
Author URI: http://www.rhanney.co.uk
License: GPL2

---

Copyright 2010 Ross Hanney (email: rosshanney@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('GCE_PLUGIN_NAME', str_replace('.php', '', basename(__FILE__)));
define('GCE_TEXT_DOMAIN', 'google-calendar-events');
define('GCE_OPTIONS_NAME', 'gce_options');
define('GCE_GENERAL_OPTIONS_NAME', 'gce_general');
define('GCE_VERSION', '0.6');

if(!class_exists('Google_Calendar_Events')){
	class Google_Calendar_Events{
		function __construct(){
			add_action('activate_google-calendar-events/google-calendar-events.php', array($this, 'activate_plugin'));
			add_action('init', array($this, 'init_plugin'));
			add_action('wp_ajax_gce_ajax', array($this, 'gce_ajax'));
			add_action('wp_ajax_nopriv_gce_ajax', array($this, 'gce_ajax'));
			add_action('widgets_init', array($this, 'add_widget'));

			//No point doing any of this if currently processing an AJAX request
			if(!defined('DOING_AJAX') || !DOING_AJAX){
				add_action('admin_menu', array($this, 'setup_admin'));
				add_action('admin_init', array($this, 'init_admin'));
				add_action('wp_print_styles', array($this, 'add_styles'));
				add_action('wp_print_scripts', array($this, 'add_scripts'));
				add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
				add_shortcode('google-calendar-events', array($this, 'shortcode_handler'));
			}
		}

		//If any new options have been added between versions, this will update any saved feeds with defaults for new options (shouldn't overwrite anything saved)
		function activate_plugin(){
			//PHP 5.2 is required (json_decode), so if PHP version is lower then 5.2, display an error message and deactivate the plugin
			if(version_compare(PHP_VERSION, '5.2', '<')){
				if(is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)){
					require_once ABSPATH . '/wp-admin/includes/plugin.php';
					deactivate_plugins(basename(__FILE__));
					wp_die('Google Calendar Events requires the server on which your site resides to be running PHP 5.2 or higher. As of version 3.2, WordPress itself will also <a href="http://wordpress.org/news/2010/07/eol-for-php4-and-mysql4">have this requirement</a>. You should get in touch with your web hosting provider and ask them to update PHP.<br /><br /><a href="' . admin_url('plugins.php') . '">Back to Plugins</a>');
				}
			}

			//If there are some plugin options in the database, but no version info, then this must be an upgrade from version 0.5 or below, so add flag that will provide user with option to clear old transients
			if(get_option(GCE_OPTIONS_NAME) && !get_option('gce_version')) add_option('gce_clear_old_transients', true);

			add_option('gce_version', GCE_VERSION);

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
						'retrieve_from' => 'today',
						'retrieve_from_value' => 0,
						'retrieve_until' => 'any',
						'retrieve_until_value' => 0,
						'max_events' => 25,
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
						'display_separator' => ', ',
						'use_builder' => 'false',
						'builder' => ''
					);

					//If necessary, copy saved behaviour of old show_past_events and day_limit options into the new from / until options
					if(isset($saved_feed_options['show_past_events'])){
						if($saved_feed_options['show_past_events'] == 'true'){
							$saved_feed_options['retrieve_from'] = 'month-start';
						}else{
							$saved_feed_options['retrieve_from'] = 'today';
						}
					}

					if(isset($saved_feed_options['day_limit']) && $saved_feed_options['day_limit'] != ''){
						$saved_feed_options['retrieve_until'] = 'today';
						$saved_feed_options['retrieve_until_value'] = (int)$saved_feed_options['day_limit'] * 86400;
					}

					//Update old display_start / display_end values
					if(!isset($saved_feed_options['display_start']))
						$saved_feed_options['display_start'] = 'none';
					elseif($saved_feed_options['display_start'] == 'on')
						$saved_feed_options['display_start'] = 'time';

					if(!isset($saved_feed_options['display_end']))
						$saved_feed_options['display_end'] = 'none';
					elseif($saved_feed_options['display_end'] == 'on')
						$saved_feed_options['display_end'] = 'time-date';

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
				'loading' => 'Loading...',
				'error' => 'Events cannot currently be displayed, sorry! Please check back later.',
				'fields' => true
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
			if(isset($options['error'])) $defaults['error'] = $options['error'];
			if(isset($options['fields'])) $defaults['fields'] = $options['fields'];

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
			?>
			<div class="wrap">
				<div id="icon-options-general" class="icon32"><br /></div>

				<h2><?php _e('Google Calendar Events', GCE_TEXT_DOMAIN); ?></h2>

				<?php if(get_option('gce_clear_old_transients')): ?>
					<div class="error">
						<p><?php _e('<strong>Notice:</strong> The way in which Google Calendar Events stores cached data has been much improved in version 0.6. As you have upgraded from a previous version of the plugin, there is likely to be some data from the old caching system hanging around in your database that is now useless. Click below to clear expired cached data from your database.', GCE_TEXT_DOMAIN); ?></p>
						<p><a href="<?php echo wp_nonce_url(add_query_arg(array('gce_action' => 'clear_old_transients')), 'gce_action_clear_old_transients'); ?>"><?php _e('Clear expired cached data', GCE_TEXT_DOMAIN); ?></a></p>
						<p><?php _e('or', GCE_TEXT_DOMAIN); ?></p>
						<p><a href="<?php echo wp_nonce_url(add_query_arg(array('gce_action' => 'ignore_old_transients')), 'gce_action_ignore_old_transients'); ?>"><?php _e('Ignore this notice', GCE_TEXT_DOMAIN); ?></a></p>
					</div>
				<?php endif; ?>

				<form method="post" action="options.php" id="test-form">
					<?php
					if(isset($_GET['action']) && !isset($_GET['settings-updated'])){
						switch($_GET['action']){
							//Add feed section
							case 'add':
								settings_fields('gce_options');
								do_settings_sections('add_feed');
								do_settings_sections('add_display');
								do_settings_sections('add_builder');
								do_settings_sections('add_simple_display');
								?><p class="submit"><input type="submit" class="button-primary submit" name="gce_options[submit_add]" value="<?php _e('Add Feed', GCE_TEXT_DOMAIN); ?>" /></p>
								<p><a href="<?php echo admin_url('options-general.php?page=' . GCE_PLUGIN_NAME . '.php'); ?>" class="button-secondary"><?php _e('Cancel', GCE_TEXT_DOMAIN); ?></a></p><?php
								break;
							case 'refresh':
								settings_fields('gce_options');
								do_settings_sections('refresh_feed');
								?><p class="submit"><input type="submit" class="button-primary submit" name="gce_options[submit_refresh]" value="<?php _e('Refresh Feed', GCE_TEXT_DOMAIN); ?>" /></p>
								<p><a href="<?php echo admin_url('options-general.php?page=' . GCE_PLUGIN_NAME . '.php'); ?>" class="button-secondary"><?php _e('Cancel', GCE_TEXT_DOMAIN); ?></a></p><?php
								break;
							//Edit feed section
							case 'edit':
								settings_fields('gce_options');
								do_settings_sections('edit_feed');
								do_settings_sections('edit_display');
								do_settings_sections('edit_builder');
								do_settings_sections('edit_simple_display');
								?><p class="submit"><input type="submit" class="button-primary submit" name="gce_options[submit_edit]" value="<?php _e('Save Changes', GCE_TEXT_DOMAIN); ?>" /></p>
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
			//If the message about old transients was displayed, check authority and intention, and then either clear transients or clear flag
			if(isset($_GET['gce_action']) && current_user_can('manage_options')){
				switch($_GET['gce_action']){
					case 'clear_old_transients':
						check_admin_referer('gce_action_clear_old_transients');
						$this->clear_old_transients();
						add_settings_error('gce_options', 'gce_cleared_old_transients', __('Old cached data cleared.', GCE_TEXT_DOMAIN), 'updated');
						break;
					case 'ignore_old_transients':
						check_admin_referer('gce_action_ignore_old_transients');
						delete_option('gce_clear_old_transients');
				}
			}

			register_setting('gce_options', 'gce_options', array($this, 'validate_feed_options'));
			register_setting('gce_general', 'gce_general', array($this, 'validate_general_options'));

			require_once 'admin/add.php';
			require_once 'admin/edit.php';
			require_once 'admin/delete.php';
			require_once 'admin/refresh.php';
		}

		//Clears any expired transients from the database
		function clear_old_transients(){
			global $wpdb;

			//Retrieve names of all transients
			$transients = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '%transient%' AND option_name NOT LIKE '%transient_timeout%'");

			if(!empty($transients)){
				foreach($transients as $transient){
					//Attempt to retrieve the transient. If it has expired, it will be deleted
					get_transient(str_replace('_transient_', '', $transient->option_name));
				}
			}

			//Remove the flag
			delete_option('gce_clear_old_transients');
		}

		//Register the widget
		function add_widget(){
			require_once 'widget/gce-widget.php';
			return register_widget('GCE_Widget');
		}

		//Check / validate submitted feed options data before being stored
		function validate_feed_options($input){
			//Get saved options
			$options = get_option(GCE_OPTIONS_NAME);

			if(isset($input['submit_delete'])){
				//If delete button was clicked, delete feed from options array and remove associated transients
				unset($options[$input['id']]);
				$this->delete_feed_transients((int)$input['id']);
				add_settings_error('gce_options', 'gce_deleted', __(sprintf('Feed %s deleted.', absint($input['id'])), GCE_TEXT_DOMAIN), 'updated');
			}else if(isset($input['submit_refresh'])){
				//If refresh button was clicked, delete transients associated with feed
				$this->delete_feed_transients((int)$input['id']);
				add_settings_error('gce_options', 'gce_refreshed', __(sprintf('Cached data for feed %s cleared.', absint($input['id'])), GCE_TEXT_DOMAIN), 'updated');
			}else{
				//Otherwise, validate options and add / update them

				//Check id is positive integer
				$id = absint($input['id']);
				//Escape title text
				$title = esc_html($input['title']);
				//Escape feed url
				$url = esc_url($input['url']);

				//Array of valid options for retrieve_from and retrieve_until settings
				$valid_retrieve_options = array('now', 'today', 'week', 'month-start', 'month-end', 'any', 'date');

				$retrieve_from = 'today';
				$retrieve_from_value = 0;

				//Ensure retrieve_from is valid
				if(in_array($input['retrieve_from'], $valid_retrieve_options)){
					$retrieve_from = $input['retrieve_from'];
					$retrieve_from_value = (int)$input['retrieve_from_value'];
				}

				$retrieve_until = 'any';
				$retrieve_until_value = 0;

				//Ensure retrieve_until is valid
				if(in_array($input['retrieve_until'], $valid_retrieve_options)){
					$retrieve_until = $input['retrieve_until'];
					$retrieve_until_value = (int)$input['retrieve_until_value'];
				}

				//Check max events is a positive integer. If absint returns 0, reset to default (25)
				$max_events = (absint($input['max_events']) == 0 ? 25 : absint($input['max_events']));

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

				$use_builder = (($input['use_builder'] == 'false') ? 'false' : 'true');
				$builder = wp_kses_post($input['builder']);

				//Fill options array with validated values
				$options[$id] = array(
					'id' => $id, 
					'title' => $title,
					'url' => $url,
					'retrieve_from' => $retrieve_from,
					'retrieve_until' => $retrieve_until,
					'retrieve_from_value' => $retrieve_from_value,
					'retrieve_until_value' => $retrieve_until_value,
					'max_events' => $max_events,
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
					'display_separator' => $display_separator,
					'use_builder' => $use_builder,
					'builder' => $builder
				);

				if(isset($input['submit_add'])){
					add_settings_error('gce_options', 'gce_added', __(sprintf('Feed %s added.', absint($input['id'])), GCE_TEXT_DOMAIN), 'updated');
				}else{
					add_settings_error('gce_options', 'gce_edited', __(sprintf('Settings for feed %s updated.', absint($input['id'])), GCE_TEXT_DOMAIN), 'updated');
				}
			}

			return $options;
		}

		//Validate submitted general options
		function validate_general_options($input){
			$options = get_option(GCE_GENERAL_OPTIONS_NAME);

			$options['stylesheet'] = esc_url($input['stylesheet']);
			$options['javascript'] = (isset($input['javascript']) ? true : false);
			$options['loading'] = esc_html($input['loading']);
			$options['error'] = wp_filter_kses($input['error']);
			$options['fields'] = (isset($input['fields']) ? true : false);

			add_settings_error('gce_general', 'gce_general_updated', __('General options updated.', GCE_TEXT_DOMAIN), 'updated');

			return $options;
		}

		//Delete all transients (cached feed data) associated with feeds specified
		function delete_feed_transients($id){
				delete_transient('gce_feed_' . $id);
				delete_transient('gce_feed_' . $id . '_url');
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

				//Ensure max events is a positive integer
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
				wp_enqueue_style('gce_styles', WP_PLUGIN_URL . '/' . GCE_PLUGIN_NAME . '/css/gce-style.css');

				//If user has entered a URL to a custom stylesheet, enqueue it too
				$options = get_option(GCE_GENERAL_OPTIONS_NAME);
				if(isset($options['stylesheet']) && $options['stylesheet'] != '') wp_enqueue_style('gce_custom_styles', $options['stylesheet']);
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
			}else{
				wp_enqueue_script('gce_scripts', WP_PLUGIN_URL . '/' . GCE_PLUGIN_NAME . '/js/gce-admin-script.js', array('jquery'));
			}
		}

		//AJAX stuffs
		function gce_ajax(){
			if(isset($_GET['gce_feed_ids'])){
				if($_GET['gce_type'] == 'page'){
					//The page grid markup to be returned via AJAX
					echo gce_print_grid($_GET['gce_feed_ids'], $_GET['gce_title_text'], $_GET['gce_max_events'], true, $_GET['gce_month'], $_GET['gce_year']);
				}elseif($_GET['gce_type'] == 'widget'){
					//The widget grid markup to be returned via AJAX
					gce_widget_content_grid($_GET['gce_feed_ids'], $_GET['gce_title_text'], $_GET['gce_max_events'], $_GET['gce_widget_id'], true, $_GET['gce_month'], $_GET['gce_year']);
				}
			}
			die();
		}
	}
}

function gce_print_list($feed_ids, $title_text, $max_events, $grouped = false){
	require_once 'inc/gce-parser.php';

	$ids = explode('-', $feed_ids);

	//Create new GCE_Parser object, passing array of feed id(s)
	$list = new GCE_Parser($ids, $title_text, $max_events);

	$num_errors = $list->get_num_errors();

	//If there are less errors than feeds parsed, at least one feed must have parsed successfully so continue to display the list
	if($num_errors < count($ids)){
		$markup = '<div class="gce-page-list">' . $list->get_list($grouped) . '</div>';

		//If there was at least one error, return the list markup with error messages (for admins only)
		if($num_errors > 0 && current_user_can('manage_options')) return $list->error_messages() . $markup;

		//Otherwise just return the list markup
		return $markup;
	}else{
		//If current user is an admin, display an error message explaining problem(s). Otherwise, display a 'nice' error messsage
		if(current_user_can('manage_options')){
			return $list->error_messages();
		}else{
			$options = get_option(GCE_GENERAL_OPTIONS_NAME);
			return $options['error'];
		}
	}
}

function gce_print_grid($feed_ids, $title_text, $max_events, $ajaxified = false, $month = null, $year = null){
	require_once 'inc/gce-parser.php';

	$ids = explode('-', $feed_ids);

	//Create new GCE_Parser object, passing array of feed id(s) returned from gce_get_feed_ids()
	$grid = new GCE_Parser($ids, $title_text, $max_events);

	$num_errors = $grid->get_num_errors();

	//If there are less errors than feeds parsed, at least one feed must have parsed successfully so continue to display the grid
	if($num_errors < count($ids)){
		$markup = '<div class="gce-page-grid" id="gce-page-grid-' . $feed_ids .'">';

		//Add AJAX script if required
		if($ajaxified) $markup .= '<script type="text/javascript">jQuery(document).ready(function($){gce_ajaxify("gce-page-grid-' . $feed_ids . '", "' . $feed_ids . '", "' . $max_events . '", "' . $title_text . '", "page");});</script>';

		$markup .= $grid->get_grid($year, $month, $ajaxified) . '</div>';

		//If there was at least one error, return the grid markup with an error message (for admins only)
		if($num_errors > 0 && current_user_can('manage_options')) return $grid->error_messages() . $markup;

		//Otherwise just return the grid markup
		return $markup;
	}else{
		//If current user is an admin, display an error message explaining problem. Otherwise, display a 'nice' error messsage
		if(current_user_can('manage_options')){
			return $grid->error_messages();
		}else{
			$options = get_option(GCE_GENERAL_OPTIONS_NAME);
			return $options['error'];
		}
	}
}

$gce = new Google_Calendar_Events();
?>