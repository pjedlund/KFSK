<?php
/*
Plugin Name: Relevanssi Premium
Plugin URI: http://www.relevanssi.com/
Description: This premium plugin replaces WordPress search with a relevance-sorting search.
Version: 1.8
Author: Mikko Saari
Author URI: http://www.mikkosaari.fi/
*/

/*  Copyright 2012 Mikko Saari  (email: mikko@mikkosaari.fi)

    This file is part of Relevanssi Premium, a search plugin for WordPress.

    Relevanssi Premium is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Relevanssi Premium is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Relevanssi Premium.  If not, see <http://www.gnu.org/licenses/>.
*/

// For debugging purposes
//error_reporting(E_ALL);
//ini_set("display_errors", 1); 
//define('WP-DEBUG', true);

register_activation_hook(__FILE__,'relevanssi_install');
add_action('admin_menu', 'relevanssi_menu');
add_filter('the_posts', 'relevanssi_query');
add_action('save_post', 'relevanssi_edit', 99, 1);				// thanks to Brian D Gajus
add_action('delete_post', 'relevanssi_delete');
add_action('comment_post', 'relevanssi_comment_index'); 	//added by OdditY
add_action('edit_comment', 'relevanssi_comment_edit'); 		//added by OdditY 
add_action('delete_comment', 'relevanssi_comment_remove'); 	//added by OdditY
add_action('wp_insert_post', 'relevanssi_insert_edit', 99, 1 ); // added by lumpysimon
// BEGIN added by renaissancehack
// *_page and *_post hooks do not trigger on attachments
add_action('delete_attachment', 'relevanssi_delete');
add_action('add_attachment', 'relevanssi_publish');
add_action('edit_attachment', 'relevanssi_edit');
// When a post status changes, check child posts that inherit their status from parent
add_action('transition_post_status', 'relevanssi_update_child_posts',99,3);
// END added by renaissancehack
add_action('init', 'relevanssi_init');
add_action('init', 'relevanssi_check_old_data', 99);
add_filter('relevanssi_hits_filter', 'relevanssi_wpml_filter');
add_action('profile_update', 'relevanssi_profile_update');
add_action('delete_user', 'relevanssi_delete_user');
add_action('edit_term', 'relevanssi_edit_term');
add_action('delete_term', 'relevanssi_delete_taxonomy_term');
add_action('wpmu_new_blog', 'relevanssi_new_blog', 10, 6); 		

add_filter('the_permalink', 'relevanssi_permalink');
add_filter('relevanssi_permalink', 'relevanssi_permalink');
add_filter('relevanssi_remove_punctuation', 'relevanssi_remove_punct');
add_filter('relevanssi_post_ok', 'relevanssi_default_post_ok', 10, 2);

add_action('save_post', 'relevanssi_save_postdata');
add_filter('posts_request', 'relevanssi_prevent_default_request', 10, 2 );

include_once('SpellCorrector.php');

$plugin_dir = dirname(plugin_basename(__FILE__));
load_plugin_textdomain('relevanssi', false, $plugin_dir);

global $wpSearch_low;
global $wpSearch_high;
global $relevanssi_table;
global $relevanssi_stopword_table;
global $relevanssi_log_table;
global $relevanssi_cache;
global $relevanssi_excerpt_cache;
global $stopword_list;
global $title_boost_default;
global $link_boost_default;
global $comment_boost_default;
global $relevanssi_db_version;
global $relevanssi_hits;
global $wpdb;
global $relevanssi_plugin_version;

$wpSearch_low = 0;
$wpSearch_high = 0;
$relevanssi_table = $wpdb->prefix . "relevanssi";
$relevanssi_stopword_table = $wpdb->prefix . "relevanssi_stopwords";
$relevanssi_log_table = $wpdb->prefix . "relevanssi_log";
$relevanssi_cache = $wpdb->prefix . "relevanssi_cache";
$relevanssi_excerpt_cache = $wpdb->prefix . "relevanssi_excerpt_cache";
$title_boost_default = 5;
$link_boost_default = 0.25;
$comment_boost_default = 0.75;
$relevanssi_db_version = 12;
$relevanssi_plugin_version = 1.8;

function relevanssi_menu() {
	add_options_page(
		'Relevanssi Premium',
		'Relevanssi Premium',
		'manage_options',
		__FILE__,
		'relevanssi_options'
	);
	add_dashboard_page(
		__('User searches', 'relevanssi'),
		__('User searches', 'relevanssi'),
		'edit_pages',
		__FILE__,
		'relevanssi_search_stats'
	);
}

add_action('init', 'relevanssi_wptuts_activate_au');
function relevanssi_wptuts_activate_au() {
	global $relevanssi_plugin_version;
    require_once ('wp_autoupdate.php');
    $wptuts_plugin_remote_path = 'http://www.relevanssi.com/update/update.php';
    $wptuts_plugin_slug = plugin_basename(__FILE__);
    new relevanssi_wp_auto_update ($relevanssi_plugin_version, $wptuts_plugin_remote_path, $wptuts_plugin_slug);
}

function relevanssi_init() {
	global $pagenow;
	isset($_POST['index']) ? $index = true : $index = false;
	if (!get_option('relevanssi_indexed') && !$index) {
		function relevanssi_warning() {
			echo "<div id='relevanssi-warning' class='updated fade'><p><strong>"
			   . sprintf(__('Relevanssi needs attention: Remember to build the index (you can do it at <a href="%1$s">the settings page</a>), otherwise searching won\'t work.'), "options-general.php?page=relevanssi-premium/relevanssi.php")
			   . "</strong></p></div>";
		}
		add_action('admin_notices', 'relevanssi_warning');
	}
	
	if (!function_exists('mb_internal_encoding')) {
		function relevanssi_mb_warning() {
			echo "<div id='relevanssi-warning' class='updated fade'><p><strong>"
			   . "Multibyte string functions are not available. Relevanssi may not work well without them. "
			   . "Please install (or ask your host to install) the mbstring extension."
			   . "</strong></p></div>";
		}
		if ( 'options-general.php' == $pagenow and isset( $_GET['page'] ) and plugin_basename( __FILE__ ) == $_GET['page'] )
			add_action('admin_notices', 'relevanssi_mb_warning');
	}

	if (!wp_next_scheduled('relevanssi_truncate_cache')) {
		wp_schedule_event(time(), 'daily', 'relevanssi_truncate_cache');
		add_action('relevanssi_truncate_cache', 'relevanssi_truncate_cache');
	}

	if (get_option('relevanssi_hide_post_controls') == 'off') {
		add_action('add_meta_boxes', 'relevanssi_add_metaboxes');
	}
	
	return;
}

function relevanssi_check_old_data() {
	if (is_admin()) {
		global $wpdb;

		// Version 1.7.3 renamed relevanssi_hide_post
		$query = "UPDATE $wpdb->postmeta SET meta_key = '_relevanssi_hide_post' WHERE meta_key = 'relevanssi_hide_post'";
		$wpdb->query($query);
	
		// Version 1.6.3 removed relevanssi_tag_boost
		$tag_boost = get_option('relevanssi_tag_boost', 'nothing');
		if ($tag_boost != 'nothing') {
			$post_type_weights = get_option('relevanssi_post_type_weights');
			if (!is_array($post_type_weights)) {
				$post_type_weights = array();
			}
			$post_type_weights['post_tag'] = $tag_boost;
			delete_option('relevanssi_tag_boost');
			update_option('relevanssi_post_type_weights', $post_type_weights);
		}
	
		$index_type = get_option('relevanssi_index_type', 'nothing');
		if ($index_type != 'nothing') {
			// Delete unused options from 1.5 versions
			$post_types = get_option('relevanssi_index_post_types');
			
			if (!is_array($post_types)) $post_types = array();
			
			switch ($index_type) {
				case "posts":
					array_push($post_types, 'post');
					break;
				case "pages":
					array_push($post_types, 'page');
					break;
				case 'public':
					if (function_exists('get_post_types')) {
						$pt_1 = get_post_types(array('exclude_from_search' => '0'));
						$pt_2 = get_post_types(array('exclude_from_search' => false));
						foreach (array_merge($pt_1, $pt_2) as $type) {
							array_push($post_types, $type);				
						}
					}
					break;
				case "both": 								// really should be "everything"
					$pt = get_post_types();
					foreach ($pt as $type) {
						array_push($post_types, $type);				
					}
					break;
			}
			
			$attachments = get_option('relevanssi_index_attachments');
			if ('on' == $attachments) array_push($post_types, 'attachment');
			
			$custom_types = get_option('relevanssi_custom_types');
			$custom_types = explode(',', $custom_types);
			if (is_array($custom_types)) {
				foreach ($custom_types as $type) {
					$type = trim($type);
					if (substr($type, 0, 1) != '-') {
						array_push($post_types, $type);
					}
				}
			}
			
			update_option('relevanssi_index_post_types', $post_types);
			
			delete_option('relevanssi_index_type');
			delete_option('relevanssi_index_attachments');
			delete_option('relevanssi_custom_types');
		}
	}
}

function relevanssi_truncate_cache($all = false) {
	global $relevanssi_cache, $relevanssi_excerpt_cache, $wpdb;
		
	if ($all) {
		$query = "TRUNCATE TABLE $relevanssi_excerpt_cache";
		$wpdb->query($query);

		$query = "TRUNCATE TABLE $relevanssi_cache";
	}
	else {
		$time = get_option('relevanssi_cache_seconds', 172800);
		$query = "DELETE FROM $relevanssi_cache
			WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(tstamp) > $time";
		// purge all expired cache data
	}
	$wpdb->query($query);
}

add_filter('relevanssi_query_filter', 'relevanssi_limit_filter');
function relevanssi_limit_filter($query) {
	if (get_option('relevanssi_throttle', 'on') == 'on') {
		return $query . " ORDER BY tf DESC LIMIT 500";
	}
	else {
		return $query;
	}
}

function relevanssi_didyoumean($query, $pre, $post, $n = 5) {
	global $wpdb, $relevanssi_log_table, $wp_query;
	
	$total_results = $wp_query->found_posts;	
	
	if ($total_results > $n) return;

	$suggestion = "";
	$suggestion_enc = "";

	if (class_exists('SpellCorrector')) {
		$tokens = relevanssi_tokenize($query);

		$sc = new SpellCorrector();

		$correct = array();
		foreach ($tokens as $token => $count) {
			$c = $sc->correct($token);
			if ($c !== $token) 
				array_push($correct, $c);
		}
		if (count($correct) > 0) {
			$suggestion = implode(' ', $correct);
			$suggestion_enc = urlencode($suggestion);
		}
	}
	
	if ("" == $suggestion) {
		$q = "SELECT query, count(query) as c, AVG(hits) as a FROM $relevanssi_log_table WHERE hits > 1 GROUP BY query ORDER BY count(query) DESC";
		$q = apply_filters('relevanssi_didyoumean_query', $q);
		
		$data = $wpdb->get_results($q);
				
		$distance = -1;
		$closest = "";
			
		foreach ($data as $row) {
			if ($row->c < 2) break;
			$lev = levenshtein($query, $row->query);
			
			if ($lev < $distance || $distance < 0) {
				if ($row->a > 0) {
					$distance = $lev;
					$closest = $row->query;
					if ($lev == 1) break; // get the first with distance of 1 and go
				}
			}
		}
			
		if ($distance > 0) {
			$suggestion = $closest;
			$suggestion_enc = urlencode($closest);
		}
	}
	
	if ($suggestion) {
 		$url = get_bloginfo('url');
		$url = esc_attr(add_query_arg(array(
			's' => $suggestion_enc
			), $url));
		echo "$pre<a href='$url'>$suggestion</a>$post";
 	}
}

// BEGIN added by renaissancehack
function relevanssi_update_child_posts($new_status, $old_status, $post) {
// called by 'transition_post_status' action hook when a post is edited/published/deleted
//  and calls appropriate indexing function on child posts/attachments
    global $wpdb;
    $index_statuses = array('publish', 'private', 'draft', 'future', 'pending');
    if (($new_status == $old_status)
          || (in_array($new_status, $index_statuses) && in_array($old_status, $index_statuses))
          || (in_array($post->post_type, array('attachment', 'revision')))) {
        return;
    }
    $q = "SELECT * FROM $wpdb->posts WHERE post_parent=$post->ID AND post_type!='revision'";
    $child_posts = $wpdb->get_results($q);
    if ($child_posts) {
        if (!in_array($new_status, $index_statuses)) {
            foreach ($child_posts as $post) {
                relevanssi_delete($post->ID);
            }
        } else {
            foreach ($child_posts as $post) {
                relevanssi_publish($post->ID);
            }
        }
    }
}
// END added by renaissancehack

function relevanssi_edit($post) {
	// Check if the post is public
	global $wpdb;

	$post_status = get_post_status($post);
	if ('auto-draft' == $post_status) return;

// BEGIN added by renaissancehack
    //  if post_status is "inherit", get post_status from parent
    if ($post_status == 'inherit') {
        $post_type = $wpdb->get_var("SELECT post_type FROM $wpdb->posts WHERE ID=$post");
    	$post_status = $wpdb->get_var("SELECT p.post_status FROM $wpdb->posts p, $wpdb->posts c WHERE c.ID=$post AND c.post_parent=p.ID");
    }
    
	$index_statuses = array('publish', 'private', 'draft', 'future', 'pending');
	if (!in_array($post_status, $index_statuses)) {
 		// The post isn't supposed to be indexed anymore, remove it from index
 		relevanssi_remove_doc($post);
	}
	else {
		relevanssi_publish($post);
	}
}

function relevanssi_purge_excerpt_cache($post) {
	global $wpdb, $relevanssi_excerpt_cache;
	
	$wpdb->query("DELETE FROM $relevanssi_excerpt_cache WHERE post = $post");
}

function relevanssi_profile_update($user) {
	if (get_option('relevanssi_index_users') == 'on') {
		$update = true;
		relevanssi_index_user($user, $update);
	}
}

function relevanssi_edit_term($term) {
	if (get_option('relevanssi_index_taxonomies') == 'on') {	
		$update = true;
		global $wpdb;
		$taxonomy = $wpdb->get_var("SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id=$term");
		relevanssi_index_taxonomy_term($term, $taxonomy, $update);
	}
}

function relevanssi_delete_user($user) {
	global $wpdb, $relevanssi_table;
	$wpdb->query("DELETE FROM $relevanssi_table WHERE item = $user AND type = 'user'");
}

function relevanssi_delete_taxonomy_term($term) {
	global $wpdb, $relevanssi_table;
	$wpdb->query("DELETE FROM $relevanssi_table WHERE item = $term AND type = 'taxonomy'");
}

function relevanssi_delete($post) {
	relevanssi_remove_doc($post);
	relevanssi_purge_excerpt_cache($post);
}

function relevanssi_publish($post, $bypassglobalpost = false) {
	global $relevanssi_publish_doc;
	
	$post_status = get_post_status($post);
	if ('auto-draft' == $post_status) return;

	$custom_fields = relevanssi_get_custom_fields();
	relevanssi_index_doc($post, true, $custom_fields, $bypassglobalpost);
}

// added by lumpysimon
// when we're using wp_insert_post to update a post,
// we don't want to use the global $post object
function relevanssi_insert_edit($post_id) {
	global $wpdb;

	$post_status = get_post_status( $post_id );
	if ( 'auto-draft' == $post_status ) return;

    if ( $post_status == 'inherit' ) {
        $post_type = $wpdb->get_var( "SELECT post_type FROM $wpdb->posts WHERE ID=$post_id" );
	    $post_status = $wpdb->get_var( "SELECT p.post_status FROM $wpdb->posts p, $wpdb->posts c WHERE c.ID=$post_id AND c.post_parent=p.ID" );
    }

	$index_statuses = array('publish', 'private', 'draft', 'future', 'pending');
	if ( !in_array( $post_status, $index_statuses ) ) {
		// The post isn't supposed to be indexed anymore, remove it from index
		relevanssi_remove_doc( $post_id );
	}
	else {
		$bypassglobalpost = true;
		relevanssi_publish($post_id, $bypassglobalpost);
	}
}

function relevanssi_new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	global $wpdb;
 
	if (is_plugin_active_for_network('relevanssi-premium/relevanssi.php')) {
		switch_to_blog($blog_id);
		_relevanssi_install();
		restore_current_blog();
	}
}

function relevanssi_install($network_wide = false) {
	global $wpdb;

	if ($network_wide) {
		$blogids = $wpdb->get_col($wpdb->prepare("
			SELECT blog_id
			FROM $wpdb->blogs
			WHERE site_id = %d
			AND deleted = 0
			AND spam = 0
		", $wpdb->siteid));

		foreach ($blogids as $blog_id) {
			switch_to_blog($blog_id);
			_relevanssi_install();
		}

		restore_current_blog();
	} else {
		_relevanssi_install();
	}
}

function _relevanssi_install() {
	global $wpdb, $relevanssi_table, $relevanssi_stopword_table, $title_boost_default,
	$link_boost_default, $comment_boost_default, $relevanssi_db_version;
	
	add_option('relevanssi_title_boost', $title_boost_default);
	add_option('relevanssi_link_boost', $link_boost_default);
	add_option('relevanssi_comment_boost', $comment_boost_default);
	add_option('relevanssi_admin_search', 'off');
	add_option('relevanssi_highlight', 'strong');
	add_option('relevanssi_txt_col', '#ff0000');
	add_option('relevanssi_bg_col', '#ffaf75');
	add_option('relevanssi_css', 'text-decoration: underline; text-color: #ff0000');
	add_option('relevanssi_class', 'relevanssi-query-term');
	add_option('relevanssi_excerpts', 'on');
	add_option('relevanssi_excerpt_length', '450');
	add_option('relevanssi_excerpt_type', 'chars');
	add_option('relevanssi_log_queries', 'off');
	add_option('relevanssi_cat', '0');
	add_option('relevanssi_excat', '0');
	add_option('relevanssi_index_fields', '');
	add_option('relevanssi_exclude_posts', ''); 		//added by OdditY
	add_option('relevanssi_include_tags', 'on');		//added by OdditY	
	add_option('relevanssi_hilite_title', ''); 			//added by OdditY	
	add_option('relevanssi_highlight_docs', 'off');
	add_option('relevanssi_highlight_docs_external', 'off');
	add_option('relevanssi_highlight_comments', 'off');
	add_option('relevanssi_index_comments', 'none');	//added by OdditY
	add_option('relevanssi_include_cats', '');
	add_option('relevanssi_show_matches', '');
	add_option('relevanssi_show_matches_text', '(Search hits: %body% in body, %title% in title, %tags% in tags, %comments% in comments. Score: %score%)');
	add_option('relevanssi_fuzzy', 'sometimes');
	add_option('relevanssi_indexed', '');
	add_option('relevanssi_expand_shortcodes', 'on');
	add_option('relevanssi_custom_taxonomies', '');
	add_option('relevanssi_index_author', '');
	add_option('relevanssi_implicit_operator', 'OR');
	add_option('relevanssi_omit_from_logs', '');
	add_option('relevanssi_synonyms', '');
	add_option('relevanssi_index_excerpt', '');
	add_option('relevanssi_index_limit', '500');
	add_option('relevanssi_disable_or_fallback', 'off');
	add_option('relevanssi_respect_exclude', 'on');
	add_option('relevanssi_cache_seconds', '172800');
	add_option('relevanssi_enable_cache', 'off');
	add_option('relevanssi_min_word_length', '3');
	add_option('relevanssi_throttle', 'on');
	add_option('relevanssi_db_version', '1');
	add_option('relevanssi_wpml_only_current', 'on');
	add_option('relevanssi_post_type_weights', '');
	add_option('relevanssi_index_users', 'off');
	add_option('relevanssi_index_subscribers', 'off');
	add_option('relevanssi_index_taxonomies', 'off');
	add_option('relevanssi_taxonomies_to_index', '');
	add_option('relevanssi_internal_links', 'noindex');
	add_option('relevanssi_word_boundaries', 'on');
	add_option('relevanssi_default_orderby', 'relevance');
	add_option('relevanssi_thousand_separator', '');
	add_option('relevanssi_api_key', '');
	add_option('relevanssi_index_post_types', array('post', 'page'));
	add_option('relenvassi_recency_bonus', array('bonus' => '', 'days' => ''));
	add_option('relevanssi_mysql_columns', '');
	add_option('relevanssi_hide_post_controls', 'off');
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$charset_collate_bin_column = '';
	$charset_collate = '';

	if (!empty($wpdb->charset)) {
        $charset_collate_bin_column = "CHARACTER SET $wpdb->charset";
		$charset_collate = "DEFAULT $charset_collate_bin_column";
	}
	if (strpos($wpdb->collate, "_") > 0) {
        $charset_collate_bin_column .= " COLLATE " . substr($wpdb->collate, 0, strpos($wpdb->collate, '_')) . "_bin";
        $charset_collate .= " COLLATE $wpdb->collate";
    } else {
    	if ($wpdb->collate == '' && $wpdb->charset == "utf8") {
	        $charset_collate_bin_column .= " COLLATE utf8_bin";
	    }
    }
    
	$relevanssi_table = $wpdb->prefix . "relevanssi";	
	$relevanssi_stopword_table = $wpdb->prefix . "relevanssi_stopwords";
	$relevanssi_log_table = $wpdb->prefix . "relevanssi_log";
	$relevanssi_cache = $wpdb->prefix . 'relevanssi_cache';
	$relevanssi_excerpt_cache = $wpdb->prefix . 'relevanssi_excerpt_cache';

	if(get_option('relevanssi_db_version') != $relevanssi_db_version) {
		if (get_option('relevanssi_db_version') == 7) {
			$sql = "DROP TABLE $relevanssi_table";
			$wpdb->query($sql);
			delete_option('relevanssi_indexed');
		}
	
		$sql = "CREATE TABLE " . $relevanssi_table . " (doc bigint(20) NOT NULL DEFAULT '0', 
		term varchar(50) NOT NULL DEFAULT '0', 
		content mediumint(9) NOT NULL DEFAULT '0', 
		title mediumint(9) NOT NULL DEFAULT '0', 
		comment mediumint(9) NOT NULL DEFAULT '0', 
		tag mediumint(9) NOT NULL DEFAULT '0', 
		link mediumint(9) NOT NULL DEFAULT '0', 
		author mediumint(9) NOT NULL DEFAULT '0', 
		category mediumint(9) NOT NULL DEFAULT '0', 
		excerpt mediumint(9) NOT NULL DEFAULT '0', 
		taxonomy mediumint(9) NOT NULL DEFAULT '0', 
		customfield mediumint(9) NOT NULL DEFAULT '0', 
		mysqlcolumn mediumint(9) NOT NULL DEFAULT '0',
		taxonomy_detail longtext NOT NULL,
		customfield_detail longtext NOT NULL,
		mysqlcolumn_detail longtext NOT NULL,
		type varchar(210) NOT NULL DEFAULT 'post', 
		item bigint(20) NOT NULL DEFAULT '0', 
	    UNIQUE KEY doctermitem (doc, term, item)) $charset_collate";
		
		dbDelta($sql);

		$sql = "CREATE INDEX terms ON $relevanssi_table (term(20))";
		$wpdb->query($sql);

		$sql = "CREATE INDEX docs ON $relevanssi_table (doc)";
		$wpdb->query($sql);

		$sql = "CREATE TABLE " . $relevanssi_stopword_table . " (stopword varchar(50) $charset_collate_bin_column NOT NULL, "
	    . "UNIQUE KEY stopword (stopword)) $charset_collate;";

		dbDelta($sql);

		if (get_option('relevanssi_db_version') < 12) {
			$sql = "ALTER TABLE $relevanssi_stopword_table MODIFY COLUMN stopword varchar(50) $charset_collate_bin_column NOT NULL";
			$wpdb->query($sql);
		}

		$sql = "CREATE TABLE " . $relevanssi_log_table . " (id bigint(9) NOT NULL AUTO_INCREMENT, "
		. "query varchar(200) NOT NULL, "
		. "hits mediumint(9) NOT NULL DEFAULT '0', "
		. "time timestamp NOT NULL, "
		. "user_id bigint(20) NOT NULL DEFAULT '0', "
		. "ip varchar(40) NOT NULL DEFAULT '', "
	    . "UNIQUE KEY id (id)) $charset_collate;";

		dbDelta($sql);
		
		if (get_option('relevanssi_db_version') < 12) {
			$sql = "ALTER TABLE $relevanssi_log_table ADD COLUMN user_id bigint(20) NOT NULL DEFAULT '0'";
			$wpdb->query($sql);
			$sql = "ALTER TABLE $relevanssi_log_table ADD COLUMN ip varchar(40) NOT NULL DEFAULT ''";
			$wpdb->query($sql);
		}
	
		$sql = "CREATE TABLE " . $relevanssi_cache . " (param varchar(32) $charset_collate_bin_column NOT NULL, "
		. "hits text NOT NULL, "
		. "tstamp timestamp NOT NULL, "
	    . "UNIQUE KEY param (param)) $charset_collate;";

		dbDelta($sql);

		if (get_option('relevanssi_db_version') < 12) {
			$sql = "ALTER TABLE $relevanssi_cache MODIFY COLUMN param varchar(32) $charset_collate_bin_column NOT NULL";
			$wpdb->query($sql);
		}

		$sql = "CREATE TABLE " . $relevanssi_excerpt_cache . " (query varchar(100) $charset_collate_bin_column NOT NULL, "
		. "post mediumint(9) NOT NULL, "
		. "excerpt text NOT NULL, "
	    . "UNIQUE (query, post)) $charset_collate;";

		dbDelta($sql);
		
		if (get_option('relevanssi_db_version') < 12) {
			$sql = "ALTER TABLE $relevanssi_excerpt_cache MODIFY COLUMN query(100) $charset_collate_bin_column NOT NULL";
			$wpdb->query($sql);
		}

		update_option('relevanssi_db_version', $relevanssi_db_version);
	}
	
	if ($wpdb->get_var("SELECT COUNT(*) FROM $relevanssi_stopword_table WHERE 1") < 1) {
		relevanssi_populate_stopwords();
	}
}

//Added by OdditY -> 
function relevanssi_comment_edit($comID) {
	relevanssi_comment_index($comID,$action="update");
}

function relevanssi_comment_remove($comID) {
	relevanssi_comment_index($comID,$action="remove");
}

function relevanssi_comment_index($comID,$action="add") {
	global $wpdb;
	$comtype = get_option("relevanssi_index_comments");
	switch ($comtype) {
		case "all": 
			// all (incl. customs, track-&pingbacks)
			break;
		case "normal": 
			// normal (excl. customs, track-&pingbacks)
			$restriction=" AND comment_type='' ";
			break;
		default:
			// none (don't index)
			return ;
	}
	switch ($action) {
		case "update": 
			//(update) comment status changed:
			$cpostID = $wpdb->get_var("SELECT comment_post_ID FROM $wpdb->comments WHERE comment_ID='$comID'".$restriction);
			break;
		case "remove": 
			//(remove) approved comment will be deleted (if not approved, its not in index):
			$cpostID = $wpdb->get_var("SELECT comment_post_ID FROM $wpdb->comments WHERE comment_ID='$comID' AND comment_approved='1'".$restriction);
			if($cpostID!=NULL) {
				//empty comment_content & reindex, then let WP delete the empty comment
				$wpdb->query("UPDATE $wpdb->comments SET comment_content='' WHERE comment_ID='$comID'");
			}				
			break;
		default:
			// (add) new comment:
			$cpostID = $wpdb->get_var("SELECT comment_post_ID FROM $wpdb->comments WHERE comment_ID='$comID' AND comment_approved='1'".$restriction);
			break;
	}
	if($cpostID!=NULL) relevanssi_publish($cpostID);	
}
//Added by OdditY END <-

// Reads automatically the correct stopwords for the current language set in WPLANG.
function relevanssi_populate_stopwords() {
	global $wpdb, $relevanssi_stopword_table;

	if (WPLANG == '') {
		$lang = "en_GB";
	}
	else {
		$lang = WPLANG;
	}
	
	include('stopwords.' . $lang);

	if (is_array($stopwords) && count($stopwords) > 0) {
		foreach ($stopwords as $word) {
			$q = $wpdb->prepare("INSERT IGNORE INTO $relevanssi_stopword_table (stopword) VALUES (%s)", trim($word));
			$wpdb->query($q);
		}
	}
}

function relevanssi_fetch_stopwords() {
	global $wpdb, $stopword_list, $relevanssi_stopword_table;
	
	if (count($stopword_list) < 1) {
		$results = $wpdb->get_results("SELECT stopword FROM $relevanssi_stopword_table");
		foreach ($results as $word) {
			$stopword_list[] = $word->stopword;
		}
	}
	
	return $stopword_list;
}

function relevanssi_query($posts, $query = false) {
	$admin_search = get_option('relevanssi_admin_search');
	($admin_search == 'on') ? $admin_search = true : $admin_search = false;

	global $relevanssi_active;
	global $wp_query;

	$search_ok = true; 							// we will search!
	if (!is_search()) {
		$search_ok = false;						// no, we can't
	}
	
	// Uses $wp_query->is_admin instead of is_admin() to help with Ajax queries that
	// use 'admin_ajax' hook (which sets is_admin() to true whether it's an admin search
	// or not.
	if (is_search() && $wp_query->is_admin) {
		$search_ok = false; 					// but if this is an admin search, reconsider
		if ($admin_search) $search_ok = true; 	// yes, we can search!
	}

	$search_ok = apply_filters('relevanssi_search_ok', $search_ok);
	
	if ($relevanssi_active) {
		$search_ok = false;						// Relevanssi is already in action
	}

	if ($search_ok) {
		$wp_query = apply_filters('relevanssi_modify_wp_query', $wp_query);
		$posts = relevanssi_do_query($wp_query);
	}

	return $posts;
}

add_filter('query_vars', 'relevanssi_query_vars');
function relevanssi_query_vars($qv) {
	$qv[] = 'searchblogs';
	$qv[] = 'customfield_key';
	$qv[] = 'customfield_value';
	$qv[] = 'cats';
	$qv[] = 'tags';
	$qv[] = 'post_types';
	$qv[] = 'operator';
	return $qv;
}

function relevanssi_do_query(&$query) {
	// this all is basically lifted from Kenny Katzgrau's wpSearch
	// thanks, Kenny!
	global $wpSearch_low;
	global $wpSearch_high;
	global $relevanssi_active;

	$relevanssi_active = true;
	$posts = array();

	if ( function_exists( 'mb_strtolower' ) )
		$q = trim(stripslashes(mb_strtolower($query->query_vars["s"])));
	else
		$q = trim(stripslashes(strtolower($query->query_vars["s"])));

	$cache = get_option('relevanssi_enable_cache');
	$cache == 'on' ? $cache = true : $cache = false;

	if (isset($query->query_vars['searchblogs'])) {
		$search_blogs = $query->query_vars['searchblogs'];

		$post_type = false;
		if (isset($query->query_vars["post_type"]) && $query->query_vars["post_type"] != 'any') {
			$post_type = $query->query_vars["post_type"];
		}
		if (isset($query->query_vars["post_types"])) {
			$post_type = $query->query_vars["post_types"];
		}

		$return = relevanssi_search_multi($q, $search_blogs, $post_type);
	}
	else {
		$cat = false;
		if (isset($query->query_vars["cat"])) {
			$cat = $query->query_vars["cat"];
		}
		if (isset($query->query_vars["cats"])) {
			$cat = $query->query_vars["cats"];
		}
		if (!$cat) {
			$cat = get_option('relevanssi_cat');
			if (0 == $cat) {
				$cat = false;
			}
		}

		$tag = false;
		if (isset($query->query_vars["tag"])) {
			$tag = $query->query_vars["tag"];
		}
		if (isset($query->query_vars["tags"])) {
			$tag = $query->query_vars["tags"];
		}

		$author = false;
		if (isset($query->query_vars["author"])) {
			$author = $query->query_vars["author"];
		}

		$customfield_key = false;
		if (isset($query->query_vars["customfield_key"])) {
			$customfield_key = $query->query_vars["customfield_key"];
		}
		$customfield_value = false;
		if (isset($query->query_vars["customfield_value"])) {
			$customfield_value = $query->query_vars["customfield_value"];
		}
	
		$tax = false;
		$tax_term = false;
		if (isset($query->query_vars["taxonomy"])) {
			$tax = $query->query_vars["taxonomy"];
			$tax_term = $query->query_vars["term"];
		}
		
		if (!isset($excat)) {
			$excat = get_option('relevanssi_excat');
			if (0 == $excat) {
				$excat = false;
			}
		}
	
		$search_blogs = false;
		if (isset($query->query_vars["search_blogs"])) {
			$search_blogs = $query->query_vars["search_blogs"];
		}
	
		$post_type = false;
		if (isset($query->query_vars["post_type"]) && $query->query_vars["post_type"] != 'any') {
			$post_type = $query->query_vars["post_type"];
		}
		if (isset($query->query_vars["post_types"])) {
			$post_type = $query->query_vars["post_types"];
		}
	
		$expids = get_option("relevanssi_exclude_posts");
	
		if (is_admin()) {
			// in admin search, search everything
			$cat = null;
			$tag = null;
			$excat = null;
			$expids = null;
			$tax = null;
			$tax_term = null;
		}
	
		isset($query->query_vars['operator']) ?
			$operator = $query->query_vars['operator'] : 
			$operator = get_option("relevanssi_implicit_operator");
		
		$operator = strtoupper($operator);	// just in case
		if ($operator != "OR" && $operator != "AND") $operator = get_option("relevanssi_implicit_operator");
		
		// Add synonyms
		// This is done here so the new terms will get highlighting
		if ("OR" == $operator) {
			// Synonyms are only used in OR queries
			$synonym_data = get_option('relevanssi_synonyms');
			if ($synonym_data) {
				$synonyms = array();
				$pairs = explode(";", $synonym_data);
				foreach ($pairs as $pair) {
					$parts = explode("=", $pair);
					$key = trim($parts[0]);
					$value = trim($parts[1]);
					$synonyms[$key][$value] = true;
				}
				if (count($synonyms) > 0) {
					$new_terms = array();
					$terms = array_keys(relevanssi_tokenize($q, false)); // remove stopwords is false here
					foreach ($terms as $term) {
						if (in_array($term, array_keys($synonyms))) {
							$new_terms = array_merge($new_terms, array_keys($synonyms[$term]));
						}
					}
					if (count($new_terms) > 0) {
						foreach ($new_terms as $new_term) {
							$q .= " $new_term";
						}
					}
				}
			}
		}
	
		if ($cache) {
			$params = md5(serialize(array($q, $cat, $excat, $tag, $expids, $post_type, $tax, $tax_term, $operator, $search_blogs, $customfield_key, $customfield_value, $author)));
			$return = relevanssi_fetch_hits($params);
			if (!$return) {
				$return = relevanssi_search($q, $cat, $excat, $tag, $expids, $post_type, $tax, $tax_term, $operator, $search_blogs, $customfield_key, $customfield_value, $author);
				$return_ser = serialize($return);
				relevanssi_store_hits($params, $return_ser);
			}
		}
		else {
			$return = relevanssi_search($q,
										$cat, $excat,
										$tag,
										$expids,
										$post_type,
										$tax, $tax_term,
										$operator,
										$search_blogs,
										$customfield_key,
										$customfield_value,
										$author);
		}
	}

	$hits = $return['hits'];
	$q = $return['query'];

	$filter_data = array($hits, $q);
	$hits_filters_applied = apply_filters('relevanssi_hits_filter', $filter_data);
	$hits = $hits_filters_applied[0];

	$query->found_posts = sizeof($hits);
	$query->max_num_pages = ceil(sizeof($hits) / $query->query_vars["posts_per_page"]);

	$update_log = get_option('relevanssi_log_queries');
	if ('on' == $update_log) {
		relevanssi_update_log($q, sizeof($hits));
	}

	$make_excerpts = get_option('relevanssi_excerpts');

	if (is_paged()) {
		$wpSearch_low = ($query->query_vars['paged'] - 1) * $query->query_vars["posts_per_page"];
	}
	else {
		$wpSearch_low = 0;
	}

	if (is_paged()) {
		$wpSearch_high = $wpSearch_low + $query->query_vars["posts_per_page"] - 1;
	}
	else {
		$wpSearch_high = sizeof($hits);
	}

	if ($wpSearch_high > sizeof($hits)) $wpSearch_high = sizeof($hits);

	for ($i = $wpSearch_low; $i <= $wpSearch_high; $i++) {
		if (isset($hits[intval($i)])) {
			$post = $hits[intval($i)];
		}
		else {
			continue;
		}

		if ($post == NULL) {
			// apparently sometimes you can get a null object
			continue;
		}
		
		//Added by OdditY - Highlight Result Title too -> 
		if("on" == get_option('relevanssi_hilite_title')){
			if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) {
				$post->post_title = strip_tags(qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($post->post_title));
			}
			else {
				$post->post_title = strip_tags($post->post_title);
			}
			$highlight = get_option('relevanssi_highlight');
			if ("none" != $highlight) {
				if (!is_admin()) {
					$post->post_title = relevanssi_highlight_terms($post->post_title, $q);
				}
			}
		}
		// OdditY end <-			
		
		if ('on' == $make_excerpts) {			
			if ($cache) {
				$post->post_excerpt = relevanssi_fetch_excerpt($post->ID, $q);
				if ($post->post_excerpt == null) {
					$post->post_excerpt = relevanssi_do_excerpt($post, $q);
					relevanssi_store_excerpt($post->ID, $q, $post->post_excerpt);
				}
			}
			else {
				$post->post_excerpt = relevanssi_do_excerpt($post, $q);
			}
			
			if ('on' == get_option('relevanssi_show_matches')) {
				$post->post_excerpt .= relevanssi_show_matches($return, $post->ID);
			}
		}
		
		$post->relevance_score = round($return['scores'][$post->ID], 2);
		
		$posts[] = $post;
	}

	$query->posts = $posts;
	$query->post_count = count($posts);
	
	return $posts;
}

function relevanssi_fetch_excerpt($post, $query) {
	global $wpdb, $relevanssi_excerpt_cache;

	$query = mysql_real_escape_string($query);	
	$excerpt = $wpdb->get_var("SELECT excerpt FROM $relevanssi_excerpt_cache WHERE post = $post AND query = '$query'");
	
	if (!$excerpt) return null;
	
	return $excerpt;
}

function relevanssi_store_excerpt($post, $query, $excerpt) {
	global $wpdb, $relevanssi_excerpt_cache;
	
	$query = mysql_real_escape_string($query);
	$excerpt = mysql_real_escape_string($excerpt);

	$wpdb->query("INSERT INTO $relevanssi_excerpt_cache (post, query, excerpt)
		VALUES ($post, '$query', '$excerpt')
		ON DUPLICATE KEY UPDATE excerpt = '$excerpt'");
}

function relevanssi_fetch_hits($param) {
	global $wpdb, $relevanssi_cache;

	$time = get_option('relevanssi_cache_seconds', 172800);

	$hits = $wpdb->get_var("SELECT hits FROM $relevanssi_cache WHERE param = '$param' AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(tstamp) < $time");
	
	if ($hits) {
		return unserialize($hits);
	}
	else {
		return null;
	}
}

function relevanssi_store_hits($param, $data) {
	global $wpdb, $relevanssi_cache;

	$param = mysql_real_escape_string($param);
	$data = mysql_real_escape_string($data);
	$wpdb->query("INSERT INTO $relevanssi_cache (param, hits)
		VALUES ('$param', '$data')
		ON DUPLICATE KEY UPDATE hits = '$data'");
}

// thanks to rvencu
function relevanssi_wpml_filter($data) {
	$use_filter = get_option('relevanssi_wpml_only_current');
	if ('on' == $use_filter) {
		//save current blog language
		$lang = get_bloginfo('language');
		$filtered_hits = array();
		foreach ($data[0] as $hit) {
			if (isset($hit->blog_id)) {
				switch_to_blog($hit->blog_id);
			}
			global $sitepress;
			if (function_exists('icl_object_id') && $sitepress->is_translated_post_type($hit->post_type)) {
			    if ($hit->ID == icl_object_id($hit->ID, $hit->post_type,false,ICL_LANGUAGE_CODE))
			        $filtered_hits[] = $hit;
			}
			// if there is no WPML but the target blog has identical language with current blog,
			// we use the hits. Note en-US is not identical to en-GB!
			elseif (get_bloginfo('language') == $lang) {
				$filtered_hits[] = $hit;
			}
			if (isset($hit->blog_id)) {
				restore_current_blog();
			}
		}
		return array($filtered_hits, $data[1]);
	}
	return $data;
}

/**
 * Function by Matthew Hood http://my.php.net/manual/en/function.sort.php#75036
 */
function relevanssi_object_sort(&$data, $key, $dir = 'desc') {
	$dir = strtolower($dir);
    for ($i = count($data) - 1; $i >= 0; $i--) {
		$swapped = false;
      	for ($j = 0; $j < $i; $j++) {
      		if ('asc' == $dir) {
	           	if ($data[$j]->$key > $data[$j + 1]->$key) { 
    		        $tmp = $data[$j];
        	        $data[$j] = $data[$j + 1];
            	    $data[$j + 1] = $tmp;
                	$swapped = true;
	           	}
	        }
			else {
	           	if ($data[$j]->$key < $data[$j + 1]->$key) { 
    		        $tmp = $data[$j];
        	        $data[$j] = $data[$j + 1];
            	    $data[$j + 1] = $tmp;
                	$swapped = true;
	           	}
			}
    	}
	    if (!$swapped) return;
    }
}

function relevanssi_show_matches($data, $hit) {
	isset($data['body_matches'][$hit]) ? $body = $data['body_matches'][$hit] : $body = "";
	isset($data['title_matches'][$hit]) ? $title = $data['title_matches'][$hit] : $title = "";
	isset($data['tag_matches'][$hit]) ? $tag = $data['tag_matches'][$hit] : $tag = "";
	isset($data['comment_matches'][$hit]) ? $comment = $data['comment_matches'][$hit] : $comment = "";
	isset($data['scores'][$hit]) ? $score = round($data['scores'][$hit], 2) : $score = 0;
	isset($data['term_hits'][$hit]) ? $term_hits_a = $data['term_hits'][$hit] : $term_hits_a = array();
	arsort($term_hits_a);
	$term_hits = "";
	$total_hits = 0;
	foreach ($term_hits_a as $term => $hits) {
		$term_hits .= " $term: $hits";
		$total_hits += $hits;
	}
	
	$text = get_option('relevanssi_show_matches_text');
	$replace_these = array("%body%", "%title%", "%tags%", "%comments%", "%score%", "%terms%", "%total%");
	$replacements = array($body, $title, $tag, $comment, $score, $term_hits, $total_hits);
	
	$result = " " . str_replace($replace_these, $replacements, $text);
	
	return apply_filters('relevanssi_show_matches', $result);
}

function relevanssi_update_log($query, $hits) {
	if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == "Mediapartners-Google")
		return;

	global $wpdb, $relevanssi_log_table;
	
	$user = wp_get_current_user();
	if ($user->ID != 0 && get_option('relevanssi_omit_from_logs')) {
		$omit = explode(",", get_option('relevanssi_omit_from_logs'));
		if (in_array($user->ID, $omit)) return;
		if (in_array($user->user_login, $omit)) return;
	}
		
	$q = $wpdb->prepare("INSERT INTO $relevanssi_log_table (query, hits, user_id, ip) VALUES (%s, %d, %d, %s)", $query, intval($hits), $user->ID, $_SERVER['REMOTE_ADDR']);
	$wpdb->query($q);
}

// This is my own magic working.
function relevanssi_search($q, $cat = NULL, $excat = NULL, $tag = NULL, $expost = NULL, $post_type = NULL, $taxonomy = NULL, $taxonomy_term = NULL, $operator = "AND", $search_blogs = NULL, $customfield_key = NULL, $customfield_value = NULL, $author = NULL) {
	global $relevanssi_table, $wpdb;

	$values_to_filter = array(
		'q' => $q,
		'cat' => $cat,
		'excat' => $excat,
		'tag' => $tag,
		'expost' => $expost,
		'post_type' => $post_type,
		'taxonomy' => $taxonomy,
		'taxonomy_term' => $taxonomy_term,
		'operator' => $operator,
		'search_blogs' => $search_blogs,
		'customfield_key' => $customfield_key,
		'customfield_value' => $customfield_value,
		'author' => $author,
		);
	$filtered_values = apply_filters( 'relevanssi_search_filters', $values_to_filter );
	$q               = $filtered_values['q'];
	$cat             = $filtered_values['cat'];
	$tag             = $filtered_values['tag'];
	$excat           = $filtered_values['excat'];
	$expost          = $filtered_values['expost'];
	$post_type       = $filtered_values['post_type'];
	$taxonomy        = $filtered_values['taxonomy'];
	$taxonomy_term   = $filtered_values['taxonomy_term'];
	$operator        = $filtered_values['operator'];
	$search_blogs    = $filtered_values['search_blogs'];
	$customfield_key = $filtered_values['customfield_key'];
	$customfield_value = $filtered_values['customfield_value'];
	$author	  	     = $filtered_values['author'];

	$hits = array();

	$o_cat = $cat;
	$o_excat = $excat;
	$o_tag = $tag;
	$o_expost = $expost;
	$o_post_type = $post_type;
	$o_taxonomy = $taxonomy;
	$o_taxonomy_term = $taxonomy_term;
	$o_customfield_key = $customfield_key;
	$o_customfield_value = $customfield_value;
	$o_author = $author;
	
	$customfield = '';
	if ($customfield_key) {
		$post_ids = array();
		if ($customfield_value) {
			$results = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value LIKE '%%%s%%'", $customfield_key, $customfield_value));
		}
		else {
			$results = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s", $customfield_key));
		}
		foreach ($results as $row) {
			$post_ids[] = $row->post_id;
		}
		$customfield = implode(",", $post_ids);
		if (empty($customfield)) $customfield = "no_hits";
	}
	
	if ($cat) {
		$cats = explode(",", $cat);
		$inc_term_tax_ids = array();
		$ex_term_tax_ids = array();
		foreach ($cats as $t_cat) {
			$exclude = false;
			if ($t_cat < 0) {
				// Negative category, ie. exclusion
				$exclude = true;
				$t_cat = substr($t_cat, 1); // strip the - sign.
			}
			$t_cat = $wpdb->escape($t_cat);
			$term_tax_id = $wpdb->get_var("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy
				WHERE term_id=$t_cat");
			if ($term_tax_id) {
				$exclude ? $ex_term_tax_ids[] = $term_tax_id : $inc_term_tax_ids[] = $term_tax_id;
				$children = get_term_children($term_tax_id, 'category');
				if (is_array($children)) {
					foreach ($children as $child) {
						$exclude ? $ex_term_tax_ids[] = $child : $inc_term_tax_ids[] = $child;
					}
				}
			}
		}
		
		$cat = implode(",", $inc_term_tax_ids);
		$excat_temp = implode(",", $ex_term_tax_ids);
	}

	if ($excat) {
		$excats = explode(",", $excat);
		$term_tax_ids = array();
		foreach ($excats as $t_cat) {
			$t_cat = $wpdb->escape(trim($t_cat, ' -'));
			$term_tax_id = $wpdb->get_var("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy
				WHERE term_id=$t_cat");
			if ($term_tax_id) {
				$term_tax_ids[] = $term_tax_id;
			}
		}
		
		$excat = implode(",", $term_tax_ids);
	}

	if (isset($excat_temp)) {
		$excat .= $excat_temp;
	}

	if ($tag) {
		$tags = explode(",", $tag);
		$inc_term_tax_ids = array();
		$ex_term_tax_ids = array();
		foreach ($tags as $t_tag) {
			$t_tag = $wpdb->escape($t_tag);
			$term_tax_id = $wpdb->get_var("
				SELECT term_taxonomy_id
					FROM $wpdb->term_taxonomy as a, $wpdb->terms as b
					WHERE a.term_id = b.term_id AND
						(a.term_id='$t_tag' OR b.name LIKE '$t_tag')");

			if ($term_tax_id) {
				$inc_term_tax_ids[] = $term_tax_id;
			}
		}
		
		$tag = implode(",", $inc_term_tax_ids);
	}
	
	if ($author) {
		$author = esc_sql($author);
	}

	if (!empty($taxonomy)) {
		$taxonomies = explode('|', $taxonomy);
		$terms = explode('|', $taxonomy_term);
		$i = 0;
		$taxonomy_array = array();
		foreach ($taxonomies as $taxonomy) {
			$term_tax_id = null;
			$taxonomy_term = $terms[$i];
			$term_tax_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM $wpdb->terms
				JOIN $wpdb->term_taxonomy USING(`term_id`)
				WHERE `slug` LIKE %s AND `taxonomy` LIKE %s", "%$taxonomy_term%", $taxonomy));
			if ($term_tax_id) {
				$taxonomy_array[$taxonomy][] = $term_tax_id;
			}
			$i++;
		}
		$taxonomy = $taxonomy_array;
	}

	if (!$post_type && get_option('relevanssi_respect_exclude') == 'on') {
		if (function_exists('get_post_types')) {
			$pt_1 = get_post_types(array('exclude_from_search' => '0'));
			$pt_2 = get_post_types(array('exclude_from_search' => false));
			$post_type = implode(',', array_merge($pt_1, $pt_2));
		}
	}
	
	if ($post_type) {
		if (!is_array($post_type)) {
			$post_types = explode(',', $post_type);
		}
		else {
			$post_types = $post_type;
		}
		$pt_array = array();
		foreach ($post_types as $pt) {
			$pt = "'" . trim(mysql_real_escape_string($pt)) . "'";
			array_push($pt_array, $pt);
		}
		$post_type = implode(",", $pt_array);
	}

	//Added by OdditY:
	//Exclude Post_IDs (Pages) for non-admin search ->
	if ($expost) {
		if ($expost != "") {
			$aexpids = explode(",",$expost);
			foreach ($aexpids as $exid){
				$exid = $wpdb->escape(trim($exid, ' -'));
				$postex .= " AND doc !='$exid'";
			}
		}	
	}
	// <- OdditY End

	$remove_stopwords = false;
	$phrases = relevanssi_recognize_phrases($q);

	$negative_terms = relevanssi_recognize_negatives($q);

	$terms = relevanssi_tokenize($q, $remove_stopwords);
	if (count($terms) < 1) {
		// Tokenizer killed all the search terms.
		return $hits;
	}
	$terms = array_keys($terms); // don't care about tf in query
	
	$terms = array_diff($terms, $negative_terms);
	
	$D = $wpdb->get_var("SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table");
	
	$total_hits = 0;
		
	$title_matches = array();
	$tag_matches = array();
	$comment_matches = array();
	$link_matches = array();
	$body_matches = array();
	$scores = array();
	$term_hits = array();

	$fuzzy = get_option('relevanssi_fuzzy');

	$query_restrictions = "";
	if ($expost) { //added by OdditY
		$query_restrictions .= $postex;
	}
	
	if ($negative_terms) {
		for ($i = 0; $i < sizeof($negative_terms); $i++) {
			$negative_terms[$i] = "'" . $negative_terms[$i] . "'";
		}
		$negatives = implode(',', $negative_terms);
		$query_restrictions .= " AND doc NOT IN (SELECT DISTINCT(doc) FROM $relevanssi_table WHERE term IN ($negatives))";
	}
	
	if ($cat) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
		    WHERE term_taxonomy_id IN ($cat))";
	}
	if ($excat) {
		$query_restrictions .= " AND doc NOT IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
		    WHERE term_taxonomy_id IN ($excat))";
	}
	if ($tag) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
		    WHERE term_taxonomy_id IN ($tag))";
	}
	if ($author) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(ID) FROM $wpdb->posts
		    WHERE post_author IN ($author))";
	}
	if ($post_type) {
		// the -1 is there to get user profiles and category pages
		$query_restrictions .= " AND ((doc IN (SELECT DISTINCT(ID) FROM $wpdb->posts
			WHERE post_type IN ($post_type))) OR (doc = -1))";
	}
	if ($phrases) {
		$query_restrictions .= " AND doc IN ($phrases)";
	}
	if ($customfield) {
		$query_restrictions .= " AND doc IN ($customfield)";
	}
	if (is_array($taxonomy)) {
		foreach ($taxonomy as $tax) {
			$taxonomy_in = implode(',',$tax);
			$query_restrictions .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
				WHERE term_taxonomy_id IN ($taxonomy_in))";
		}
	}

	if (isset($_REQUEST['by_date'])) {
		$n = $_REQUEST['by_date'];

		$u = substr($n, -1, 1);
		switch ($u) {
			case 'h':
				$unit = "HOUR";
				break;
			case 'd':
				$unit = "DAY";
				break;
			case 'm':
				$unit = "MONTH";
				break;
			case 'y':
				$unit = "YEAR";
				break;
			case 'w':
				$unit = "WEEK";
				break;
			default:
				$unit = "DAY";
		}

		$n = preg_replace('/[hdmyw]/', '', $n);

		if (is_numeric($n)) {
			$query_restrictions .= " AND doc IN (SELECT DISTINCT(ID) FROM $wpdb->posts
				WHERE post_date > DATE_SUB(NOW(), INTERVAL $n $unit))";
		}
	}

	$query_restrictions = apply_filters('relevanssi_where', $query_restrictions); // Charles St-Pierre

	$no_matches = true;
	if ("always" == $fuzzy) {
		$o_term_cond = apply_filters('relevanssi_fuzzy_query', "(term LIKE '%#term#' OR term LIKE '#term#%') ");
	}
	else {
		$o_term_cond = " term = '#term#' ";
	}

	$post_types = relevanssi_get_post_types();
	$post_type_weights = get_option('relevanssi_post_type_weights');
	$recency_bonus = get_option('relevanssi_recency_bonus');
	if (empty($recency_bonus['days']) OR empty($recency_bonus['bonus'])) {
		$recency_bonus = false;
	}
	if ($recency_bonus) {
		$recency_cutoff_date = time() - 60 * 60 * 24 * $recency_bonus['days'];
	}
	$min_length = get_option('relevanssi_min_word_length');
	
	$search_again = false;
	do {
		foreach ($terms as $term) {
			if (strlen($term) < $min_length) continue;
			$term = $wpdb->escape(like_escape($term));
			$term_cond = str_replace('#term#', $term, $o_term_cond);		
			
			$query = "SELECT *, title + content + comment + tag + link + author + category + excerpt + taxonomy + customfield + mysqlcolumn AS tf 
					  FROM $relevanssi_table WHERE $term_cond $query_restrictions";
			$query = apply_filters('relevanssi_query_filter', $query);

			$matches = $wpdb->get_results($query);
			if (count($matches) < 1) {
				continue;
			}
			else {
				$no_matches = false;
			}
			
			relevanssi_populate_array($matches);
			
			$total_hits += count($matches);
	
			$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE $term_cond $query_restrictions";
			$query = apply_filters('relevanssi_df_query_filter', $query);
	
			$df = $wpdb->get_var($query);
	
			if ($df < 1 && "sometimes" == $fuzzy) {
				$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table
					WHERE (term LIKE '%$term' OR term LIKE '$term%') $query_restrictions";
				$query = apply_filters('relevanssi_df_query_filter', $query);
				$df = $wpdb->get_var($query);
			}
			
			$title_boost = floatval(get_option('relevanssi_title_boost'));
			$link_boost = floatval(get_option('relevanssi_link_boost'));
			$comment_boost = floatval(get_option('relevanssi_comment_boost'));
			
			$idf = log($D / (1 + $df));
			$idf = $idf * $idf;
			foreach ($matches as $match) {
				if ('user' == $match->type) {
					$match->doc = 'u_' . $match->item;
				}

				if ('taxonomy' == $match->type) {
					$match->doc = 't_' . $match->item;
				}
				
				if (isset($match->taxonomy_detail)) {
					$match->taxonomy_score = 0;
					$match->taxonomy_detail = unserialize($match->taxonomy_detail);
					if (is_array($match->taxonomy_detail)) {
						foreach ($match->taxonomy_detail as $tax => $count) {
							$match->taxonomy_score += $count * $post_type_weights[$tax];
						}
					}
				}
				
				$match->tf =
					$match->title * $title_boost +
					$match->content +
					$match->comment * $comment_boost +
					$match->link * $link_boost +
					$match->author +
					$match->excerpt +
					$match->taxonomy_score +
					$match->customfield +
					$match->mysqlcolumn;

				$term_hits[$match->doc][$term] =
					$match->title +
					$match->content +
					$match->comment +
					$match->tag +
					$match->link +
					$match->author +
					$match->category +
					$match->excerpt +
					$match->taxonomy +
					$match->customfield +
					$match->mysqlcolumn;

				$match->weight = $match->tf * $idf;
				
				if ($recency_bonus) {
					$post = relevanssi_get_post($match->doc);
					if (strtotime($post->post_date) > $recency_cutoff_date)
						$match->weight = $match->weight * $recency_bonus['bonus'];
				}

				$body_matches[$match->doc] = $match->content;
				$title_matches[$match->doc] = $match->title;
				$tag_matches[$match->doc] = $match->tag;
				$comment_matches[$match->doc] = $match->comment;
	
				$type = $post_types[$match->doc];
				if (isset($post_type_weights[$type])) {
					$match->weight = $match->weight * $post_type_weights[$type];
				}

				$match = apply_filters('relevanssi_match', $match, $idf);

				if ($match->weight == 0) continue; // the filters killed the match

				$post_ok = true;
				$post_ok = apply_filters('relevanssi_post_ok', $post_ok, $match->doc);
				
				if ($post_ok) {
					$doc_terms[$match->doc][$term] = true; // count how many terms are matched to a doc
					isset($doc_weight[$match->doc]) ? $doc_weight[$match->doc] += $match->weight : $doc_weight[$match->doc] = $match->weight;
					isset($scores[$match->doc]) ? $scores[$match->doc] += $match->weight : $scores[$match->doc] = $match->weight;
				}
			}
		}

		if (!isset($doc_weight)) $no_matches = true;

		if ($no_matches) {
			if ($search_again) {
				// no hits even with fuzzy search!
				$search_again = false;
			}
			else {
				if ("sometimes" == $fuzzy) {
					$search_again = true;
					$o_term_cond = "(term LIKE '%#term#' OR term LIKE '#term#%') ";
				}
			}
		}
		else {
			$search_again = false;
		}
	} while ($search_again);
	
	$strip_stops = true;
	$temp_terms_without_stops = array_keys(relevanssi_tokenize(implode(' ', $terms), $strip_stops));
	$terms_without_stops = array();
	foreach ($temp_terms_without_stops as $temp_term) {
		if (strlen($temp_term) >= $min_length)
			array_push($terms_without_stops, $temp_term);
	}
	$total_terms = count($terms_without_stops);

	if (isset($doc_weight))
		$doc_weight = apply_filters('relevanssi_results', $doc_weight);

	if (isset($doc_weight) && count($doc_weight) > 0) {
		arsort($doc_weight);
		$i = 0;
		foreach ($doc_weight as $doc => $weight) {
			if (count($doc_terms[$doc]) < $total_terms && $operator == "AND") {
				// AND operator in action:
				// doc didn't match all terms, so it's discarded
				continue;
			}
			
			$hits[intval($i++)] = relevanssi_get_post($doc);
		}
	}

	if (count($hits) < 1) {
		if ($operator == "AND" AND get_option('relevanssi_disable_or_fallback') != 'on') {
			$return = relevanssi_search($q, $o_cat, $o_excat, $o_tag, $o_expost, $o_post_type, $o_taxonomy, $o_taxonomy_term, "OR", $search_blogs, $o_customfield_key, $o_customfield_value);
			extract($return);
		}
	}

	global $wp;	
	$default_order = get_option('relevanssi_default_orderby', 'relevance');
	isset($wp->query_vars["orderby"]) ? $orderby = $wp->query_vars["orderby"] : $orderby = $default_order;
	isset($wp->query_vars["order"]) ? $order = $wp->query_vars["order"] : $order = 'desc';
	if ($orderby != 'relevance')
		relevanssi_object_sort($hits, $orderby, $order);

	$return = array('hits' => $hits, 'body_matches' => $body_matches, 'title_matches' => $title_matches,
		'tag_matches' => $tag_matches, 'comment_matches' => $comment_matches, 'scores' => $scores,
		'term_hits' => $term_hits, 'query' => $q);

	return $return;
}

function relevanssi_default_post_ok($post_ok, $doc) {
	$status = relevanssi_get_post_status($doc);

	// if it's not public, don't show
	if ('publish' != $status) {
		$post_ok = false;
	}
	
	// ...unless
	
	if ('private' == $status) {
		$post_ok = false;

		if (function_exists('awp_user_can')) {
			// Role-Scoper
			$current_user = wp_get_current_user();
			$post_ok = awp_user_can('read_post', $doc, $current_user->ID);
		}
		else {
			// Basic WordPress version
			$type = relevanssi_get_post_type($doc);
			$cap = 'read_private_' . $type . 's';
			if (current_user_can($cap)) {
				$post_ok = true;
			}
		}
	}
	
	// only show drafts, pending and future posts in admin search
	if (in_array($status, array('draft', 'pending', 'future')) && is_admin()) {
		$post_ok = true;
	}
	
	if (relevanssi_s2member_level($doc) == 0) $post_ok = false; // not ok with s2member
	
	return $post_ok;
}

/**
 * Return values:
 *  2: full access to post
 *  1: show title only
 *  0: no access to post
 * -1: s2member not active
 */
function relevanssi_s2member_level($doc) {
	$return = -1;
	if (function_exists('is_permitted_by_s2member')) {
		// s2member
		$alt_view_protect = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["filter_wp_query"];
		
		if (version_compare (WS_PLUGIN__S2MEMBER_VERSION, "110912", ">="))
			$completely_hide_protected_search_results = (in_array ("all", $alt_view_protect) || in_array ("searches", $alt_view_protect)) ? true : false;
		else /* Backward compatibility with versions of s2Member, prior to v110912. */
			$completely_hide_protected_search_results = (strpos ($alt_view_protect, "all") !== false || strpos ($alt_view_protect, "searches") !== false) ? true : false;
		
		if (is_permitted_by_s2member($doc)) {
			// Show title and excerpt, even full content if you like.
			$return = 2;
		}
		else if (!is_permitted_by_s2member($doc) && $completely_hide_protected_search_results === false) {
			// Show title and excerpt. Alt View Protection is NOT enabled for search results. However, do NOT show full content body.
			$return = 1;
		}
		else {
			// Hide this search result completely.
			$return = 0;
		}
	}
	
	return $return;
}

/* 	Custom-made get_posts() replacement that creates post objects for
	users and taxonomy terms. For regular posts, the function uses
	a caching mechanism.
*/
function relevanssi_get_post($id) {
	global $relevanssi_post_array;
	
	$type = substr($id, 0, 2);
	switch ($type) {
		case 'u_':
			list($throwaway, $id) = explode('_', $id);
			$user = get_userdata($id);
		
			$post->post_title = $user->display_name;
			$post->post_content = $user->description;
			$post->post_type = 'user';
			$post->ID = $id;
			$post->link = get_author_posts_url($id);
		
			$post = apply_filters('relevanssi_user_profile_to_post', $post);
			break;
		case 't_':
			list($throwaway, $id) = explode('_', $id);
			$taxonomy = relevanssi_get_term_taxonomy($id);
			$term = get_term($id, $taxonomy);
			
			$post->post_title = $term->name;
			$post->post_content = $term->description;
			$post->post_type = $taxonomy;
			
			$post->link = get_term_link($term, $taxonomy);
			
			$post = apply_filters('relevanssi_taxonomy_term_to_post', $post);
			break;
		default:
			if (isset($relevanssi_post_array[$id])) {
				$post = $relevanssi_post_array[$id];
			}
			else {
				$post = get_post($id);
			}
	}
	return $post;
}

function relevanssi_populate_array($matches) {
	global $relevanssi_post_array, $wpdb;
	
	$ids = array();
	foreach ($matches as $match) {
		array_push($ids, $match->doc);
	}
	
	$ids = implode(',', $ids);
	$posts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE id IN ($ids)");
	foreach ($posts as $post) {
		$relevanssi_post_array[$post->ID] = $post;
	}
}

function relevanssi_get_term_taxonomy($id) {
	global $wpdb;
	$taxonomy = $wpdb->get_var("SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id = $id");
	return $taxonomy;
}

function relevanssi_get_post_type($id) {
	global $relevanssi_post_array;
	
	if (isset($relevanssi_post_array[$id])) {
		return $relevanssi_post_array[$id]->post_type;
	}
	else {
		return get_post_type($id);
	}
}

function relevanssi_get_post_status($id) {
	global $relevanssi_post_array;
	
	$type = substr($id, 0, 2);
	if ($type == 't_') {
		return 'publish';
	}
	if ($type == 'u_') {
		return 'publish';
	}
	
	if (isset($relevanssi_post_array[$id])) {
		$status = $relevanssi_post_array[$id]->post_status;
		if ('inherit' == $status) {
			$parent = $relevanssi_post_array[$id]->post_parent;
			$status = relevanssi_get_post_status($parent);
			if ($status == false) {
				// attachment without a parent
				// let's assume it's public
				$status = 'publish';
			}
		}
		return $status;
	}
	else {
		return get_post_status($id);
	}
}

function relevanssi_permalink($content, $link_post = NULL) {
	if ($link_post == NULL) {
		global $post;
		if (isset($post->link))
			$content = $post->link;
	}
	return $content;
}

function relevanssi_get_permalink() {
	$permalink = get_permalink();
	$permalink = apply_filters('relevanssi_permalink', get_permalink());
	return $permalink;
}

// do this to avoid making LOTS of calls to db
function relevanssi_get_post_types() {
	global $wpdb;
	$results = $wpdb->get_results("SELECT ID, post_type FROM $wpdb->posts");
	$post_types = array();
	foreach ($results as $result) {
		$post_types[$result->ID] = $result->post_type;
	}
	return $post_types;
}

function relevanssi_correct_query($q) {
	if (class_exists('SpellCorrector')) {
		$tokens = relevanssi_tokenize($q, false);
		$sc = new SpellCorrector();
		$correct = array();
		foreach ($tokens as $token => $count) {
			$c = $sc->correct($token);
			if ($c !== $token) array_push($correct, $c);
		}
		if (count($correct) > 0) $q = implode(' ', $correct);
	}
	return $q;
}

function relevanssi_search_multi($q, $search_blogs = NULL, $post_type) {
	global $relevanssi_table, $wpdb;

	$values_to_filter = array(
		'q' => $q,
		'post_type' => $post_type,
		'search_blogs' => $search_blogs,
		);
	$filtered_values = apply_filters( 'relevanssi_search_filters', $values_to_filter );
	$q               = $filtered_values['q'];
	$post_type       = $filtered_values['post_type'];
	$search_blogs    = $filtered_values['search_blogs'];

	$hits = array();
	
	$remove_stopwords = false;
	$terms = relevanssi_tokenize($q, $remove_stopwords);
	
	if (count($terms) < 1) {
		// Tokenizer killed all the search terms.
		return $hits;
	}
	$terms = array_keys($terms); // don't care about tf in query

	$total_hits = 0;
		
	$title_matches = array();
	$tag_matches = array();
	$link_matches = array();
	$comment_matches = array();
	$body_matches = array();
	$scores = array();
	$term_hits = array();
	$hitsbyweight = array();

	$operator = get_option('relevanssi_implicit_operator');
	$fuzzy = get_option('relevanssi_fuzzy');

	$query_restrictions = "";
	if ($post_type) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(ID) FROM $wpdb->posts
			WHERE post_type IN ($post_type))";
	}
	$query_restrictions = apply_filters('relevanssi_where', $query_restrictions); // Charles St-Pierre

	$search_blogs = explode(",", $search_blogs);
	$post_type_weights = get_option('relevanssi_post_type_weights');
	
	$orig_blog = $wpdb->blogid;
	foreach ($search_blogs as $blogid) {
		switch_to_blog($blogid);
		$relevanssi_table = $wpdb->prefix . "relevanssi";

		$D = $wpdb->get_var("SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table");
	
		$no_matches = true;
		if ("always" == $fuzzy) {
			$o_term_cond = "(term LIKE '%#term#' OR term LIKE '#term#%') ";
		}
		else {
			$o_term_cond = " term = '#term#' ";
		}
			
		$search_again = false;
		do {
			foreach ($terms as $term) {
				$term = $wpdb->escape(like_escape($term));
				$term_cond = str_replace('#term#', $term, $o_term_cond);		
	
				$query = "SELECT *, title + content + comment + tag + link + author + category + excerpt + taxonomy + customfield AS tf 
				FROM $relevanssi_table WHERE $term_cond $query_restrictions";
				$query = apply_filters('relevanssi_query_filter', $query);
			
				$matches = $wpdb->get_results($query);
				if (count($matches) < 1) {
					continue;
				}
				else {
					$no_matches = false;
				}
			
				$total_hits += count($matches);
	
				$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE $term_cond $query_restrictions";
				$query = apply_filters('relevanssi_df_query_filter', $query);
	
				$df = $wpdb->get_var($query);
	
				if ($df < 1 && "sometimes" == $fuzzy) {
					$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table
						WHERE (term LIKE '%$term' OR term LIKE '$term%') $query_restrictions";
					$query = apply_filters('relevanssi_df_query_filter', $query);
					$df = $wpdb->get_var($query);
				}
			
				$title_boost = floatval(get_option('relevanssi_title_boost'));
				isset($post_type_weights['post_tag']) ? $tag_boost = $post_type_weights['post_tag'] : 1;
				$link_boost = floatval(get_option('relevanssi_link_boost'));
				$comment_boost = floatval(get_option('relevanssi_comment_boost'));
			
				$doc_weight = array();
				$scores = array();
				$term_hits = array();
			
				$idf = log($D / (1 + $df));
				foreach ($matches as $match) {
					$match->tf =
						$match->title * $title_boost +
						$match->content +
						$match->comment * $comment_boost +
						$match->tag * $tag_boost +
						$match->link * $link_boost +
						$match->author +
						$match->category +
						$match->excerpt +
						$match->taxonomy +
						$match->customfield;

					$term_hits[$match->doc][$term] =
						$match->title +
						$match->content +
						$match->comment +
						$match->tag +
						$match->link +
						$match->author +
						$match->category +
						$match->excerpt +
						$match->taxonomy +
						$match->customfield;

					$match->weight = $match->tf * $idf;
	
					$match = apply_filters('relevanssi_match', $match);

					$doc_terms[$match->doc][$term] = true; // count how many terms are matched to a doc
					isset($doc_weight[$match->doc]) ?
						$doc_weight[$match->doc] += $match->weight :
						$doc_weight[$match->doc] = $match->weight;
					isset($scores[$match->doc]) ?
						$scores[$match->doc] += $match->weight :
						$scores[$match->doc] = $match->weight;

					$body_matches[$match->doc] = $match->content;
					$title_matches[$match->doc] = $match->title;
					$tag_matches[$match->doc] = $match->tag;
					$comment_matches[$match->doc] = $match->comment;
				}
			}

			if ($no_matches) {
				if ($search_again) {
					// no hits even with fuzzy search!
					$search_again = false;
				}
				else {
					if ("sometimes" == $fuzzy) {
						$search_again = true;
						$o_term_cond = "(term LIKE '%#term#' OR term LIKE '#term#%') ";
					}
				}
			}
			else {
				$search_again = false;
			}
		} while ($search_again);

		$strip_stops = true;
		$terms_without_stops = array_keys(relevanssi_tokenize(implode(' ', $terms), $strip_stops));
		$total_terms = count($terms_without_stops);
	
		if (isset($doc_weight) && count($doc_weight) > 0) {
			arsort($doc_weight);
			$i = 0;
			foreach ($doc_weight as $doc => $weight) {
				if (count($doc_terms[$doc]) < $total_terms && $operator == "AND") {
					// AND operator in action:
					// doc didn't match all terms, so it's discarded
					continue;
				}
				$status = get_post_status($doc);
				$post_ok = true;
				if ('private' == $status) {
					$post_ok = false;
	
					if (function_exists('awp_user_can')) {
						// Role-Scoper
						$current_user = wp_get_current_user();
						$post_ok = awp_user_can('read_post', $doc, $current_user->ID);
					}
					else {
						// Basic WordPress version
						$type = get_post_type($doc);
						$cap = 'read_private_' . $type . 's';
						if (current_user_can($cap)) {
							$post_ok = true;
						}
					}
				} else if ( 'publish' != $status ) {
					$post_ok = false;
				}
				if ($post_ok) {
					$post_object = get_blog_post($blogid, $doc);
					$post_object->blog_id = $blogid;

					$object_id = $blogid . '|' . $doc;
					$hitsbyweight[$object_id] = $weight;
					$post_objects[$object_id] = $post_object;
				}
			}
		}
	}
	switch_to_blog($orig_blog);
	
	arsort($hitsbyweight);
	$i = 0;
	foreach ($hitsbyweight as $hit => $weight) {
		$hit = $post_objects[$hit];
		$hits[intval($i++)] = $hit;
	}

	global $wp;	
	$default_order = get_option('relevanssi_default_orderby', 'relevance');
	isset($wp->query_vars["orderby"]) ? $orderby = $wp->query_vars["orderby"] : $orderby = $default_order;
	isset($wp->query_vars["order"]) ? $order = $wp->query_vars["order"] : $order = 'desc';
	if ($orderby != 'relevance')
		relevanssi_object_sort($hits, $orderby, $order);

	$return = array('hits' => $hits, 'body_matches' => $body_matches, 'title_matches' => $title_matches,
		'tag_matches' => $tag_matches, 'comment_matches' => $comment_matches, 'scores' => $scores,
		'term_hits' => $term_hits, 'query' => $q);

	return $return;
}

function relevanssi_recognize_negatives($q) {
	$term = strtok($q, " ");
	$negative_terms = array();
	while ($term !== false) {
		if (substr($term, 0, 1) == '-') array_push($negative_terms, substr($term, 1));
		$term = strtok(" ");
	}
	return $negative_terms;
}

/**
 * Extracts phrases from search query
 * Returns an array of phrases
 */
function relevanssi_extract_phrases($q) {
	if ( function_exists( 'mb_strpos' ) )
		$pos = mb_strpos($q, '"');
	else
		$pos = strpos($q, '"');

	$phrases = array();
	while ($pos !== false) {
		$start = $pos;
		if ( function_exists( 'mb_strpos' ) )
			$end = mb_strpos($q, '"', $start + 1);
		else
			$end = strpos($q, '"', $start + 1);
		
		if ($end === false) {
			// just one " in the query
			$pos = $end;
			continue;
		}
		if ( function_exists( 'mb_substr' ) )
			$phrase = mb_substr($q, $start + 1, $end - $start - 1);
		else
			$phrase = substr($q, $start + 1, $end - $start - 1);
		
		$phrases[] = $phrase;
		$pos = $end;
	}
	return $phrases;
}

/* If no phrase hits are made, this function returns false
 * If phrase matches are found, the function presents a comma-separated list of doc id's.
 * If phrase matches are found, but no matching documents, function returns -1.
 */
function relevanssi_recognize_phrases($q) {
	global $wpdb;
	
	$phrases = relevanssi_extract_phrases($q);
	
	if (count($phrases) > 0) {
		$phrase_matches = array();
		foreach ($phrases as $phrase) {
			$phrase = $wpdb->escape($phrase);
			$query = "SELECT ID,post_content,post_title FROM $wpdb->posts 
				WHERE (post_content LIKE '%$phrase%' OR post_title LIKE '%$phrase%')
				AND post_status = 'publish'";
			
			$docs = $wpdb->get_results($query);

			if (is_array($docs)) {
				foreach ($docs as $doc) {
					if (!isset($phrase_matches[$phrase])) {
						$phrase_matches[$phrase] = array();
					}
					$phrase_matches[$phrase][] = $doc->ID;
				}
			}

			$query = "SELECT ID FROM $wpdb->posts as p, $wpdb->term_relationships as r, $wpdb->term_taxonomy as s, $wpdb->terms as t
				WHERE r.term_taxonomy_id = s.term_taxonomy_id AND s.term_id = t.term_id AND p.ID = r.object_id
				AND t.name LIKE '%$phrase%' AND p.post_status = 'publish'";

			$docs = $wpdb->get_results($query);
			if (is_array($docs)) {
				foreach ($docs as $doc) {
					if (!isset($phrase_matches[$phrase])) {
						$phrase_matches[$phrase] = array();
					}
					$phrase_matches[$phrase][] = $doc->ID;
				}
			}

			$query = "SELECT ID
              FROM $wpdb->posts AS p, $wpdb->postmeta AS m
              WHERE p.ID = m.post_id
              AND m.meta_value LIKE '%$phrase%'
              AND p.post_status = 'publish'";

			$docs = $wpdb->get_results($query);
			if (is_array($docs)) {
				foreach ($docs as $doc) {
					if (!isset($phrase_matches[$phrase])) {
						$phrase_matches[$phrase] = array();
					}
					$phrase_matches[$phrase][] = $doc->ID;
				}
			}
		}
		
		if (count($phrase_matches) < 1) {
			$phrases = "-1";
		}
		else {
			// Complicated mess, but necessary...
			$i = 0;
			$phms = array();
			foreach ($phrase_matches as $phm) {
				$phms[$i++] = $phm;
			}
			
			$phrases = $phms[0];
			if ($i > 1) {
				for ($i = 1; $i < count($phms); $i++) {
					$phrases =  array_intersect($phrases, $phms[$i]);
				}
			}
			
			if (count($phrases) < 1) {
				$phrases = "-1";
			}
			else {
				$phrases = implode(",", $phrases);
			}
		}
	}
	else {
		$phrases = false;
	}
	
	return $phrases;
}

function relevanssi_the_excerpt() {
    global $post;
    if (!post_password_required($post)) {
	    echo "<p>" . $post->post_excerpt . "</p>";
	}
	else {
		echo __('There is no excerpt because this is a protected post.');
	}
}

function relevanssi_do_excerpt($t_post, $query) {
	global $post;
	$old_global_post = NULL;
	if ($post != NULL) $old_global_post = $post;
	$post = $t_post;
	
	$remove_stopwords = false;
	$terms = relevanssi_tokenize($query, $remove_stopwords);
	
	$content = apply_filters('relevanssi_pre_excerpt_content', $post->post_content, $post, $query);
	$content = apply_filters('the_content', $post->post_content);
	$content = apply_filters('relevanssi_excerpt_content', $content, $post, $query);
	
	$content = relevanssi_strip_invisibles($content); // removes <script>, <embed> &c with content
	$content = strip_tags($content); // this removes the tags, but leaves the content
	
	$content = preg_replace("/\n\r|\r\n|\n|\r/", " ", $content);
	$content = trim(preg_replace("/\s\s+/", " ", $content));
	
	$excerpt_data = relevanssi_create_excerpt($content, $terms);
	
	if (get_option("relevanssi_index_comments") != 'none') {
		$comment_content = relevanssi_get_comments($post->ID);
		$comment_excerpts = relevanssi_create_excerpt($comment_content, $terms);
		if ($comment_excerpts[1] > $excerpt_data[1]) {
			$excerpt_data = $comment_excerpts;
		}
	}

	if (get_option("relevanssi_index_excerpt") != 'none') {
		$excerpt_content = $post->post_excerpt;
		$excerpt_excerpts = relevanssi_create_excerpt($excerpt_content, $terms);
		if ($excerpt_excerpts[1] > $excerpt_data[1]) {
			$excerpt_data = $excerpt_excerpts;
		}
	}
	
	$start = $excerpt_data[2];

	$excerpt = $excerpt_data[0];	
	$excerpt = apply_filters('get_the_excerpt', $excerpt);
	$excerpt = trim($excerpt);

	$ellipsis = apply_filters('relevanssi_ellipsis', '...');

	$highlight = get_option('relevanssi_highlight');
	if ("none" != $highlight) {
		if (!is_admin()) {
			$excerpt = relevanssi_highlight_terms($excerpt, $query);
		}
	}
	
	if (!$start) {
		$excerpt = $ellipsis . $excerpt;
		// do not add three dots to the beginning of the post
	}
	
	$excerpt = $excerpt . $ellipsis;

	if (relevanssi_s2member_level($post->ID) == 1) $excerpt = $post->post_excerpt;

	if ($old_global_post != NULL) $post = $old_global_post;

	return $excerpt;
}

/**
 * Creates an excerpt from content.
 *
 * @return array - element 0 is the excerpt, element 1 the number of term hits, element 2 is
 * true, if the excerpt is from the start of the content.
 */
function relevanssi_create_excerpt($content, $terms) {
	// If you need to modify these on the go, use 'pre_option_relevanssi_excerpt_length' filter.
	$excerpt_length = get_option("relevanssi_excerpt_length");
	$type = get_option("relevanssi_excerpt_type");

	$best_excerpt_term_hits = -1;
	$excerpt = "";

	$content = " $content";	
	$start = false;
	if ("chars" == $type) {
		$term_hits = 0;
		foreach (array_keys($terms) as $term) {
			$term = " $term";
			if (function_exists('mb_stripos')) {
				$pos = ("" == $content) ? false : mb_stripos($content, $term);
			}
			else if (function_exists('mb_strpos')) {
				$pos = mb_strpos($content, $term);
				if (false === $pos) {
					$titlecased = mb_strtoupper(mb_substr($term, 0, 1)) . mb_substr($term, 1);
					$pos = mb_strpos($content, $titlecased);
					if (false === $pos) {
						$pos = mb_strpos($content, mb_strtoupper($term));
					}
				}
			}
			else {
				$pos = strpos($content, $term);
				if (false === $pos) {
					$titlecased = strtoupper(substr($term, 0, 1)) . substr($term, 1);
					$pos = strpos($content, $titlecased);
					if (false === $pos) {
						$pos = strpos($content, strtoupper($term));
					}
				}
			}
			
			if (false !== $pos) {
				$term_hits++;
				if ($term_hits > $best_excerpt_term_hits) {
					$best_excerpt_term_hits = $term_hits;
					if ($pos + strlen($term) < $excerpt_length) {
						if (function_exists('mb_substr'))
							$excerpt = mb_substr($content, 0, $excerpt_length);
						else
							$excerpt = substr($content, 0, $excerpt_length);
						$start = true;
					}
					else {
						$half = floor($excerpt_length/2);
						$pos = $pos - $half;
						if (function_exists('mb_substr'))
							$excerpt = mb_substr($content, $pos, $excerpt_length);
						else
							$excerpt = substr($content, $pos, $excerpt_length);
					}
				}
			}
		}
		
		if ("" == $excerpt) {
			if (function_exists('mb_substr'))
				$excerpt = mb_substr($content, 0, $excerpt_length);
			else
				$excerpt = substr($content, 0, $excerpt_length);
			$start = true;
		}
	}
	else {
		$words = explode(' ', $content);
		
		$i = 0;
		
		while ($i < count($words)) {
			if ($i + $excerpt_length > count($words)) {
				$i = count($words) - $excerpt_length;
			}
			$excerpt_slice = array_slice($words, $i, $excerpt_length);
			$excerpt_slice = implode(' ', $excerpt_slice);

			$excerpt_slice = " $excerpt_slice";
			$term_hits = 0;
			foreach (array_keys($terms) as $term) {
				$term = " $term";
				if (function_exists('mb_stripos')) {
					$pos = ("" == $excerpt_slice) ? false : mb_stripos($excerpt_slice, $term);
					// To avoid "empty haystack" warnings
				}
				else if (function_exists('mb_strpos')) {
					$pos = mb_strpos($excerpt_slice, $term);
					if (false === $pos) {
						$titlecased = mb_strtoupper(mb_substr($term, 0, 1)) . mb_substr($term, 1);
						$pos = mb_strpos($excerpt_slice, $titlecased);
						if (false === $pos) {
							$pos = mb_strpos($excerpt_slice, mb_strtoupper($term));
						}
					}
				}
				else {
					$pos = strpos($excerpt_slice, $term);
					if (false === $pos) {
						$titlecased = strtoupper(substr($term, 0, 1)) . substr($term, 1);
						$pos = strpos($excerpt_slice, $titlecased);
						if (false === $pos) {
							$pos = strpos($excerpt_slice, strtoupper($term));
						}
					}
				}
			
				if (false !== $pos) {
					$term_hits++;
					if (0 == $i) $start = true;
					if ($term_hits > $best_excerpt_term_hits) {
						$best_excerpt_term_hits = $term_hits;
						$excerpt = $excerpt_slice;
					}
				}
			}
			
			$i += $excerpt_length;
		}
		
		if ("" == $excerpt) {
			$excerpt = explode(' ', $content, $excerpt_length);
			array_pop($excerpt);
			$excerpt = implode(' ', $excerpt);
			$start = true;
		}
	}
	
	return array($excerpt, $best_excerpt_term_hits, $start);
}

// found here: http://forums.digitalpoint.com/showthread.php?t=1106745
function relevanssi_strip_invisibles($text) {
	$text = preg_replace(
		array(
			'@<style[^>]*?>.*?</style>@siu',
			'@<script[^>]*?.*?</script>@siu',
			'@<object[^>]*?.*?</object>@siu',
			'@<embed[^>]*?.*?</embed>@siu',
			'@<applet[^>]*?.*?</applet>@siu',
			'@<noscript[^>]*?.*?</noscript>@siu',
			'@<noembed[^>]*?.*?</noembed>@siu',
			'@<iframe[^>]*?.*?</iframe>@siu',
			'@<del[^>]*?.*?</del>@siu',
		),
		' ',
		$text );
	return $text;
}

function relevanssi_get_internal_links($text) {
	$links = array();
    if ( preg_match_all( '@<a[^>]*?href="(' . home_url() . '[^"]*?)"[^>]*?>(.*?)</a>@siu', $text, $m ) ) {
		foreach ( $m[1] as $i => $link ) {
			if ( !isset( $links[$link] ) )
				$links[$link] = '';
			$links[$link] .= ' ' . $m[2][$i];
		}
	}
    if ( preg_match_all( '@<a[^>]*?href="(/[^"]*?)"[^>]*?>(.*?)</a>@siu', $text, $m ) ) {
		foreach ( $m[1] as $i => $link ) {
			if ( !isset( $links[$link] ) )
				$links[$link] = '';
			$links[$link] .= ' ' . $m[2][$i];
		}
	}
	if (count($links) > 0)
		return $links;
	return false;
}

function relevanssi_strip_internal_links($text) {
	$text = preg_replace(
		array(
			'@<a[^>]*?href="' . home_url() . '[^>]*?>.*?</a>@siu',
		),
		' ',
		$text );
	$text = preg_replace(
		array(
			'@<a[^>]*?href="/[^>]*?>.*?</a>@siu',
		),
		' ',
		$text );
	return $text;
}

if (get_option('relevanssi_highlight_docs', 'off') != 'off') {
	add_filter('the_content', 'relevanssi_highlight_in_docs', 11);
	if (get_option('relevanssi_hilite_title', 'off') != 'off') {
		add_filter('the_title', 'relevanssi_highlight_in_docs');
	}
}
if (get_option('relevanssi_highlight_comments', 'off') != 'off') {
	add_filter('comment_text', 'relevanssi_highlight_in_docs', 11);
}
function relevanssi_highlight_in_docs($content) {
	if (is_singular()) {
		$referrer = preg_replace('@(http|https)://@', '', stripslashes(urldecode($_SERVER['HTTP_REFERER'])));
		$args     = explode('?', $referrer);
		$query    = array();
	
		if ( count( $args ) > 1 )
			parse_str( $args[1], $query );
	
		if (substr($referrer, 0, strlen($_SERVER['SERVER_NAME'])) == $_SERVER['SERVER_NAME'] && (isset($query['s']) || strpos($referrer, '/search/') !== false)) {
			// Local search
			$content = relevanssi_highlight_terms($content, $query['s']);
		}
		elseif (get_option('relevanssi_highlight_docs_external', 'off') != 'off') {
			if (strpos($referrer, 'google') !== false) {
				$content = relevanssi_highlight_terms($content, $query['q']);
			} elseif (strpos($referrer, 'bing') !== false) {
				$content = relevanssi_highlight_terms($content, $query['q']);
			} elseif (strpos($referrer, 'ask') !== false) {
				$content = relevanssi_highlight_terms($content, $query['q']);
			} elseif (strpos($referrer, 'aol') !== false) {
				$content = relevanssi_highlight_terms($content, $query['q']);
			} elseif (strpos($referrer, 'yahoo') !== false) {
				$content = relevanssi_highlight_terms($content, $query['p']);
			}
		}
	}
	
	return $content;
}

function relevanssi_highlight_terms($excerpt, $query) {
	$type = get_option("relevanssi_highlight");
	if ("none" == $type) {
		return $excerpt;
	}
	
	switch ($type) {
		case "mark":						// thanks to Jeff Byrnes
			$start_emp = "<mark>";
			$end_emp = "</mark>";
			break;
		case "strong":
			$start_emp = "<strong>";
			$end_emp = "</strong>";
			break;
		case "em":
			$start_emp = "<em>";
			$end_emp = "</em>";
			break;
		case "col":
			$col = get_option("relevanssi_txt_col");
			if (!$col) $col = "#ff0000";
			$start_emp = "<span style='color: $col'>";
			$end_emp = "</span>";
			break;
		case "bgcol":
			$col = get_option("relevanssi_bg_col");
			if (!$col) $col = "#ff0000";
			$start_emp = "<span style='background-color: $col'>";
			$end_emp = "</span>";
			break;
		case "css":
			$css = get_option("relevanssi_css");
			if (!$css) $css = "color: #ff0000";
			$start_emp = "<span style='$css'>";
			$end_emp = "</span>";
			break;
		case "class":
			$css = get_option("relevanssi_class");
			if (!$css) $css = "relevanssi-query-term";
			$start_emp = "<span class='$css'>";
			$end_emp = "</span>";
			break;
		default:
			return $excerpt;
	}
	
	$start_emp_token = "*[/";
	$end_emp_token = "\]*";

	if ( function_exists('mb_internal_encoding') )
		mb_internal_encoding("UTF-8");
	
	$terms = array_keys(relevanssi_tokenize($query, $remove_stopwords = true));

	$phrases = relevanssi_extract_phrases(stripslashes($query));
	
	$non_phrase_terms = array();
	foreach ($phrases as $phrase) {
		$phrase_terms = array_keys(relevanssi_tokenize($phrase, false));
		foreach ($terms as $term) {
			if (!in_array($term, $phrase_terms)) {
				$non_phrase_terms[] = $term;
			}
		}
		$terms = $non_phrase_terms;
		$terms[] = $phrase;
	}

	usort($terms, 'relevanssi_strlen_sort');

	get_option('relevanssi_word_boundaries', 'on') == 'on' ? $word_boundaries = true : $word_boundaries = false;
	foreach ($terms as $term) {
		if ($word_boundaries) {
			$pr_term = preg_quote($term);
			$excerpt = preg_replace("/(\b$pr_term|$pr_term\b)(?!([^<]+)?>)/iu", $start_emp_token . '\\1' . $end_emp_token, $excerpt);
		}
		else {
			$excerpt = preg_replace("/($pr_term)(?!([^<]+)?>)/iu", $start_emp_token . '\\1' . $end_emp_token, $excerpt);
		}
		// thanks to http://pureform.wordpress.com/2008/01/04/matching-a-word-characters-outside-of-html-tags/
	}
	
	$excerpt = relevanssi_remove_nested_highlights($excerpt, $start_emp_token, $end_emp_token);
	
	$excerpt = str_replace($start_emp_token, $start_emp, $excerpt);
	$excerpt = str_replace($end_emp_token, $end_emp, $excerpt);
	$excerpt = str_replace($end_emp . $start_emp, "", $excerpt);
	if (function_exists('mb_ereg_replace')) {
		$pattern = $end_emp . '\s*' . $start_emp;
		$excerpt = mb_ereg_replace($pattern, " ", $excerpt);
	}

	return $excerpt;
}

function relevanssi_remove_nested_highlights($s, $a, $b) {
	$offset = 0;
	$string = "";
	$bits = explode($a, $s);	
	$new_bits = array($bits[0]);
	$in = false;
	for ($i = 1; $i < count($bits); $i++) {
		if ($bits[$i] == '') continue;
		
		if (!$in) {
			array_push($new_bits, $a);
			$in = true;
		}
		if (substr_count($bits[$i], $b) > 0) {
			$in = false;
		}
		if (substr_count($bits[$i], $b) > 1) {
			$more_bits = explode($b, $bits[$i]);
			$j = 0;
			$k = count($more_bits) - 2;
			$whole_bit = "";
			foreach ($more_bits as $bit) {
				$whole_bit .= $bit;
				if ($j == $k) $whole_bit .= $b;
				$j++;
			}
			$bits[$i] = $whole_bit;
		}
		array_push($new_bits, $bits[$i]);
	}
	$whole = implode('', $new_bits);
	
	return $whole;
}

function relevanssi_strlen_sort($a, $b) {
	return strlen($b) - strlen($a);
}

function relevanssi_get_comments($postID) {	
	global $wpdb;

	$comtype = get_option("relevanssi_index_comments");
	$restriction = "";
	$comment_string = "";
	switch ($comtype) {
		case "all": 
			// all (incl. customs, track- & pingbacks)
			break;
		case "normal": 
			// normal (excl. customs, track- & pingbacks)
			$restriction=" AND comment_type='' ";
			break;
		default:
			// none (don't index)
			return "";
	}

	$to = 20;
	$from = 0;

	while ( true ) {
		$sql = "SELECT 	comment_content, comment_author
				FROM 	$wpdb->comments
				WHERE 	comment_post_ID = '$postID'
				AND 	comment_approved = '1' 
				".$restriction."
				LIMIT 	$from, $to";		
		$comments = $wpdb->get_results($sql);
		if (sizeof($comments) == 0) break;
		foreach($comments as $comment) {
			$comment_string .= $comment->comment_author . ' ' . $comment->comment_content . ' ';
		}
		$from += $to;
	}
	return $comment_string;
}

function relevanssi_index_users() {
	global $wpdb, $relevanssi_table;

	$wpdb->query("DELETE FROM $relevanssi_table WHERE type = 'user'");
	if (function_exists('get_users')) {
		$users_list = get_users();
	}
	else {
		$users_list = get_users_of_blog();
	}

	$users = array();
	foreach ($users_list as $user) {
		$users[] = get_userdata($user->ID);
	}

	$index_subscribers = get_option('relevanssi_index_subscribers');
	foreach ($users as $user) {
		if ($index_subscribers == 'off') {
			$cap = $wpdb->prefix . 'capabilities'; 
			$vars = get_object_vars($user);
			$subscriber = false;
			foreach ($vars[$cap] as $role => $val) {
				if ($role == 'subscriber') {
					$subscriber = true;
					break;
				}
			}
			if ($subscriber) continue;
		}

		$update = false;
		
		$index_this_user = apply_filters('relevanssi_user_index_ok', true, $user);
		if ($index_this_user)
			relevanssi_index_user($user, $update);
	}
}

function relevanssi_index_user($user, $remove_first = false) {
	global $wpdb, $relevanssi_table;
	
	if (is_numeric($user)) {
		$user = get_userdata($user);
	}
	
	if ($remove_first)
		relevanssi_delete_user($user->ID);

	$insert_data = array();
	$min_length = get_option('relevanssi_min_word_length', 3);
	
	$extra_fields = get_option('relevanssi_index_user_fields');
	if ($extra_fields) {
		$extra_fields = explode(',', $extra_fields);
		$user_vars = get_object_vars($user);
		foreach ($extra_fields as $field) {
			$tokens = relevanssi_tokenize($user_vars[$field], true, $min_length); // true = remove stopwords
			foreach($tokens as $term => $tf) {
				isset($insert_data[$term]['content']) ? $insert_data[$term]['content'] += $tf : $insert_data[$term]['content'] = $tf;
			}		
		}
	}
	
	if (isset($user->description) && $user->description != "") {
		$tokens = relevanssi_tokenize($user->description, true, $min_length); // true = remove stopwords
		foreach($tokens as $term => $tf) {
			isset($insert_data[$term]['content']) ? $insert_data[$term]['content'] += $tf : $insert_data[$term]['content'] = $tf;
		}
	}

	if (isset($user->first_name) && $user->first_name != "") {
		$parts = explode(" ", $user->first_name);
		foreach($parts as $part) {
			isset($insert_data[$part]['title']) ? $insert_data[$part]['title']++ : $insert_data[$part]['title'] = 1;
		}
	}

	if (isset($user->last_name) && $user->last_name != "") {
		$parts = explode(" ", $user->last_name);
		foreach($parts as $part) {
			isset($insert_data[$part]['title']) ? $insert_data[$part]['title']++ : $insert_data[$part]['title'] = 1;
		}
	}

	if (isset($user->display_name) && $user->display_name != "") {
		$parts = explode(" ", $user->display_name);
		foreach($parts as $part) {
			isset($insert_data[$part]['title']) ? $insert_data[$part]['title']++ : $insert_data[$part]['title'] = 1;
		}
	}

	foreach ($insert_data as $term => $data) {
		$content = 0;
		$title = 0;
		$comment = 0;
		$tag = 0;
		$link = 0;
		$author = 0;
		$category = 0;
		$excerpt = 0;
		$taxonomy = 0;
		$customfield = 0;
		extract($data);

		$query = $wpdb->prepare("INSERT INTO $relevanssi_table
			(item, doc, term, content, title, comment, tag, link, author, category, excerpt, taxonomy, customfield, type)
			VALUES (%d, %d, %s, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %s)",
			$user->ID, -1, $term, $content, $title, $comment, $tag, $link, $author, $category, $excerpt, $taxonomy, $customfield, 'user');
		$wpdb->query($query);
	}
}

function relevanssi_index_taxonomies() {
	global $wpdb, $relevanssi_table;

	$wpdb->query("DELETE FROM $relevanssi_table WHERE type = 'taxonomy'");
	
	$taxonomies = get_taxonomies();
	
	$taxonomies_to_index = get_option('relevanssi_taxonomies_to_index');
	if ($taxonomies_to_index == 'all')
		unset($taxonomies_to_index);

	if ($taxonomies_to_index == '')
		unset($taxonomies_to_index);
		
	if (isset($taxonomies_to_index))
		$taxonomies_to_index = explode(',', $taxonomies_to_index);

	foreach ($taxonomies as $taxonomy) {
		if (is_array($taxonomies_to_index)) {
			if (!in_array($taxonomy, $taxonomies_to_index)) continue;
		}
		$terms = get_terms($taxonomy);
		foreach ($terms as $term) {
			$update = false;
			relevanssi_index_taxonomy_term($term, $taxonomy, $update);
		}
	}
}

function relevanssi_index_taxonomy_term($term, $taxonomy, $remove_first = false) {
	global $wpdb, $relevanssi_table;
	
	if (is_numeric($term)) {
		$term = get_term($term, $taxonomy);
	}

	if ($remove_first)
		relevanssi_delete_taxonomy_term($term->term_id);

	$insert_data = array();
	
	$min_length = get_option('relevanssi_min_word_length', 3);
	if (isset($term->description) && $term->description != "") {
		$tokens = relevanssi_tokenize($term->description, true, $min_length); // true = remove stopwords
		foreach ($tokens as $t_term => $tf) {
			isset($insert_data[$t_term]['content']) ? $insert_data[$t_term]['content'] += $tf : $insert_data[$t_term]['content'] = $tf;
		}
	}

	if (isset($term->name) && $term->name != "") {
		$tokens = relevanssi_tokenize($term->name, true, $min_length); // true = remove stopwords
		foreach ($tokens as $t_term => $tf) {
			isset($insert_data[$t_term]['title']) ? $insert_data[$t_term]['title'] += $tf : $insert_data[$t_term]['title'] = $tf;
		}
	}

	foreach ($insert_data as $t_term => $data) {
		$content = 0;
		$title = 0;
		$comment = 0;
		$tag = 0;
		$link = 0;
		$author = 0;
		$category = 0;
		$excerpt = 0;
		$taxonomy = 0;
		$customfield = 0;
		extract($data);

		$query = $wpdb->prepare("INSERT INTO $relevanssi_table
			(item, doc, term, content, title, comment, tag, link, author, category, excerpt, taxonomy, customfield, type)
			VALUES (%d, %d, %s, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %s)",
			$term->term_id, -1, $t_term, $content, $title, $comment, $tag, $link, $author, $category, $excerpt, $taxonomy, $customfield, 'taxonomy');

		$wpdb->query($query);
	}
}

function relevanssi_build_index($extend = false) {
	global $wpdb, $relevanssi_table;
	set_time_limit(0);
	
	$post_types = array();
	$types = get_option("relevanssi_index_post_types");
	if (!is_array($types)) $types = array();
	foreach ($types as $type) {
		array_push($post_types, "'$type'");
	}
	
	if (count($post_types) > 0) {
		$restriction = " AND post.post_type IN (" . implode(', ', $post_types) . ') ';
	}
	else {
		$restriction = "";
	}

	$n = 0;
	$size = 0;
	
	if (!$extend) {
		// truncate table first
		$wpdb->query("TRUNCATE TABLE $relevanssi_table");

		if (get_option('relevanssi_index_taxonomies') == 'on') {
			relevanssi_index_taxonomies();
		}

		if (get_option('relevanssi_index_users') == 'on') {
			relevanssi_index_users();
		}

// BEGIN modified by renaissancehack
//  modified query to get child records that inherit their post_status
/*        $q = "SELECT *,parent.post_status as post_status
		FROM $wpdb->posts parent, $wpdb->posts post WHERE
        (parent.post_status='publish' OR parent.post_status='private')
        AND (
            (post.post_status='inherit'
            AND post.post_parent=parent.ID)
            OR
            (parent.ID=post.ID)
        )
		AND post.post_type!='nav_menu_item' AND post.post_type!='revision' $attachments $restriction $negative_restriction";*/
        $q = "SELECT post.ID
		FROM $wpdb->posts parent, $wpdb->posts post WHERE
        (parent.post_status IN ('publish', 'draft', 'private', 'pending', 'future'))
        AND (
            (post.post_status='inherit'
            AND post.post_parent=parent.ID)
            OR
            (parent.ID=post.ID)
        )
		$restriction";
		
		// END modified by renaissancehack
		update_option('relevanssi_index', '');
	}
	else {
		// extending, so no truncate and skip the posts already in the index
		$limit = get_option('relevanssi_index_limit', 200);
		if ($limit > 0) {
			$size = $limit;
			$limit = " LIMIT $limit";
		}
// BEGIN modified by renaissancehack
//  modified query to get child records that inherit their post_status
        $q = "SELECT post.ID
		FROM $wpdb->posts parent, $wpdb->posts post WHERE
        (parent.post_status IN ('publish', 'draft', 'private', 'pending', 'future'))
        AND (
            (post.post_status='inherit'
            AND post.post_parent=parent.ID)
            OR
            (parent.ID=post.ID)
        )
		AND post.ID NOT IN (SELECT DISTINCT(doc) FROM $relevanssi_table) $restriction $limit";
// END modified by renaissancehack
	}

	$custom_fields = relevanssi_get_custom_fields();

	$content = $wpdb->get_results($q);
	
	foreach ($content as $post) {
		$n += relevanssi_index_doc($post->ID, false, $custom_fields);
		// n calculates the number of insert queries
	}
	
    echo '<div id="message" class="updated fade"><p>'
		. __((($size == 0) || (count($content) < $size)) ? "Indexing complete!" : "More to index...", "relevanssi")
		. '</p></div>';
	update_option('relevanssi_indexed', 'done');
}

function relevanssi_remove_doc($id, $keep_internal_links = false) {
	global $wpdb, $relevanssi_table;

	$and = $keep_internal_links ? 'AND link = 0' : '';

	$q = "DELETE FROM $relevanssi_table WHERE doc = $id $and";
	$wpdb->query($q);
}

function relevanssi_remove_item($id, $type) {
	global $wpdb, $relevanssi_table;
	
	$q = "DELETE FROM $relevanssi_table WHERE item = $id AND type = '$type'";
	$wpdb->query($q);
}

// BEGIN modified by renaissancehack
//  recieve $post argument as $indexpost, so we can make it the $post global.  This will allow shortcodes
//  that need to know what post is calling them to access $post->ID
function relevanssi_index_doc($indexpost, $remove_first = false, $custom_fields = false, $bypassglobalpost = false) {
	global $wpdb, $relevanssi_table, $post;
	$post_was_null = false;
	$previous_post = NULL;

	if ($bypassglobalpost) {
		// if $bypassglobalpost is set, relevanssi_index_doc() will index the post object or post
		// ID as specified in $indexpost
		isset($post) ?
			$previous_post = $post : $post_was_null = true;
		is_object($indexpost) ?
			$post = $indexpost : $post = get_post($indexpost);
	}
	else {
		if (is_array($post)) {
			$post = get_post($post['ID']);
		}
		
		if (!isset($post)) {
			$post_was_null = true;
			if (is_object($indexpost)) {
				$post = $indexpost;
			}
			else {
				$post = get_post($indexpost);
			}
		}
		else {
			$previous_post = $post;
		}
	}
	
	if ($post == NULL) {
		if ($post_was_null) $post = null;
		if ($previous_post) $post = $previous_post;
		return;
	}
	
	$post = get_post($post->ID);

	$hide = get_post_meta($post->ID, '_relevanssi_hide_post', true);
	if ("on" == $hide) {
		if ($post_was_null) $post = null;
		if ($previous_post) $post = $previous_post;
		return;
	}	

	if (true == apply_filters('relevanssi_do_not_index', false, $post->ID)) {
		// filter says no
		if ($post_was_null) $post = null;
		if ($previous_post) $post = $previous_post;
		return;
	}

	$post->indexing_content = true;
	$index_types = get_option('relevanssi_index_post_types');
	if (!is_array($index_types)) $index_types = array();
	if (in_array($post->post_type, $index_types)) $index_this_post = true;

	if ($remove_first) {
		// we are updating a post, so remove the old stuff first
		relevanssi_remove_doc($post->ID,true);
		relevanssi_remove_item($post->ID,'post');
		relevanssi_purge_excerpt_cache($post->ID);
	}

	// This needs to be here, after the call to relevanssi_remove_doc(), because otherwise
	// a post that's in the index but shouldn't be there won't get removed. A remote chance,
	// I mean who ever flips exclude_from_search between true and false once it's set, but
	// I'd like to cover all bases.
	if (!$index_this_post) {
		if ($post_was_null) $post = null;
		if ($previous_post) $post = $previous_post;
		return;
	}

	$n = 0;	

	$min_word_length = get_option('relevanssi_min_word_length', 3);
	$insert_data = array();

	//Added by OdditY - INDEX COMMENTS of the POST ->
	if ("none" != get_option("relevanssi_index_comments")) {
		$pcoms = relevanssi_get_comments($post->ID);
		if ($pcoms != "") {
			$pcoms = relevanssi_strip_invisibles($pcoms);
			$pcoms = preg_replace('/<[a-zA-Z\/][^>]*>/', ' ', $pcoms);
			$pcoms = strip_tags($pcoms);
			$pcoms = relevanssi_tokenize($pcoms, true, $min_word_length);		
			if (count($pcoms) > 0) {
				foreach ($pcoms as $pcom => $count) {
					$n++;
					$insert_data[$pcom]['comment'] = $count;
				}
			}				
		}
	} //Added by OdditY END <-


	$taxonomies = array();
	//Added by OdditY - INDEX TAGs of the POST ->
	if ("on" == get_option("relevanssi_include_tags")) {
		array_push($taxonomies, "post_tag");
	} // Added by OdditY END <- 

	$custom_taxos = get_option("relevanssi_custom_taxonomies");
	if ("" != $custom_taxos) {
		$cts = explode(",", $custom_taxos);
		foreach ($cts as $taxon) {
			$taxon = trim($taxon);
			array_push($taxonomies, $taxon);
		}
	}

	// index categories
	if ("on" == get_option("relevanssi_include_cats")) {
		array_push($taxonomies, 'category');
	}

	// Then process all taxonomies, if any.
	foreach ($taxonomies as $taxonomy) {
		$insert_data = relevanssi_index_taxonomy_terms($post, $taxonomy, $insert_data);
	}
	
	// index author
	if ("on" == get_option("relevanssi_index_author")) {
		$auth = $post->post_author;
		$display_name = $wpdb->get_var("SELECT display_name FROM $wpdb->users WHERE ID=$auth");
		$names = relevanssi_tokenize($display_name, false, $min_word_length);
		foreach($names as $name => $count) {
			isset($insert_data[$name]['author']) ? $insert_data[$name]['author'] += $count : $insert_data[$name]['author'] = $count;
		}
	}

	if ($custom_fields) {
		$remove_underscore_fields = false;
		if ($custom_fields == 'all') 
			$custom_fields = get_post_custom_keys($post->ID);
		if ($custom_fields == 'visible') {
			$custom_fields = get_post_custom_keys($post->ID);
			$remove_underscore_fields = true;
		}
		foreach ($custom_fields as $field) {
			if ($remove_underscore_fields) {
				if (substr($field, 0, 1) == '_') continue;
			}
			$values = get_post_meta($post->ID, $field, false);
			if ("" == $values) continue;
			foreach ($values as $value) {
				$value_tokens = relevanssi_tokenize($value, true, $min_word_length);
				foreach ($value_tokens as $token => $count) {
					isset($insert_data[$token]['customfield']) ? $insert_data[$token]['customfield'] += $count : $insert_data[$token]['customfield'] = $count;
					isset($insert_data[$token]['customfield_detail']) ? $cfdetail = unserialize($insert_data[$token]['customfield_detail']) : $cfdetail = array();
					isset($cfdetail[$field]) ? $cfdetail[$field] += $count : $cfdetail[$field] = $count;
					$insert_data[$token]['customfield_detail'] = serialize($cfdetail);
				}
			}
		}
	}

	if (isset($post->post_excerpt) && ("on" == get_option("relevanssi_index_excerpt") || "attachment" == $post->post_type)) { // include excerpt for attachments which use post_excerpt for captions - modified by renaissancehack
		$excerpt_tokens = relevanssi_tokenize($post->post_excerpt, true, $min_word_length);
		foreach ($excerpt_tokens as $token => $count) {
			isset($insert_data[$token]['excerpt']) ? $insert_data[$token]['excerpt'] += $count : $insert_data[$token]['excerpt'] = $count;
		}
	}

	$custom_columns = get_option('relevanssi_mysql_columns');
	if (!empty($custom_columns)) {
		$custom_column_data = $wpdb->get_row("SELECT $custom_columns FROM $wpdb->posts WHERE ID=$post->ID", ARRAY_A);
		if (is_array($custom_column_data)) {
			foreach ($custom_column_data as $column => $data) {
				$data = relevanssi_tokenize($data);
				if (count($data) > 0) {
					foreach ($data as $term => $count) {
						isset($insert_data[$term]['mysqlcolumn']) ? $insert_data[$term]['mysqlcolumn'] += $count : $insert_data[$term]['mysqlcolumn'] = $count;
					}		
				}
			}
		}
	}
	

	$index_titles = true;
	if (apply_filters('relevanssi_index_titles', $index_titles)) {
		$titles = relevanssi_tokenize($post->post_title);

		if (count($titles) > 0) {
			foreach ($titles as $title => $count) {
				if (strlen($title) < 2) continue;
				$n++;
				isset($insert_data[$title]['title']) ? $insert_data[$title]['title'] += $count : $insert_data[$title]['title'] = $count;
			}
		}
	}
	
	$index_content = true;
	if (apply_filters('relevanssi_index_content', $index_content)) {
		remove_shortcode('noindex');
		add_shortcode('noindex', 'relevanssi_noindex_shortcode_indexing');

		$contents = $post->post_content;
		
		// Allow user to add extra content for Relevanssi to index
		// Thanks to Alexander Gieg
		$additional_content = trim(apply_filters('relevanssi_content_to_index', '', $post));
		if ('' != $additional_content)
			$contents .= ' '.$additional_content;		
			
		if ('on' == get_option('relevanssi_expand_shortcodes')) {
			if (function_exists("do_shortcode")) {
				$contents = do_shortcode($contents);
			}
		}
		else {
			if (function_exists("strip_shortcodes")) {
				// WP 2.5 doesn't have the function
				$contents = strip_shortcodes($contents);
			}
		}
		
		remove_shortcode('noindex');
		add_shortcode('noindex', 'relevanssi_noindex_shortcode');

		$contents = relevanssi_strip_invisibles($contents);
	
		$internal_links_behaviour = get_option('relevanssi_internal_links', 'noindex');
	
		if ($internal_links_behaviour != 'noindex') {
			// index internal links
			$internal_links = relevanssi_get_internal_links($contents);
			if ( !empty( $internal_links ) ) {
		
				foreach ( $internal_links as $link => $text ) {
					$link_id = url_to_postid( $link );
					if ( !empty( $link_id ) ) {
					$link_words = relevanssi_tokenize($text, true, $min_word_length);
						if ( count( $link_words > 0 ) ) {
							foreach ( $link_words as $word => $count ) {
								$n++;
								$wpdb->query("INSERT INTO $relevanssi_table (doc, term, link, item)
								VALUES ($link_id, '$word', $count, $post->ID)");
							}
						}
					}
				}
		
				if ('strip' == $internal_links_behaviour) 
					$contents = relevanssi_strip_internal_links($contents);
			}
		}

		$contents = preg_replace('/<[a-zA-Z\/][^>]*>/', ' ', $contents);
		$contents = strip_tags($contents);
		$contents = relevanssi_tokenize($contents, true, $min_word_length);
	
		if (count($contents) > 0) {
			foreach ($contents as $content => $count) {
		 		$n++;
				isset($insert_data[$content]['content']) ? $insert_data[$content]['content'] += $count : $insert_data[$content]['content'] = $count;
			}
		}
	}
	
	$type = 'post';
	if ($post->post_type == 'attachment') $type = 'attachment';
	
	$values = array();
	foreach ($insert_data as $term => $data) {
		$content = 0;
		$title = 0;
		$comment = 0;
		$tag = 0;
		$link = 0;
		$author = 0;
		$category = 0;
		$excerpt = 0;
		$taxonomy = 0;
		$customfield = 0;
		$taxonomy_detail = '';
		$customfield_detail = '';
		$mysqlcolumn = 0;
		extract($data);

		$value = $wpdb->prepare("(%d, %s, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s, %d)",
			$post->ID, $term, $content, $title, $comment, $tag, $link, $author, $category, $excerpt, $taxonomy, $customfield, $type, $taxonomy_detail, $customfield_detail, $mysqlcolumn);

		array_push($values, $value);
	}
	
	if (!empty($values)) {
		$values = implode(', ', $values);
		$query = "INSERT IGNORE INTO $relevanssi_table (doc, term, content, title, comment, tag, link, author, category, excerpt, taxonomy, customfield, type, taxonomy_detail, customfield_detail, mysqlcolumn)
			VALUES $values";
		$wpdb->query($query);
	}

	if ($post_was_null) $post = null;
	if ($previous_post) $post = $previous_post;

	return $n;
}

/**
 * Index taxonomy terms for given post and given taxonomy.
 *
 * @since 1.8
 * @param object $post Post object.
 * @param string $taxonomy Taxonomy name.
 * @param array $insert_data Insert query data array.
 * @return array Updated insert query data array.
 */
function relevanssi_index_taxonomy_terms($post = null, $taxonomy = "", $insert_data) {
	global $wpdb, $relevanssi_table;
	
	$n = 0;

	if (null == $post) return 0;
	if ("" == $taxonomy) return 0;
	
	$min_word_length = get_option('relevanssi_min_word_length', 3);
	$ptagobj = get_the_terms($post->ID, $taxonomy);
	if ($ptagobj !== FALSE) { 
		$tagstr = "";
		foreach ($ptagobj as $ptag) {
			if (is_object($ptag)) {
				$tagstr .= $ptag->name . ' ';
			}
		}		
		$tagstr = trim($tagstr);
		$ptags = relevanssi_tokenize($tagstr, true, $min_word_length);		
		if (count($ptags) > 0) {
			foreach ($ptags as $ptag => $count) {
				$n++;
				
				if ('post_tags' == $taxonomy) {
					$insert_data[$ptag]['tag'] = $count;
				}
				else if ('category' == $taxonomy) {
					$insert_data[$ptag]['category'] = $count;
				}
				else {
					if (isset($insert_data[$ptag]['taxonomy'])) {
						$insert_data[$ptag]['taxonomy'] += $count;
					}
					else {
						$insert_data[$ptag]['taxonomy'] = $count;
					}
				}
				if (isset($insert_data[$ptag]['taxonomy_detail'])) {
					$tax_detail = unserialize($insert_data[$ptag]['taxonomy_detail']);
				}
				else {
					$tax_detail = array();
				}
				if (isset($tax_detail[$taxonomy])) {
					$tax_detail[$taxonomy] += $count;
				}
				else {
					$tax_detail[$taxonomy] = $count;
				}
				$insert_data[$ptag]['taxonomy_detail'] = serialize($tax_detail);
			}
		}	
	}
	return $insert_data;
}

function relevanssi_get_custom_fields() {
	$custom_fields = get_option("relevanssi_index_fields");
	if ($custom_fields) {
		if ($custom_fields != 'all') {
			$custom_fields = explode(",", $custom_fields);
			for ($i = 0; $i < count($custom_fields); $i++) {
				$custom_fields[$i] = trim($custom_fields[$i]);
			}
		}
	}
	else {
		$custom_fields = false;
	}
	return $custom_fields;
}

function relevanssi_tokenize($str, $remove_stops = true, $min_word_length = -1) {
	$tokens = array();
	if (is_array($str)) {
		foreach ($str as $part) {
			$tokens = array_merge($tokens, relevanssi_tokenize($part, $remove_stops, $min_word_length));
		}
	}
	if (is_array($str)) return $tokens;
	
	if ( function_exists('mb_internal_encoding') )
		mb_internal_encoding("UTF-8");

	if ($remove_stops) {
		$stopword_list = relevanssi_fetch_stopwords();
	}
	
	$thousandsep = get_option('relevanssi_thousand_separator', '');
	if (!empty($thousandsep)) {
		$pattern = "/(\d+)" . $thousandsep . "(\d+)/u";
		$str = preg_replace($pattern, "$1$2", $str);
	}

	$str = apply_filters('relevanssi_remove_punctuation', $str);

	if ( function_exists('mb_strtolower') )
		$str = mb_strtolower($str);
	else
		$str = strtolower($str);
	
	$t = strtok($str, "\n\t ");
	while ($t !== false) {
		$accept = true;
		if (strlen($t) < $min_word_length) {
			$t = strtok("\n\t  ");
			continue;
		}
		if ($remove_stops == false) {
			$accept = true;
		}
		else {
			if (count($stopword_list) > 0) {	//added by OdditY -> got warning when stopwords table was empty
				if (in_array($t, $stopword_list)) {
					$accept = false;
				}
			}
		}

		$t = apply_filters('relevanssi_stemmer', $t);
		
		if ($accept) {
			$t = relevanssi_mb_trim($t);
			if (!isset($tokens[$t])) {
				$tokens[$t] = 1;
			}
			else {
				$tokens[$t]++;
			}
		}
		
		$t = strtok("\n\t ");
	}

	return $tokens;
}

function relevanssi_simple_english_stemmer($term) {
	$len = strlen($term);

	$end1 = substr($term, -1, 1);
	if ("s" == $end1 && $len > 3) {
		$term = substr($term, 0, -1);
	}
	$end = substr($term, -3, 3);

	if ("ing" == $end && $len > 5) {
		return substr($term, 0, -3);
	}
	if ("est" == $end && $len > 5) {
		return substr($term, 0, -3);
	}
	
	$end = substr($end, 1);
	if ("ed" == $end && $len > 3) {
		return substr($term, 0, -2);
	}
	if ("en" == $end && $len > 3) {
		return substr($term, 0, -2);
	}
	if ("er" == $end && $len > 3) {
		return substr($term, 0, -2);
	}
	if ("ly" == $end && $len > 4) {
		return substr($term, 0, -2);
	}

	return $term;
}

function relevanssi_mb_trim($string) { 
	$string = str_replace(chr(194) . chr(160), '', $string);
    $string = preg_replace( "/(^\s+)|(\s+$)/us", "", $string ); 
    return $string; 
} 

function relevanssi_remove_punct($a) {
		$a = strip_tags($a);
		$a = stripslashes($a);

		$a = str_replace("’", '', $a);
		$a = str_replace("‘", '', $a);
		$a = str_replace("„", '', $a);
		$a = str_replace("·", '', $a);
		$a = str_replace("”", '', $a);
		$a = str_replace("“", '', $a);
		$a = str_replace("…", '', $a);
		$a = str_replace("€", '', $a);
		$a = str_replace("&shy;", '', $a);

		$a = str_replace('&#8217;', ' ', $a);
		$a = str_replace("'", ' ', $a);
		$a = str_replace("´", ' ', $a);
		$a = str_replace("—", ' ', $a);
		$a = str_replace("–", ' ', $a);
		$a = str_replace("×", ' ', $a);
        $a = preg_replace('/[[:punct:]]+/u', ' ', $a);

        $a = preg_replace('/[[:space:]]+/', ' ', $a);
		$a = trim($a);

        return $a;
}

function relevanssi_shortcode($atts, $content, $name) {
	global $wpdb;

	extract(shortcode_atts(array('term' => false, 'phrase' => 'not'), $atts));
	
	if ($term != false) {
		$term = urlencode(strtolower($term));
	}
	else {
		$term = urlencode(strip_tags(strtolower($content)));
	}
	
	if ($phrase != 'not') {
		$term = '%22' . $term . '%22';	
	}
	
	$link = get_bloginfo('url') . "/?s=$term";
	
	$pre  = "<a href='$link'>";
	$post = "</a>";

	return $pre . do_shortcode($content) . $post;
}

add_shortcode('search', 'relevanssi_shortcode');

add_shortcode('noindex', 'relevanssi_noindex_shortcode');
function relevanssi_noindex_shortcode($atts, $content) {
	// When in general use, make the shortcode disappear.
	return $content;
}

function relevanssi_noindex_shortcode_indexing($atts, $content) {
	// When indexing, make the text disappear.
	return '';
}

/*
 * Example:
 * 
 * relevanssi_related(get_search_query(), '<h3>Related Searches:</h3><ul><li>', '</li><li>', '</li></ul>');
 * 
 * Function written by John Blackbourn.
 */

function relevanssi_related($query, $pre = '<ul><li>', $sep = '</li><li>', $post = '</li></ul>', $number = 5) {
	global $wpdb, $relevanssi_log_table, $wp_query;
	$output = $related = array();
	$tokens = relevanssi_tokenize($query);
	if (empty($tokens))
		return;
	/* Loop over each token in the query and return logged queries which:
	 *
	 *  - Contain a matching token
	 *  - Don't match the query or the token exactly
	 *  - Have at least 2 hits
	 *  - Have been queried at least twice
	 *
	 * then order by most queried with a max of $number results.
	 */
	foreach ($tokens as $token => $count) {
		$sql = $wpdb->prepare("
			SELECT query
			FROM $relevanssi_log_table
			WHERE query LIKE '%%%s%%'
			AND query NOT IN (%s, %s)
			AND hits > 1
			GROUP BY query
			HAVING count(query) > 1
			ORDER BY count(query) DESC
			LIMIT %d
		", $token, $token, $query, $number);
		foreach ($wpdb->get_results($sql) as $result)
			$related[] = $result->query;
	}
	if (empty($related))
		return;
	/* Order results by most matching tokens
	 * then slice to a maximum of $number results:
	 */
	$related = array_keys(array_count_values($related));
	$related = array_slice($related, 0, $number);
	foreach ($related as $rel) {
		$url = add_query_arg(array(
			's' => urlencode($rel)
		), home_url());
		$rel = esc_attr($rel);
		$output[] = "<a href='$url'>$rel</a>";
	}
	echo $pre;
	echo implode($sep, $output);
	echo $post;
}

/*****
 * Interface functions
 */

function relevanssi_options() {
	$options_txt = __('Relevanssi Premium Search Options', 'relevanssi');

	printf("<div class='wrap'><h2>%s</h2>", $options_txt);
	if (!empty($_POST)) {
		if (isset($_REQUEST['submit'])) {
			check_admin_referer(plugin_basename(__FILE__), 'relevanssi_options');
			update_relevanssi_options();
		}
	
		if (isset($_REQUEST['index'])) {
			check_admin_referer(plugin_basename(__FILE__), 'relevanssi_options');
			update_relevanssi_options();
			relevanssi_build_index();
		}
	
		if (isset($_REQUEST['index_extend'])) {
			check_admin_referer(plugin_basename(__FILE__), 'relevanssi_options');
			update_relevanssi_options();
			relevanssi_build_index(true);
		}

		if (isset($_REQUEST['import_options'])) {
			check_admin_referer(plugin_basename(__FILE__), 'relevanssi_options');
			$options = $_REQUEST['relevanssi_settings'];
			relevanssi_import_options($options);
		}
		
		if (isset($_REQUEST['search'])) {
			relevanssi_search($_REQUEST['q']);
		}
		
		if (isset($_REQUEST['dowhat'])) {
			if ("add_stopword" == $_REQUEST['dowhat']) {
				if (isset($_REQUEST['term'])) {
					check_admin_referer(plugin_basename(__FILE__), 'relevanssi_options');
					relevanssi_add_stopword($_REQUEST['term']);
				}
			}
		}
	
		if (isset($_REQUEST['addstopword'])) {
			check_admin_referer(plugin_basename(__FILE__), 'relevanssi_options');
			relevanssi_add_stopword($_REQUEST['addstopword']);
		}
		
		if (isset($_REQUEST['removestopword'])) {
			check_admin_referer(plugin_basename(__FILE__), 'relevanssi_options');
			relevanssi_remove_stopword($_REQUEST['removestopword']);
		}
	
		if (isset($_REQUEST['removeallstopwords'])) {
			check_admin_referer(plugin_basename(__FILE__), 'relevanssi_options');
			relevanssi_remove_all_stopwords();
		}

		if (isset($_REQUEST['truncate'])) {
			check_admin_referer(plugin_basename(__FILE__), 'relevanssi_options');
			$clear_all = true;
			relevanssi_truncate_cache($clear_all);
		}
	}
	relevanssi_options_form();
	
	relevanssi_common_words();
	
	echo "<div style='clear:both'></div>";
	
	echo "</div>";
}

function relevanssi_import_options($options) {
	$unserialized = unserialize(stripslashes($options));
	foreach ($unserialized as $key => $value) {
		update_option($key, $value);
	}
	
	echo "<div id='relevanssi-warning' class='updated fade'>" . __("Options updated!", "relevanssi") . "</div>";
}

function relevanssi_search_stats() {

	$relevanssi_hide_branding = get_option( 'relevanssi_hide_branding' );

	if ( 'on' == $relevanssi_hide_branding )
		$options_txt = __('User Searches', 'relevanssi');
	else
		$options_txt = __('Relevanssi User Searches', 'relevanssi');

	if (isset($_REQUEST['relevanssi_reset']) and current_user_can('manage_options')) {
		check_admin_referer('relevanssi_reset_logs', '_relresnonce');
		if (isset($_REQUEST['relevanssi_reset_code'])) {
			if ($_REQUEST['relevanssi_reset_code'] == 'reset') {
				relevanssi_truncate_logs();
			}
		}
	}

	wp_enqueue_style('dashboard');
	wp_print_styles('dashboard');
	wp_enqueue_script('dashboard');
	wp_print_scripts('dashboard');

	printf("<div class='wrap'><h2>%s</h2>", $options_txt);

	if ( 'on' == $relevanssi_hide_branding )
		echo '<div class="postbox-container">';
	else
		echo '<div class="postbox-container" style="width:70%;">';


	if ('on' == get_option('relevanssi_log_queries')) {
		relevanssi_query_log();
	}
	else {
		echo "<p>Enable query logging to see stats here.</p>";
	}
	
	echo "</div>";
	
	if ('on' != $relevanssi_hide_branding )
		relevanssi_sidebar();
}

function relevanssi_truncate_logs() {
	global $wpdb, $relevanssi_log_table;
	
	$query = "TRUNCATE $relevanssi_log_table";
	$wpdb->query($query);
	
	echo "<div id='relevanssi-warning' class='updated fade'>Logs clear!</div>";
}

function update_relevanssi_options() {
	if (isset($_REQUEST['relevanssi_title_boost'])) {
		$boost = floatval($_REQUEST['relevanssi_title_boost']);
		update_option('relevanssi_title_boost', $boost);
	}

	if (isset($_REQUEST['relevanssi_link_boost'])) {
		$boost = floatval($_REQUEST['relevanssi_link_boost']);
		update_option('relevanssi_link_boost', $boost);
	}

	if (isset($_REQUEST['relevanssi_comment_boost'])) {
		$boost = floatval($_REQUEST['relevanssi_comment_boost']);
		update_option('relevanssi_comment_boost', $boost);
	}

	if (isset($_REQUEST['relevanssi_min_word_length'])) {
		$value = intval($_REQUEST['relevanssi_min_word_length']);
		if ($value == 0) $value = 3;
		update_option('relevanssi_min_word_length', $value);
	}

	if (isset($_REQUEST['relevanssi_cache_seconds'])) {
		$value = intval($_REQUEST['relevanssi_cache_seconds']);
		if ($value == 0) $value = 86400;
		update_option('relevanssi_cache_seconds', $value);
	}
	
	if (!isset($_REQUEST['relevanssi_admin_search'])) {
		$_REQUEST['relevanssi_admin_search'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_excerpts'])) {
		$_REQUEST['relevanssi_excerpts'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_show_matches'])) {
		$_REQUEST['relevanssi_show_matches'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_inccats'])) {
		$_REQUEST['relevanssi_inccats'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_inctags'])) {
		$_REQUEST['relevanssi_inctags'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_throttle'])) {
		$_REQUEST['relevanssi_throttle'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_index_author'])) {
		$_REQUEST['relevanssi_index_author'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_index_excerpt'])) {
		$_REQUEST['relevanssi_index_excerpt'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_log_queries'])) {
		$_REQUEST['relevanssi_log_queries'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_disable_or_fallback'])) {
		$_REQUEST['relevanssi_disable_or_fallback'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_hilite_title'])) {
		$_REQUEST['relevanssi_hilite_title'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_highlight_docs'])) {
		$_REQUEST['relevanssi_highlight_docs'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_highlight_docs_external'])) {
		$_REQUEST['relevanssi_highlight_docs_external'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_highlight_comments'])) {
		$_REQUEST['relevanssi_highlight_comments'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_expand_shortcodes'])) {
		$_REQUEST['relevanssi_expand_shortcodes'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_enable_cache'])) {
		$_REQUEST['relevanssi_enable_cache'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_respect_exclude'])) {
		$_REQUEST['relevanssi_respect_exclude'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_wpml_only_current'])) {
		$_REQUEST['relevanssi_wpml_only_current'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_index_subscribers'])) {
		$_REQUEST['relevanssi_index_subscribers'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_index_users'])) {
		$_REQUEST['relevanssi_index_users'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_index_taxonomies'])) {
		$_REQUEST['relevanssi_index_taxonomies'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_word_boundaries'])) {
		$_REQUEST['relevanssi_word_boundaries'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_hide_branding'])) {
		$_REQUEST['relevanssi_hide_branding'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_hide_post_controls'])) {
		$_REQUEST['relevanssi_hide_post_controls'] = "off";
	}

	if (isset($_REQUEST['relevanssi_excerpt_length'])) {
		$value = intval($_REQUEST['relevanssi_excerpt_length']);
		if ($value != 0) {
			update_option('relevanssi_excerpt_length', $value);
		}
	}
	
	if (isset($_REQUEST['relevanssi_synonyms'])) {
		$linefeeds = array("\r\n", "\n", "\r");
		$value = str_replace($linefeeds, ";", $_REQUEST['relevanssi_synonyms']);
		$value = stripslashes($value);
		update_option('relevanssi_synonyms', $value);
	}

	if (isset($_REQUEST['relevanssi_show_matches'])) update_option('relevanssi_show_matches', $_REQUEST['relevanssi_show_matches']);
	if (isset($_REQUEST['relevanssi_show_matches_text'])) {
		$value = $_REQUEST['relevanssi_show_matches_text'];
		$value = str_replace('"', "'", $value);
		update_option('relevanssi_show_matches_text', $value);
	}

	$post_type_weights = array();
	$index_post_types = array();
	foreach ($_REQUEST as $key => $value) {
		if (substr($key, 0, strlen('relevanssi_weight_')) == 'relevanssi_weight_') {
			$type = substr($key, strlen('relevanssi_weight_'));
			$post_type_weights[$type] = $value;
		}
		if (substr($key, 0, strlen('relevanssi_index_type_')) == 'relevanssi_index_type_') {
			$type = substr($key, strlen('relevanssi_index_type_'));
			if ('on' == $value) $index_post_types[$type] = true;
		}
	}
	
	if (count($post_type_weights) > 0) {
		update_option('relevanssi_post_type_weights', $post_type_weights);
	}

	if (count($index_post_types) > 0) {
		update_option('relevanssi_index_post_types', array_keys($index_post_types));
	}

	if (isset($_REQUEST['relevanssi_recency_bonus']) && isset($_REQUEST['relevanssi_recency_days'])) {
		$relevanssi_recency_bonus = array();
		$relevanssi_recency_bonus['bonus'] = $_REQUEST['relevanssi_recency_bonus'];
		$relevanssi_recency_bonus['days'] = $_REQUEST['relevanssi_recency_days'];
		update_option('relevanssi_recency_bonus', $relevanssi_recency_bonus);
	}

	if (isset($_REQUEST['relevanssi_api_key'])) update_option('relevanssi_api_key', $_REQUEST['relevanssi_api_key']);
	if (isset($_REQUEST['relevanssi_admin_search'])) update_option('relevanssi_admin_search', $_REQUEST['relevanssi_admin_search']);
	if (isset($_REQUEST['relevanssi_excerpts'])) update_option('relevanssi_excerpts', $_REQUEST['relevanssi_excerpts']);	
	if (isset($_REQUEST['relevanssi_excerpt_type'])) update_option('relevanssi_excerpt_type', $_REQUEST['relevanssi_excerpt_type']);	
	if (isset($_REQUEST['relevanssi_log_queries'])) update_option('relevanssi_log_queries', $_REQUEST['relevanssi_log_queries']);	
	if (isset($_REQUEST['relevanssi_highlight'])) update_option('relevanssi_highlight', $_REQUEST['relevanssi_highlight']);
	if (isset($_REQUEST['relevanssi_highlight_docs'])) update_option('relevanssi_highlight_docs', $_REQUEST['relevanssi_highlight_docs']);
	if (isset($_REQUEST['relevanssi_highlight_docs_external'])) update_option('relevanssi_highlight_docs_external', $_REQUEST['relevanssi_highlight_docs_external']);
	if (isset($_REQUEST['relevanssi_highlight_comments'])) update_option('relevanssi_highlight_comments', $_REQUEST['relevanssi_highlight_comments']);
	if (isset($_REQUEST['relevanssi_txt_col'])) update_option('relevanssi_txt_col', $_REQUEST['relevanssi_txt_col']);
	if (isset($_REQUEST['relevanssi_bg_col'])) update_option('relevanssi_bg_col', $_REQUEST['relevanssi_bg_col']);
	if (isset($_REQUEST['relevanssi_css'])) update_option('relevanssi_css', $_REQUEST['relevanssi_css']);
	if (isset($_REQUEST['relevanssi_class'])) update_option('relevanssi_class', $_REQUEST['relevanssi_class']);
	if (isset($_REQUEST['relevanssi_cat'])) update_option('relevanssi_cat', $_REQUEST['relevanssi_cat']);
	if (isset($_REQUEST['relevanssi_excat'])) update_option('relevanssi_excat', $_REQUEST['relevanssi_excat']);
	if (isset($_REQUEST['relevanssi_custom_taxonomies'])) update_option('relevanssi_custom_taxonomies', $_REQUEST['relevanssi_custom_taxonomies']);
	if (isset($_REQUEST['relevanssi_index_fields'])) update_option('relevanssi_index_fields', $_REQUEST['relevanssi_index_fields']);
	if (isset($_REQUEST['relevanssi_expst'])) update_option('relevanssi_exclude_posts', $_REQUEST['relevanssi_expst']); 			//added by OdditY
	if (isset($_REQUEST['relevanssi_inctags'])) update_option('relevanssi_include_tags', $_REQUEST['relevanssi_inctags']); 			//added by OdditY	
	if (isset($_REQUEST['relevanssi_hilite_title'])) update_option('relevanssi_hilite_title', $_REQUEST['relevanssi_hilite_title']); 	//added by OdditY	
	if (isset($_REQUEST['relevanssi_index_comments'])) update_option('relevanssi_index_comments', $_REQUEST['relevanssi_index_comments']); //added by OdditY	
	if (isset($_REQUEST['relevanssi_inccats'])) update_option('relevanssi_include_cats', $_REQUEST['relevanssi_inccats']);
	if (isset($_REQUEST['relevanssi_index_author'])) update_option('relevanssi_index_author', $_REQUEST['relevanssi_index_author']);
	if (isset($_REQUEST['relevanssi_index_excerpt'])) update_option('relevanssi_index_excerpt', $_REQUEST['relevanssi_index_excerpt']);
	if (isset($_REQUEST['relevanssi_fuzzy'])) update_option('relevanssi_fuzzy', $_REQUEST['relevanssi_fuzzy']);
	if (isset($_REQUEST['relevanssi_expand_shortcodes'])) update_option('relevanssi_expand_shortcodes', $_REQUEST['relevanssi_expand_shortcodes']);
	if (isset($_REQUEST['relevanssi_implicit_operator'])) update_option('relevanssi_implicit_operator', $_REQUEST['relevanssi_implicit_operator']);
	if (isset($_REQUEST['relevanssi_omit_from_logs'])) update_option('relevanssi_omit_from_logs', $_REQUEST['relevanssi_omit_from_logs']);
	if (isset($_REQUEST['relevanssi_index_limit'])) update_option('relevanssi_index_limit', $_REQUEST['relevanssi_index_limit']);
	if (isset($_REQUEST['relevanssi_disable_or_fallback'])) update_option('relevanssi_disable_or_fallback', $_REQUEST['relevanssi_disable_or_fallback']);
	if (isset($_REQUEST['relevanssi_respect_exclude'])) update_option('relevanssi_respect_exclude', $_REQUEST['relevanssi_respect_exclude']);
	if (isset($_REQUEST['relevanssi_enable_cache'])) update_option('relevanssi_enable_cache', $_REQUEST['relevanssi_enable_cache']);
	if (isset($_REQUEST['relevanssi_throttle'])) update_option('relevanssi_throttle', $_REQUEST['relevanssi_throttle']);
	if (isset($_REQUEST['relevanssi_wpml_only_current'])) update_option('relevanssi_wpml_only_current', $_REQUEST['relevanssi_wpml_only_current']);
	if (isset($_REQUEST['relevanssi_index_users'])) update_option('relevanssi_index_users', $_REQUEST['relevanssi_index_users']);
	if (isset($_REQUEST['relevanssi_index_subscribers'])) update_option('relevanssi_index_subscribers', $_REQUEST['relevanssi_index_subscribers']);
	if (isset($_REQUEST['relevanssi_index_user_fields'])) update_option('relevanssi_index_user_fields', $_REQUEST['relevanssi_index_user_fields']);
	if (isset($_REQUEST['relevanssi_internal_links'])) update_option('relevanssi_internal_links', $_REQUEST['relevanssi_internal_links']);
	if (isset($_REQUEST['relevanssi_word_boundaries'])) update_option('relevanssi_word_boundaries', $_REQUEST['relevanssi_word_boundaries']);
	if (isset($_REQUEST['relevanssi_hide_branding'])) update_option('relevanssi_hide_branding', $_REQUEST['relevanssi_hide_branding']);
	if (isset($_REQUEST['relevanssi_hide_post_controls'])) update_option('relevanssi_hide_post_controls', $_REQUEST['relevanssi_hide_post_controls']);
	if (isset($_REQUEST['relevanssi_index_taxonomies'])) update_option('relevanssi_index_taxonomies', $_REQUEST['relevanssi_index_taxonomies']);
	if (isset($_REQUEST['relevanssi_taxonomies_to_index'])) update_option('relevanssi_taxonomies_to_index', $_REQUEST['relevanssi_taxonomies_to_index']);
	if (isset($_REQUEST['relevanssi_default_orderby'])) update_option('relevanssi_default_orderby', $_REQUEST['relevanssi_default_orderby']);
	if (isset($_REQUEST['relevanssi_thousand_separator'])) update_option('relevanssi_thousand_separator', $_REQUEST['relevanssi_thousand_separator']);
	if (isset($_REQUEST['relevanssi_mysql_columns'])) update_option('relevanssi_mysql_columns', $_REQUEST['relevanssi_mysql_columns']);
}

function relevanssi_add_stopword($term) {
	global $wpdb, $relevanssi_table, $relevanssi_stopword_table;
	if ('' == $term) return; // do not add empty $term to stopwords - added by renaissancehack
	
	$n = 0;
	$s = 0;
	
	$terms = explode(',', $term);
	if (count($terms) > 1) {
		foreach($terms as $term) {
			$n++;
			$term = trim($term);
			$success = relevanssi_add_single_stopword($term);
			if ($success) $s++;
		}
		printf(__("<div id='message' class='updated fade'><p>Successfully added %d/%d terms to stopwords!</p></div>", "relevanssi"), $s, $n);
	}
	else {
		// add to stopwords
		$success = relevanssi_add_single_stopword($term);
		
		if ($success) {
			printf(__("<div id='message' class='updated fade'><p>Term '%s' added to stopwords!</p></div>", "relevanssi"), $term);
		}
		else {
			printf(__("<div id='message' class='updated fade'><p>Couldn't add term '%s' to stopwords!</p></div>", "relevanssi"), $term);
		}
	}
}

function relevanssi_add_single_stopword($term) {
	global $wpdb, $relevanssi_table, $relevanssi_stopword_table;
	if ('' == $term) return;

	$q = $wpdb->prepare("INSERT INTO $relevanssi_stopword_table (stopword) VALUES (%s)", $term);
	$success = $wpdb->query($q);
	
	if ($success) {
		// remove from index
		$q = $wpdb->prepare("DELETE FROM $relevanssi_table WHERE term=%s", $term);
		$wpdb->query($q);
		return true;
	}
	else {
		return false;
	}
}

function relevanssi_remove_all_stopwords() {
	global $wpdb, $relevanssi_stopword_table;
	
	$q = $wpdb->prepare("TRUNCATE $relevanssi_stopword_table");
	$success = $wpdb->query($q);
	
	printf(__("<div id='message' class='updated fade'><p>Stopwords removed! Remember to re-index.</p></div>", "relevanssi"), $term);
}

function relevanssi_remove_stopword($term) {
	global $wpdb, $relevanssi_stopword_table;
	
	$q = $wpdb->prepare("DELETE FROM $relevanssi_stopword_table WHERE stopword = '$term'");
	$success = $wpdb->query($q);
	
	if ($success) {
		printf(__("<div id='message' class='updated fade'><p>Term '%s' removed from stopwords! Re-index to get it back to index.</p></div>", "relevanssi"), $term);
	}
	else {
		printf(__("<div id='message' class='updated fade'><p>Couldn't remove term '%s' from stopwords!</p></div>", "relevanssi"), $term);
	}
}

function relevanssi_common_words() {
	global $wpdb, $relevanssi_table, $wp_version;
	
	echo "<div style='float:left; width: 45%'>";
	
	echo "<h3>" . __("25 most common words in the index", 'relevanssi') . "</h3>";
	
	echo "<p>" . __("These words are excellent stopword material. A word that appears in most of the posts in the database is quite pointless when searching. This is also an easy way to create a completely new stopword list, if one isn't available in your language. Click the icon after the word to add the word to the stopword list. The word will also be removed from the index, so rebuilding the index is not necessary.", 'relevanssi') . "</p>";
	
	$words = $wpdb->get_results("SELECT COUNT(DISTINCT(doc)) as cnt, term
		FROM $relevanssi_table GROUP BY term ORDER BY cnt DESC LIMIT 25");

?>
<form method="post">
<?php wp_nonce_field(plugin_basename(__FILE__), 'relevanssi_options'); ?>
<input type="hidden" name="dowhat" value="add_stopword" />
<ul>
<?php

	if (function_exists("plugins_url")) {
		if (version_compare($wp_version, '2.8dev', '>' )) {
			$src = plugins_url('delete.png', __FILE__);
		}
		else {
			$src = plugins_url('relevanssi-premium/delete.png');
		}
	}
	else {
		// We can't check, so let's assume something sensible
		$src = '/wp-content/plugins/relevanssi-premium/delete.png';
	}
	
	foreach ($words as $word) {
		$stop = __('Add to stopwords', 'relevanssi');
		printf('<li>%s (%d) <input style="padding: 0; margin: 0" type="image" src="%s" alt="%s" name="term" value="%s"/></li>', $word->term, $word->cnt, $src, $stop, $word->term);
	}
	echo "</ul>\n</form>";
	
	echo "</div>";
}

function relevanssi_query_log() {
	global $relevanssi_log_table, $wpdb;

	echo '<h3>' . __("Total Searches", 'relevanssi') . '</h3>';

	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_total_queries( __("Totals", 'relevanssi') );
	echo '</div>';

	echo '<div style="clear: both"></div>';

	echo '<h3>' . __("Common Queries", 'relevanssi') . '</h3>';

	$lead = __("Here you can see the 20 most common user search queries, how many times those 
		queries were made and how many results were found for those queries.", 'relevanssi');

	echo "<p>$lead</p>";
	
	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(1, __("Today and yesterday", 'relevanssi'));
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(7, __("Last 7 days", 'relevanssi'));
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(30, __("Last 30 days", 'relevanssi'));
	echo '</div>';

	echo '<div style="clear: both"></div>';
	
	echo '<h3>' . __("Unsuccessful Queries", 'relevanssi') . '</h3>';

	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(1, __("Today and yesterday", 'relevanssi'), 'bad');
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(7, __("Last 7 days", 'relevanssi'), 'bad');
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(30, __("Last 30 days", 'relevanssi'), 'bad');
	echo '</div>';

	if ( current_user_can('manage_options') ) {

		echo '<div style="clear: both"></div>';
		$nonce = wp_nonce_field('relevanssi_reset_logs', '_relresnonce', true, false);
		echo <<<EOR
<h3>Reset Logs</h3>

<form method="post">
$nonce
<p>To reset the logs, type 'reset' into the box here <input type="text" name="relevanssi_reset_code" />
and click <input type="submit" name="relevanssi_reset" value="Reset" class="button" /></p>
</form>
EOR;

	}

	echo "</div>";
}

function relevanssi_total_queries( $title ) {

	global $wpdb, $relevanssi_log_table;

	$count = array();

	$count['Today and yesterday'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $relevanssi_log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 1;" ) );
	$count['Last 7 days'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $relevanssi_log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 7;" ) );
	$count['Last 30 days'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $relevanssi_log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 30;" ) );
	$count['Forever'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $relevanssi_log_table;" ) );

	echo "<table class='widefat'><thead><tr><th colspan='2'>$title</th></tr></thead><tbody><tr><th>When</th><th>Searches</th></tr>";
	foreach ( $count as $when => $searches ) {
		echo "<tr><td style='padding: 3px 5px'>$when</td><td style='padding: 3px 5px;'>$searches</td></tr>";
	}
	echo "</tbody></table>";

}

function relevanssi_date_queries($d, $title, $version = 'good') {
	global $wpdb, $relevanssi_log_table;
	
	if ($version == 'good')
		$queries = $wpdb->get_results("SELECT COUNT(DISTINCT(id)) as cnt, query, hits
		  FROM $relevanssi_log_table
		  WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= $d
		  GROUP BY query
		  ORDER BY cnt DESC
		  LIMIT 20");
	
	if ($version == 'bad')
		$queries = $wpdb->get_results("SELECT COUNT(DISTINCT(id)) as cnt, query, hits
		  FROM $relevanssi_log_table
		  WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= $d
		    AND hits = 0
		  GROUP BY query
		  ORDER BY time DESC
		  LIMIT 20");

	if (count($queries) > 0) {
		echo "<table class='widefat'><thead><tr><th colspan='3'>$title</th></tr></thead><tbody><tr><th>Query</th><th>#</th><th>Hits</th></tr>";
		foreach ($queries as $query) {
			$url = get_bloginfo('url');
			$u_q = urlencode($query->query);
			echo "<tr><td style='padding: 3px 5px'><a href='$url/?s=$u_q'>" . esc_attr($query->query) . "</a></td><td style='padding: 3px 5px; text-align: center'>" . $query->cnt . "</td><td style='padding: 3px 5px; text-align: center'>" . $query->hits . "</td></tr>";
		}
		echo "</tbody></table>";
	}
}

function relevanssi_options_form() {
	global $title_boost_default, $link_boost_default, $comment_boost_default, $wpdb, $relevanssi_table, $relevanssi_cache;
	
	wp_enqueue_style('dashboard');
	wp_print_styles('dashboard');
	wp_enqueue_script('dashboard');
	wp_print_scripts('dashboard');

	$docs_count = $wpdb->get_var("SELECT COUNT(DISTINCT doc) FROM $relevanssi_table");
	$terms_count = $wpdb->get_var("SELECT COUNT(*) FROM $relevanssi_table");
	$biggest_doc = $wpdb->get_var("SELECT doc FROM $relevanssi_table ORDER BY doc DESC LIMIT 1");
	$cache_count = $wpdb->get_var("SELECT COUNT(tstamp) FROM $relevanssi_cache");
	
	$serialize_options = array();
	
	$api_key = get_option('relevanssi_api_key');
	$serialize_options['relevanssi_api_key'] = $api_key;

	$title_boost = get_option('relevanssi_title_boost');
	$serialize_options['relevanssi_title_boost'] = $title_boost;
	$link_boost = get_option('relevanssi_link_boost');
	$serialize_options['relevanssi_link_boost'] = $link_boost;
	$comment_boost = get_option('relevanssi_comment_boost');
	$serialize_options['relevanssi_comment_boost'] = $comment_boost;
	$admin_search = get_option('relevanssi_admin_search');
	$serialize_options['relevanssi_admin_search'] = $admin_search;
	if ('on' == $admin_search) {
		$admin_search = 'checked="checked"';
	}
	else {
		$admin_search = '';
	}

	$index_limit = get_option('relevanssi_index_limit');
	$serialize_options['relevanssi_index_limit'] = $index_limit;

	$excerpts = get_option('relevanssi_excerpts');
	$serialize_options['relevanssi_excerpts'] = $excerpts;
	if ('on' == $excerpts) {
		$excerpts = 'checked="checked"';
	}
	else {
		$excerpts = '';
	}
	
	$excerpt_length = get_option('relevanssi_excerpt_length');
	$serialize_options['relevanssi_excerpt_length'] = $excerpt_length;
	$excerpt_type = get_option('relevanssi_excerpt_type');
	$serialize_options['relevanssi_excerpt_type'] = $excerpt_type;
	$excerpt_chars = "";
	$excerpt_words = "";
	switch ($excerpt_type) {
		case "chars":
			$excerpt_chars = 'selected="selected"';
			break;
		case "words":
			$excerpt_words = 'selected="selected"';
			break;
	}

	$log_queries = ('on' == get_option('relevanssi_log_queries') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_log_queries'] = $log_queries;
	$hide_branding = ('on' == get_option('relevanssi_hide_branding') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_hide_branding'] = $hide_branding;
	
	$highlight = get_option('relevanssi_highlight');
	$serialize_options['relevanssi_highlight'] = $highlight;
	$highlight_none = "";
	$highlight_mark = "";
	$highlight_em = "";
	$highlight_strong = "";
	$highlight_col = "";
	$highlight_bgcol = "";
	$highlight_style = "";
	$highlight_class = "";
	switch ($highlight) {
		case "no":
			$highlight_none = 'selected="selected"';
			break;
		case "mark":
			$highlight_mark = 'selected="selected"';
			break;
		case "em":
			$highlight_em = 'selected="selected"';
			break;
		case "strong":
			$highlight_strong = 'selected="selected"';
			break;
		case "col":
			$highlight_col = 'selected="selected"';
			break;
		case "bgcol":
			$highlight_bgcol = 'selected="selected"';
			break;
		case "css":
			$highlight_style = 'selected="selected"';
			break;
		case "class":
			$highlight_class = 'selected="selected"';
			break;
	}
	
	$custom_taxonomies = get_option('relevanssi_custom_taxonomies');
	$serialize_options['relevanssi_custom_taxonomies'] = $custom_taxonomies;
	$index_fields = get_option('relevanssi_index_fields');
	$serialize_options['relevanssi_index_fields'] = $index_fields;

	$txt_col = get_option('relevanssi_txt_col');
	$serialize_options['relevanssi_txt_col'] = $txt_col;
	$bg_col = get_option('relevanssi_bg_col');
	$serialize_options['relevanssi_bg_col'] = $bg_col;
	$css = get_option('relevanssi_css');
	$serialize_options['relevanssi_css'] = $css;
	$class = get_option('relevanssi_class');
	$serialize_options['relevanssi_class'] = $class;
	
	$cat = get_option('relevanssi_cat');
	$serialize_options['relevanssi_cat'] = $cat;
	$excat = get_option('relevanssi_excat');
	$serialize_options['relevanssi_excat'] = $excat;
	
	$fuzzy = get_option('relevanssi_fuzzy');
	$serialize_options['relevanssi_fuzzy'] = $fuzzy;
	$fuzzy_sometimes = ('sometimes' == $fuzzy ? 'selected="selected"' : '');
	$fuzzy_always = ('always' == $fuzzy ? 'selected="selected"' : '');
	$fuzzy_never = ('never' == $fuzzy ? 'selected="selected"' : '');

	$intlinks = get_option('relevanssi_internal_links');
	$serialize_options['relevanssi_internal_links'] = $intlinks;
	$intlinks_strip = ('strip' == $intlinks ? 'selected="selected"' : '');
	$intlinks_nostrip = ('nostrip' == $intlinks ? 'selected="selected"' : '');
	$intlinks_noindex = ('noindex' == $intlinks ? 'selected="selected"' : '');

	$implicit = get_option('relevanssi_implicit_operator');
	$serialize_options['relevanssi_implicit_operator'] = $implicit;
	$implicit_and = ('AND' == $implicit ? 'selected="selected"' : '');
	$implicit_or = ('OR' == $implicit ? 'selected="selected"' : '');

	$expand_shortcodes = ('on' == get_option('relevanssi_expand_shortcodes') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_expand_shortcodes'] = get_option('relevanssi_expand_shortcodes');
	$disablefallback = ('on' == get_option('relevanssi_disable_or_fallback') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_disable_or_fallback'] = get_option('relevanssi_disable_or_fallback');

	$throttle = ('on' == get_option('relevanssi_throttle') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_throttle'] = get_option('relevanssi_throttle');

	$omit_from_logs	= get_option('relevanssi_omit_from_logs');
	$serialize_options['relevanssi_omit_from_logs'] = $omit_from_logs;
	
	$synonyms = get_option('relevanssi_synonyms');
	$serialize_options['relevanssi_synonyms'] = $synonyms;
	isset($synonyms) ? $synonyms = str_replace(';', "\n", $synonyms) : $synonyms = "";
	
	//Added by OdditY ->
	$expst = get_option('relevanssi_exclude_posts'); 
	$serialize_options['relevanssi_exclude_posts'] = $expst;
	$inctags = ('on' == get_option('relevanssi_include_tags') ? 'checked="checked"' : ''); 
	$hititle = ('on' == get_option('relevanssi_hilite_title') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_include_tags'] = get_option('relevanssi_include_tags');
	$serialize_options['relevanssi_hilite_title'] = get_option('relevanssi_hilite_title');
	$incom_type = get_option('relevanssi_index_comments');
	$serialize_options['relevanssi_index_comments'] = $incom_type;
	$incom_type_all = "";
	$incom_type_normal = "";
	$incom_type_none = "";
	switch ($incom_type) {
		case "all":
			$incom_type_all = 'selected="selected"';
			break;
		case "normal":
			$incom_type_normal = 'selected="selected"';
			break;
		case "none":
			$incom_type_none = 'selected="selected"';
			break;
	}//added by OdditY END <-

	$highlight_docs = ('on' == get_option('relevanssi_highlight_docs') ? 'checked="checked"' : ''); 
	$highlight_docs_ext = ('on' == get_option('relevanssi_highlight_docs_external') ? 'checked="checked"' : ''); 
	$highlight_coms = ('on' == get_option('relevanssi_highlight_comments') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_highlight_docs'] = get_option('relevanssi_highlight_docs');
	$serialize_options['relevanssi_highlight_docs_external'] = get_option('relevanssi_highlight_docs_external');
	$serialize_options['relevanssi_highlight_comments'] = get_option('relevanssi_highlight_comments');

	$respect_exclude = ('on' == get_option('relevanssi_respect_exclude') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_respect_exclude'] = get_option('relevanssi_respect_exclude');

	$enable_cache = ('on' == get_option('relevanssi_enable_cache') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_enable_cache'] = get_option('relevanssi_enable_cache');
	$cache_seconds = get_option('relevanssi_cache_seconds');
	$serialize_options['relevanssi_cache_seconds'] = $cache_seconds;

	$min_word_length = get_option('relevanssi_min_word_length');
	$serialize_options['relevanssi_min_word_length'] = $min_word_length;
	$thousand_separator = get_option('relevanssi_thousand_separator');
	$serialize_options['relevanssi_thousand_separator'] = $thousand_separator;

	$inccats = ('on' == get_option('relevanssi_include_cats') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_include_cats'] = get_option('relevanssi_include_cats');
	$index_author = ('on' == get_option('relevanssi_index_author') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_index_author'] = get_option('relevanssi_index_author');
	$index_excerpt = ('on' == get_option('relevanssi_index_excerpt') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_index_excerpt'] = get_option('relevanssi_index_excerpt');
	$index_users = ('on' == get_option('relevanssi_index_users') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_index_users'] = get_option('relevanssi_index_users');
	$index_user_fields = get_option('relevanssi_index_user_fields');
	$serialize_options['relevanssi_index_user_fields'] = $index_user_fields;
	$index_subscribers = ('on' == get_option('relevanssi_index_subscribers') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_index_subscribers'] = get_option('relevanssi_index_subscribers');
	$index_taxonomies = ('on' == get_option('relevanssi_index_taxonomies') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_index_taxonomies'] = get_option('relevanssi_index_taxonomies');
	$taxonomies_to_index = get_option('relevanssi_taxonomies_to_index');
	$serialize_options['relevanssi_taxonomies_to_index'] = $taxonomies_to_index;
	
	$show_matches = ('on' == get_option('relevanssi_show_matches') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_show_matches'] = get_option('relevanssi_show_matches');
	$show_matches_text = stripslashes(get_option('relevanssi_show_matches_text'));
	$serialize_options['relevanssi_show_matches_text'] = get_option('relevanssi_show_matches_text');
	
	$wpml_only_current = ('on' == get_option('relevanssi_wpml_only_current') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_wpml_only_current'] = get_option('relevanssi_wpml_only_current');

	$word_boundaries = ('on' == get_option('relevanssi_word_boundaries') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_word_boundaries'] = get_option('relevanssi_word_boundaries');

	$post_type_weights = get_option('relevanssi_post_type_weights');
	$serialize_options['relevanssi_post_type_weights'] = $post_type_weights;

	$hide_post_controls = ('on' == get_option('relevanssi_hide_post_controls') ? 'checked="checked"' : ''); 
	$serialize_options['relevanssi_hide_post_controls'] = get_option('relevanssi_hide_post_controls');

	$index_post_types = get_option('relevanssi_index_post_types');
	if (empty($index_post_types)) $index_post_types = array();
	$serialize_options['relevanssi_index_post_types'] = $index_post_types;

	$orderby = get_option('relevanssi_default_orderby');
	$serialize_options['relevanssi_default_orderby'] = $orderby;
	$orderby_relevance = ('relevance' == $orderby ? 'selected="selected"' : '');
	$orderby_date = ('post_date' == $orderby ? 'selected="selected"' : '');
	
	$recency_bonus_array = get_option('relevanssi_recency_bonus');
	$serialize_options['recency_bonus'] = $recency_bonus_array;
	$recency_bonus = $recency_bonus_array['bonus'];
	$recency_bonus_days = $recency_bonus_array['days'];
	
	$mysql_columns = get_option('relevanssi_mysql_columns');
	$serialize_options['relevanssi_mysql_columns'] = $mysql_columns;
	
	$serialized_options = serialize($serialize_options);

?>
<div class='postbox-container' style='width:70%;'>
	<form method='post' action='options-general.php?page=relevanssi-premium/relevanssi.php'>
	
	<?php wp_nonce_field(plugin_basename(__FILE__), 'relevanssi_options'); ?>
	
    <p><a href="#basic"><?php _e("Basic options", "relevanssi"); ?></a> |
	<a href="#weights"><?php _e("Weights", "relevanssi"); ?></a> |
	<a href="#logs"><?php _e("Logs", "relevanssi"); ?></a> |
    <a href="#exclusions"><?php _e("Exclusions and restrictions", "relevanssi"); ?></a> |
    <a href="#excerpts"><?php _e("Custom excerpts", "relevanssi"); ?></a> |
    <a href="#highlighting"><?php _e("Highlighting search results", "relevanssi"); ?></a> |
    <a href="#indexing"><?php _e("Indexing options", "relevanssi"); ?></a> |
    <a href="#caching"><?php _e("Caching", "relevanssi"); ?></a> |
    <a href="#synonyms"><?php _e("Synonyms", "relevanssi"); ?></a> |
    <a href="#stopwords"><?php _e("Stopwords", "relevanssi"); ?></a> |
    <a href="#options"><?php _e("Import/export options", "relevanssi"); ?></a>
    </p>

	<h3><?php _e('Quick tools', 'relevanssi') ?></h3>
	<p>
	<input type='submit' name='submit' value='<?php _e('Save options', 'relevanssi'); ?>' style="background-color:#007f00; border-color:#5fbf00; border-style:solid; border-width:thick; padding: 5px; color: #fff;" />
	<input type="submit" name="index" value="<?php _e('Build the index', 'relevanssi') ?>" style="background-color:#007f00; border-color:#5fbf00; border-style:solid; border-width:thick; padding: 5px; color: #fff;" />
	<input type="submit" name="index_extend" value="<?php _e('Continue indexing', 'relevanssi') ?>"  style="background-color:#e87000; border-color:#ffbb00; border-style:solid; border-width:thick; padding: 5px; color: #fff;" />, <?php _e('add', 'relevanssi'); ?> <input type="text" size="4" name="relevanssi_index_limit" value="<?php echo $index_limit ?>" /> <?php _e('documents.', 'relevanssi'); ?></p>

	<p><?php _e("Use 'Build the index' to build the index with current <a href='#indexing'>indexing options</a>. If you can't finish indexing with one go, use 'Continue indexing' to finish the job. You can change the number of documents to add until you find the largest amount you can add with one go. See 'State of the Index' below to find out how many documents actually go into the index.", 'relevanssi') ?></p>
	
	<h3><?php _e("State of the Index", "relevanssi"); ?></h3>
	<p>
	<?php _e("Documents in the index", "relevanssi"); ?>: <strong><?php echo $docs_count ?></strong><br />
	<?php _e("Terms in the index", "relevanssi"); ?>: <strong><?php echo $terms_count ?></strong><br />
	<?php _e("Highest post ID indexed", "relevanssi"); ?>: <strong><?php echo $biggest_doc ?></strong>
	</p>
	
	<h3 id="basic"><?php _e("Basic options", "relevanssi"); ?></h3>

	<label for='relevanssi_api_key'><?php _e('API key:', 'relevanssi'); ?>
	<input type='text' name='relevanssi_api_key' value='<?php echo $api_key ?>' /></label><br />
	<small><?php _e('API key is required to use the automatic update feature. Get yours from Relevanssi.com.', 'relevanssi'); ?></small>

	<br /><br />
	
	<label for='relevanssi_admin_search'><?php _e('Use search for admin:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_admin_search' <?php echo $admin_search ?> /></label>
	<small><?php _e('If checked, Relevanssi will be used for searches in the admin interface', 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_implicit_operator'><?php _e("Default operator for the search?", "relevanssi"); ?>
	<select name='relevanssi_implicit_operator'>
	<option value='AND' <?php echo $implicit_and ?>><?php _e("AND - require all terms", "relevanssi"); ?></option>
	<option value='OR' <?php echo $implicit_or ?>><?php _e("OR - any term present is enough", "relevanssi"); ?></option>
	</select></label><br />
	<small><?php _e("If you choose AND and the search finds no matches, it will automatically do an OR search.", "relevanssi"); ?></small>
	
	<br /><br />

	<label for='relevanssi_disable_or_fallback'><?php _e("Disable OR fallback:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_disable_or_fallback' <?php echo $disablefallback ?> /></label><br />
	<small><?php _e("If you don't want Relevanssi to fall back to OR search when AND search gets no hits, check this option. For most cases, leave this one unchecked.", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_default_orderby'><?php _e('Default order for results:', 'relevanssi'); ?>
	<select name='relevanssi_default_orderby'>
	<option value='relevance' <?php echo $orderby_relevance ?>><?php _e("Relevance (highly recommended)", "relevanssi"); ?></option>
	<option value='post_date' <?php echo $orderby_date ?>><?php _e("Post date", "relevanssi"); ?></option>
	</select></label><br />
	<small><?php _e("If you want date-based results, see the recent post bonus in the Weights section.", "relevanssi"); ?></small>
	
	<br /><br />

	<label for='relevanssi_fuzzy'><?php _e('When to use fuzzy matching?', 'relevanssi'); ?>
	<select name='relevanssi_fuzzy'>
	<option value='sometimes' <?php echo $fuzzy_sometimes ?>><?php _e("When straight search gets no hits", "relevanssi"); ?></option>
	<option value='always' <?php echo $fuzzy_always ?>><?php _e("Always", "relevanssi"); ?></option>
	<option value='never' <?php echo $fuzzy_never ?>><?php _e("Don't use fuzzy search", "relevanssi"); ?></option>
	</select></label><br />
	<small><?php _e("Straight search matches just the term. Fuzzy search matches everything that begins or ends with the search term.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_internal_links'><?php _e("How to index internal links:", "relevanssi"); ?>
	<select name='relevanssi_internal_links'>
	<option value='noindex' <?php echo $intlinks_noindex ?>><?php _e("No special processing for internal links", "relevanssi"); ?></option>
	<option value='strip' <?php echo $intlinks_strip ?>><?php _e("Index internal links for target documents only", "relevanssi"); ?></option>
	<option value='nostrip' <?php echo $intlinks_nostrip ?>><?php _e("Index internal links for both target and source", "relevanssi"); ?></option>
	</select></label><br />
	<small><?php _e("Internal link anchor tags can be indexed for target document (so the text will match the document the link points to), both target and source or source only (with no extra significance for the links). See Relevanssi Knowledge Base for more details. Changing this option requires reindexing.", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_throttle'><?php _e("Limit searches:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_throttle' <?php echo $throttle ?> /></label><br />
	<small><?php _e("If this option is checked, Relevanssi will limit search results to at most 500 results per term. This will improve performance, but may cause some relevant documents to go unfound. However, Relevanssi tries to prioritize the most relevant documents. <strong>This does not work well when sorting results by date.</strong> The throttle can end up cutting off recent posts to favour more relevant posts.", 'relevanssi'); ?></small>

	<br /><br />
	
	<label for='relevanssi_hide_post_controls'><?php _e("Hide Relevanssi on edit pages:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_hide_post_controls' <?php echo $hide_post_controls ?> /></label><br />
	<small><?php _e("If you check this option, all Relevanssi features are removed from edit pages.", 'relevanssi'); ?></small>

	<h3 id="weights"><?php _e('Weights', 'relevanssi'); ?></h3>

	<p><?php _e('These values affect the weights of the documents. These are all multipliers, so 1 means no change in weight, less than 1 means less weight, and more than 1 means more weight. Setting something to zero makes that worthless. For example, if title weight is more than 1, words in titles are more significant than words elsewhere. If title weight is 0, words in titles won\'t make any difference to the search results.', 'relevanssi'); ?></p>
	
	<table class="widefat">
	<thead>
		<tr>
			<th><?php _e('Element', 'relevanssi'); ?></th>
			<th><?php _e('Weight', 'relevanssi'); ?></th>
			<th><?php _e('Default weight', 'relevanssi'); ?></th>
		</tr>
	</thead>
	<tr>
		<td>
			<?php _e('Post titles', 'relevanssi'); ?>
		</td>
		<td>
			<input type='text' name='relevanssi_title_boost' size='4' value='<?php echo $title_boost ?>' />
		</td>
		<td>
			<?php echo $title_boost_default; ?>
		</td>
	</tr>
	<tr>
		<td>
			<?php _e('Internal links', 'relevanssi'); ?> 
		</td>
		<td>
			<input type='text' name='relevanssi_link_boost' size='4' value='<?php echo $link_boost ?>' />
		</td>
		<td>
			<?php echo $link_boost_default; ?>
		</td>
	</tr>
	<tr>
		<td>
			<?php _e('Comment text', 'relevanssi'); ?> 
		</td>
		<td>
			<input type='text' name='relevanssi_comment_boost' size='4' value='<?php echo $comment_boost ?>' />
		</td>
		<td>
			<?php echo $comment_boost_default; ?>
		</td>
	</tr>
	<?php
		$post_types = get_post_types(); 
		foreach ($post_types as $type) {
			if ('nav_menu_item' == $type) continue;
			if ('revision' == $type) continue;
			if (isset($post_type_weights[$type])) {
				$value = $post_type_weights[$type];
			}
			else {
				$value = 1;
			}
			$label = sprintf(__("Post type '%s':", 'relevanssi'), $type);
			
			echo <<<EOH
	<tr>
		<td>
			$label 
		</td>
		<td>
			<input type='text' name='relevanssi_weight_$type' size='4' value='$value' />
		</td>
		<td>&nbsp;</td>
	</tr>
EOH;
		}

		$taxonomies = get_taxonomies('', 'names'); 
		foreach ($taxonomies as $type) {
			if ('nav_menu' == $type) continue;
			if ('post_format' == $type) continue;
			if ('link_category' == $type) continue;
			if (isset($post_type_weights[$type])) {
				$value = $post_type_weights[$type];
			}
			else {
				$value = 1;
			}
			$label = sprintf(__("Taxonomy '%s':", 'relevanssi'), $type);
			
			echo <<<EOH
	<tr>
		<td>
			$label 
		</td>
		<td>
			<input type='text' name='relevanssi_weight_$type' size='4' value='$value' />
		</td>
		<td>&nbsp;</td>
	</tr>
EOH;
		}
	?>
	</table>

	<br /><br />

	<label for='relevanssi_recency_bonus'><?php _e("Weight multiplier for new posts:", "relevanssi"); ?>
	<input type='text' name='relevanssi_recency_bonus' size='4' value="<?php echo $recency_bonus ?>" /></label><br />
	<label for='relevanssi_recency_days'><?php _e("Assign bonus for posts newer than:", "relevanssi"); ?>
	<input type='text' name='relevanssi_recency_days' size='4' value='<?php echo $recency_bonus_days ?>' /> <?php _e("days", "relevanssi"); ?></label><br />
	<small><?php _e('Posts newer than the day cutoff specified here will have their weight multiplied with the bonus above.', 'relevanssi'); ?></small>
	
	<?php if (function_exists('icl_object_id')) : ?>
	<h3 id="wpml"><?php _e('WPML compatibility', 'relevanssi'); ?></h3>
	
	<label for='relevanssi_wpml_only_current'><?php _e("Limit results to current language:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_wpml_only_current' <?php echo $wpml_only_current ?> /></label>
	<small><?php _e("If this option is checked, Relevanssi will only return results in the current active language. Otherwise results will include posts in every language.", "relevanssi");?></small>
	
	<?php endif; ?>
	
	<h3 id="logs"><?php _e('Logs', 'relevanssi'); ?></h3>
	
	<label for='relevanssi_log_queries'><?php _e("Keep a log of user queries:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_log_queries' <?php echo $log_queries ?> /></label>
	<small><?php _e("If checked, Relevanssi will log user queries. The log appears in 'User searches' on the Dashboard admin menu.", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_omit_from_logs'><?php _e("Don't log queries from these users:", "relevanssi"); ?>
	<input type='text' name='relevanssi_omit_from_logs' size='20' value='<?php echo $omit_from_logs ?>' /></label>
	<small><?php _e("Comma-separated list of numeric user IDs or user login names that will not be logged.", "relevanssi"); ?></small>

	<p><?php _e("If you enable logs, you can see what your users are searching for. Logs are also needed to use the 'Did you mean?' feature. You can prevent your own searches from getting in the logs with the omit feature.", "relevanssi"); ?></p>

	<label for='relevanssi_hide_branding'><?php _e("Don't show Relevanssi branding on the 'User Searches' screen:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_hide_branding' <?php echo $hide_branding ?> /></label>

	<h3 id="exclusions"><?php _e("Exclusions and restrictions", "relevanssi"); ?></h3>
	
	<label for='relevanssi_cat'><?php _e('Restrict search to these categories and tags:', 'relevanssi'); ?>
	<input type='text' name='relevanssi_cat' size='20' value='<?php echo $cat ?>' /></label><br />
	<small><?php _e("Enter a comma-separated list of category and tag IDs to restrict search to those categories or tags. You can also use <code>&lt;input type='hidden' name='cats' value='list of cats and tags' /&gt;</code> in your search form. The input field will 	overrun this setting.", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_excat'><?php _e('Exclude these categories and tags from search:', 'relevanssi'); ?>
	<input type='text' name='relevanssi_excat' size='20' value='<?php echo $excat ?>' /></label><br />
	<small><?php _e("Enter a comma-separated list of category and tag IDs that are excluded from search results. You can exclude categories with the 'cat' input field by using negative values.", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_excat'><?php _e('Exclude these posts/pages from search:', 'relevanssi'); ?>
	<input type='text' name='relevanssi_expst' size='20' value='<?php echo $expst ?>' /></label><br />
	<small><?php _e("Enter a comma-separated list of post/page IDs that are excluded from search results. This only works here, you can't use the input field option (WordPress doesn't pass custom parameters there). You can also use a checkbox on post/page edit pages to remove posts from index.", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_respect_exclude'><?php _e('Respect exclude_from_search for custom post types:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_respect_exclude' <?php echo $respect_exclude ?> /></label><br />
	<small><?php _e("If checked, Relevanssi won't display posts of custom post types that have 'exclude_from_search' set to true. If not checked, Relevanssi will display anything that is indexed.", 'relevanssi'); ?></small>

	<h3 id="excerpts"><?php _e("Custom excerpts/snippets", "relevanssi"); ?></h3>
	
	<label for='relevanssi_excerpts'><?php _e("Create custom search result snippets:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_excerpts' <?php echo $excerpts ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will create excerpts that contain the search term hits. To make them work, make sure your search result template uses the_excerpt() to display post excerpts.", 'relevanssi'); ?></small>
	
	<br /><br />
	
	<label for='relevanssi_excerpt_length'><?php _e("Length of the snippet:", "relevanssi"); ?>
	<input type='text' name='relevanssi_excerpt_length' size='4' value='<?php echo $excerpt_length ?>' /></label>
	<select name='relevanssi_excerpt_type'>
	<option value='chars' <?php echo $excerpt_chars ?>><?php _e("characters", "relevanssi"); ?></option>
	<option value='words' <?php echo $excerpt_words ?>><?php _e("words", "relevanssi"); ?></option>
	</select><br />
	<small><?php _e("This must be an integer.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_show_matches'><?php _e("Show breakdown of search hits in excerpts:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_show_matches' <?php echo $show_matches ?> /></label>
	<small><?php _e("Check this to show more information on where the search hits were made. Requires custom snippets to work.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_show_matches_text'><?php _e("The breakdown format:", "relevanssi"); ?>
	<input type='text' name='relevanssi_show_matches_text' value="<?php echo $show_matches_text ?>" size='20' /></label>
	<small><?php _e("Use %body%, %title%, %tags% and %comments% to display the number of hits (in different parts of the post), %total% for total hits, %score% to display the document weight and %terms% to show how many hits each search term got. No double quotes (\") allowed!", "relevanssi"); ?></small>

	<h3 id="highlighting"><?php _e("Search hit highlighting", "relevanssi"); ?></h3>

	<?php _e("First, choose the type of highlighting used:", "relevanssi"); ?><br />

	<div style='margin-left: 2em'>
	<label for='relevanssi_highlight'><?php _e("Highlight query terms in search results:", 'relevanssi'); ?>
	<select name='relevanssi_highlight'>
	<option value='no' <?php echo $highlight_none ?>><?php _e('No highlighting', 'relevanssi'); ?></option>
	<option value='mark' <?php echo $highlight_mark ?>>&lt;mark&gt;</option>
	<option value='em' <?php echo $highlight_em ?>>&lt;em&gt;</option>
	<option value='strong' <?php echo $highlight_strong ?>>&lt;strong&gt;</option>
	<option value='col' <?php echo $highlight_col ?>><?php _e('Text color', 'relevanssi'); ?></option>
	<option value='bgcol' <?php echo $highlight_bgcol ?>><?php _e('Background color', 'relevanssi'); ?></option>
	<option value='css' <?php echo $highlight_style ?>><?php _e("CSS Style", 'relevanssi'); ?></option>
	<option value='class' <?php echo $highlight_class ?>><?php _e("CSS Class", 'relevanssi'); ?></option>
	</select></label>
	<small><?php _e("Highlighting isn't available unless you use custom snippets", 'relevanssi'); ?></small>
	
	<br />

	<label for='relevanssi_hilite_title'><?php _e("Highlight query terms in result titles too:", 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_hilite_title' <?php echo $hititle ?> /></label>
	<small><?php _e("", 'relevanssi'); ?></small>

	<br />

	<label for='relevanssi_highlight_docs'><?php _e("Highlight query terms in documents from local searches:", 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_highlight_docs' <?php echo $highlight_docs ?> /></label>
	<small><?php _e("Highlights hits when user opens the post from search results. This is based on HTTP referrer, so if that's blocked, there'll be no highlights.", "relevanssi"); ?></small>

	<br />

	<label for='relevanssi_highlight_docs_external'><?php _e("Highlight query terms in documents from external searches:", 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_highlight_docs_external' <?php echo $highlight_docs_ext ?> /></label>
	<small><?php _e("Highlights hits when user arrives from external search. Currently supports Google, Bing, Ask, Yahoo and AOL Search.", "relevanssi"); ?></small>

	<br />
	
	<label for='relevanssi_highlight_comments'><?php _e("Highlight query terms in comments:", 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_highlight_comments' <?php echo $highlight_coms ?> /></label>
	<small><?php _e("Highlights hits in comments when user opens the post from search results.", "relevanssi"); ?></small>

	<br />
	
	<label for='relevanssi_word_boundaries'><?php _e("Uncheck this if you use non-ASCII characters:", 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_word_boundaries' <?php echo $word_boundaries ?> /></label>
	<small><?php _e("If you use non-ASCII characters (like Cyrillic alphabet) and the highlights don't work, uncheck this option to make highlights work.", "relevanssi"); ?></small>

	<br /><br />
	</div>
	
	<?php _e("Then adjust the settings for your chosen type:", "relevanssi"); ?><br />

	<div style='margin-left: 2em'>
	
	<label for='relevanssi_txt_col'><?php _e("Text color for highlights:", "relevanssi"); ?>
	<input type='text' name='relevanssi_txt_col' size='7' value='<?php echo $txt_col ?>' /></label>
	<small><?php _e("Use HTML color codes (#rgb or #rrggbb)", "relevanssi"); ?></small>

	<br />
	
	<label for='relevanssi_bg_col'><?php _e("Background color for highlights:", "relevanssi"); ?>
	<input type='text' name='relevanssi_bg_col' size='7' value='<?php echo $bg_col ?>' /></label>
	<small><?php _e("Use HTML color codes (#rgb or #rrggbb)", "relevanssi"); ?></small>

	<br />
	
	<label for='relevanssi_css'><?php _e("CSS style for highlights:", "relevanssi"); ?>
	<input type='text' name='relevanssi_css' size='30' value='<?php echo $css ?>' /></label>
	<small><?php _e("You can use any CSS styling here, style will be inserted with a &lt;span&gt;", "relevanssi"); ?></small>

	<br />
	
	<label for='relevanssi_css'><?php _e("CSS class for highlights:", "relevanssi"); ?>
	<input type='text' name='relevanssi_class' size='10' value='<?php echo $class ?>' /></label>
	<small><?php _e("Name a class here, search results will be wrapped in a &lt;span&gt; with the class", "relevanssi"); ?></small>

	</div>
	
	<br />
	<br />
	
	<input type='submit' name='submit' value='<?php _e('Save the options', 'relevanssi'); ?>' class='button button-primary' />

	<h3 id="indexing"><?php _e('Indexing options', 'relevanssi'); ?></h3>

	<p><?php _e('Choose post types to index:', 'relevanssi'); ?></p>
	
	<table class="widefat" id="index_post_types_table">
	<thead>
		<tr>
			<th><?php _e('Type', 'relevanssi'); ?></th>
			<th><?php _e('Index', 'relevanssi'); ?></th>
			<th><?php _e('Public?', 'relevanssi'); ?></th>
		</tr>
	</thead>
	<?php
		$pt_1 = get_post_types(array('exclude_from_search' => '0'));
		$pt_2 = get_post_types(array('exclude_from_search' => false));
		$public_types = array_merge($pt_1, $pt_2);
		$post_types = get_post_types(); 
		foreach ($post_types as $type) {
			if ('nav_menu_item' == $type) continue;
			if ('revision' == $type) continue;
			if (in_array($type, $index_post_types)) {
				$checked = 'checked="checked"';
			}
			else {
				$checked = '';
			}
			$label = sprintf(__("%s", 'relevanssi'), $type);
			in_array($type, $public_types) ? $public = __('yes', 'relevanssi') : $public = __('no', 'relevanssi');
			
			echo <<<EOH
	<tr>
		<td>
			$label 
		</td>
		<td>
			<input type='checkbox' name='relevanssi_index_type_$type' $checked />
		</td>
		<td>
			$public
		</td>
	</tr>
EOH;
		}
	?>
	</table>
	
	<br /><br />
	
	<label for='relevanssi_min_word_length'><?php _e("Minimum word length to index", "relevanssi"); ?>:
	<input type='text' name='relevanssi_min_word_length' size='30' value='<?php echo $min_word_length ?>' /></label><br />
	<small><?php _e("Words shorter than this number will not be indexed.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_thousand_separator'><?php _e("Thousands separator", "relevanssi"); ?>:
	<input type='text' name='relevanssi_thousand_separator' size='30' value='<?php echo $thousand_separator ?>' /></label><br />
	<small><?php _e("If Relevanssi sees this character between numbers, it'll stick the numbers together no matter how the character would otherwise be handled. Especially useful if a space is used as a thousands separator.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_expand_shortcodes'><?php _e("Expand shortcodes in post content:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_expand_shortcodes' <?php echo $expand_shortcodes ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will expand shortcodes in post content before indexing. Otherwise shortcodes will be stripped. If you use shortcodes to include dynamic content, Relevanssi will not keep the index updated, the index will reflect the status of the shortcode content at the moment of indexing.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_inctags'><?php _e('Index and search your posts\' tags:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_inctags' <?php echo $inctags ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will also index and search the tags of your posts. Remember to rebuild the index if you change this option!", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_inccats'><?php _e('Index and search your posts\' categories:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_inccats' <?php echo $inccats ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will also index and search the categories of your posts. Category titles will pass through 'single_cat_title' filter. Remember to rebuild the index if you change this option!", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_index_author'><?php _e('Index and search your posts\' authors:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_index_author' <?php echo $index_author ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will also index and search the authors of your posts. Author display name will be indexed. Remember to rebuild the index if you change this option!", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_index_excerpt'><?php _e('Index and search post excerpts:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_index_excerpt' <?php echo $index_excerpt ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will also index and search the excerpts of your posts.Remember to rebuild the index if you change this option!", 'relevanssi'); ?></small>

	<br /><br />
	
	<label for='relevanssi_index_comments'><?php _e("Index and search these comments:", "relevanssi"); ?>
	<select name='relevanssi_index_comments'>
	<option value='none' <?php echo $incom_type_none ?>><?php _e("none", "relevanssi"); ?></option>
	<option value='normal' <?php echo $incom_type_normal ?>><?php _e("normal", "relevanssi"); ?></option>
	<option value='all' <?php echo $incom_type_all ?>><?php _e("all", "relevanssi"); ?></option>
	</select></label><br />
	<small><?php _e("Relevanssi will index and search ALL (all comments including track- &amp; pingbacks and custom comment types), NONE (no comments) or NORMAL (manually posted comments on your blog).<br />Remember to rebuild the index if you change this option!", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_index_fields'><?php _e("Custom fields to index:", "relevanssi"); ?>
	<input type='text' name='relevanssi_index_fields' size='30' value='<?php echo $index_fields ?>' /></label><br />
	<small><?php _e("A comma-separated list of custom fields to include in the index. Set to 'visible' to index all visible custom fields and to 'all' to index all custom fields, also those starting with a '_' character.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_custom_taxonomies'><?php _e("Custom taxonomies to index:", "relevanssi"); ?>
	<input type='text' name='relevanssi_custom_taxonomies' size='30' value='<?php echo $custom_taxonomies ?>' /></label><br />
	<small><?php _e("A comma-separated list of custom taxonomy IDs to include in the index.", "relevanssi"); ?></small>

	<br /><br />

<?php

	$column_list = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->posts");
	$columns = array();
	foreach ($column_list as $column) {
		array_push($columns, $column->Field);
	}
	$columns = implode(', ', $columns);
	
?>

	<label for='relevanssi_mysql_columns'><?php _e("Custom MySQL columns to index:", "relevanssi"); ?>
	<input type='text' name='relevanssi_mysql_columns' size='30' value='<?php echo $mysql_columns ?>' /></label><br />
	<small><?php _e("A comma-separated list of wp_posts MySQL table columns to include in the index. Following columns are available: ", "relevanssi"); echo $columns; ?>.</small>

	<br /><br />

	<label for='relevanssi_index_users'><?php _e('Index and search user profiles:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_index_users' <?php echo $index_users ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will also index and search user profiles (first name, last name, display name and user description). Requires changes to search results template, see Relevanssi Knowledge Base.", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_index_subscribers'><?php _e('Index subscriber profiles:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_index_subscribers' <?php echo $index_subscribers ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will index subscriber profiles as well, otherwise only authors, editors, contributors and admins are indexed.", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_index_user_fields'><?php _e("Extra user fields to index:", "relevanssi"); ?>
	<input type='text' name='relevanssi_index_user_fields' size='30' value='<?php echo $index_user_fields ?>' /></label><br />
	<small><?php _e("A comma-separated list of user profile field names (names of the database columns) to include in the index.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_index_taxonomies'><?php _e('Index and search taxonomy pages:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_index_taxonomies' <?php echo $index_taxonomies ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will also index and search taxonomy pages (categories, tags, custom taxonomies).", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_taxonomies_to_index'><?php _e("Taxonomy pages to index:", "relevanssi"); ?>
	<input type='text' name='relevanssi_taxonomies_to_index' size='30' value='<?php echo $taxonomies_to_index ?>' /></label><br />
	<small><?php _e("A comma-separated list of taxonomies to include in the taxonomy page index ('all' indexes all custom taxonomies. If you don't use 'all', remember to list 'category' and 'post_tag').", "relevanssi"); ?></small>

	<br /><br />


	<input type='submit' name='index' value='<?php _e("Save indexing options and build the index", 'relevanssi'); ?>' class='button button-primary' />

	<input type='submit' name='index_extend' value='<?php _e("Continue indexing", 'relevanssi'); ?>' class='button' />

	<h3 id="caching"><?php _e("Caching", "relevanssi"); ?></h3>

	<p><?php _e("Warning: In many cases caching is not useful, and in some cases can be even harmful. Do not
	activate cache unless you have a good reason to do so.", 'relevanssi'); ?></p>
	
	<label for='relevanssi_enable_cache'><?php _e('Enable result and excerpt caching:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_enable_cache' <?php echo $enable_cache ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will cache search results and post excerpts.", 'relevanssi'); ?></small>

	<br /><br />
	
	<label for='relevanssi_cache_seconds'><?php _e("Cache expire (in seconds):", "relevanssi"); ?>
	<input type='text' name='relevanssi_cache_seconds' size='30' value='<?php echo $cache_seconds ?>' /></label><br />
	<small><?php _e("86400 = day", "relevanssi"); ?></small>

	<br /><br />
	
	<?php _e("Entries in the cache", 'relevanssi'); ?>: <?php echo $cache_count; ?>

	<br /><br />
	
	<input type='submit' name='truncate' value='<?php _e('Clear all caches', 'relevanssi'); ?>' class='button' />

	<h3 id="synonyms"><?php _e("Synonyms", "relevanssi"); ?></h3>
	
	<p><textarea name='relevanssi_synonyms' rows='9' cols='60'><?php echo $synonyms ?></textarea></p>

	<p><small><?php _e("Add synonyms here in 'key = value' format. When searching with the OR operator, any search of 'key' will be expanded to include 'value' as well. Using phrases is possible. The key-value pairs work in one direction only, but you can of course repeat the same pair reversed.", "relevanssi"); ?></small></p>

	<input type='submit' name='submit' value='<?php _e('Save the options', 'relevanssi'); ?>' class='button' />

	<h3 id="stopwords"><?php _e("Stopwords", "relevanssi"); ?></h3>
	
	<?php relevanssi_show_stopwords(); ?>
	
	<h3 id="options"><?php _e("Import or export options", "relevanssi"); ?></h3>
	
	<p><?php _e("Here you find the current Relevanssi Premium options in a text format. Copy the contents of the text field to make a backup of your settings. You can also paste new settings here to change all settings at the same time. This is useful if you have default settings you want to use on every system.", "relevanssi"); ?></p>
	
	<p><textarea name='relevanssi_settings' rows='2' cols='60'><?php echo $serialized_options; ?></textarea></p>

	<input type='submit' name='import_options' value='<?php _e("Import settings", 'relevanssi'); ?>' class='button' />

	<p><?php _e("Note! Make sure you've got correct settings from a right version of Relevanssi. Settings from a different version of Relevanssi may or may not work and may or may not mess your settings.", "relevanssi"); ?></p>
	
	</form>
</div>

	<?php

	relevanssi_sidebar();
}

function relevanssi_show_stopwords() {
	global $wpdb, $relevanssi_stopword_table, $wp_version;

	_e("<p>Enter a word here to add it to the list of stopwords. The word will automatically be removed from the index, so re-indexing is not necessary. You can enter many words at the same time, separate words with commas.</p>", 'relevanssi');

?><label for="addstopword"><p><?php _e("Stopword(s) to add: ", 'relevanssi'); ?><textarea name="addstopword" rows="2" cols="40"></textarea>
<input type="submit" value="<?php _e("Add", 'relevanssi'); ?>" class='button' /></p></label>
<?php

	_e("<p>Here's a list of stopwords in the database. Click a word to remove it from stopwords. Removing stopwords won't automatically return them to index, so you need to re-index all posts after removing stopwords to get those words back to index.", 'relevanssi');

	if (function_exists("plugins_url")) {
		if (version_compare($wp_version, '2.8dev', '>' )) {
			$src = plugins_url('delete.png', __FILE__);
		}
		else {
			$src = plugins_url('relevanssi-premium/delete.png');
		}
	}
	else {
		// We can't check, so let's assume something sensible
		$src = '/wp-content/plugins/relevanssi-premium/delete.png';
	}
	
	echo "<ul>";
	$results = $wpdb->get_results("SELECT * FROM $relevanssi_stopword_table");
	$exportlist = array();
	foreach ($results as $stopword) {
		$sw = $stopword->stopword; 
		printf('<li style="display: inline;"><input type="submit" name="removestopword" value="%s"/></li>', $sw, $src, $sw);
		array_push($exportlist, $sw);
	}
	echo "</ul>";
	
?>
<p><input type="submit" name="removeallstopwords" value="<?php _e('Remove all stopwords', 'relevanssi'); ?>" class='button' /></p>
<?php

	$exportlist = implode(", ", $exportlist);
	
?>
<p><?php _e("Here's a list of stopwords you can use to export the stopwords to another blog.", "relevanssi"); ?></p>

<textarea name="stopwords" rows="2" cols="40"><?php echo $exportlist; ?></textarea>
<?php

}

function relevanssi_sidebar() {
	if (function_exists("plugins_url")) {
		global $wp_version;
		if (version_compare($wp_version, '2.8dev', '>' )) {
			$facebooklogo = plugins_url('facebooklogo.jpg', __FILE__);
		}
		else {
			$facebooklogo = plugins_url('relevanssi-premium/facebooklogo.jpg');
		}
	}
	else {
		// We can't check, so let's assume something sensible
		$facebooklogo = '/wp-content/plugins/relevanssi-premium/facebooklogo.jpg';
	}

	echo <<<EOH
<div class="postbox-container" style="width:20%; margin-top: 35px; margin-left: 15px;">
	<div class="metabox-holder">	
		<div class="meta-box-sortables" style="min-height: 0">
			<div id="relevanssi_donate" class="postbox">
			<h3 class="hndle"><span>Thank you!</span></h3>
			<div class="inside">
			<p>Thank you for buying Relevanssi Premium! Your support makes it possible for me to keep working on this plugin.</p>
			<p>I can do custom hacks based on Relevanssi and other WordPress development. If you need someone to fix your WordPress, just ask me for a quote.</p>
			</div>
		</div>
	</div>

		<div class="meta-box-sortables" style="min-height: 0">
			<div id="relevanssi_donate" class="postbox">
			<h3 class="hndle"><span>Relevanssi in Facebook</span></h3>
			<div class="inside">
			<div style="float: left; margin-right: 5px"><img src="$facebooklogo" width="45" height="43" alt="Facebook" /></div>
			<p><a href="http://www.facebook.com/relevanssi">Check
			out the Relevanssi page in Facebook</a> for news and updates about your favourite plugin.</p>
			</div>
		</div>
	</div>

		<div class="meta-box-sortables" style="min-height: 0">
			<div id="relevanssi_donate" class="postbox">
			<h3 class="hndle"><span>Help and support</span></h3>
			<div class="inside">
			<p>For Relevanssi support, see:</p>
			
			<p>
			- <a href="http://www.relevanssi.com/support/">Plugin support page</a><br />
			- <a href="http://wordpress.org/tags/relevanssi?forum_id=10">WordPress.org forum</a><br />
			- mikko@relevanssi.com
			</p>
			</div>
		</div>
	</div>

		<div class="meta-box-sortables" style="min-height: 0">
			<div id="relevanssi_donate" class="postbox">
			<h3 class="hndle"><span>Did you know this feature?</span></h3>
			<div class="inside">
			<p><strong>[noindex]</strong></p>
			
			<p>Wrap the parts of the posts you don't want to include in the index in [noindex]
			shortcode.</p>

			<p><strong>[search]</strong></p>
			
			<p>Use the [search] shortcode to build easy links to search results.</p>
			
			<p><strong>Stemmer</strong></p>
			
			<p>Enable the English-language stemmer by adding this line in your functions.php:</p>
			
			<p>add_filter('relevanssi_stemmer', 'relevanssi_simple_english_stemmer');</p>

			<p><strong>Boolean NOT</strong></p>
			
			<p>To get results without particular word, use the minus operator</p>
			
			<p><em>cats -dogs</em></p>
			</div>
		</div>
	</div>
</div>
</div>
EOH;
}

function relevanssi_add_metaboxes() {
	global $post;
	add_meta_box( 
        'relevanssi_hidebox',
        __( 'Relevanssi post controls', 'relevanssi' ),
    	'relevanssi_hide_metabox',
     	$post->post_type
   	 );
}

function relevanssi_hide_metabox() {
	wp_nonce_field(plugin_basename(__FILE__), 'relevanssi_hidepost');

	global $post;
	$hide = get_post_meta($post->ID, '_relevanssi_hide_post', true);
	$check = '';
	if ('on' == $hide) {
		$check = ' checked="checked" ';
	}
	
	// The actual fields for data entry
	echo '<input type="checkbox" id="relevanssi_hide_post" name="relevanssi_hide_post" ' . $check . ' />';
	echo ' <label for="relevanssi_hide_post">';
	_e("Exclude this post or page from the index.", 'relevanssi');
	echo '</label> ';
}

function relevanssi_save_postdata($post_id) {
	// verify if this is an auto save routine. 
	// If it is our form has not been submitted, so we dont want to do anything
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
		return;

	if (isset($_POST['relevanssi_hidepost'])) {
		if (!wp_verify_nonce($_POST['relevanssi_hidepost'], plugin_basename( __FILE__ )))
			return;
	}

	// Check permissions
	if (isset($_POST['post_type'])) {
		if ('page' == $_POST['post_type']) {
			if (!current_user_can('edit_page', $post_id))
				return;
		}
		else {
			if (!current_user_can('edit_post', $post_id))
				return;
		}
	}

	if (isset($_POST['relevanssi_hide_post'])) {
		$hide = $_POST['relevanssi_hide_post'];
	}
	else {
		$hide = '';
	}

	if ('on' == $hide) {
		relevanssi_delete($post_id);
	}

	update_post_meta($post_id, '_relevanssi_hide_post', $hide);
}

function relevanssi_get_words() {
	global $wpdb, $relevanssi_table;
	
	$q = "SELECT term, title + content + comment + tag + link + author + category + excerpt + taxonomy + customfield as c FROM $relevanssi_table GROUP BY term";
	$q = apply_filters('relevanssi_get_words_query', $q);
	$results = $wpdb->get_results($q);
	
	$words = array();
	foreach ($results as $result) {
		$words[$result->term] = $result->c;
	}
	
	return $words;
}

/**
 * This function will prevent the default search from running, when Relevanssi is
 * active.
 * Thanks to John Blackbourne.
 */
function relevanssi_prevent_default_request( $request, $query ) {
	if ($query->is_search) {
		if (!is_admin())
			$request = false;
		else if ('on' == get_option('relevanssi_admin_search'))
			$request = false;
	}
	return $request;
}

?>