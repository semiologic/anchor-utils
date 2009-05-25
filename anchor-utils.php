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
		if ( has_filter('ob_filter_anchor') ) {
			ob_start(array('anchor_utils', 'ob_filter'));
			add_action('wp_footer', array('anchor_utils', 'ob_flush'), 1000000);
		}
	} # wp_head()
	
	
	/**
	 * ob_filter()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function ob_filter($text) {
		$text = anchor_utils::escape($text);
		
		$text = preg_replace_callback("/
			<\s*a\s+
			([^<>]+)
			>
			(.+?)
			<\s*\/\s*a\s*>
			/isx", array('anchor_utils', 'ob_filter_callback'), $text);
		
		$text = anchor_utils::unescape($text);
		
		return $text;
	} # ob_filter()
	
	
	/**
	 * ob_flush()
	 *
	 * @return void
	 **/

	function ob_flush() {
		ob_end_flush();
	} # ob_flush()
	
	
	/**
	 * ob_filter_callback()
	 *
	 * @param array $match
	 * @return string $str
	 **/

	function ob_filter_callback($match) {
		# skip empty anchors
		if ( !trim($match[2]) )
			return $match[0];
		
		# parse anchor
		$anchor = anchor_utils::parse_anchor($match);
		
		if ( !$anchor )
			return $match[0];
		
		# filter anchor
		$anchor = apply_filters('ob_filter_anchor', $anchor);
		
		# return anchor
		return anchor_utils::build_anchor($anchor);
	} # ob_filter_callback()
	
	
	/**
	 * filter()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function filter($text) {
		if ( !has_filter('filter_anchor') )
			return $text;
		
		$text = anchor_utils::escape($text);
		
		$text = preg_replace_callback("/
			<\s*a\s+
			([^<>]+)
			>
			(.+?)
			<\s*\/\s*a\s*>
			/isx", array('anchor_utils', 'filter_callback'), $text);
		
		$text = anchor_utils::unescape($text);
		
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
		
		if ( !$anchor )
			return $match[0];
		
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
		
		if ( !is_array($anchor['attr']) || empty($anchor['attr']['href']) ) # parser error or not a link
			return false;
		
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
	
	
	/**
	 * escape()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function escape($text) {
		global $anchor_filter_escape;
		
		if ( !isset($anchor_filter_escape) )
			$anchor_filter_escape = array();
		
		foreach ( array(
			'head' => "/
				.*?
				<\s*\/\s*head\s*>
				/isx",
			'blocks' => "/
				<\s*(script|object)(?:\s.*?)?>
				.*?
				<\s*\/\s*\\1\s*>
				/isx"
			) as $regex ) {
			$text = preg_replace_callback($regex, array('anchor_utils', 'escape_callback'), $text);
		}
		
		return $text;
	} # escape()
	
	
	/**
	 * escape_callback()
	 *
	 * @param array $match
	 * @return string $text
	 **/

	function escape_callback($match) {
		global $anchor_filter_escape;
		
		$tag_id = "----escape_anchor_utils:" . strtolower(md5($match[0])) . "----";
		$anchor_filter_escape[$tag_id] = $match[0];
		
		return $tag_id;
	} # escape_callback()
	
	
	/**
	 * unescape()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function unescape($text) {
		global $anchor_filter_escape;
		
		if ( !$anchor_filter_escape )
			return $text;
		
		$text = preg_replace_callback("/
			----escape_anchor_utils:[a-f0-9]{32}----
			/x", array('anchor_utils', 'unescape_callback'), $text);
		
		return $text;
	} # unescape()
	
	
	/**
	 * unescape_callback()
	 *
	 * @param array $match
	 * @return string $text
	 **/

	function unescape_callback($match) {
		global $anchor_filter_escape;
		
		return $anchor_filter_escape[$match[0]];
	} # unescape_callback()
} # anchor_utils
?>