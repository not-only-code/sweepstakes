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

load_plugin_textdomain( 'sw', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

////////////////////////////////////////////////////////////////////////////////////////
	

	
/**
 * prints meta-box code generator and user list
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_manage_donwloads() {
	global $pagenow;
	
	if ( !in_array($pagenow, array('edit.php', 'post.php')) ) return;
	
	if ( isset($_GET['print_serie']) && !empty($_GET['print_serie']) ):
		
		$code = get_post_meta($_GET['print_serie'], SW_PREFIX.'promo_code', true); // ####### cache this !!!!
		
		if ($code && $code['enabled'] === 'true') {
			
			header('Content-disposition: attachment; filename='.$code['base'].'.txt');
			header('Content-type: text/plain');
			
			for ($n = $code['start']; $n < ($code['end']+1); $n++) { 
				$hash = $code['base'] . '-' . $n;
				echo $hash . ' - ' . sw_custom_hash($hash, $code['digits']) . PHP_EOL;
			}
			
			exit;
		};
		
	elseif ( isset($_GET['download_user_list']) && !empty($_GET['download_user_list']) ):
		
		$code = get_post_meta($_GET['download_user_list'], SW_PREFIX .'promo_code', true); // ####### cache this !!!!
		$users = get_post_meta($_GET['download_user_list'], SW_PREFIX .'promo_user', false); // ####### cache this !!!!
		$post_ = get_post($_GET['download_user_list']);
		
		if ( $post_ && $users && !empty($users) ) {
			
			header('Content-disposition: attachment; filename='.$post_->post_name.'-userlist.txt');
			header('Content-type: text/plain');
			
			foreach ($users as $user) {
				$user_ = get_userdata($user['user_id']);
				if ($user_):
					
					//$code_ = ( $code['enabled'] === 'true' && $user['code'] ) ? __('- using this code: ', 'sm') . $user['code'] : '';
					//printf(__('%s, was recorded for this draw, on %s %s', 'sw') , $user_->display_name, trim($user['date']), $code_);
					
					$code_ = ( $code['enabled'] === 'true' && $user['code'] ) ? ' - ' . $user['code'] : '';
					echo sw_format_date($user['date'])."h - $user_->display_name$code_" . PHP_EOL;
					
				endif;
			}
			
			exit;
		}
		
	endif;
}
if (is_admin()) {
	add_action('load-post.php', 'sw_manage_donwloads');
	add_action('load-edit.php', 'sw_manage_donwloads');
}



/**
 * register scripts and styles
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_enqueue_scripts() {
	if (is_admin()) {
		wp_enqueue_style('sw', plugins_url( 'assets/css/admin-styles.css' , __FILE__ ) );
		wp_enqueue_script('sw', plugins_url( 'assets/js/admin-scripts.js' , __FILE__ ), array('jquery', 'jquery-ui-core', 'jquery-ui-sortable'), null, true );
	} else {
		///////////////////////
	}
}
add_action('init', 'sw_enqueue_scripts');


	
/**
 * creates 'promo' post type 
 *
 * @package Site Promos
 *
 * @since 0.1
**/	
function sw_post_types() {
	// labels
	$labels = array(
		'name' => __( 'Promos', 'sw'),
		'singular_name' => __( 'Promo', 'sw'),
		'add_new' => __( 'Add new promo', 'sw'),
		'add_new_item' => __( 'Add new promo', 'sw'),
		'edit' => __( 'Edit'),
		'edit_item' => __( 'Edit promo', 'sw'),
		'new_item' => __( 'New promo', 'sw'),
		'view' => __( 'View promo', 'sw'),
		'view_item' => __( 'View promo', 'sw'),
		'search_items' => __( 'Search promos', 'sw'),
		'not_found' => __( 'No promo found', 'sw'),
		'not_found_in_trash' => __( 'No promos in trash', 'sw'),
		'parent' => __( 'Parent promo', 'sw'),
		'menu_name' => __( 'Promos', 'sw')
	);
	
	$descripcion = __( 'Specific content for promos' ,'sw');
	
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
 * adds metabox to control codes, register form and users
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_meta_boxes() {
	global $post;
		
	if ( $post->post_parent != 0 ) return;
	
	add_meta_box('promo_code', __('Promo code', 'sw'), 'sw_show_metabox_code', 'promo', 'side');
	add_meta_box('promo_form', __('Register form', 'sw'), 'sw_show_metabox_form', 'promo', 'normal', 'high');
	add_meta_box('promo_users', __('Participants / Winners', 'sw'), 'sw_show_metabox_users', 'promo', 'normal');
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
function sw_show_metabox_code($post) {
	
	if ($post->post_parent != 0) return;
	
	$code = get_post_meta($post->ID, SW_PREFIX.'promo_code', true); // ####### cache this !!!!
	
	$code_enabled = (isset($code['enabled'])) ? $code['enabled'] : "";
	$code_base = (isset($code['base'])) ? $code['base'] : "";
	$code_start = (isset($code['start'])) ? $code['start'] : "1";
	$code_end = (isset($code['end'])) ? $code['end'] : "100";
	$code_digits = (isset($code['digits']) && $code['digits'] > 5 ) ? $code['digits'] : "6";
	
	?>
	<table class="form-table sw">
		<tr><th colspan="2"><input type="checkbox" value="true" name="promo-code-enabled" id="promo-code-enabled" <?php checked($code_enabled, 'true') ?>><label for="promo-code-enabled">&nbsp;&nbsp;<?php _e('Activate promo codes series', 'sw') ?></label></th></tr>
	</table>
	<table id="promo-code-table" class="form-table sw <?php echo $code_enabled ?>">
		<tr><th colspan="2"><p class="description"><?php  _e('Creates a serial code for this sweepstake', 'sw') ?></p></th></tr>
		<tr>
			<td><label for="promo-code-base"><?php _e('code', 'sw') ?></label></td>
			<td>
				<input type="text" name="promo-code-base" id="promo-code-base" class="sp-text-field" value="<?php echo $code_base ?>" /><br/>
				<p class="description"><?php _e('PROMO2012', 'sw') ?></p>
			</td>
		</tr>
		<tr>
			<td><label><?php _e('range', 'sw') ?></label></td>
			<td>
				<label for="promo-code-start"><?php _e('start', 'sw') ?></label>&nbsp;
				<input name="promo-code-start" id="promo-code-start" type="number" step="1" min="1" value="<?php echo $code_start ?>" class="small-text">&nbsp;&nbsp;&nbsp;&nbsp;
				<label for="promo-code-end"><?php _e('end', 'sw') ?></label>&nbsp;
				<input name="promo-code-end" id="promo-code-end" type="number" step="1" min="1" value="<?php echo $code_end ?>" class="small-text">
				<p class="description"><?php _e('For example: 1 - 100000', 'sw')  ?></p>
			</td>
		</tr>
		<tr>
			<td><label><?php _e('digits', 'sw') ?></label></td>
			<td>
				<input name="promo-code-digits" id="promo-code-digits" type="number" step="1" min="5" max="32" value="<?php echo $code_digits ?>" class="small-text">&nbsp;&nbsp;
				<p class="description"><?php _e('10 digits will create: <code>nUFyPiFdB1</code>', 'sw')  ?></p>
			</td>
		</tr>
		<tr><td colspan="2"><? wp_nonce_field(basename(__FILE__), 'promo-metabox-code'); ?><a href="<?php echo add_query_arg('print_serie', $post->ID, get_admin_url(1, "/post.php?post=$post->ID&action=edit")) ?>" class="button alignright"><?php _e('Download serie', 'sw') ?></a></td></tr>
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
function sw_show_metabox_form($post) {
	
	if ($post->post_parent != 0) return;
	
	$wp_default_fields = array(
		'0' => __('Select'),
		'first_name' => 'First Name',
		'last_name' => 'Last Name',
		'description' => 'Biographical Info',
		'user_login' => 'Login',
		'user_pass' => 'Password',
		'user_url' => 'Website',
		'aim' => 'Aim',
		'yim' => 'Yahoo IM',
		'jabber' => 'Jabber / Google Talk'
	);
	
	$code = get_post_meta($post->ID, SW_PREFIX.'promo_code', true); // ####### cache this !!!!
	$form = get_post_meta($post->ID, SW_PREFIX.'promo_form', true); // ####### cache this !!!!
	
	if ( !isset($form['fields']) || empty($form['fields']) )
		$form['fields']['user_email'] = 'Email';
	
	if ( isset($code['enabled']) && !empty($code['enabled']) && !array_key_exists('promo_code', $form['fields']) )
		$form['fields']['promo_code'] = 'Promo Code';
	
	?>
	<table class="form-table sw">
		<tr><td colspan="2">
				<p style="display: inline"><?php _e('Select witch fields to use in registration form:', 'sw'); ?></p>&nbsp;&nbsp;
				<select id="promo-form-fields" size="1">
					<?php foreach ($wp_default_fields as $key => $name): ?>
						<option value="<?php echo $key ?>"<?php if ((isset($form['fields']) && array_key_exists($key, $form['fields']))) echo ' disabled="disabled"'; ?>><?php echo $name ?></option>
					<?php endforeach; ?>
				</select>&nbsp;&nbsp;
				<a href="#" id="promo-form-add-field" class="button"><?php _e('Add field', 'sw') ?></a>
		</td></tr>
		<tr><td colspan="2">
				<p style="display: inline"><?php _e('Or create a new one yourself', 'sw'); ?></p>&nbsp;&nbsp;
				<input type="text" id="promo-form-new-field" value="" />
				<a href="#" id="promo-form-create-field" class="button"><?php _e('Add field', 'sw') ?></a>
		</td></tr>
		<tr><td colspan="2">
				<ul id="promo-form-list" class="tagchecklist the-tagcloud form-list-container ui-sortable" style="padding-left: 20px; margin-top:10px; background-color: #fafafa">
					<?php foreach ($form['fields'] as $key => $name): ?>
					<li style="cursor: move">
						<?php if( in_array($key, array('user_email', 'promo_code'))): ?>
						&nbsp;&nbsp;
						<?php else: ?>
						<span><a href='#' class='ntdelbutton sw-remove-item' style='top:-3px;'>X</a></span>&nbsp;&nbsp;
						<?php endif; ?>
						<?php echo $name; ?><input type="hidden" name="promo-form-list[<?php echo $key ?>]" value="<?php echo $name ?>">
					</li>
					<?php endforeach; ?>
						
				</ul>
				<p class="description"><?php _e('Add and sort the fields you want display in the form, all field will be displayed as text input', 'sw') ?></p>
		</td></tr>
		<tr>
			<td>
				<label for="promo-form-terms"><?php _e('Terms page', 'sw') ?></label>
			</td>
			<td>
				<select name="promo-form-terms">
					<option value="0"><?php _e('Without Terms page') ?></option>
					<?php $pages = get_posts(array('post_type' => 'page', 'numberposts' => -1)); ?>
					<?php foreach ($pages as $page): ?>
					<option value="<?php echo $page->ID ?>" <?php selected($page->ID, $form['terms']) ?>><?php echo apply_filters('the_title', $page->post_title) ?></option>
					<?php endforeach ?>
				</select>
				<p class="description"><?php _e('If you define a "Terms" page the customer will be asked to accept it before submit the form.', 'sw'); ?></p>
			</td>
		</tr>
	</table>
	<?php
}



/**
 * prints meta-box users
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_show_metabox_users() {
	global $post;
	
	$code = get_post_meta($post->ID, SW_PREFIX .'promo_code', true); // ###################### cache this !!!!
	$winners = get_post_meta($post->ID, SW_PREFIX .'promo_winners', true); // ###################### cache this !!!!
	$users = get_post_meta($post->ID, SW_PREFIX .'promo_user', false); // ###################### cache this !!!!
	
	if ( !isset($winners['number']) || empty($winners['number']) || $winners['number'] == 0 )
		$winners['number'] = 1;
	
	$tr_class = ( !isset($users) || empty($users) ) ? ' class="hide"' : "";
	
	$processed_winners = array();
	foreach ( $winners['winners'] as $winner_ ) {
		$user_ = get_userdata($winner_['user_id']);
		$temp_ = "<a href=\"/wp-admin/user-edit.php?user_id={$user_->ID}\" target=\"_blank\">".sw_process_username($user_)."</a>";
		if ( isset($code['enabled']) && $code['enabled'] === 'true' )
			$temp_ .= "(<code>{$winner_['code']}</code>)";
		$processed_winners[] = $temp_;
	}
	?>
	<table class="form-table sw">
		<tr><td>
			<label for="promo-winner-number"><?php _e('Number of winners', 'sw') ?></label>&nbsp;&nbsp;
			<input name="promo-winner-number" id="promo-winner-number" type="number" step="1" min="1" max="<?php echo (count($users) < 1) ? 1 : count($users); ?>" value="<?php echo $winners['number'] ?>" class="small-text">
			<p class="description" sryle="display: inline"><?php _e('For example, a draw could have 3 winners, with 3 different lots', 'sw')  ?></p>
		</td></tr>
		<tr><td>
			<p><?php  _e('Users who have participated in the draw:', 'sw') ?></p>
			<ul id="promo-user-list" class='the-tagcloud' style='padding-left: 20px; margin-top:10px; background-color: #fafafa'>
				<?php if (!$users || empty($users)): ?>
					<li><p class="description"><?php __('no participants', 'sw') ?></p></li>;
				<?php 
					else:
					foreach ($users as $user) {
						$user_ = get_userdata($user['user_id']);
						if ($user_):
					  		$user_link = "<a href=\"/wp-admin/user-edit.php?user_id={$user_->ID}\" target=\"_blank\">".sw_process_username($user_)."</a>";
							echo '<li>';
							printf("<strong>%s</strong>h - %s", sw_format_date($user['date']), $user_link);
							if ($code && isset($code['enabled']) && $code['enabled'] === 'true') echo " - <code style=\"display: inline-block\">{$user['code']}</code>";
							echo '</li>';
						endif; 
					}			
				endif;
				?>
			</ul>
		</td></tr>
		<tr<?php echo $tr_class ?>><td>
			<a href="<?php echo add_query_arg('download_user_list', $post->ID, get_admin_url(1, "/post.php?post=$post->ID&action=edit")) ?>" class="button alignleft"><?php _e('Download participants', 'sw') ?></a>
		</td></tr>
		<tr<?php echo $tr_class ?>><td>
			<p><?php _e('Winners', 'sw') ?></p>
			<div id="promo-winner-result" class='tagchecklist surveylist the-tagcloud' style='padding-left: 20px; margin-top:10px; background-color: #fafafa'>
				<?php if (!$processed_winners || empty($processed_winners)): ?>
				<p class="description"><?php _e('no winner/s for now'); ?></p>
				<?php else: ?>
				<p><?php echo implode(', ', $processed_winners); ?></p>
				<?php endif; ?>
			</div>
		</td></tr>
		<tr<?php echo $tr_class ?>><td>
			<input type="hidden" value="<?php echo wp_create_nonce('promo_winner') ?>" id="promo-winner-nonce"><input type="hidden" value="<?php echo $winners['number'] ?>" id="promo-winner-num"><a href="#" class="button-primary" id="promo-select-winner"><?php _e('Random winner/s', 'sw') ?></a>
		</td></tr>
	</table>
	<?php
}



/**
 * retrieve participant user data by hash
 *
 * @param $hash encripted code
 *
 * @return object | bolean combined result
 *
 * @package Site Promos
 * @since 0.1
 *
**/
function sw_get_participant_by_hash($hash) {
	global $post;
	
	$users = get_post_meta($post->ID, SW_PREFIX .'promo_user', false); // ###################### cache this !!!!
	
	if (!$users || empty($users)) return false;
	
	foreach ($users as $user) 
		if ( $user['code'] === $hash ) return get_userdata($user['user_id']);
	
	return false;
}



/**
 * format user name
 *
 * @param $data object user data object
 *
 * @return formated string username
 *
 * @package Site Promos
 * @since 0.1
 *
**/
function sw_process_username($data) {

	$user_name = $data->user_firstname;
	if ($data->user_lastname) $user_name .= " " . $data->user_lastname;
	if (!$data->user_firstname && !$data->user_lastname) $user_name = $data->user_login;
	
	return $user_name;
}


/**
 * format date from string
 *
 * @param $string string with date-time
 *
 * @return formated string
 *
 * @package Site Promos
 * @since 0.1
 *
**/
function sw_format_date($string) {
	
	$format = get_option('date_format') . ' : ' . get_option('time_format'); // ###################### cache this !!!!
	return date_i18n( $format, strtotime($string) );
}



/**
 * encodes a string with length parameter
 *
 * @param $input string to be encripted
 * @param $length length of result
 * @param $charset avaiable source for encript
 *
 * @return ecripted string
 *
 * @package Site Promos
 * @since 0.1
 *
**/
function sw_custom_hash($input, $length, $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUFWXIZ0123456789'){
    $output = '';
    $input = md5($input); //this gives us a nice random hex string regardless of input 

    do{
        foreach (str_split($input,8) as $chunk){
            srand(hexdec($chunk));
            $output .= substr($charset, rand(0,strlen($charset)), 1);
        }
        $input = md5($input);

    } while(strlen($output) < $length);

    return substr($output,0,$length);
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
	
	if (wp_verify_nonce( $_POST['nonce'], 'promo_winner' )) {
		
		$code = get_post_meta($_POST['post_id'], SW_PREFIX .'promo_code', true); // ###################### cache this !!!!
		$winners = get_post_meta($_POST['post_id'], SW_PREFIX .'promo_winners', true); // ###################### cache this !!!!
		$users = get_post_meta($_POST['post_id'], SW_PREFIX .'promo_user', false); // ###################### cache this !!!!
		
		if (!$users || empty($users) )  {
			
			$response = array( 'status' => 0, 'content' => '');
			
		} else {
			
			$rand = array_rand($users, $winners['number']);
			$final_winners = array();
			$output = array();
			
			if (!is_array($rand)) $rand = array($rand);
			
			foreach ($rand as $key) {
				$user_data = get_userdata($users[$key]['user_id']);
				
				$output_ = "<a href=\"/wp-admin/user-edit.php?user_id={$user_data->ID}\" target=\"_blank\">".sw_process_username($user_data)."</a>";
				if ( isset($code['enabled']) && $code['enabled'] === 'true' ) $output_ .= " (<code>{$users[$key]['code']}</code>)";
				$output[] = $output_;
				 
				$final_winners[] = $users[$key];	
			}
			$output = implode(', ', $output);
			
			//save participants data
			$winners['winners'] = $final_winners;
			update_post_meta($_POST['post_id'], SW_PREFIX.'promo_winners', $winners);
				
			$response = array( 'status' => 1, 'content' => $output );
		}
		
	} else {
			
		$response = array( 'status' => 0, 'content' => '');
	}
		
	// response output
	header( "Content-Type: application/json" );
	echo json_encode($response);
	exit;
}
if (is_admin()) {
	add_action('wp_ajax_set_promo_winner', 'sw_promo_winner');
	add_action('wp_ajax_nopriv_set_promo_winner', 'sw_promo_winner');
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
	$start_ = $_POST['promo-code-start'];
	$end_ = $_POST['promo-code-end'];
	
	if ( $start_ >= $end_) $start_ = $end_;
	
	$code = array(
		'enabled' => $_POST['promo-code-enabled'],
		'base' => $_POST['promo-code-base'],
		'start' => $_POST['promo-code-start'],
		'end' => $_POST['promo-code-end'],
		'digits' => $_POST['promo-code-digits']
	);
	update_post_meta($post_id, SW_PREFIX.'promo_code', $code);
	
	// save form data	
	$form = array(
		'fields' => $_POST['promo-form-list'],
		'terms' => $_POST['promo-form-terms']
	);
	update_post_meta($post_id, SW_PREFIX.'promo_form', $form);
	
	//save participants data
	$winners = get_post_meta($post_id, SW_PREFIX .'promo_winners', true); // ###################### cache this !!!!
	
	if (!$winners) $winners = array(
		'number' => 1,
		'winners' => array()
	);
	
	$winners['number'] = $_POST['promo-winner-number'];
	update_post_meta($post_id, SW_PREFIX.'promo_winners', $winners);	
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
	
	unset($columns['date']);
	
	$columns['users'] = __('Participants', 'sw');
	$columns['promo_code'] = __('Promo Code', 'sw');
	$columns['winners'] = __('Winner/s', 'sw');
	$columns['date'] = __('Date');
	
	return $columns;
}
add_filter('manage_edit-promo_columns', 'sw_promo_columns');



/**
 * adds gear icon to mark blocked pages
 *
 * @package Site Promos
 *
 * @since 0.1
 *
**/
function sw_promo_show_columns($name) {
	global $post;
	
	switch ($name) {
		case 'users':
			$users = get_post_meta($post->ID, SW_PREFIX .'promo_user', false);
			
			if ($users && !empty($users)) {
				
				echo "<p><strong>" . count($users) . "</strong> " . __('participants', 'sw') . '</p>' . '<p><a href="'. add_query_arg('download_user_list', $post->ID, get_admin_url(1, "/edit.php?post_type=promo")).'" class="button-secondary">' . __('Download participants', 'sw') . '</a></p>';
			} else {
				echo '<p class="disabled">' . __('no participants', 'sw') . '</p>';
			}
				
			break;
		case 'promo_code':
			$code = get_post_meta($post->ID, SW_PREFIX .'promo_code', true);
			
			if ($code && $code['enabled'] === 'true') {
				echo "<p><code>{$code['base']}</code></p>" . '<p><a href="'. add_query_arg('print_serie', $post->ID, get_admin_url(1, "/edit.php?post_type=promo")).'" class="button-secondary">' . __('Download serie', 'sw') . '</a></p>';
			} else {
				echo '<p class="disabled">' . __('disabled', 'sw') . "</p>";
			}
				
		break;
		case 'winners':
		
			$code = get_post_meta($post->ID, SW_PREFIX .'promo_code', true);
			$winners = get_post_meta($post->ID, SW_PREFIX .'promo_winners', true);
			
			if (isset($winners['winners']) && !empty($winners['winners'])) {
				
				$processed_winners = array();
				foreach ( $winners['winners'] as $winner_ ) {
					$user_ = get_userdata($winner_['user_id']);
					$temp_ = "<a href=\"/wp-admin/user-edit.php?user_id={$user_->ID}\" target=\"_blank\">".sw_process_username($user_)."</a>";
					if ( isset($code['enabled']) && $code['enabled'] === 'true' )
						$temp_ .= " - <code>{$winner_['code']}</code>";
					$processed_winners[] = $temp_;
				}
				
				echo implode('<br/>', $processed_winners);
				
			} else {
				echo '<p class="disabled">' . __('no winner/s for now', 'sw') . "</p>";
			}
				
		break;
		
	}
}
add_filter('manage_promo_posts_custom_column',  'sw_promo_show_columns');



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
		.wp-list-table td .disabled { color:#999 }
		/* participants */
		.manage-column.column-users {  width: 160px; text-align: center; }
		.users.column-users { text-align: center;	}
		/* column code */
		.manage-column.column-promo_code {  width: 130px; text-align: center; }
		.promo_code.column-promo_code { text-align: center;	}
		/* column winners */
		.manage-column.column-winners {  width: 22%; text-align: center; }
		.winners.column-winners { text-align: center; }
		
	</style>
	".PHP_EOL;
}
add_filter('admin_head', 'sw_tables_styles', 640);