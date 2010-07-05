<?php
require_once WP_PLUGIN_DIR . '/' . GCE_PLUGIN_NAME . '/inc/gce-parser.php';

class GCE_Widget extends WP_Widget{
	function GCE_Widget(){
		parent::WP_Widget(false, $name = 'Google Calendar Events', array('description' => 'Display a list or calendar grid of events from a Google Calendar feed you have added'));
	}

	function widget($args, $instance){
		extract($args);

		//Get saved feed options
		$options = get_option(GCE_OPTIONS_NAME);

		//Output before widget and widget title stuff
		echo $before_widget;
		echo $before_title . $options[$instance['id']]['title'] . $after_title;

		//Output correct widget content based on display type chosen
		switch($instance['display_type']){
			case 'grid':
				echo '<div class="gce-widget-grid">';
				//Output main widget content as grid (no AJAX)
				gce_widget_content_grid($instance['id'], $args['widget_id']);
				break;
			case 'ajax':
				echo '<div class="gce-widget-grid">';
				//Output main widget content as grid (with AJAX)
				gce_widget_content_grid($instance['id'], $args['widget_id'], true);
				break;
			case 'list':
				echo '<div class="gce-widget-list">';
				//Output main widget content as list
				gce_widget_content_list($instance['id']);
				break;
		}

		echo '</div>';

		//Output after widget stuff
		echo $after_widget;
	}

	function update($new_instance, $old_instance){
		$instance = $old_instance;
		$instance['id'] = $new_instance['id'];
		$instance['display_type'] = $new_instance['display_type'];
		return $instance;
	}

	function form($instance){
		//Get saved feed options
		$options = get_option(GCE_OPTIONS_NAME);

		if(empty($options)){
			//If no feeds ?>
			<p><?php _e('No feeds have been added yet. You can add a feed in the Google Calendar Events settings.', GCE_TEXT_DOMAIN); ?></p>
			<?php
		}else{
			//If there are feeds, select a feed ?>
			<p>
				<label for="<?php echo $this->get_field_id('id'); ?>"><?php _e('Select a Feed to Use:', GCE_TEXT_DOMAIN); ?></label>
				<select id="<?php echo $this->get_field_id('id'); ?>" name="<?php echo $this->get_field_name('id'); ?>" class="widefat">
				<?php foreach($options as $key => $feed){ ?>
					<option value="<?php echo $key; ?>"<?php selected($instance['id'], $key);?>><?php echo $key . ' - ' . $feed['title']; ?></option>
				<?php } ?>
				</select>
			</p>
			<?php //Display type (grid / ajax / list) ?>
			<p>
				<label for="<?php echo $this->get_field_id('display_type'); ?>"><?php _e('Display as:', GCE_TEXT_DOMAIN); ?></label>
				<select id="<?php echo $this->get_field_id('display_type'); ?>" name="<?php echo $this->get_field_name('display_type'); ?>" class="widefat">
					<option value="grid"<?php selected($instance['display_type'], 'grid');?>><?php _e('Calendar Grid', GCE_TEXT_DOMAIN); ?></option>
					<option value="ajax"<?php selected($instance['display_type'], 'ajax');?>><?php _e('Calendar Grid - with AJAX', GCE_TEXT_DOMAIN); ?></option>
					<option value="list"<?php selected($instance['display_type'], 'list');?>><?php _e('List', GCE_TEXT_DOMAIN); ?></option>
				</select>
			</p>
			<?php 
		}
	}
}

//Outputs the main widget content as a calendar grid
function gce_widget_content_grid($feed_id, $widget_id, $ajaxified = false, $month = null, $year = null){
	//Get saved feed options
	$options = get_option('gce_options');

	//Set time and date formats to WordPress defaults if not set by user
	$df = $options[$feed_id]['date_format'];
	$tf = $options[$feed_id]['time_format'];
	if($df == '') $df = get_option('date_format');
	if($tf == '') $tf = get_option('time_format');

	//Creates a new GCE_Parser object for $feed_id
	$widget_feed_data = new GCE_Parser(
		$options[$feed_id]['url'],
		$options[$feed_id]['show_past_events'],
		$options[$feed_id]['max_events'],
		$options[$feed_id]['cache_duration'],
		$df,
		$tf,
		get_option('start_of_week')
	);

	//Check that feed parsed ok
	if($widget_feed_data->parsed_ok()){
		//Only add AJAX script if AJAX grid is enabled
		if($ajaxified){
			?><script type="text/javascript">jQuery(document).ready(function($){gce_ajaxify("<?php echo $widget_id; ?>", "<?php echo $feed_id; ?>", "widget");});</script><?php
		}

		//Outputs calendar grid for specified month and year
		echo $widget_feed_data->get_grid($year, $month, $ajaxified);
	}else{
		echo 'The Google Calendar feed was not parsed successfully, please check that the feed URL is correct.';
	}
}

//Outputs the main widget content as a list of events
function gce_widget_content_list($id){
	//Get saved feed options
	$options = get_option(GCE_OPTIONS_NAME);

	//Set time and date formats to WordPress defaults if not set by user
	$df = $options[$id]['date_format'];
	$tf = $gce_options[$id]['time_format'];
	if($df == '') $df = get_option('date_format');
	if($tf == '') $tf = get_option('time_format');

	//Creates a new GCE_Parser object for $feed_id
	$widget_feed_data = new GCE_Parser(
		$options[$id]['url'],
		$options[$id]['show_past_events'],
		$options[$id]['max_events'],
		$options[$id]['cache_duration'],
		$df,
		$tf
	);

	//Check that feed parsed ok
	if($widget_feed_data->parsed_ok()){
		echo $widget_feed_data->get_list();
	}else{
		echo 'The Google Calendar feed was not parsed successfully, please check that the feed URL is correct.';
	}
}

//AJAX stuff. Passes the data from JavaScript to above gce_widget_content_grid function
if($_GET['gce_type'] == 'widget'){
	if(isset($_GET['gce_feed_id'])){
		gce_widget_content_grid($_GET['gce_feed_id'], $_GET['gce_widget_id'], true, $_GET['gce_month'], $_GET['gce_year']);
		die();
	}
}
?>