<?php
/*
 * Anchor Utils
 * Author: Denis de Bernardy <http://www.mesoconcepts.com>
 * Version: 1.0
 */

/**
 * anchor_utils
 *
 * @package Anchor Utils
 **/

add_filter('the_content', array('anchor_utils', 'filter'), 100);
add_filter('the_excerpt', array('anchor_utils', 'filter'), 100);
add_filter('widget_text', array('anchor_utils', 'filter'), 100);
add_filter('comment_text', array('anchor_utils', 'filter'), 100);

add_action('wp_head', array('anchor_utils', 'wp_head'), 10000);

class anchor_utils {
	/**
	 * wp_head()
	 *
	 * @return void
	 **/

	function wp_head() {
		if ( has_filter('ob_filter_anchor') )
			ob_start(array('anchor_utils', 'ob_filter'));
	} # wp_head()
	
	
	/**
	 * ob_filter()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function ob_filter($text) {
		$text = preg_replace_callback("/
			<\s*a\s+
			([^<>]+)
			>
			(.+?)
			<\s*\/\s*a\s*>
			/isx", array('anchor_utils', 'ob_callback'), $text);
		
		return $text;
	} # ob_filter()
	
	
	/**
	 * ob_callback()
	 *
	 * @param array $match
	 * @return string $str
	 **/

	function ob_callback($match) {
		# skip empty anchors
		if ( !trim($match[2]) )
			return $match[0];
		
		# parse anchor
		$anchor = anchor_utils::parse_anchor($match);
		
		# filter anchor
		$anchor = apply_filters('ob_filter_anchor', $anchor);
		
		# return anchor
		return anchor_utils::build_anchor($anchor);
	} # ob_callback()
	
	
	/**
	 * filter()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function filter($text) {
		if ( !has_filter('filter_anchor') )
			return $text;
		
		$text = preg_replace_callback("/
			<\s*a\s+
			([^<>]+)
			>
			(.+?)
			<\s*\/\s*a\s*>
			/isx", array('anchor_utils', 'filter_callback'), $text);
		
		return $text;
	} # filter()
	
	
	/**
	 * filter_callback()
	 *
	 * @param array $match
	 * @return string $str
	 **/

	function filter_callback($match) {
		# skip empty anchors
		if ( !trim($match[2]) )
			return $match[0];
		
		# parse anchor
		$anchor = anchor_utils::parse_anchor($match);
		
		# filter anchor
		$anchor = apply_filters('filter_anchor', $anchor);
		
		# return anchor
		return anchor_utils::build_anchor($anchor);
	} # filter_callback()
	
	
	/**
	 * parse_anchor()
	 *
	 * @param array $match
	 * @return array $anchor
	 **/

	function parse_anchor($match) {
		
		$anchor = array();
		
		$anchor['attr'] = shortcode_parse_atts($match[1]);
		
		if ( !is_array($anchor['attr']) ) # shortcode parser error
			return $match[0];
		
		foreach ( array('class', 'rel') as $attr ) {
			if ( !isset($anchor['attr'][$attr]) ) {
				$anchor['attr'][$attr] = array();
			} else {
				$anchor['attr'][$attr] = explode(' ', $anchor['attr'][$attr]);
				$anchor['attr'][$attr] = array_map('trim', $anchor['attr'][$attr]);
			}
		}
		
		$anchor['body'] = $match[2];
		
		return $anchor;
	} # parse_anchor()
	
	
	/**
	 * build_anchor()
	 *
	 * @param array $anchor
	 * @return string $anchor
	 **/

	function build_anchor($anchor) {
		$str = '<a ';
		foreach ( $anchor['attr'] as $k => $v ) {
			if ( is_array($v) ) {
				$v = array_unique($v);
				if ( $v )
					$str .= ' ' . $k . '="' . implode(' ', $v) . '"';
			} else {
				$str .= ' ' . $k . '="' . $v . '"';
			}
		}
		$str .= '>' . $anchor['body'] . '</a>';
		
		return $str;
	} # build_anchor()
} # anchor_utils
?>