<?php
require_once(ABSPATH . WPINC . '/class-feed.php');
require_once('simplepie-gcalendar.php');

class GCE_Feed extends SimplePie_GCalendar{
	var $feed_id;
	var $d_format;
	var $t_format;
	var $display_opts;
	var $multi_day;

	function GCE_Feed(){
		$this->__construct();
	}

	function __construct(){
		parent::__construct();
		$this->set_cache_class('WP_Feed_Cache');
		$this->set_file_class('WP_SimplePie_File');
	}

	//Setters

	function set_feed_id($id){
		$this->feed_id = $id;
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

	//Getters

	function get_feed_id(){
		return $this->feed_id;
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
}
?>