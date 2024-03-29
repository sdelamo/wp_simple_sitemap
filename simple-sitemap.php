<?php
/*
Plugin Name: Simple Sitemap
Plugin URI: http://www.presscoders.com/plugins/free-plugins/simple-sitemap/
Description: HTML sitemap to display content as a single linked list of posts and pages, or as groups sorted by taxonomy (via a drop-down box).
Version: 1.41
Author: David Gwyer
Author URI: http://www.presscoders.com
*/



/**
 * Constants
 */

define( 'WPSS_URL', plugin_dir_url(__FILE__) );


/*  Copyright 2009 David Gwyer (email : d.v.gwyer@presscoders.com)

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

/* Next Plugin release to do:
- Show all the posts in each category not just the maximum allowed in Settings -> Reading.

*/

/* Future to do:
- Be able to sort ascending/descending in addition to the sort drop down options for each list.
- Have styling (in Plugins options page textarea?) to control sitemap rendering.
- Add all in-line styles to external style sheet.
- Options to display custom post types, with ability to show which custom post types to display or not display, and in what order?
- Check which pages the simple-sitemap css is loaded. Don't really want it showing on pages that are not sitemap pages. It's being loaded on all pages currently?
- Consider adding a drop down in Plugin options to show the page hierchy as it appears in 'Pages' (i.e. the way it works now), or to show it via a defined custom menu hierarchy.
- Include option to show only posts/or pages.
- Add option to remove certain tags, categories, or posts.
- Add option to include CPT posts underneath.
- Add option to show/hide drop down boxes on sitemap page.
- Use the 'prepare' WP feature when querying the db directly.
*/

/* wpss_ prefix is derived from [W]ord[P]ress [s]imple [s]itemap. */
add_shortcode('simple-sitemap', 'wpss_gen');
add_filter('query_vars', 'parameter_queryvars' );
add_action('wp_print_styles', 'add_my_stylesheet');

register_activation_hook(__FILE__, 'wpss_add_defaults');
register_uninstall_hook(__FILE__, 'wpss_delete_plugin_options');
add_action('admin_init', 'wpss_init' );
add_action('admin_menu', 'wpss_add_options_page');



// ***************************************
// *** START - Create Admin Options    ***
// ***************************************

/* Delete options table entries ONLY when plugin deactivated AND deleted. */
function wpss_delete_plugin_options() {
	delete_option('wpss_options');
}

/* Define default option settings. */
function wpss_add_defaults() {
	$tmp = get_option('wpss_options');
	if( ( (isset($tmp['chk_default_options_db']) && $tmp['chk_default_options_db']=='1')) || (!is_array($tmp)) ) {
		delete_option('wpss_options');
		$arr = array(	"drp_pages_default"			=> "post_title",
						"drp_posts_default"			=> "title",
						"chk_default_options_db"	=> "0",
						"rdo_display_order"			=> "pages_left",
						"txt_page_ids"				=> ""
		);
		update_option( 'wpss_options', $arr );
	}
}


/* Init plugin options to white list our options. */
function wpss_init(){
	/* This is primarily to check newly added options have correct initial values. */
	$tmp = get_option('wpss_options');
	if(!$tmp['rdo_display_order']) /* Check radio buttons have a starting value. */
	{
		$tmp["rdo_display_order"] = "pages_left";
		update_option('wpss_options', $tmp);
	}
	register_setting( 'wpss_plugin_options', 'wpss_options', 'wpss_validate_options' );
	
}


function wpss_textdomain() {

	if ( function_exists('load_plugin_textdomain') ) {
		load_plugin_textdomain( 'wpss', WPSS_URL . 'languages', 'simple-sitemap/languages' );
	}

}
add_action( 'init', 'wpss_textdomain' );

/* Add menu page. */
function wpss_add_options_page() {
	add_options_page('Simple Sitemap Options Page', 'Simple Sitemap', 'manage_options', __FILE__, 'wpss_render_form');
}

/* Record Plugin activation error messages. */
add_action( 'activated_plugin', 'wpss_save_error' );
function wpss_save_error(){
    update_option( 'pc_plugin_error', ob_get_contents() );
}

/* Draw the menu page itself. */
function wpss_render_form() {
	?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2><?php _e('Simple Sitemap Options','wpss'); ?></h2>
		<div style="background:#eee;border: 1px dashed #ccc;font-size: 13px;margin: 20px 0 10px 0;padding: 5px 0 5px 8px;"><?php _e('To display the Simple Sitemap on a post/page, enter the following','wpss'); ?> <a href="http://codex.wordpress.org/Shortcode_API" target="_blank">shortcode</a>: <b>[simple-sitemap]</b></div>
		<form method="post" action="options.php">
			<?php settings_fields('wpss_plugin_options'); ?>
			<?php $options = get_option('wpss_options'); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Content Display Order','wpss'); ?></th>
					<td>
						<label><input name="wpss_options[rdo_display_order]" type="radio" value="posts_left" <?php checked('posts_left', $options['rdo_display_order']); ?> /> <?php _e('Display','wpss'); ?> <u><?php _e('Posts','wpss'); ?></u> <?php _e('on LHS','wpss'); ?><span style="margin-left:10px;color: #999;">(<?php _e('with pages on the RHS','wpss'); ?>)</span></label><br />
						<label><input name="wpss_options[rdo_display_order]" type="radio" value="pages_left" <?php checked('pages_left', $options['rdo_display_order']); ?> /> <?php _e('Display','wpss'); ?> <u><?php _e('Pages','wpss'); ?></u> <?php _e('on LHS','wpss'); ?><span style="margin-left:10px;color: #999;">(<?php _e('with posts on the RHS','wpss'); ?>)</span></label><br />
					</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('Pages Drop-Down Default','wpss'); ?></th>
					<td>
						<select style="width:90px;" name='wpss_options[drp_pages_default]'>
							<option value='post_title' <?php selected('post_title', $options['drp_pages_default']); ?>><?php _e('Title','wpss'); ?></option>
							<option value='post_date' <?php selected('post_date', $options['drp_pages_default']); ?>><?php _e('Date','wpss'); ?></option>
							<option value='post_author' <?php selected('post_author', $options['drp_pages_default']); ?>><?php _e('Author','wpss'); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('Posts Drop-Down Default','wpss'); ?></th>
					<td>
						<select style="width:90px;" name='wpss_options[drp_posts_default]'>
							<option value='title' <?php selected('title', $options['drp_posts_default']); ?>><?php _e('Title','wpss'); ?></option>
							<option value='date' <?php selected('date', $options['drp_posts_default']); ?>><?php _e('Date','wpss'); ?></option>
							<option value='author' <?php selected('author', $options['drp_posts_default']); ?>><?php _e('Author','wpss'); ?></option>
							<option value='category' <?php selected('category', $options['drp_posts_default']); ?>><?php _e('Category','wpss'); ?></option>
							<option value='tags' <?php selected('tags', $options['drp_posts_default']); ?>><?php _e('Tags','wpss'); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('Exclude Pages','wpss'); ?></th>
					<td>
						<input type="text" size="30" name="wpss_options[txt_page_ids]" value="<?php echo $options['txt_page_ids']; ?>" /><span style="margin-left:10px;color: #999;"><?php _e("Enter a comma separated list of Page ID's",'wpss'); ?>.</span>
					</td>
				</tr>

				<tr><td colspan="2"><div style="margin-top:10px;"></div></td></tr>

				<tr valign="top" style="border-top:#dddddd 1px solid;">
					<th scope="row"><?php _e('Database Options','wpss'); ?></th>
					<td>
						<label><input name="wpss_options[chk_default_options_db]" type="checkbox" value="1" <?php if (isset($options['chk_default_options_db'])) { checked('1', $options['chk_default_options_db']); } ?> /> <?php _e('Restore Plugin defaults upon deactivation/reactivation','wpss'); ?></label><br /><span style="color:#666666;margin-left:2px;"><?php _e('Only check this if you want to reset plugin settings upon reactivation','wpss'); ?></span>
					</td>
				</tr>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes','wpss') ?>" />
			</p>
		</form>

		<div style="margin-top:15px;">
			<p style="margin-bottom:10px;"><?php _e('If you use this Plugin on your website','wpss'); ?> <b><em><?php _e('please','wpss'); ?></em></b> <?php _e('consider making a','wpss'); ?> <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=UUFZZU35RZPW8" target="_blank"><?php _e('donation','wpss'); ?></a> <?php _e('to support continued development. Thanks.','wpss'); ?>&nbsp;&nbsp;:-)</p>
		</div>

		<div style="clear:both;">
			<p>
				<a href="http://www.facebook.com/PressCoders" title="Our Facebook page" target="_blank"><img src="<?php echo plugins_url(); ?>/simple-sitemap/images/facebook.png" /></a><a href="http://www.twitter.com/dgwyer" title="Follow on Twitter" target="_blank"><img src="<?php echo plugins_url(); ?>/simple-sitemap/images/twitter.png" /></a>&nbsp;<input class="button" style="vertical-align:12px;" type="button" value="Visit Our Site" onClick="window.open('http://www.presscoders.com')">&nbsp;<input class="button" style="vertical-align:12px;" type="button" value="Free Responsive Theme!" onClick="window.open('http://www.presscoders.com/designfolio')">
			</p>
		</div>

	</div>
	<?php	
}

/* Sanitize and validate input. Accepts an array, return a sanitized array. */
function wpss_validate_options($input) {
	// Strip html from textboxes
	// e.g. $input['textbox'] =  wp_filter_nohtml_kses($input['textbox']);

	$input['txt_page_ids'] = sanitize_text_field( $input['txt_page_ids'] );

	return $input;
}

// ***************************************
// *** END - Create Admin Options    ***
// ***************************************

// ---------------------------------------------------------------------------------

// ***************************************
// *** START - Plugin Core Functions   ***
// ***************************************

function add_my_stylesheet() {
	$styleSheet = WP_PLUGIN_URL.'/simple-sitemap/css/ss_style.css';
	wp_register_style('myStyleSheets', $styleSheet);
	wp_enqueue_style( 'myStyleSheets');
}

function parameter_queryvars( $qvars ) {
	$qvars[] = 'pagesort';
	$qvars[] = 'postsort';
	return $qvars;
}

function page_params() {
	global $wp_query;
	if (isset($wp_query->query_vars['pagesort'])) {
		return $wp_query->query_vars['pagesort'];
	}
}

function post_params() {
	global $wp_query;
	if (isset($wp_query->query_vars['postsort'])) {
		return $wp_query->query_vars['postsort'];
	}
}

/* Shortcode function. */
function wpss_gen() {
	ob_start(); // start output caching (so that existing content in the [simple-sitemap] post doesn't get shoved to the bottom of the post

	global $post; //wordpress post global object
	$permalink_structure = get_option( 'permalink_structure' );
	if($permalink_structure == null) {
		$link_url = get_permalink($post->ID);
		$query_symbol = '&';	// add a '&' character prefix onto the query string
	}
	else if((substr($permalink_structure, -1)) != '/') {
		$link_url = get_permalink($post->ID).'/';
		$query_symbol = '?';	// add a '?' character prefix onto the query string
	}
	else {
		$link_url = get_permalink($post->ID);
		$query_symbol = '?';	// add a '?' character prefix onto the query string
	}

    /* Sort by value in drop down box (make sure drop down default is title which is the default used by wp_list_pages). */
    $page_params = page_params();
    $post_params = post_params();

	$opt = get_option('wpss_options');
	//echo '$opt[\'drp_pages_default\'] = '.$opt['drp_pages_default'].'<br />';
	//echo '$opt[\'drp_posts_default\'] = '.$opt['drp_posts_default'].'<br />';

	if($page_params == null ) {
		if($opt['drp_pages_default'] == "post_title") {	$page_params = 'menu_order, post_title'; }
		else if ($opt['drp_pages_default'] == "post_date") { $page_params = 'post_date'; }
		else { $page_params = 'post_author'; }
	}
	$page_args = array( 'sort_column' => $page_params, 'title_li' => '');
	if( !empty( $opt['txt_page_ids'] ) ) $page_args['exclude'] = $opt['txt_page_ids'];

	if($post_params == null ) {
		if($opt['drp_posts_default'] == "title") { $post_params = 'title'; }
		else if ($opt['drp_posts_default'] == "date") { $post_params = 'date'; }
		else if ($opt['drp_posts_default'] == "author") { $post_params = 'author'; }
		else if ($opt['drp_posts_default'] == "category") { $post_params = 'category'; }
		else { $post_params = 'tags'; }
	}
	$post_args = array( 'orderby' => $post_params, 'posts_per_page' => -1, 'order' => 'asc' );

	//echo "<br />Page Args: <br />";
	//print_r($page_args);
	//echo "<br /><br />Posts Args: <br />";
	//print_r($post_args);
	//echo "<br /><br />Page Params: $page_params";
	//echo "<br />Post Params: $post_params";

	/* Initialise to "" to prevent undefined variable error. */
	$pt1=$pd1=$pa1=$pt2=$pd2=$pa2=$pc2=$ptg2="";

	/* Page drop down box. */
	if($page_params == 'menu_order, post_title') {
		$pt1 = "selected='selected'";
	}
	else if($page_params == 'post_date') {
		$pd1 = "selected='selected'";
	}
	else if($page_params == 'post_author') {
		$pa1 = "selected='selected'";
	}

	/* Post drop down box. */
	if($post_params == 'title') {
		$pt2 = "selected='selected'";
	}
	else if($post_params == 'date') {
		$pd2 = "selected='selected'";
	}
	else if($post_params == 'author') {
		$pa2 = "selected='selected'";
	}
	else if($post_params == 'category') {
		$pc2 = "selected='selected'";
	}
	else if($post_params == 'tags') {
		$ptg2 = "selected='selected'";
	}
?>
<div class="ss_wrapper">
		<?php
			$opt = get_option('wpss_options');
			if($opt['rdo_display_order'] == "pages_left") {
				$pages_float = "float:left;";
			}
			else {
				$pages_float = "float:right;";
			}
		?>
		<div id="ss_pages" style="<?php echo $pages_float ?>">
			<h2 class='page_heading'><?php _e('Pages','wpss'); ?></h2>
			
			<div id="page_drop_down">
				<form name="page_drop_form" id="page_drop_form">
					<span id="page_dd_label"><?php _e('Show pages by:','wpss'); ?></span>
					<select name="page_drop_select" OnChange="location.href=page_drop_form.page_drop_select.options[selectedIndex].value">
						<option value="<?php echo $link_url.$query_symbol.'pagesort=post_title'; ?>" <?php echo $pt1; ?>><?php _e('Title','wpss'); ?></option>
						<option value="<?php echo $link_url.$query_symbol.'pagesort=post_date'; ?>" <?php echo $pd1; ?>><?php _e('Date','wpss'); ?></option>
						<option value="<?php echo $link_url.$query_symbol.'pagesort=post_author'; ?>" <?php echo $pa1; ?>><?php _e('Author','wpss'); ?></option>
					</select>
				</form>
			</div>
			<?php
			if(strpos($page_params, 'post_date') !== false) {
				echo '<ul class="page_item_list">';
				$page_args=array('sort_order' => 'desc', 'sort_column' => 'post_date', 'date_format' => ' (m.d.y)', 'show_date'=> 'created', 'title_li' => '');
				if( !empty( $opt['txt_page_ids'] ) ) $page_args['exclude'] = $opt['txt_page_ids'];
				wp_list_pages($page_args); // show the sorted pages
				echo '</ul>';
			}
			elseif(strpos($page_params, 'post_author') !== false) {
				$authors = get_users(); //gets registered users
				foreach ($authors as $author) {
					$empty_page_args=array('echo' => 0, 'authors' => $author->ID, 'title_li' => '');
					$empty_test = wp_list_pages($empty_page_args); // test for authors with zero pages
					//echo '$empty_test = '.$empty_test;
					
					if($empty_test != null || $empty_test != "") {
						echo "<div class='page_author'>$author->display_name</div>";
						echo "<div class=\"ss_date_header\"><ul class=\"page_item_list\">";
						$page_args=array('authors' => $author->ID, 'title_li' => '');
						if( !empty( $opt['txt_page_ids'] ) ) $page_args['exclude'] = $opt['txt_page_ids'];
						wp_list_pages($page_args);
						echo "</ul></div>";
					}
					else {
						echo "<div class='page_author'>$author->display_name <span class=\"ss_sticky\">("; _e('no pages published','wpss'); echo ")</span></div>";
					}
				} ?>
			<?php
			}
			else { /* default = title */
				echo '<ul class="page_item_list">';
				wp_list_pages($page_args); /* Show sorted pages with default $page_args. */
				echo '</ul>';
			}
			?>
		</div><!--ss_pages -->
		<div id="ss_content_sep"></div>
		<?php
			$opt = get_option('wpss_options');
			if($opt['rdo_display_order'] == "pages_left") {
				$posts_float = "float:right;";
			}
			else {
				$posts_float = "float:left;";
			}
		?>
		<div id="ss_posts" style="<?php echo $posts_float ?>">
			<h2 class='post_heading'><?php _e('Posts','wpss'); ?></h2>
			<div id="post_drop_down">
				<form name="post_drop_form" id="post_drop_form">
					<span id="post_dd_label"><?php _e('Show posts by:','wpss'); ?></span>
					<select name="post_drop_select" OnChange="location.href=post_drop_form.post_drop_select.options[selectedIndex].value">
						<option value="<?php echo $link_url.$query_symbol.'postsort=title'; ?>" <?php echo $pt2; ?>><?php _e('Title','wpss'); ?></option>
						<option value="<?php echo $link_url.$query_symbol.'postsort=date'; ?>" <?php echo $pd2; ?>><?php _e('Date','wpss'); ?></option>
						<option value="<?php echo $link_url.$query_symbol.'postsort=author'; ?>" <?php echo $pa2; ?>><?php _e('Author','wpss'); ?></option>
						<option value="<?php echo $link_url.$query_symbol.'postsort=category'; ?>" <?php echo $pc2; ?>><?php _e('Category','wpss'); ?></option>
						<option value="<?php echo $link_url.$query_symbol.'postsort=tags'; ?>" <?php echo $ptg2; ?>><?php _e('Tags','wpss'); ?></option>	
					</select>
				</form>
			</div>
			<?php
			if(strpos($post_params, 'category') !== false) {
				$categories = get_categories();
				foreach ($categories as $category) {
					$category_link = get_category_link($category->term_id);
					$cat_count = $category->category_count;

					echo '<div class="ss_cat_header"><a href="'.$category_link.'">'.ucwords($category->cat_name).'</a> ';
					query_posts('posts_per_page=-1&post_status=publish&cat='.$category->term_id); // show the sorted posts ?>
					<?php
						global $wp_query;	
						echo '('.$wp_query->post_count.')</div>'; ?>
					<?php
					if (have_posts()) :
						echo '<div class="post_item_list"><ul class="post_item_list">';
						while (have_posts()) :
							the_post(); ?>
							<li class="post_item">
								<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e('Permanent Link to','wpss'); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
							</li>
						<?php  endwhile;
						echo '</ul></div>';
					endif;
					wp_reset_query();
				}
			}
			else if(strpos($post_params, 'author') !== false) {
				$authors = get_users(); //gets registered users
				foreach ($authors as $author) {
					echo '<a href="'.get_author_posts_url($author->ID).'">'.$author->display_name.'</a> ';
					query_posts('posts_per_page=-1&post_status=publish&author='.$author->ID); // show the sorted posts ?>
					<?php
					global $wp_query;	
					echo '('.$wp_query->post_count.')'; ?>
					<?php
					if (have_posts()) :
						echo '<div class="post_item_list"><ul class="post_item_list">';
						while (have_posts()) :
							the_post(); ?>
							<li class="post_item">
								<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e('Permanent Link to','wpss'); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
							</li>
						<?php  endwhile;
						echo '</ul></div>';
					endif;
					wp_reset_query();
				}
			}
			else if(strpos($post_params, 'tags') !== false) {
				$post_tags = get_tags();
				echo '<div class="ss_tag_header">';
				foreach ($post_tags as $tag){
					$tag_link = get_tag_link($tag->term_id);
					echo "<a href='{$tag_link}' title='{$tag->name} Tag' class='{$tag->slug}'>";
					echo "{$tag->name}</a> ($tag->count)";

					query_posts('posts_per_page=-1&post_status=publish&tag='.$tag->slug); // show posts ?>
					<?php
					if (have_posts()) :
						echo '<div class="post_item_list"><ul class="post_item_list">';
						while (have_posts()) :
							the_post(); ?>
							<li class="post_item">
								<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e('Permanent Link to','wpss'); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
							</li>
						<?php  endwhile;
						echo '</ul></div>';
					endif;
					wp_reset_query();
				}
				echo '</div>';
			}
			else if(strpos($post_params, 'date') !== false) {
				?><div class="ss_date_header">
				<?php
				global $wpdb;
				$months = $wpdb->get_results("SELECT DISTINCT MONTH(post_date) AS month , YEAR(post_date) AS year FROM $wpdb->posts WHERE post_status = 'publish' and post_date <= now( ) and post_type = 'post' GROUP BY month, year ORDER BY post_date DESC");
				foreach($months as $curr_month){
					query_posts('posts_per_page=-1&post_status=publish&monthnum='.$curr_month->month.'&year='.$curr_month->year); // show posts ?>
					<?php
					global $wp_query;	
					echo "<a href=\"";
					echo get_month_link($curr_month->year, $curr_month->month);
					echo '">'.date( 'F', mktime(0, 0, 0, $curr_month->month) ).' '.$curr_month->year.'</a> ('.$wp_query->post_count.')'; ?>
					<?php
					if (have_posts()) :
						echo '<div class="post_item_list"><ul class="post_item_list">';
						while (have_posts()) :
							the_post(); ?>
							<li class="post_item">
								<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e('Permanent Link to','wpss'); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
							</li>
						<?php  endwhile;
						echo '</ul></div>';
					endif;
					wp_reset_query();
				} ?>
				</div>
			<?php
			}
			else { /* default = title */
			?>
				<?php query_posts($post_args); /* Show sorted posts with default $post_args. */
					if (have_posts()) :
						echo '<ul class="post_item_list">';
						while (have_posts()) :
							the_post();
							$sticky="";
							if(is_sticky(get_the_ID())) { $sticky=" (sticky post)"; } ?>
							<li class="post_item">
								<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e('Permanent Link to','wpss'); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
								<?php if ( $sticky == ' (sticky post)' ) : ?>
									<span class="ss_sticky"><?php echo $sticky; ?></span>
								<?php endif; ?>
							</li>
						<?php  endwhile;
						echo '</ul>';
					endif;
					wp_reset_query();
			}
			?>
		</div><!--ss_posts-->
</div>
<?php

$output = ob_get_contents();;
ob_end_clean();

return $output;

}
// ***************************************
// *** END - Plugin Core Functions     ***
// ***************************************
?>