<?php
require_once(ABSPATH . WPINC . '/class-feed.php');
require_once('simplepie-gcalendar.php');

class GCE_Feed extends SimplePie_GCalendar{
	private $feed_id;
	private $feed_title;
	private $d_format;
	private $t_format;
	private $display_opts;
	private $multi_day;
	private $feed_start;
	private $use_builder;
	private $builder;

	function GCE_Feed(){
		$this->__construct();
	}

	function __construct(){
		parent::__construct();
		$this->set_cache_class('WP_Feed_Cache');
		$this->set_file_class('WP_SimplePie_File');
	}

	function init(){
		parent::init();
		$this->set_item_class('GCE_Event');
	}

	//Setters

	function set_feed_id($id){
		$this->feed_id = $id;
	}

	function set_feed_title($title){
		$this->feed_title = $title;
	}

	function set_date_format($format_string){
		$this->d_format = $format_string;
	}

	function set_time_format($format_string){
		$this->t_format = $format_string;
	}

	function set_display_options($display_options){
		$this->display_opts = $display_options;
	}

	function set_multi_day($multiple_day){
		$this->multi_day = $multiple_day;
	}

	function set_start_date($start_date){
		$this->feed_start = $start_date;
		parent::set_start_date($start_date);
	}

	function set_use_builder($b){
		$this->use_builder = $b;
	}

	function set_builder($b){
		$this->builder = $b;
	}

	//Getters

	function get_feed_id(){
		return $this->feed_id;
	}

	function get_feed_title(){
		return $this->feed_title;
	}

	function get_date_format(){
		return $this->d_format;
	}

	function get_time_format(){
		return $this->t_format;
	}

	function get_display_options(){
		return $this->display_opts;
	}

	function get_multi_day(){
		return $this->multi_day;
	}

	function get_start_date(){
		return $this->feed_start;
	}

	function get_timezone(){
		return $this->timezone;
	}

	function get_use_builder(){
		return $this->use_builder;
	}

	function get_builder(){
		return $this->builder;
	}
}

class GCE_Event extends SimplePie_Item_GCalendar{
	private $type;

	//Returns the markup for this event, so that it can be used in the construction of a grid / list
	function get_event_markup($type){
		//Set the display type (either tooltip or list)
		$this->type = $type;

		//Use the builder or the old display options to create the markup, depending on user choice
		if($this->get_feed()->get_use_builder()){
			return $this->use_builder();
		}else{
			return $this->use_old_display_options();
		}
	}

	//Return the event markup using the builder
	function use_builder(){
		//Array of valid shortcodes
		$shortcodes =
			'all-day|' .        //Anything within this shortcode (including further shortcodes) will only be displayed if this IS an all-day event
			'not-all-day|' .    //Anything within this shortcode (including further shortcodes) will only be displayed if this IS NOT an all-day event
			'event-title|' .    //The event title
			'start-time|' .     //The start time of the event (uses the time format from the feed options, if it is set. Otherwise uses the default WordPress time format)
			'start-date|' .     //The start date of the event (uses the date format from the feed options, if it is set. Otherwise uses the default WordPress date format)
			'start-custom|' .   //The start time / date of the event (uses a custom PHP date format, specified in the 'format' attribute)
			'end-time|' .       //The end time of the event (uses the time format from the feed options, if it is set. Otherwise uses the default WordPress time format)
			'end-date|' .       //The end date of the event (uses the date format from the feed options, if it is set. Otherwise uses the default WordPress date format)
			'end-custom|' .     //The end time / date of the event (uses a custom PHP date format, specified in the 'format' attribute)
			'location|' .       //The event location
			'description|' .    //The event deescription (number of words can be limited by the 'limit' attribute)
			'link|' .           //Anything within this shortcode (including further shortcodes) will be linked to the Google Calendar page for this event (can open in a new window / tab by setting the 'nw' attribute to true)
			'link-path|' .      //The raw link URL to the Google Calendar page for this event (can be used to construct more customized links)
			'feed-id|' .        //The ID of this feed (Can be useful for constructing feed specific CSS classes)
			'feed-title|' .     //The feed title
			'timezone|' .       //The feed timezone
			'maps-link|' .      //Anything within this shortcode (including further shortcodes) will be linked to a Google Maps page based on whatever is specified for the event location
			'if-description|' . //Anything within this shortcode (including further shortcodes) will only be displayed if the event has a description
			'if-location|' .    //Anything within this shortcode (including further shortcodes) will only be displayed if the event has a location
			'if-tooltip|' .     //Anything within this shortcode (including further shortcodes) will only be displayed if the current display type is 'tooltip'
			'if-list|';         //Anything within this shortcode (including further shortcodes) will only be displayed if the current display type is 'list'

		$markup = $this->get_feed()->get_builder();

		$count = 0;

		//Go through the builder text looking for valid shortcodes. If one is found, send it to parse_shortcodes(). Once $count reaches 0, there are no un-parse shortcodes
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

		//If this is an all-day event
		$is_all_day = ($this->get_day_type() == $this->SINGLE_WHOLE_DAY || $this->get_day_type() == $this->MULTIPLE_WHOLE_DAY);

		//Do the appropriate stuff depending on which shortcode we're looking at. See valid shortcode list (above) for explanation of each shortcode
		switch($m[2]){
			case 'all-day':
				if($is_all_day) return $m[1] . $m[5] . $m[6];
				return '';
			case 'not-all-day':
				if(!$is_all_day) return $m[1] . $m[5] . $m[6];
				return '';
			case 'event-title':
				$title = esc_html($this->get_title());

				//Handle markdown / HTML if required
				if($markdown == 'true' && function_exists('Markdown')) $title = Markdown($title);
				if($html == 'true') $title = wp_kses_post(html_entity_decode($title));

				return $m[1] . $title . $m[6];
			case 'start-time':
				return $m[1] . date_i18n($this->get_feed()->get_time_format(), $this->get_start_date()) . $m[6];
			case 'start-date':
				return $m[1] . date_i18n($this->get_feed()->get_date_format(), $this->get_start_date()) . $m[6];
			case 'start-custom':
				return $m[1] . date_i18n($format, $this->get_start_date()) . $m[6];
			case 'end-time':
				return $m[1] . date_i18n($this->get_feed()->get_time_format(), $this->get_end_date()) . $m[6];
			case 'end-date':
				return $m[1] . date_i18n($this->get_feed()->get_date_format(), $this->get_end_date()) . $m[6];
			case 'end-custom':
				return $m[1] . date_i18n($format, $this->get_end_date()) . $m[6];
			case 'location':
				$location = esc_html($this->get_location());

				//Handle markdown / HTML if required
				if($markdown == 'true' && function_exists('Markdown')) $location = Markdown($location);
				if($html == 'true') $location = wp_kses_post(html_entity_decode($location));

				return $m[1] . $location . $m[6];
			case 'description':
				$description = esc_html($this->get_description());

				//If a word limit has been set, trim the description to the required length
				if($limit != 0){
					preg_match('/([\S]+\s*){0,' . $limit . '}/', esc_html($this->get_description()), $description);
					$description = trim($description[0]);
				}

				//Handle markdown / HTML if required
				if($markdown == 'true' && function_exists('Markdown')) $description = Markdown($description);
				if($html == 'true') $description = wp_kses_post(html_entity_decode($description));

				return $m[1] . $description . $m[6];
			case 'link':
				$new_window = ($newwindow == 'true') ? ' target="_blank"' : '';
				return $m[1] . '<a href="' . $this->get_link() . '"' . $new_window . '>' . $m[5] . '</a>' . $m[6];
			case 'link-path':
				return $m[1] . $this->get_link() . $m[6];
			case 'feed-id':
				return $m[1] . $this->get_feed()->get_feed_id() . $m[6];
			case 'feed-title':
				return $m[1] . $this->get_feed()->get_feed_title() . $m[6];
			case 'timezone':
				return $m[1] . $this->get_feed()->get_timezone() . $m[6];
			case 'maps-link':
				$new_window = ($newwindow == 'true') ? ' target="_blank"' : '';
				return $m[1] . '<a href="http://maps.google.com?q=' . urlencode($this->get_location()) . '"' . $new_window . '>' . $m[5] . '</a>' . $m[6];
			case 'if-description':
				if($this->get_description() != '') return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-location':
				if($this->get_location() != '') return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-tooltip':
				if($this->type == 'tooltip') return $m[1] . $m[5] . $m[6];
				return '';
			case 'if-list':
				if($this->type == 'list') return $m[1] . $m[5] . $m[6];
				return '';
		}
	}

	//Return the event markup using the old display options
	function use_old_display_options(){
		//Get the feed from which this event comes
		$feed = $this->get_feed();

		$display_options = $feed->get_display_options();

		$markup = '<p class="gce-' . $this->type . '-event">' . esc_html($this->get_title())  . '</p>';

		$start_end = array();

		//If start date / time should be displayed, set up array of start date and time
		if($display_options['display_start'] != 'none'){
			$sd = $this->get_start_date();
			$start_end['start'] = array('time' => date_i18n($feed->get_time_format(), $sd), 'date' => date_i18n($feed->get_date_format(), $sd));
		}

		//If end date / time should be displayed, set up array of end date and time
		if($display_options['display_end'] != 'none'){
			$ed = $this->get_end_date();
			$start_end['end'] = array('time' => date_i18n($feed->get_time_format(), $ed), 'date' => date_i18n($feed->get_date_format(), $ed));
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
			$event_location = $this->get_location();
			if($event_location != '') $markup .= '<p class="gce-' . $this->type . '-loc"><span>' . $display_options['display_location_text'] . '</span> ' . esc_html($event_location) . '</p>';
		}

		//If description should be displayed (and is not empty) add to $markup
		if(isset($display_options['display_desc'])){
			$event_desc = $this->get_description();

			if($event_desc != ''){
				//Limit number of words of description to display, if required
				if($display_options['display_desc_limit'] != ''){
					preg_match('/([\S]+\s*){0,' . $display_options['display_desc_limit'] . '}/', $this->get_description(), $event_desc);
					$event_desc = trim($event_desc[0]);
				}

				$markup .= '<p class="gce-' . $this->type . '-desc"><span>' . $display_options['display_desc_text'] . '</span> ' . make_clickable(nl2br(esc_html($event_desc))) . '</p>';
			}
		}

		//If link should be displayed add to $markup
		if(isset($display_options['display_link'])){                                                                                   //Below: add target="_blank" if required
			$markup .= '<p class="gce-' . $this->type . '-link"><a href="' . $this->get_link() . '&amp;ctz=' . $feed->get_timezone() . '"' . (isset($display_options['display_link_target']) ? ' target="_blank"' : '') . '>' . $display_options['display_link_text'] . '</a></p>';
		}

		return $markup;
	}
}
?>