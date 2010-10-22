<?php
require_once('gce-feed.php');

class GCE_Parser{
	var $feeds = array();
	var $merged_feed_data = array();
	var $title = null;

	function GCE_Parser($feed_ids, $title_text = null){
		$this->__construct($feed_ids, $title_text);
	}

	function __construct($feed_ids, $title_text = null){
		$this->title = $title_text;

		//Get the feed options
		$options = get_option(GCE_OPTIONS_NAME);

		foreach($feed_ids as $single_feed){
			//Get the options for this particular feed
			if(isset($options[$single_feed])){
				$feed_options = $options[$single_feed];

				$feed = new GCE_Feed();

				$feed->set_feed_id($feed_options['id']);
				$feed->set_feed_url($feed_options['url']);
				$feed->set_max_events($feed_options['max_events']);
				//If day limit is not blank, set end date to specified number of days in the future, including today
				if($feed_options['day_limit'] != '') $feed->set_end_date(mktime(0, 0, 0, date('n'), date('j'), date('Y')) + (86400 * $feed_options['day_limit']));
				$feed->set_cache_duration($feed_options['cache_duration']);
				//Set the timezone if anything other than default
				if($feed_options['timezone'] != 'default') $feed->set_timezone($feed_options['timezone']);
				//If show past events is true, set start date to 1st of this month. Otherwise, set start date to today
				if($feed_options['show_past_events'] == 'true'){
					$feed->set_start_date(mktime(0, 0, 0, date('m'), 1, date('Y')) - (int)date('Z'));
				}else{
					$feed->set_start_date(mktime(0, 0, 0, date('m'), date('j'), date('Y')) - (int)date('Z'));
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
			echo $feed->error();
			if($feed->error()) $errors[] = $feed->get_feed_id();
		}

		return $errors;
	}

	//Returns array of days with events, with sub-arrays of events for that day
	function get_event_days(){
		$event_days = array();

		foreach($this->merged_feed_data as $item){
			if($item->get_end_date() >= $item->get_feed()->get_start_date()){
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

		$no_more_events = false;

		$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

		foreach($event_days as $key => $event_day){
			//If event day is in the month and year specified (by $month and $year)
			if(date('mY', $key) == $m_y){
				//If this event day is the last in $event_days, there are no more events so set $no_more_events to true
				if($event_day === end($event_days)) $no_more_events = true;

				//Create array of CSS classes. Add gce-has-events
				$css_classes = array('gce-has-events');

				//Create markup for display
				$markup = '<div class="gce-event-info">';

				//If title option has been set for display, add it                                                         Below: this is rubbish, unsure of better alternative
				if(isset($this->title)) $markup .= '<p class="gce-tooltip-title">' . $this->title . ' ' . date_i18n($event_day[0]->get_feed()->get_date_format(), $key) . '</p>';

				$markup .= '<ul>';

				foreach($event_day as $event){
					$markup .=
						'<li class="gce-tooltip-feed-' . $event->get_feed()->get_feed_id() . '">' .
						//Add the event title
						'<p class="gce-tooltip-event">' . esc_html($event->get_title())  . '</p>' .
						$this->get_event_info_markup($event, 'tooltip') .
						'</li>';

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
			//If the month shown is the current month, don't add previous month link. If it isn't, add previous month link
			$prev_key = ($m_y == date('mY') ? '&nbsp;' : '&laquo;');
			$prev = ($m_y == date('mY') ? null : date('m-Y', mktime(0, 0, 0, $month - 1, 1, $year)));

			//If $no_more_events don't add next month link. If there are more events, add next month link
			$next_key = ($no_more_events ? '&nbsp;' : '&raquo;');
			$next = ($no_more_events ? null : date('m-Y', mktime(0, 0, 0, $month + 1, 1, $year)));

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

			foreach($event_day as $event){
				//Create the markup for this event
				$markup .=
					'<li>' .
					//If this isn't a grouped list and a date title should be displayed, add the date title
					((!$grouped && isset($this->title)) ? '<p class="gce-list-title">' . $this->title . ' ' . date_i18n($event->get_feed()->get_date_format(), $key) . '</p>' : '') .
					//Add the event title
					'<p class="gce-list-event">' . esc_html($event->get_title())  . '</p>' .
					$this->get_event_info_markup($event, 'list') .
					'</li>';
			}

			//If this is a grouped list, close the nested list for this day
			if($grouped) $markup .= '</ul></li>';
		}

		$markup .= '</ul>';

		return $markup;
	}

	//Returns the event information markup for the specified event (type = tooltip or list)
	function get_event_info_markup($event, $type){
		//Get the feed from which this event comes
		$feed = $event->get_feed();

		$display_options = $feed->get_display_options();

		$markup = '';

		$start_end = array();

		//If start date / time should be displayed, set up array of start date and time
		if($display_options['display_start'] != 'none'){
			$sd = $event->get_start_date();
			$start_end['start'] = array('time' => date_i18n($feed->get_time_format(), $sd), 'date' => date_i18n($feed->get_date_format(), $sd));
		}

		//If end date / time should be displayed, set up array of end date and time
		if($display_options['display_end'] != 'none'){
			$ed = $event->get_end_date();
			$start_end['end'] = array('time' => date_i18n($feed->get_time_format(), $ed), 'date' => date_i18n($feed->get_date_format(), $ed));
		}

		//Add the correct start / end, date / time information to $markup
		foreach($start_end as $start_or_end => $info){
			$markup .= '<p class="gce-' . $type . '-' . $start_or_end . '"><span>' . $display_options['display_' . $start_or_end . '_text'] . '</span> ';

			switch($display_options['display_' . $start_or_end]){
				case 'time': $markup .= $info['time'];
					break;
				case 'date': $markup .= $info['date'];
					break;
				case 'time-date': $markup .= $info['time'] . $display_options['display_separator'] . $info['date'];
					break;
				case 'date-time': $markup .= $info['date'] . $display_options['display_separator'] . $info['time'];
			}

			$markup .= '</p>';
		}

		//If location should be displayed (and is not empty) add to $markup
		if(isset($display_options['display_location'])){
			$event_location = $event->get_location();
			if($event_location != '') $markup .= '<p class="gce-' . $type . '-loc"><span>' . $display_options['display_location_text'] . '</span> ' . esc_html($event_location) . '</p>';
		}

		//If description should be displayed (and is not empty) add to $markup
		if(isset($display_options['display_desc'])){
			$event_desc = $event->get_description();

			if($event_desc != ''){
				//Limit number of words of desc to display, if required
				if($display_options['display_desc_limit'] != ''){
					preg_match('/([\S]+\s*){0,' . $display_options['display_desc_limit'] . '}/', $event->get_description(), $event_desc);
					$event_desc = trim($event_desc[0]);
				}

				$markup .= '<p class="gce-' . $type . '-desc"><span>' . $display_options['display_desc_text'] . '</span> ' . make_clickable(nl2br(esc_html($event_desc))) . '</p>';
			}
		}

		//If link should be displayed add to $markup
		if(isset($display_options['display_link'])){                                                                                     //Below: add target="_blank" if required
			$markup .= '<p class="gce-' . $type . '-link"><a href="' . $event->get_link() . '&amp;ctz=' . $feed->get_timezone() . '"' . (isset($display_options['display_link_target']) ? ' target="_blank"' : '') . '>' . $display_options['display_link_text'] . '</a></p>';
		}

		return $markup;
	}
}
?>