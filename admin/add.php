<?php
//Redirect to the main plugin options page if form has been submitted
if(isset($_GET['action'])){
	if($_GET['action'] == 'add' && isset($_GET['updated'])){
		wp_redirect(admin_url('options-general.php?page=' . GCE_PLUGIN_NAME . '.php&updated=added'));
	}
}

add_settings_section('gce_add', __('Add a Feed', GCE_TEXT_DOMAIN), 'gce_add_main_text', 'add_feed');
//Unique ID                                          //Title                                                         //Function                        //Page      //Section ID
add_settings_field('gce_add_id_field',               __('Feed ID', GCE_TEXT_DOMAIN),                                 'gce_add_id_field',               'add_feed', 'gce_add');
add_settings_field('gce_add_title_field',            __('Feed Title', GCE_TEXT_DOMAIN),                              'gce_add_title_field',            'add_feed', 'gce_add');
add_settings_field('gce_add_url_field',              __('Feed URL', GCE_TEXT_DOMAIN),                                'gce_add_url_field',              'add_feed', 'gce_add');
add_settings_field('gce_add_show_past_events_field', __('Retrieve past events for current month?', GCE_TEXT_DOMAIN), 'gce_add_show_past_events_field', 'add_feed', 'gce_add');
add_settings_field('gce_add_max_events_field',       __('Maximum number of events to retrieve', GCE_TEXT_DOMAIN),    'gce_add_max_events_field',       'add_feed', 'gce_add');
add_settings_field('gce_add_date_format_field',      __('Date format', GCE_TEXT_DOMAIN),                             'gce_add_date_format_field',      'add_feed', 'gce_add');
add_settings_field('gce_add_time_format_field',      __('Time format', GCE_TEXT_DOMAIN),                             'gce_add_time_format_field',      'add_feed', 'gce_add');
add_settings_field('gce_add_timezone_field',         __('Timezone adjustment', GCE_TEXT_DOMAIN),                     'gce_add_timezone_field',         'add_feed', 'gce_add');
add_settings_field('gce_add_cache_duration_field',   __('Cache duration', GCE_TEXT_DOMAIN),                          'gce_add_cache_duration_field',   'add_feed', 'gce_add');

add_settings_section('gce_add_display', __('Display Options', GCE_TEXT_DOMAIN), 'gce_add_display_main_text', 'add_display');
add_settings_field('gce_add_display_start_field',    __('Display start time?', GCE_TEXT_DOMAIN),                     'gce_add_display_start_field',    'add_display', 'gce_add_display');
add_settings_field('gce_add_display_end_field',      __('Display end time and date?', GCE_TEXT_DOMAIN),              'gce_add_display_end_field',      'add_display', 'gce_add_display');
add_settings_field('gce_add_display_location_field', __('Display location?', GCE_TEXT_DOMAIN),                       'gce_add_display_location_field', 'add_display', 'gce_add_display');
add_settings_field('gce_add_display_desc_field',     __('Display description?', GCE_TEXT_DOMAIN),                    'gce_add_display_desc_field',     'add_display', 'gce_add_display');
add_settings_field('gce_add_display_link_field',     __('Display link to event?', GCE_TEXT_DOMAIN),                  'gce_add_display_link_field',     'add_display', 'gce_add_display');

//Main text
function gce_add_main_text(){
	?>
	<p><?php _e('Enter the feed details below, then click the Add Feed button.', GCE_TEXT_DOMAIN); ?></p>
	<?php
}

//ID
function gce_add_id_field(){
	$options = get_option(GCE_OPTIONS_NAME);
	$id = 1;
	if(!empty($options)){ //If there are no saved feeds
		//Go to last saved feed
		end($options);
		//Set id to last feed id + 1
		$id = key($options) + 1;
	}

	?>
	<input type="text" disabled="disabled" value="<?php echo $id; ?>" size="3" />
	<input type="hidden" name="gce_options[id]" value="<?php echo $id; ?>" />
	<?php
}

//Title
function gce_add_title_field(){
	?>
	<span class="description"><?php _e('Anything you like. \'Upcoming Club Events\', for example.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[title]" size="50" />
	<?php
}

//URL
function gce_add_url_field(){
	?>
	<span class="description"><?php _e('This will probably be something like: \'http://www.google.com/calendar/feeds/your-email@gmail.com/public/full\'.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[url]" size="100" class="required" />
	<?php
}

//Show past events
function gce_add_show_past_events_field(){
	?>
	<span class="description"><?php _e('Select No to retrieve events from now onwards. Select Yes to retrieve events from the first of this month onwards.', GCE_TEXT_DOMAIN); ?></span>
	<fieldset>
		<label><input type="radio" name="gce_options[show_past_events]" value="false" checked="checked" /> <?php _e('No', GCE_TEXT_DOMAIN); ?></label>
		<br />
		<label><input type="radio" name="gce_options[show_past_events]" value="true" /> <?php _e('Yes', GCE_TEXT_DOMAIN); ?></label>
	</fieldset>
	<?php
}

//Max events
function gce_add_max_events_field(){
	?>
	<span class="description"><?php _e('The default number of events to retrieve from a Google Calendar feed is 25, but you may want less for a list, or more for a calendar grid.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[max_events]" value="25" size="3" />
	<?php
}

//Date format
function gce_add_date_format_field(){
	?>
	<span class="description"><?php _e('In <a href="http://php.net/manual/en/function.date.php">PHP date format</a>. Leave this blank if you\'d rather stick with the default format for your blog.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[date_format]" />
	<?php
}

//Time format
function gce_add_time_format_field(){
	?>
	<span class="description"><?php _e('In <a href="http://php.net/manual/en/function.date.php">PHP date format</a>. Again, leave this blank to stick with the default.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[time_format]" />
	<?php
}

//Timezone offset
function gce_add_timezone_field(){
	require_once 'timezone-choices.php';
	$timezone_list = gce_get_timezone_choices();
	//Set selected="selected" for default option
	$timezone_list = str_replace('<option value="default">Default</option>', '<option value="default" selected="selected">Default</option>', $timezone_list);
	?>
	<span class="description"><?php _e('If you are having problems with dates and times displaying in the wrong timezone, select a city in your required timezone here.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<?php echo $timezone_list; ?>
	<?php
}

//Cache duration
function gce_add_cache_duration_field(){
	?>
	<span class="description"><?php _e('The length of time, in seconds, to cache the feed (43200 = 12 hours). If this feed changes regularly, you may want to reduce the cache duration.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[cache_duration]" value="43200" />
	<?php
}


//Display options
function gce_add_display_main_text(){
	?>
	<p><?php _e('These settings control what information will be displayed for this feed in the tooltip (for grids), or in a list.', GCE_TEXT_DOMAIN); ?></p>
	<p><?php _e('You can use some HTML in the text fields, but ensure it is valid or things might go wonky. Text fields can be empty too.', GCE_TEXT_DOMAIN); ?></p>
	<?php
}

function gce_add_display_start_field(){
	?>
	<input type="checkbox" name="gce_options[display_start]" value="on" checked="checked" />
	<span class="description"><?php _e('Show the start time of events?', GCE_TEXT_DOMAIN); ?></span>
	<br /><br />
	<input type="text" name="gce_options[display_start_text]" value="Starts:" />
	<span class="description"><?php _e('Text to display before the start time.', GCE_TEXT_DOMAIN); ?></span>
	<?php
}

function gce_add_display_end_field(){
	?>
	<input type="checkbox" name="gce_options[display_end]" value="on" />
	<span class="description"><?php _e('Show the end time and date of events? (Date will be shown as well as time as events can span several days).', GCE_TEXT_DOMAIN); ?></span>
	<br /><br />
	<input type="text" name="gce_options[display_end_text]" value="Ends:" />
	<span class="description"><?php _e('Text to display before the end time / date.', GCE_TEXT_DOMAIN); ?></span>
	<?php
}

function gce_add_display_location_field(){
	?>
	<input type="checkbox" name="gce_options[display_location]" value="on" />
	<span class="description"><?php _e('Show the location of events?', GCE_TEXT_DOMAIN); ?></span>
	<br /><br />
	<input type="text" name="gce_options[display_location_text]" value="Location:" />
	<span class="description"><?php _e('Text to display before the location.', GCE_TEXT_DOMAIN); ?></span>
	<?php
}

function gce_add_display_desc_field(){
	?>
	<input type="checkbox" name="gce_options[display_desc]" value="on" />
	<span class="description"><?php _e('Show the description of events? (URLs in the description will be made into links).', GCE_TEXT_DOMAIN); ?></span>
	<br /><br />
	<input type="text" name="gce_options[display_desc_text]" value="Description:" />
	<span class="description"><?php _e('Text to display before the description.', GCE_TEXT_DOMAIN); ?></span>
	<?php
}

function gce_add_display_link_field(){
	?>
	<input type="checkbox" name="gce_options[display_link]" value="on" checked="checked" />
	<span class="description"><?php _e('Show a link to the Google Calendar page for an event?', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="checkbox" name="gce_options[display_link_target]" value="on" />
	<span class="description"><?php _e('Links open in a new window / tab?', GCE_TEXT_DOMAIN); ?></span>
	<br /><br />
	<input type="text" name="gce_options[display_link_text]" value="More details" />
	<span class="description"><?php _e('The link text to be displayed.', GCE_TEXT_DOMAIN); ?></span>
	<?php
}
?>