<?php
class GCE_Event{
	private $title;
	private $description;
	private $location;
	private $start_time;
	private $end_time;
	private $link;
	private $type;
	private $num_in_day;
	private $feed;
	private $day_type;

	function __construct($title, $description, $location, $start_time, $end_time, $link){
		$this->title = $title;
		$this->description = $description;
		$this->location = $location;
		$this->start_time = $start_time;
		$this->end_time = $end_time;
		$this->link = $link;

		if(($start_time + 86400) <= $end_time){
			if(($start_time + 86400) == $end_time){
				$this->day_type = 'SHD';
			}else{
				if((date('g:i a', $start_time) == '12:00 am') && (date('g:i a', $end_time) == '12:00 am')){
					$this->day_type = 'MHD';
				}else{
					$this->day_type = 'MPD';
				}
			}
		}else{
			$this->day_type = 'SPD';
		}
	}

	function set_feed($feed){
		$this->feed = $feed;
	}

	function get_feed(){
		return $this->feed;
	}

	function get_start_time(){
		return $this->start_time;
	}

	function get_end_time(){
		return $this->end_time;
	}

	//Returns the markup for this event, so that it can be used in the construction of a grid / list
	function get_event_markup($display_type, $event_num){
		//Set the display type (either tooltip or list)
		$this->type = $display_type;

		//Set which number event this is in day (first in day etc)
		$this->num_in_day = $event_num;

		//Use the builder or the old display options to create the markup, depending on user choice
		if($this->feed->get_use_builder()) return $this->use_builder();
		return $this->use_old_display_options();
	}

	//Return the event markup using the builder
	function use_builder(){
		//Array of valid shortcodes
		$shortcodes =
			'event-title|' .    //The event title
			'start-time|' .     //The start time of the event (uses the time format from the feed options, if it is set. Otherwise uses the default WordPress time format)
			'start-date|' .     //The start date of the event (uses the date format from the feed options, if it is set. Otherwise uses the default WordPress date format)
			'start-custom|' .   //The start time / date of the event (uses a custom PHP date format, specified in the 'format' attribute)
			'start-human|' .    //The difference between the start time of the event and the time now, in human-readable format, such as '1 hour', '4 days', '15 mins'
			'end-time|' .       //The end time of the event (uses the time format from the feed options, if it is set. Otherwise uses the default WordPress time format)
			'end-date|' .       //The end date of the event (uses the date format from the feed options, if it is set. Otherwise uses the default WordPress date format)
			'end-custom|' .     //The end time / date of the event (uses a custom PHP date format, specified in the 'format' attribute)
			'end-human|' .      //The difference between the end time of the event and the time now, in human-readable format, such as '1 hour', '4 days', '15 mins'
			'location|' .       //The event location
			'description|' .    //The event deescription (number of words can be limited by the 'limit' attribute)
			'link|' .           //Anything within this shortcode (including further shortcodes) will be linked to the Google Calendar page for this event
			'link-path|' .      //The raw link URL to the Google Calendar page for this event (can be used to construct more customized links)
			'feed-id|' .        //The ID of this feed (Can be useful for constructing feed specific CSS classes)
			'feed-title|' .     //The feed title
			'maps-link|' .      //Anything within this shortcode (including further shortcodes) will be linked to a Google Maps page based on whatever is specified for the event location

			//Anything between the opening and closing tags of the following logical shortcodes (including further shortcodes) will only be displayed if:

			'if-all-day|' .     //This is an all-day event
			'if-not-all-day|' . //This is not an all-day event
			'if-title|' .       //The event has a title
			'if-description|' . //The event has a description
			'if-location|' .    //The event has a location
			'if-tooltip|' .     //The current display type is 'tooltip'
			'if-list|' .        //The current display type is 'list'
			'if-now|' .         //The event is taking place now (after the start time, but before the end time)
			'if-not-now|' .     //The event is not taking place now (may have ended or not yet started)
			'if-started|' .     //The event has started (and even if it has ended)
			'if-not-started|' . //The event has not yet started
			'if-ended|' .       //The event has ended
			'if-not-ended|' .   //The event has not ended (and even if it hasn't started)
			'if-first|' .       //This event is the first in the day
			'if-not-first';     //This event is not the first in the day

		$markup = $this->feed->get_builder();

		$count = 0;

		//Go through the builder text looking for valid shortcodes. If one is found, send it to parse_shortcodes(). Once $count reaches 0, there are no un-parsed shortcodes
		//left, so return the markup (which now contains all the appropriate event information)
		do{
			$markup = preg_replace_callback('/(.?)\[(' . $shortcodes . ')\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)/s', array($this, 'parse_shortcode'), $markup, -1, $count);
		}while($count > 0);

		return $markup;
	}

	//Parse a shortcode, returning the appropriate event information
	//Much of this code is 'borrowed' from WordPress' own shortcode handling stuff!
	function parse_shortcode($m){
		if($m[1] == '[' && $m[6] == ']' ) return substr($m[0], 1, -1);

		//Extract any attributes contained in the shortcode
		extract(shortcode_atts(array(
			'newwindow' => 'false',
			'format' => '',
			'limit' => '0',
			'html' => 'false',
			'markdown' => 'false'
		), shortcode_parse_atts($m[3])));

		//Sanitize the attributes
		$format = esc_attr($format);
		$limit = absint($limit);

		//Do the appropriate stuff depending on which shortcode we're looking at. See valid shortcode list (above) for explanation of each shortcode
		switch($m[2]){
			case 'event-title':
				$title = esc_html($this->title);

				//Handle markdown / HTML if required
				if($markdown == 'true' && function_exists('Markdown')) $title = Markdown($title);
				if($html == 'true') $title = wp_kses_post(html_entity_decode($title));

				return $m[1] . $title . $m[6];
			case 'start-time':
				return $m[1] . date_i18n($this->feed->get_time_format(), $this->start_time) . $m[6];
			case 'start-date':
				return $m[1] . date_i18n($this->feed->get_date_format(), $this->start_time) . $m[6];
			case 'start-custom':
				return $m[1] . date_i18n($format, $this->start_time) . $m[6];
			case 'start-human':
				return $m[1] . human_time_diff($this->start_time) . $m[6];
			case 'end-time':
				return $m[1] . date_i18n($this->feed->get_time_format(), $this->end_time) . $m[6];
			case 'end-date':
				return $m[1] . date_i18n($this->feed->get_date_format(), $this->end_time) . $m[6];
			case 'end-custom':
				return $m[1] . date_i18n($format, $this->end_time) . $m[6];
			case 'end-human':
				return $m[1] . human_time_diff($this->end_time) . $m[6];
			case 'location':
				$location = esc_html($this->location);

				//Handle markdown / HTML if required
				if($markdown == 'true' && function_exists('Markdown')) $location = Markdown($location);
				if($html == 'true') $location = wp_kses_post(html_entity_decode($location));

				return $m[1] . $location . $m[6];
			case 'description':
				$description = esc_html($this->description);

				//If a word limit has been set, trim the description to the required length
				if($limit != 0){
					preg_match('/([\S]+\s*){0,' . $limit . '}/', esc_html($this->description), $description);
					$description = trim($description[0]);
				}

				//Handle markdown / HTML if required
				if($markdown == 'true' && function_exists('Markdown')) $description = Markdown($description);
				if($html == 'true') $description = wp_kses_post(html_entity_decode($description));

				return $m[1] . $description . $m[6];
			case 'link':
				$new_window = ($newwindow == 'true') ? ' target="_blank"' : '';
				return $m[1] . '<a href="' . $this->link . '"' . $new_window . '>' . $m[5] . '</a>' . $m[6];
			case 'link-path':
				return $m[1] . $this->link . $m[6];
			case 'feed-id':
				return $m[1] . $this->feed->get_feed_id() . $m[6];
			case 'feed-title':
				return $m[1] . $this->feed->get_feed_title() . $m[6];
			case 'maps-link':
				$new_window = ($newwindow == 'true') ? ' target="_blank"' : '';
				return $m[1] . '<a href="http://maps.google.com?q=' . urlencode($this->location) . '"' . $new_window . '>' . $m[5] . '</a>' . $m[6];
			case 'if-all-day':
				if($this->day_type == 'SHD' || $this->day_type == 'MHD') return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-not-all-day':
				if($this->day_type == 'SPD' || $this->day_type == 'MPD') return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-title':
				if($this->title != '') return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-description':
				if($this->description != '') return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-location':
				if($this->location != '') return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-tooltip':
				if($this->type == 'tooltip') return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-list':
				if($this->type == 'list') return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-now':
				if(time() >= $this->start_time && time() < $this->end_time) return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-not-now':
				if($this->end_time < time() || $this->start_time > time()) return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-started':
				if($this->start_time < time()) return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-not-started':
				if($this->start_time > time()) return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-ended':
				if($this->end_time < time()) return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-not-ended':
				if($this->end_time > time()) return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-first':
				if($this->num_in_day == 0) return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-not-first':
				if($this->num_in_day != 0) return $m[1] . $m[5] . $m[6];
				return '';
		}
	}

	//Return the event markup using the old display options
	function use_old_display_options(){
		$display_options = $this->feed->get_display_options();

		$markup = '<p class="gce-' . $this->type . '-event">' . esc_html($this->title)  . '</p>';

		$start_end = array();

		//If start date / time should be displayed, set up array of start date and time
		if($display_options['display_start'] != 'none'){
			$sd = $this->start_time;
			$start_end['start'] = array('time' => date_i18n($this->feed->get_time_format(), $sd), 'date' => date_i18n($this->feed->get_date_format(), $sd));
		}

		//If end date / time should be displayed, set up array of end date and time
		if($display_options['display_end'] != 'none'){
			$ed = $this->end_time;
			$start_end['end'] = array('time' => date_i18n($this->feed->get_time_format(), $ed), 'date' => date_i18n($this->feed->get_date_format(), $ed));
		}

		//Add the correct start / end, date / time information to $markup
		foreach($start_end as $start_or_end => $info){
			$markup .= '<p class="gce-' . $this->type . '-' . $start_or_end . '"><span>' . $display_options['display_' . $start_or_end . '_text'] . '</span> ';

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
			$event_location = $this->location;
			if($event_location != '') $markup .= '<p class="gce-' . $this->type . '-loc"><span>' . $display_options['display_location_text'] . '</span> ' . esc_html($event_location) . '</p>';
		}

		//If description should be displayed (and is not empty) add to $markup
		if(isset($display_options['display_desc'])){
			$event_desc = $this->description;

			if($event_desc != ''){
				//Limit number of words of description to display, if required
				if($display_options['display_desc_limit'] != ''){
					preg_match('/([\S]+\s*){0,' . $display_options['display_desc_limit'] . '}/', $this->description, $event_desc);
					$event_desc = trim($event_desc[0]);
				}

				$markup .= '<p class="gce-' . $this->type . '-desc"><span>' . $display_options['display_desc_text'] . '</span> ' . make_clickable(nl2br(esc_html($event_desc))) . '</p>';
			}
		}

		//If link should be displayed add to $markup
		if(isset($display_options['display_link'])){                                                                                   //Below: add target="_blank" if required
			$markup .= '<p class="gce-' . $this->type . '-link"><a href="' . $this->link . '&amp;ctz=' . $this->feed->get_timezone() . '"' . (isset($display_options['display_link_target']) ? ' target="_blank"' : '') . '>' . $display_options['display_link_text'] . '</a></p>';
		}

		return $markup;
	}
}
?>