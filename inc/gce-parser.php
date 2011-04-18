<?php
class GCE_Parser{
	var $feeds = array();
	var $merged_feed_data = array();
	var $title = null;
	var $max_events_display = 0;

	function __construct($feed_ids, $title_text = null, $max_events = 0){
		require_once('gce-feed.php');

		$this->title = $title_text;
		$this->max_events_display = $max_events;

		//Get the feed options
		$options = get_option(GCE_OPTIONS_NAME);

		foreach($feed_ids as $single_feed){
			//Get the options for this particular feed
			if(isset($options[$single_feed])){
				$feed_options = $options[$single_feed];

				$feed = new GCE_Feed();

				$feed->set_feed_id($feed_options['id']);
				$feed->set_feed_title($feed_options['title']);
				$feed->set_feed_url($feed_options['url']);
				$feed->set_max_events($feed_options['max_events']);
				$feed->set_cache_duration($feed_options['cache_duration']);

				//Set the timezone if anything other than default
				if($feed_options['timezone'] != 'default') $feed->set_timezone($feed_options['timezone']);

				//Set the start date to the appropriate value based on the retrieve_from option
				switch($feed_options['retrieve_from']){
					case 'now':
						$feed->set_start_date(time() + $feed_options['retrieve_from_value'] - date('Z'));
						break;
					case 'today':
						$feed->set_start_date(mktime(0, 0, 0, date('m'), date('j'), date('Y')) + $feed_options['retrieve_from_value'] - date('Z'));
						break;
					case 'week':
						$feed->set_start_date(mktime(0, 0, 0, date('m'), (date('j') - date('w') + get_option('start_of_week')), date('Y')) + $feed_options['retrieve_from_value'] - date('Z'));
						break;
					case 'month-start':
						$feed->set_start_date(mktime(0, 0, 0, date('m'), 1, date('Y')) + $feed_options['retrieve_from_value'] - date('Z'));
						break;
					case 'month-end':
						$feed->set_start_date(mktime(0, 0, 0, date('m') + 1, 1, date('Y')) + $feed_options['retrieve_from_value'] - date('Z'));
						break;
					case 'date':
						$feed->set_start_date($feed_options['retrieve_from_value']);
						break;
					case 'any':
						$feed->set_show_past_events(true);
				}

				//Set the end date to the appropriate value based on the retrieve_until option
				switch($feed_options['retrieve_until']){
					case 'now':
						$feed->set_end_date(time() + $feed_options['retrieve_until_value'] - date('Z'));
						break;
					case 'today':
						$feed->set_end_date(mktime(0, 0, 0, date('m'), date('j'), date('Y')) + $feed_options['retrieve_until_value'] - date('Z'));
						break;
					case 'week':
						$feed->set_end_date(mktime(0, 0, 0, date('m'), (date('j') - date('w') + get_option('start_of_week')), date('Y')) + $feed_options['retrieve_until_value'] - date('Z'));
						break;
					case 'month-start':
						$feed->set_end_date(mktime(0, 0, 0, date('m'), 1, date('Y')) + $feed_options['retrieve_until_value'] - date('Z'));
						break;
					case 'month-end':
						$feed->set_end_date(mktime(0, 0, 0, date('m') + 1, 1, date('Y')) + $feed_options['retrieve_until_value'] - date('Z'));
						break;
					case 'date':
						$feed->set_end_date($feed_options['retrieve_until_value']);
						break;
					case 'any':
						$feed->set_show_past_events(true);
				}

				//Set date and time formats. If they have not been set by user, set to global WordPress formats 
				$feed->set_date_format($feed_options['date_format'] == '' ? get_option('date_format') : $feed_options['date_format']);
				$feed->set_time_format($feed_options['time_format'] == '' ? get_option('time_format') : $feed_options['time_format']);
				//Set whether to handle multiple day events
				$feed->set_multi_day($feed_options['multiple_day'] == 'true' ? true : false);

				//Sets all display options
				$feed->set_display_options(array(
					'display_start' => $feed_options['display_start'],
					'display_end' => $feed_options['display_end'],
					'display_location' => $feed_options['display_location'],
					'display_desc' => $feed_options['display_desc'],
					'display_link' => $feed_options['display_link'],
					'display_start_text' => $feed_options['display_start_text'],
					'display_end_text' => $feed_options['display_end_text'],
					'display_location_text' => $feed_options['display_location_text'],
					'display_desc_text' => $feed_options['display_desc_text'],
					'display_desc_limit' => $feed_options['display_desc_limit'],
					'display_link_text' => $feed_options['display_link_text'],
					'display_link_target' => $feed_options['display_link_target'],
					'display_separator' => $feed_options['display_separator']
				));

				$feed->set_use_builder($feed_options['use_builder'] == 'true' ? true : false);
				$feed->set_builder($feed_options['builder']);

				//SimplePie does the hard work
				$feed->init();

				//Add feed object to array of feeds
				$this->feeds[$single_feed] = $feed;
			}
		}

		//More SimplePie magic to merge items from all feeds together
		$this->merged_feed_data = SimplePie::merge_items($this->feeds);

		//Sort the items by into date order
		usort($this->merged_feed_data, array('SimplePie_Item_GCalendar', 'compare'));
	}

	//Returns an array of feed ids that have encountered errors
	function get_errors(){
		$errors = array();

		foreach($this->feeds as $feed){
			//Remove '//' on line below to see more error information
			//echo $feed->error();
			if($feed->error()) $errors[] = $feed->get_feed_id();
		}

		return $errors;
	}

	//Returns array of days with events, with sub-arrays of events for that day
	function get_event_days(){
		$event_days = array();

		//Total number of events retrieved
		$count = count($this->merged_feed_data);

		//If maximum events to display is 0 (unlimited) set $max to 1, otherwise use maximum of events specified by user
		$max = $this->max_events_display == 0 ? 1 : $this->max_events_display;

		//Loop through entire array of events, or until maximum number of events to be displayed has been reached
		for($i = 0; $i < $count && $max > 0; $i++){
			$item = $this->merged_feed_data[$i];

			//Check that event end time isn't before start time of feed (ignores events from before start time that may have been inadvertently retrieved)
			if($item->get_end_date() > ($item->get_feed()->get_start_date() + date('Z'))){

				$start_date = $item->get_start_date();

				//Round start date to nearest day
				$start_date = mktime(0, 0, 0, date('m', $start_date), date('d', $start_date) , date('Y', $start_date));

				//If multiple day events should be handled, add multiple day event to required days
				if($item->get_feed()->get_multi_day()){
					$on_next_day = true;
					$next_day = $start_date + 86400;
					while($on_next_day){
						if($item->get_end_date() > $next_day){
							$event_days[$next_day][] = $item;
						}else{
							$on_next_day = false;
						}
						$next_day += 86400;
					}
				}

				//Add item into array of events for that day
				$event_days[$start_date][] = $item;

				//If maximum events to display isn't 0 (unlimited) decrement $max counter
				if($this->max_events_display != 0) $max--;
			}
		}

		return $event_days;
	}

	//Returns grid markup
	function get_grid($year = null, $month = null, $ajaxified = false){
		require_once('php-calendar.php');

		//If year and month have not been passed as paramaters, use current month and year
		if($year == null) $year = date('Y');
		if($month == null) $month = date('m');

		//Month and year to be displayed, in format mY (e.g. 052010)
		$m_y = date('mY', mktime(0, 0, 0, $month, 1, $year));

		//Get events data
		$event_days = $this->get_event_days();

		//If event_days is empty, then there are no events in the feed(s), so set ajaxified to false (Prevents AJAX calendar from allowing to endlessly click through months with no events)
		if(count((array)$event_days) == 0) $ajaxified = false;

		$at_last_event = false;
		$at_first_event = false;

		$last_event = end($event_days);
		$first_event = reset($event_days);

		$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

		foreach($event_days as $key => $event_day){
			//If event day is in the month and year specified (by $month and $year)
			if(date('mY', $key) == $m_y){
				//If this event day is the last in $event_days, there are no more events in the future so set $at_last_event to true
				if($event_day === $last_event) $at_last_event = true;

				//If this event day is the first in $event_days, there are no more events in the past so set $at_first_event to true
				if($event_day === $first_event) $at_first_event = true;

				//Create array of CSS classes. Add gce-has-events
				$css_classes = array('gce-has-events');

				//Create markup for display
				$markup = '<div class="gce-event-info">';

				//If title option has been set for display, add it                                                         Below: this is rubbish, unsure of better alternative
				if(isset($this->title)) $markup .= '<p class="gce-tooltip-title">' . $this->title . ' ' . date_i18n($event_day[0]->get_feed()->get_date_format(), $key) . '</p>';

				$markup .= '<ul>';

				foreach($event_day as $event_num => $event){
					$markup .= '<li class="gce-tooltip-feed-' . $event->get_feed()->get_feed_id() . '">' . $event->get_event_markup('tooltip', $event_num) . '</li>';

					//Add CSS class for the feed from which this event comes. If there are multiple events from the same feed on the same day, the CSS class will only be added once.
					$css_classes['feed-' . $event->get_feed()->get_feed_id()] = 'gce-feed-' . $event->get_feed()->get_feed_id();
				}

				$markup .= '</ul></div>';

				//If number of CSS classes is greater than 2 ('gce-has-events' plus one specific feed class) then there must be events from multiple feeds on this day, so add gce-multiple CSS class
				if(count($css_classes) > 2) $css_classes[] = 'gce-multiple';
				//If event day is today, add gce-today CSS class
				if($key == $today) $css_classes[] = 'gce-today';

				//Change array entry to array of link href, CSS classes, and markup for use in gce_generate_calendar (below)
				$event_days[$key] = array(null, implode(' ', $css_classes), $markup);
			}else{
				//Else if event day isn't in month and year specified, remove event day (and all associated events) from the array
				unset($event_days[$key]);
			}
		}

		//Ensures that gce-today CSS class is added even if there are no events for 'today'. A bit messy :(
		if(!isset($event_days[$today])) $event_days[$today] = array(null, 'gce-today', null);

		$pn = array();

		//Only add previous / next functionality if AJAX grid is enabled
		if($ajaxified){
			//If $at_first_event don't add previous month link. Otherwise, do add previous month link
			$prev_key = ($at_first_event ? '&nbsp;' : '&laquo;');
			$prev = ($at_first_event ? null : date('m-Y', mktime(0, 0, 0, $month - 1, 1, $year)));

			//If $at_last_event don't add next month link. Otherwise, do add next month link
			$next_key = ($at_last_event ? '&nbsp;' : '&raquo;');
			$next = ($at_last_event ? null : date('m-Y', mktime(0, 0, 0, $month + 1, 1, $year)));

			//Array of previous and next link stuff for use in gce_generate_calendar (below)
			$pn = array($prev_key => $prev, $next_key => $next);
		}

		//Generate the calendar markup and return it
		return gce_generate_calendar($year, $month, $event_days, 1, null, get_option('start_of_week'), $pn);
	}

	function get_list($grouped = false){
		$event_days = $this->get_event_days();

		//If event_days is empty, there are no events in the feed(s), so return a message indicating this
		if(count((array)$event_days) == 0) return '<p>' . __('There are currently no upcoming events.', GCE_TEXT_DOMAIN) . '</p>';

		$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

		$markup = '<ul class="gce-list">';

		foreach($event_days as $key => $event_day){
			//If this is a grouped list, add the date title and begin the nested list for this day
			if($grouped){
				$markup .=
					'<li' . ($key == $today ? ' class="gce-today"' : '') . '>' .
					'<p class="gce-list-title">' . $this->title . ' ' . date_i18n($event_day[0]->get_feed()->get_date_format(), $key) . '</p>' .
					'<ul>';
			}

			foreach($event_day as $event_num => $event){
				//Create the markup for this event
				$markup .=
					'<li class="gce-feed-' . $event->get_feed()->get_feed_id() . '">' .
					//If this isn't a grouped list and a date title should be displayed, add the date title
					((!$grouped && isset($this->title)) ? '<p class="gce-list-title">' . $this->title . ' ' . date_i18n($event->get_feed()->get_date_format(), $key) . '</p>' : '') .
					//Add the event markup
					$event->get_event_markup('list', $event_num) .
					'</li>';
			}

			//If this is a grouped list, close the nested list for this day
			if($grouped) $markup .= '</ul></li>';
		}

		$markup .= '</ul>';

		return $markup;
	}
}
?>