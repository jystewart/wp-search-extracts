<?php
/*
Plugin Name: WP Search extracts
Plugin URI: http://www.ketlai.co.uk
Description: Display an excerpt in search results around the matched terms
Author: James Stewart
Version: 0.1
Author URI: http://jystewart.net/process/
*/

/**
 * How many characters either side of first result to show.
 *
 * TODO: Turn this into a config option
 */
define('PADDING_AROUND_RESULT', 150);

/**
 * Our key hook - process the post content
 */
add_filter('the_content', 'search_extract_process', 11);

/**
 * Return the full search term requested with surrounding quotes removed
 *
 * @return Array
 */
function search_extract_full_term() {
  global $wp_query, $wpdb;
  $s = $wp_query->query_vars['s'];
  $s = preg_replace('/^"(.+)"$/', "$1", $s);
  return $s;
}

/**
 * Return an array of search terms
 * Nabbed from the search-everything plugin
 *
 * @return Array
 */
function search_extract_get_terms() {
  global $wp_query, $wpdb;
  $s = $wp_query->query_vars['s'];
  $sentence = $wp_query->query_vars['sentence'];
  $search_terms = array();

  if (! empty($s)) {
    // added slashes screw with quote grouping when done early, so done later
    $s = stripslashes($s);
    if ($sentence) {
      $search_terms = array($s);
    } else {
      preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches);
      $search_terms = array_map(create_function('$a', 'return trim($a, "\\"\'\\n\\r ");'), $matches[0]);
    }
  }
  return $search_terms;
}

/**
 * Called as a filter on the content - if this is a search will return an extract around 
 * the matched terms
 *
 * @param String
 * @return String
 */
function search_extract_process($postcontent) {
  if (is_search()) {
    $stripped_content = strip_tags($postcontent);
    $full_term = search_extract_full_term();
    $position = stripos($stripped_content, $full_term);

    if ($position !== FALSE) {
      $start = $position > PADDING_AROUND_RESULT ? $position - PADDING_AROUND_RESULT : 0;
      $before = substr($stripped_content, $start, PADDING_AROUND_RESULT);
      $after = substr($stripped_content, $position + strlen($full_term), PADDING_AROUND_RESULT);
      return $before . '<em class="search-match">' . $full_term . '</em>' . $after;
    }

    $terms = search_extract_get_terms();
    foreach ($terms as $term) {
      $position = stripos($stripped_content, $term);
      if ($position !== FALSE) {
        $start = $position > PADDING_AROUND_RESULT ? $position - PADDING_AROUND_RESULT : 0;
        $before = substr($stripped_content, $start, PADDING_AROUND_RESULT);
        $after = substr($stripped_content, $position + strlen($term), PADDING_AROUND_RESULT);
        return $before . '<em class="search-match">' . $term . '</em>' . $after;
      }
    }
    
  }
  return $postcontent;
}
