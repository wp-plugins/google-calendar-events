<?php
require_once(ABSPATH . WPINC . '/class-feed.php');
require_once('simplepie-gcalendar.php');

class GCE_Parser{
	var $feed;
	var $d_format;
	var $t_format;
	var $week_start_day;
	var $display_options;

	//PHP 4 constructor
	function GCE_Parser($feed_url = null, $past_events = false, $max_events = 25, $cache_duration = 43200, $date_format = 'F j, Y', $time_format = 'g:i a', $timezone = 'default', $week_start = 0, $display_opts = array()){
		$this->__construct($feed_url, $past_events, $max_events, $cache_duration, $date_format, $timezone, $week_start, $display_opts);
	}

	//PHP 5 constructor
	function __construct($feed_url = null, $past_events = false, $max_events = 25, $cache_duration = 43200, $date_format = 'F j, Y', $time_format = 'g:i a', $timezone = 'default', $week_start = 0,  $display_opts = array()){
		$new_feed = new SimplePie_GCalendar(null, null, $cache_duration);
		$new_feed->set_cache_class('WP_Feed_Cache');
		$new_feed->set_file_class('WP_SimplePie_File');

		$new_feed->set_feed_url($feed_url);

		//Set start date to 1st of this month if $past_events is true (otherwise leave as todays date)
		if($past_events == 'true') $new_feed->set_start_date(mktime(0, 0, 0, date('m'), 1, date('Y')));

		if($timezone != 'default') $new_feed->set_timezone($timezone);

		$new_feed->set_max_events($max_events);
		$new_feed->enable_order_by_date(false);

		$new_feed->init();
		$new_feed->handle_content_type();

		$this->feed = $new_feed;

		$this->d_format = $date_format;
		$this->t_format = $time_format;
		$this->week_start_day = $week_start;
		$this->display_options = $display_opts;
	}

	//Check for SimplePie errors. Return false if an error occurred, otherwise return true
	function parsed_ok(){
		if($this->feed->error()) return false;
		return true;
	}

	//Returns array of days with events, with sub-arrays of events for that day
	function get_event_days(){
		$event_days = array();

		foreach($this->feed->get_items() as $item){
			$start_date = $item->get_start_date();

			//Round start date to nearest day
			$start_date = mktime(0, 0, 0, date('m', $start_date), date('d', $start_date) , date('Y', $start_date));

			if(!isset($event_days[$start_date])){
				//Create new array in $event_days for this date (only dates with events will go into array, so, for 
				//example $event_days[26] will exist if 26th of month has events, but won't if it has no events)
				//(Now uses unix timestamp rather than day number, but same concept applies).
				$event_days[$start_date] = array();
			}

			//Push event into array just created (may be another event for this date later in feed)
			array_push($event_days[$start_date], $item);
		}

		return $event_days;
	}

	//Returns list markup
	function get_list(){
		$event_days = $this->get_event_days();

		$markup = '<ul class="gce-list">';

		foreach($event_days as $key => $event_day){
			foreach($event_day as $event){
				//Get the various information from the event
				$event_start_time = date_i18n($this->t_format, $event->get_start_date());
				$event_end_time = date_i18n($this->t_format . ' ' . $this->d_format, $event->get_end_date());
				$event_location = $event->get_location();
				$event_desc = nl2br(make_clickable($event->get_description()));
				$event_link = $event->get_link() . '&ctz=' . $this->feed->get_timezone();
				$event_link_target = (isset($this->display_options['link_target']) ? ' target="_blank"' : '');

				$markup .= '<li>';

				//Check whether to add each piece of info. If yes, add info (location and desc are also checked if empty, as they may not have been entered when event was created)
				if(isset($this->display_options['title'])) $markup .= '<p class="gce-list-title">' . $this->display_options['title'] . ' ' . date_i18n($this->d_format, $key) . '</p>';
				$markup .= '<p class="gce-list-event">' . $event->get_title()  . '</p>';
				if(isset($this->display_options['start'])) $markup .= '<p class="gce-list-start"><span>' . $this->display_options['start'] . '</span> ' . $event_start_time . '</p>';
				if(isset($this->display_options['end'])) $markup .= '<p class="gce-list-end"><span>' . $this->display_options['end'] . '</span> ' . $event_end_time . '</p>';
				if(isset($this->display_options['location']) && $event_location != '') $markup .= '<p class="gce-list-loc"><span>' . $this->display_options['location'] . '</span> ' . $event_location . '</p>';
				if(isset($this->display_options['desc']) && $event_desc != '') $markup .= '<p class="gce-list-desc"><span>' . $this->display_options['desc'] . '</span> ' . $event_desc . '</p>';
				if(isset($this->display_options['link'])) $markup .= '<p class="gce-list-link"><a href="' . $event_link . '"' . $event_link_target . '>' . $this->display_options['link'] . '</a></p>';

				$markup .= '</li>';
			}
		}

		$markup .= '</ul>';

		return $markup;
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

		$no_more_events = false;

		$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

		foreach($event_days as $key => $event_day){
			//If event day is in the month and year specified (by $month and $year)
			if(date('mY', $key) == $m_y){
				//If this event day is the last in $event_days, there are no more events so set $no_more_events to true
				if($event_day === end($event_days)) $no_more_events = true;

				//Create markup for display
				$events_markup = '<div class="gce-event-info">';

				//If title option has been set for display, add it
				if(isset($this->display_options['title'])) $events_markup .= '<p class="gce-tooltip-title">' . $this->display_options['title'] . ' ' . date_i18n($this->d_format, $key) . '</p>';

				$events_markup .= '<ul>';

				foreach($event_day as $event){
					//Get the various information from the event
					$event_start_time = date_i18n($this->t_format, $event->get_start_date());
					$event_end_time = date_i18n($this->t_format . ' ' . $this->d_format, $event->get_end_date());
					$event_location = $event->get_location();
					$event_desc = nl2br(make_clickable($event->get_description()));
					$event_link = $event->get_link() . '&ctz=' . $this->feed->get_timezone();
					$event_link_target = (isset($this->display_options['link_target']) ? ' target="_blank"' : '');

					//Add event title
					$events_markup .= '<li><p class="gce-tooltip-event">' . $event->get_title()  . '</p>';

					//Check whether to add each piece of info. If yes, add info (location and desc are also checked if empty, as they may not have been entered when event was created)
					if(isset($this->display_options['start'])) $events_markup .= '<p class="gce-tooltip-start"><span>' . $this->display_options['start'] . '</span> ' . $event_start_time . '</p>';
					if(isset($this->display_options['end'])) $events_markup .= '<p class="gce-tooltip-end"><span>' . $this->display_options['end'] . '</span> ' . $event_end_time . '</p>';
					if(isset($this->display_options['location']) && $event_location != '') $events_markup .= '<p class="gce-tooltip-loc"><span>' . $this->display_options['location'] . '</span> ' . $event_location . '</p>';
					if(isset($this->display_options['desc']) && $event_desc != '') $events_markup .= '<p class="gce-tooltip-desc"><span>' . $this->display_options['desc'] . '</span> ' . $event_desc . '</p>';
					if(isset($this->display_options['link'])) $events_markup .= '<p class="gce-tooltip-link"><a href="' . $event_link . '"' . $event_link_target . '>' . $this->display_options['link'] . '</a></p>';

					$events_markup .= '</li>';
				}

				$events_markup .= '</ul></div>';

				//If this event day is 'today', add gce-today class to $css_classes
				$css_classes = 'gce-has-events';
				if($key == $today) $css_classes .= ' gce-today';

				//Change array entry to array of link href, CSS classes, and markup for use in gce_generate_calendar (below)
				$event_days[$key] = array(null, $css_classes, $events_markup);
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
		return gce_generate_calendar($year, $month, $event_days, 1, null, $this->week_start_day, $pn);
	}
}
?>