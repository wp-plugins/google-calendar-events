<?php
//Redirect to the main plugin options page if form has been submitted
if($_GET['action'] == 'add' && $_GET['updated']){
	wp_redirect(admin_url() . 'options-general.php?page=' . GCE_PLUGIN_NAME . '.php&updated=added');
}

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

//Cache duration
function gce_add_cache_duration_field(){
	?>
	<span class="description"><?php _e('The length of time, in seconds, to cache the feed (43200 = 12 hours). If this feed changes regularly, you may want to reduce the cache duration.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[cache_duration]" value="43200" />
	<?php
}
?>