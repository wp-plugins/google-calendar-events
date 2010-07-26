<?php
require_once WP_PLUGIN_DIR . '/' . GCE_PLUGIN_NAME . '/inc/gce-parser.php';

class GCE_Widget extends WP_Widget{
	function GCE_Widget(){
		parent::WP_Widget(false, $name = __('Google Calendar Events', GCE_TEXT_DOMAIN), array('description' => __('Display a list or calendar grid of events from one or more Google Calendar feeds you have added', GCE_TEXT_DOMAIN)));
	}

	function widget($args, $instance){
		extract($args);

		//Output before widget stuff
		echo $before_widget;

		//Get saved feed options
		$options = get_option(GCE_OPTIONS_NAME);

		//Check whether any feeds have been added yet
		if(is_array($options) && !empty($options)){
			//Output title stuff
			echo $before_title . $instance['title'] . $after_title;

			//Break comma delimited list of feed ids into array
			$feed_ids = explode(',', str_replace(' ', '', $instance['id']));

			//Check each id is an integer, if not, remove it from the array
			foreach($feed_ids as $key => $feed_id){
				if(absint($feed_id) == 0) unset($feed_ids[$key]);
			}

			$no_feeds_exist = true;

			//If at least one of the feed ids entered exists, set no_feeds_exist to false
			foreach($feed_ids as $feed_id){
				if(isset($options[$feed_id])) $no_feeds_exist = false;
			}

			//Check that at least one valid feed id has been entered
			if(count((array)$feed_ids) == 0 || $no_feeds_exist){
				_e('No valid Feed IDs have been entered for this widget. Please check that you have entered the IDs correctly and that the Feeds have not been deleted.', GCE_TEXT_DOMAIN);
			}else{
				//Turns feed_ids back into string or feed ids delimited by '-' ('1-2-3-4' for example)
				$feed_ids = implode('-', $feed_ids);

				$title_text = $instance['display_title'] ? $instance['display_title_text'] : null;

				//Output correct widget content based on display type chosen
				switch($instance['display_type']){
					case 'grid':
						echo '<div class="gce-widget-grid" id="' . $args['widget_id'] . '-container">';
						//Output main widget content as grid (no AJAX)
						gce_widget_content_grid($feed_ids, $title_text, $args['widget_id'] . '-container');
						echo '</div>';
						break;
					case 'ajax':
						echo '<div class="gce-widget-grid" id="' . $args['widget_id'] . '-container">';
						//Output main widget content as grid (with AJAX)
						gce_widget_content_grid($feed_ids, $title_text, $args['widget_id'] . '-container', true);
						echo '</div>';
						break;
					case 'list':
						echo '<div class="gce-widget-list" id="' . $args['widget_id'] . '-container">';
						//Output main widget content as list
						gce_widget_content_list($feed_ids, $title_text);
						echo '</div>';
						break;
				}
			}
		}else{
			_e('No feeds have been added yet. You can add a feed in the Google Calendar Events settings.', GCE_TEXT_DOMAIN);
		}

		//Output after widget stuff
		echo $after_widget;
	}

	function update($new_instance, $old_instance){
		$instance = $old_instance;
		$instance['title'] = esc_html($new_instance['title']);
		$instance['id'] = esc_html($new_instance['id']);
		$instance['display_type'] = esc_html($new_instance['display_type']);
		$instance['display_title'] = $new_instance['display_title'] == 'on' ? true : false;
		$instance['display_title_text'] = wp_filter_kses($new_instance['display_title_text']);
		return $instance;
	}

	function form($instance){
		//Get saved feed options
		$options = get_option(GCE_OPTIONS_NAME);

		if(empty($options)){
			//If no feeds or groups ?>
			<p><?php _e('No feeds have been added yet. You can add feeds in the Google Calendar Events settings.', GCE_TEXT_DOMAIN); ?></p>
			<?php
		}else{
			$title = isset($instance['title']) ? $instance['title'] : '';
			$ids = isset($instance['id']) ? $instance['id'] : '';
			$display_type = isset($instance['display_type']) ? $instance['display_type'] : 'grid';
			$display_title = isset($instance['display_title']) ? $instance['display_title'] : true;
			$title_text = isset($instance['display_title_text']) ? $instance['display_title_text'] : 'Events on';
			?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
				<input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>" class="widefat" />
			</p><p>
				<label for="<?php echo $this->get_field_id('id'); ?>">
					<?php _e('Feed IDs to display in this widget, separated by commas (e.g. 1, 2, 4):', GCE_TEXT_DOMAIN); ?>
				</label>
				<input type="text" id="<?php echo $this->get_field_id('id'); ?>" name="<?php echo $this->get_field_name('id'); ?>" value="<?php echo $ids; ?>" class="widefat" />
			</p><p>
				<label for="<?php echo $this->get_field_id('display_type'); ?>"><?php _e('Display as:', GCE_TEXT_DOMAIN); ?></label>
				<select id="<?php echo $this->get_field_id('display_type'); ?>" name="<?php echo $this->get_field_name('display_type'); ?>" class="widefat">
					<option value="grid"<?php selected($display_type, 'grid');?>><?php _e('Calendar Grid', GCE_TEXT_DOMAIN); ?></option>
					<option value="ajax"<?php selected($display_type, 'ajax');?>><?php _e('Calendar Grid - with AJAX', GCE_TEXT_DOMAIN); ?></option>
					<option value="list"<?php selected($display_type, 'list');?>><?php _e('List', GCE_TEXT_DOMAIN); ?></option>
				</select>
			</p><p>
				<label for="<?php echo $this->get_field_id('display_title'); ?>">Display title on tooltip / list item? (e.g. 'Events on 7th March')</label>
				<br />
				<input type="checkbox" id="<?php echo $this->get_field_id('display_title'); ?>" name="<?php echo $this->get_field_name('display_title'); ?>"<?php checked($display_title, true); ?> value="on" />
				<input type="text" id="<?php echo $this->get_field_id('display_title_text'); ?>" name="<?php echo $this->get_field_name('display_title_text'); ?>" value="<?php echo $title_text; ?>" style="width:90%;" />
			</p>
			<?php 
		}
	}
}

function gce_widget_content_grid($feed_ids, $title_text, $widget_id, $ajaxified = false, $month = null, $year = null){
	//Create new GCE_Parser object, passing array of feed id(s)
	$grid = new GCE_Parser(explode('-', $feed_ids), $title_text);

	//If the feed(s) parsed ok, output the grid markup, otherwise output an error message
	if(count($grid->get_errors()) == 0){
		//Add AJAX script if required
		if($ajaxified) ?><script type="text/javascript">jQuery(document).ready(function($){gce_ajaxify("<?php echo $widget_id; ?>", "<?php echo $feed_ids; ?>", "<?php echo $title_text; ?>", "widget");});</script><?php

		echo $grid->get_grid($year, $month, $ajaxified);
	}else{
		printf(__('The following feeds were not parsed successfully: %s. Please check that the feed URLs are correct and that the feeds have public sharing enabled.'), implode(', ', $grid->get_errors()));
	}
}

function gce_widget_content_list($feed_ids, $title_text){
	//Create new GCE_Parser object, passing array of feed id(s)
	$list = new GCE_Parser(explode('-', $feed_ids), $title_text);

	//If the feed(s) parsed ok, output the list markup, otherwise output an error message
	if(count($list->get_errors()) == 0){
		echo $list->get_list();
	}else{
		printf(__('The following feeds were not parsed successfully: %s. Please check that the feed URLs are correct and that the feeds have public sharing enabled.'), implode(', ', $list->get_errors()));
	}
}

//AJAX stuff. Passes the data from JavaScript to above gce_widget_content_grid function
if(isset($_GET['gce_type']) && $_GET['gce_type'] == 'widget'){
	if(isset($_GET['gce_feed_ids'])){
		gce_widget_content_grid($_GET['gce_feed_ids'], $_GET['gce_title_text'], $_GET['gce_widget_id'], true, $_GET['gce_month'], $_GET['gce_year']);
		die();
	}
}
?>