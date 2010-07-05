<?php
//Redirect to the main plugin options page if form has been submitted
if($_GET['action'] == 'edit' && $_GET['updated']){
	wp_redirect(admin_url() . 'options-general.php?page=' . GCE_PLUGIN_NAME . '.php&updated=edited');
}

//Main text
function gce_edit_main_text(){
	?>
	<p><?php _e('Make any changes you require to the feed details below, then click the Save Changes button.', GCE_TEXT_DOMAIN); ?></p>
	<?php
}

//ID
function gce_edit_id_field(){
	$options = get_option(GCE_OPTIONS_NAME);
	$options = $options[$_GET['id']];
	?>
	<input type="text" disabled="disabled" value="<?php echo $options['id']; ?>" size="3" />
	<input type="hidden" name="gce_options[id]" value="<?php echo $options['id']; ?>" />
	<?php
}

//Title
function gce_edit_title_field(){
	$options = get_option(GCE_OPTIONS_NAME);
	$options = $options[$_GET['id']];
	?>
	<span class="description"><?php _e('Anything you like. \'Upcoming Club Events\', for example.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[title]" value="<?php echo $options['title']; ?>" size="50" />
	<?php
}

//URL
function gce_edit_url_field(){
	$options = get_option(GCE_OPTIONS_NAME);
	$options = $options[$_GET['id']];
	?>
	<span class="description"><?php _e('This will probably be something like: \'http://www.google.com/calendar/feeds/your-email@gmail.com/public/full\'.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[url]" value="<?php echo $options['url']; ?>" size="100" />
	<?php
}

//Show past events
function gce_edit_show_past_events_field(){
	$options = get_option(GCE_OPTIONS_NAME);
	$options = $options[$_GET['id']];
	?>
	<span class="description"><?php _e('Select No to retrieve events from now onwards. Select Yes to retrieve events from the first of this month onwards.', GCE_TEXT_DOMAIN); ?></span>
	<fieldset>
		<label><input type="radio" name="gce_options[show_past_events]" value="false"<?php checked($options['show_past_events'], 'false'); ?> /> <?php _e('No', GCE_TEXT_DOMAIN); ?></label>
		<br />
		<label><input type="radio" name="gce_options[show_past_events]" value="true"<?php checked($options['show_past_events'], 'true'); ?> /> <?php _e('Yes', GCE_TEXT_DOMAIN); ?></label>
	</fieldset>
	<?php
}

//Max events
function gce_edit_max_events_field(){
	$options = get_option(GCE_OPTIONS_NAME);
	$options = $options[$_GET['id']];
	?>
	<span class="description"><?php _e('The default number of events to retrieve from a Google Calendar feed is 25, but you may want less for a list, or more for a calendar grid.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[max_events]" value="<?php echo $options['max_events']; ?>" size="3" />
	<?php
}

//Date format
function gce_edit_date_format_field(){
	$options = get_option(GCE_OPTIONS_NAME);
	$options = $options[$_GET['id']];
	?>
	<span class="description"><?php _e('In <a href="http://php.net/manual/en/function.date.php">PHP date format</a>. Leave this blank if you\'d rather stick with the default format for your blog.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[date_format]" value="<?php echo $options['date_format']; ?>" />
	<?php
}

//Time format
function gce_edit_time_format_field(){
	$options = get_option(GCE_OPTIONS_NAME);
	$options = $options[$_GET['id']];
	?>
	<span class="description"><?php _e('In <a href="http://php.net/manual/en/function.date.php">PHP date format</a>. Again, leave this blank to stick with the default.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[time_format]" value="<?php echo $options['time_format']; ?>" />
	<?php
}

//Cache duration
function gce_edit_cache_duration_field(){
	$options = get_option(GCE_OPTIONS_NAME);
	$options = $options[$_GET['id']];
	?>
	<span class="description"><?php _e('The length of time, in seconds, to cache the feed (43200 = 12 hours). If this feed changes regularly, you may want to reduce the cache duration.', GCE_TEXT_DOMAIN); ?></span>
	<br />
	<input type="text" name="gce_options[cache_duration]" value="<?php echo $options['cache_duration']; ?>" />
	<?php
}
?>