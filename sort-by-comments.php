<?php
/*
Plugin Name: Sort by Comments
Description: Adds some forum functionality to WordPress. Changes the order of posts so that the most recently commented posts show up first. Also displays last comment with the posts. Changes affect home page only. Intended for use with the <a href="http://svn.automattic.com/wpcom-themes/prologue/">Prologue theme</a>.
Author: Thomas Misund Hansen
Version: 0.1.1
Author URI: http://thomas.hemmeligadresse.com/
*/

/*  Copyright 2008 Thomas Misund Hansen (email: sort-by-comments@hemmeligadresse.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/**
 * Sorts $posts on home page by [published date | most recent comment], whichever is more recent.
 */
function sbc_sortPostsByMostRecentComment() {
	if(is_home()&&!$_REQUEST['kill_sbc']):
		global $wpdb,$posts,$id,$tablecomments,$wp_query;
		$postCounter=0;
		// TODO Add an option to override posts_per_page?
		// $postsToDisplay=<integer>;
	
		if(have_posts()):

			// Assign each post with a timestamp to sort by
			// = Most recent comment's comment date if commented, else post's date
			while(have_posts()):
				the_post();
				$postCounter++;

				$comments = $wpdb->get_results("SELECT comment_date FROM $tablecomments WHERE comment_post_ID = '$id' AND comment_approved = '1' ORDER BY comment_date DESC LIMIT 1");

				if ($comments)
					$posts[$postCounter-1]->timeToSortBy=strtotime($comments[0]->comment_date);
				else
					$posts[$postCounter-1]->timeToSortBy=strtotime(get_the_time("Y-m-d H:i:s"));

			endwhile; // have posts


			// Rearrange the post order by the timestamp we set
			$posts=sbc_sortArrayOfObjectsByProperty($posts);

			// Fool WP to believe it only has a given amount of posts,
			// so that it doesn't display a million posts on the front page
			$wp_query->post_count=get_option('posts_per_page');
			// TODO Add option to override posts_per_page?
			//$wp_query->post_count=$postsToDisplay;

		endif; // have posts
	endif; // is home
} // end sbc_sortPostsByMostRecentComment()

/**
 * Sorts an array of objects by the value of one of the object properties.
 *
 * @param array $array
 * @param key value $id
 * @param boolean $sort_ascending
 * @return array
 */
function sbc_sortArrayOfObjectsByProperty($array,$property="timeToSortBy",$sort_ascending=false) {
	$temp_array = array();
	while(count($array)>0) {
		$lowest_property=0;
		$index=0;
		foreach($array as $item) {
			if(isset($item->$property)) {
				if ($array[$lowest_property]->$property) {
					if ($item->$property<$array[$lowest_property]->$property) {
						$lowest_property = $index;
					}
				}
			}
			$index++;
		}
		$temp_array[] = $array[$lowest_property];
		$array = array_merge(array_slice($array, 0,$lowest_property), array_slice($array, $lowest_property+1));
	}
	if($sort_ascending) {
		return $temp_array;
	} else {
		return array_reverse($temp_array);
	}
} // end sbc_sortArrayOfObjectsByProperty()

/**
 * Removes post limits from query so that all posts show on the front page.
 *
 * Removes post limits from query so that all posts show on the front page. If this is removed, the plugin will only sort posts that would normally appear on the first page.
 *
 * @param string $limits
 * @return string
 */
function sbc_setLimits($limits) {
	if(is_home()&&!$_REQUEST['kill_sbc']):
		// remove limits
		return "";
	endif; // is home

	// not home <=> sbc not activated, limits should be default
	return $limits;
}

/**
 * Displays the most recent comment below each post on the home page.
 *
 * @param string $content
 */
function sbc_displayLastComment($content){
	if(is_home()&&!$_REQUEST['kill_sbc']):
		global $wpdb,$tablecomments,$id;

		$comments = $wpdb->get_results("SELECT comment_author,comment_content FROM $tablecomments WHERE comment_post_ID = '$id' AND comment_approved = '1' ORDER BY comment_date DESC LIMIT 1");

		if ($comments)
			$content.=
"
<h3>Last reply:</h3>
<p>".$comments[0]->comment_author.": ".$comments[0]->comment_content."</p>
";
		$content.=
'
<p><span class="meta"><a href="'.get_comments_link().'">View all replies</a> | <a href="'.get_permalink().'#commentform">Reply to this</a></span></p>
';
	endif; // is home
	return $content;
} // end sbc_displayLastComment

/**
 * Displays a link to normal view, since paging is not available. ?kill_sbc=true
 */
function sbc_linkToNormalView(){
	if(is_home()&&!$_REQUEST['kill_sbc']):
		if (headers_sent()) {
			echo
'
<div>
    <p><a href="?kill_sbc=true" title="View all posts in reverse chronological order, sorted by post date">View all posts</a></p>
</div>
';
		}
	endif; // is home
}

// Connect to hooks
add_filter('the_content','sbc_displayLastComment');
add_filter('post_limits','sbc_setLimits');
add_action('wp','sbc_sortPostsByMostRecentComment');
add_action('loop_end','sbc_linkToNormalView');
?>
