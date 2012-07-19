<?php
/*
Plugin Name: Sweepstakes
Plugin URI: https://github.com/not-only-code/sweepstakes
Description: adds sweepstakes on site when users can register with a serialized code 
Version: 0.1
Author: Carlos Sanz García
Author URI: http://codingsomething.wordpress.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


////////////////////////////////////////////////////////////////////////////////////////


if ( !function_exists('_debug') ):
function _debug( $message ) {
	   
	if ( WP_DEBUG === true ):
		 
		if ( is_array( $message ) || is_object( $message ) ) {
			
			error_log( print_r( $message, true ) );
			
		} else {
			
			error_log( $message );
		}
			 
	 endif;
}
endif;
  

////////////////////////////////////////////////////////////////////////////////////////

/**
 * Define Constants
 *
 * @since 0.1
 */
if (!defined("SW_VERSION")) 		define("SW_VERSION", '0.1');
if (!defined("SW_PREFIX")) 			define("SW_PREFIX", '_sw_');
if (!defined("SW_OPTIONS_NAME")) 	define("DTP_OPTIONS_NAME", 'sw_options');
if (!defined("PHP_EOL")) 			define("PHP_EOL", "\r\n");

load_plugin_textdomain( 'promos', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

////////////////////////////////////////////////////////////////////////////////////////


function sw_enqueue_scripts() {
	if (is_admin()) {
		wp_enqueue_style('promos', plugins_url( 'assets/css/admin-styles.css' , __FILE__ ));
		wp_enqueue_script('promos', plugins_url( 'assets/js/admin-scripts.js' , __FILE__ ), array('jquery'), null, true);
	} else {
		///////////////////////
	}
}
add_action('init', 'sw_enqueue_scripts');
	
	
/**
 * creates post type 
 *
 * @package Site Promos
 *
 * @since 0.1
**/	
function sw_post_types() {
	// labels
	$labels = array(
		'name' => __( 'Promos', 'promos'),
		'singular_name' => __( 'Promo', 'promos'),
		'add_new' => __( 'Add new promo', 'promos'),
		'add_new_item' => __( 'Add new promo', 'promos'),
		'edit' => __( 'Edit'),
		'edit_item' => __( 'Edit promo', 'promos'),
		'new_item' => __( 'New promo', 'promos'),
		'view' => __( 'View promo', 'promos'),
		'view_item' => __( 'View promo', 'promos'),
		'search_items' => __( 'Search promos', 'promos'),
		'not_found' => __( 'No promo found', 'promos'),
		'not_found_in_trash' => __( 'No promos in trash', 'promos'),
		'parent' => __( 'Parent promo', 'promos'),
		'menu_name' => __( 'Promos', 'promos')
	);
	
	$descripcion = __( 'Specific content for promos' ,'promos');
	
	$args = array(
		'labels' => $labels,
		'description' => $descripcion,
		'public' => true,
		'publicly_queryable' => true,
		'exclude_from_search' => false,
		'show_ui' => true,
		'capability_type' => 'post',
		'menu_position' => 7,
		'hierarchical' => true,
		'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		'taxonomies' => array(),
		'has_archive' => false,
		'rewrite' => array('slug' => 'promo', 'in_front' => false),
		'query_var' => true,
		'can_export' => true,
		'show_in_nav_menus' => true
		);
	
	//register post type
	register_post_type( 'promo', $args);
	
}
add_action('init', 'sw_post_types');



/**
 * prevent change 'publish' status from any default page, maintains 'password'
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
/*
function sw_check_status_page( $post_id, $post_after, $post_before ) {
	global $wpdb, $default_theme_pages;
	
	if ( is_default_page($post_after) && $post_after->post_status != 'publish' )
		wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
 }
add_action('post_updated', 'sw_check_status_page', 640, 3);
*/



/**
 * prevent move to trash any default page
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_trashed_post($post_id = false) {
	if ( !$post_id ) return;
	
	if ( is_default_page($post_id) )
		wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
}
add_action('trashed_post', 'sw_trashed_post', 640);



/**
 * adds metabox to control users and promo code
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_meta_boxes() {
	global $post;
		
	if ( $post->post_parent != 0 ) return;
	
	add_meta_box('promo_code', __('Promo code', 'promos'), 'sw_show_metabox_code', 'promo', 'side');
	add_meta_box('promo_users', __('Users', 'promos'), 'sw_show_metabox_users', 'promo', 'normal', 'high');
}
if (is_admin()) add_action('add_meta_boxes', 'sw_meta_boxes');	



/**
 * prints meta-box code generator
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_show_metabox_code() {
	global $post;
	
	if ($post->post_parent != 0) return
	
	// bla bla bla
	
	$code = get_post_meta($post->ID, SW_PREFIX.'promo_code', true);
	
	$code_base = (isset($code['base'])) ? $code['base'] : "";
	$code_start = (isset($code['start'])) ? $code['start'] : "";
	$code_end = (isset($code['end'])) ? $code['end'] : "";
	
	wp_nonce_field(basename(__FILE__), 'promo-metabox');
	?>
	<table class="form-table">
		<tr><th colspan="2"><input type="checkbox" value="true" name="promo-activate-code" id="promo-activate-code"><label for="promo-activate-code"> <?php _e('Activates promo codes serie') ?></label></th></tr>
	</table>
	<table id="promo-code-table" class="form-table">
		<tr><th colspan="2"><p><?php  _e('Generates a range code for this promotion', 'promos') ?></p></th></tr>
		<tr>
			<td><label for="promo-code-base"><?php _e('base code', 'promos') ?></label></td>
			<td>
				<input type="text" name="promo-code-base" id="promo-code-base" class="sp-text-field" value="<?php echo $code_base ?>" /><br/>
				<p class="description"><?php _e('PROMO2012', 'promos') ?></p>
			</td>
		</tr>
		<tr>
			<td><label for="promo-code-start"><?php _e('start', 'promos') ?></label></td>
			<td>
				<input type="text" name="promo-code-start" id="promo-code-start" class="sp-text-field sp-number-field" value="<?php echo $code_start ?>" /><br/>
				<p class="description"><?php _e('Start number: 1', 'promos')  ?></p>
			</td>
		</tr>
		<tr>
			<td><label for="promo-code-end"><?php _e('end', 'promos') ?></label></td>
			<td>
				<input type="text" name="promo-code-end" id="promo-code-end" class="sp-text-field sp-number-field" value="<?php echo $code_end ?>" /><br/>
				<p class="description"><?php _e('End number: 100000', 'promos')  ?></p>
			</td>
		</tr>
		<tr><td colspan="2"><input type="submit" value=" <?php _e('Download archive', 'promos') ?> " id="create-code" class="button alignright" /></td></tr>
	</table>
	<?php
}



/**
 * prints meta-box code generator
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_show_metabox_users() {
	global $post;
	
	// bla bla bla
	
	$users = get_post_meta($post->ID, SW_PREFIX .'promo_user', false);
	
	wp_nonce_field(basename(__FILE__), 'promo-metabox');
	?>
	<table class="form-table">
		<tr><th><p><?php  _e('Users who participate on this promo:', 'promos') ?></p></th></tr>
		<tr><td>
			<ul class='tagchecklist the-tagcloud' style='padding-left: 20px; margin-top:10px; background-color: #fafafa'>
				<!--<li><a href="/wp-admin/user-edit.php?user_id=1" target="_blank">Carlos Sanz Garcia</a>, código: <u>CARIBE2012-1</u>, <em>15-07-2012 15:55h</em></li>-->
	<?php
		foreach ($users as $user) {
			$user_ = get_userdata($user['user_id']);
			if ($user_):
				if (!$user_->user_firstname && !$user_->user_lastname) $user_name = $user_->user_login;
		  		$user_link = "<a href=\"/wp-admin/user-edit.php?user_id={$user_->ID}\" target=\"_blank\">".ucfirst($user_name)."</a>";
				printf("<li>%s, code: %s, <em>%s</em></li>", $user_link, $user['code'], $user['date']);
			endif; 
		}			
	?>
			</ul>
		</td></tr>
		<tr><td><div id="promo-winner-result" class='tagchecklist surveylist the-tagcloud' style='padding-left: 20px; text-align:right; margin-top:10px; background-color: #fafafa'>no hay ganador todabía</div></td></tr>
		<tr><td><input type="hidden" value="<?php echo wp_create_nonce('promo_winner') ?>" id="promo-nonce"><a href="#" class="button-primary alignright" id="select-winner"><?php _e('Select winner', 'promos') ?></a></td></tr>
	</table>
	<?php
}



/**
 * set-up a promo winner by ajax
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_promo_winner() {
	// check to see if the submitted nonce matches with the
	// generated nonce we created earlier
	/*
	if (wp_verify_nonce( $_POST['nonce'], 'promo_winner' )) {
		
		update_post_meta($_POST['post_id'], 'survey_winner', $_POST['winner_id']);
		$user_ = get_userdata($_POST['winner_id']);
		if (!$user_->user_firstname && !$user_->user_lastname) $user_name = $user_->user_login;
		$winner = "<a href=\"/wp-admin/user-edit.php?user_id={$user_->ID}\" target=\"_blank\">".ucfirst($user_name)."</a>";
		$response = array( 'status' => 1, 'content' => $winner);
		
	} else {
			
		$response = array( 'status' => 0, 'content' => '');
	}
		
	// response output
	header( "Content-Type: application/json" );
	echo json_encode($response);
	exit;
	*/
}
if (is_admin()) {
	add_action('wp_ajax_set_promo_winner', array($this, 'sw_promo_winner'));
	add_action('wp_ajax_nopriv_set_promo_winner', array($this, 'sw_promo_winner'));
}



function sw_save_promo($post_id) {
	global $post_type;
	
	if ($post_type !== 'promo') return;
	
	$post_type_object = get_post_type_object($post_type);
		
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)						// check autosave
	|| (!isset($_POST['post_ID']) || $post_id != $_POST['post_ID'])			// check revision
	|| ($post->post_parent != 0)											// check post parent
	|| (!current_user_can($post_type_object->cap->edit_post, $post_id))) {	// check permission
		return $post_id;
	}
	
	// save code data
	$code = array(
		'base' => $_POST['promo-code-base'],
		'start' => $_POST['promo-code-start'],
		'end' => $_POST['promo-code-end'],
	);
	update_post_meta($post_id, SW_PREFIX.'promo_code', $code);
}
if (is_admin()) add_action('save_post', 'sw_save_promo');



/**
 * adds image thumbnail, to project, slider lists
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_promo_columns($columns) {
	
	$comments_icon = $columns['comments'];
	
	unset($columns['comments']);
	unset($columns['author']);
	unset($columns['date']);
	
	$columns['blocked'] = "<img src=\"" . plugins_url( 'assets/images/padlock-icon.png' , __FILE__ ) . "\" width=\"22\" height=\"22\" />";
	$columns['comments'] = $comments_icon;
	$columns['date'] = __('Date');
	
	return $columns;
}
add_filter('manage_edit-page_columns', 'sw_promo_columns');



/**
 * adds gear icon to mark blocked pages
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_promo_show_columns($name) {
	global $post, $default_theme_pages;
	
	if (!isset($default_theme_pages)) return;
	
	switch ($name) {
		case 'blocked':
			foreach ($default_theme_pages as $page) {
				if ( get_option($page['option']) == $post->ID ) {
					echo "<img src=\"" . plugins_url( 'assets/images/gear-icon.png' , __FILE__ ) . "\" width=\"19\" height=\"19\" /><br /><small style=\"color: gray\">" . $page['description'] . "</small>";
				}
			}
			break;
	}
}
add_filter('manage_page_posts_custom_column',  'sw_promo_show_columns');



/**
 * disable trash button on page publish meta box
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
/*
function sw_remove_delete_link() {
	global $pagenow, $post;
	
	if (!$post) return;
	
	if ( $pagenow == 'post.php' && is_default_page($post) ) {
		echo "<!-- DTP remove delete link -->" . PHP_EOL;
		echo "<style type=\"text/css\" media=\"screen\">" . PHP_EOL;
		echo "	#misc-publishing-actions > .misc-pub-section:first-child, #delete-action { display: none !important}" . PHP_EOL;
		echo "</style>" . PHP_EOL;
	}
}
add_action( 'admin_head', 'sw_remove_delete_link', 900 );
*/



/**
 * disable trash button on page row actions
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_promo_row_actions($actions, $post) {
	
	if ( $post->post_parent != 0 )
		unset($actions['trash']);
	
	return $actions;
}
add_filter('page_row_actions', 'sw_promo_row_actions', 0, 2);



/**
 * some columns admin styles
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_tables_styles() {
	echo "
	<style type=\"text/css\">
		/*
		.blocked.column-blocked { text-align: center;	}
		.manage-column.column-blocked { 
			width: 120px;
			text-align: center;
		}
		*/
	</style>
	".PHP_EOL;
}
add_filter('admin_head', 'sw_tables_styles', 640);