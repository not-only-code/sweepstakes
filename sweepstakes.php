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

add_action('plugins_loaded', 'sw_init');

////////////////////////////////////////////////////////////////////////////////////////
	
	
/**
 * adds hooks of the plugin
 *
 * @package Swepstakes
 * @since 0.1
 *
**/	
function sw_init() {
	
	// textdomain
	load_plugin_textdomain( 'sw', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	
	// create post types
	add_action('init', 'sw_post_types');
	
	if (is_admin()) {
		
		// caching metas
		add_action('init', 'sw_caching_metas');
		
		// scripts + styles
		add_action('init', 'sw_admin_scripts');
		
		// download archives
		add_action('load-post.php', 'sw_manage_donwloads');
		add_action('load-edit.php', 'sw_manage_donwloads');
		
		// meta boxes
		add_action('add_meta_boxes', 'sw_meta_boxes');
		add_action('save_post', 'sw_save_promo');
		
		// ajax hooks
		add_action('wp_ajax_set_promo_winner', 'sw_promo_winner');
		add_action('wp_ajax_nopriv_set_promo_winner', 'sw_promo_winner');
		add_action('wp_ajax_del_promo_winner', 'sw_delete_winner');
		add_action('wp_ajax_nopriv_del_promo_winner', 'sw_delete_winner');
		add_action('right_now_content_table_end', 'sw_right_now_promos');
		
		// promos list table
		add_filter('manage_edit-promo_columns', 'sw_promo_columns');
		add_filter('manage_promo_posts_custom_column',  'sw_promo_show_columns');
		add_filter('admin_head', 'sw_tables_styles', 640);
		
	} else {
		
		// scripts + styles
		add_action('wp', 'sw_frontend_scripts');
		
		// front form filters
		add_filter('the_content', 'sw_promo_content', 640);
		add_filter('promo_form_terms', 'sw_form_terms', 0, 3);
		add_filter('promo_form_submit', 'sw_form_submit', 0, 2);
		add_filter('promo_form_field', 'sw_form_field', 0, 4);
		add_filter('promo_form_field', 'sw_form_field_password', 1, 4);
		
		// front form action
		add_action('wp', 'sw_cache_metas', 1);
		add_action('wp', 'sw_process_register', 2);
		add_action('sw-before-form', 'sw_get_responses', 1);
		add_action('sw-before-form', 'sw_print_errors', 2);
		add_action('sw-before-form', 'sw_print_messages', 2);
	}
}



/**
 * creates 'promo' post type 
 *
 * @package Swepstakes
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
		'supports' => array( 'title', 'editor', 'thumbnail' ),
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



/**
 * enqueue admin plugin scripts
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_admin_scripts() {
			
	// enqueue admin scripts and styles
	wp_enqueue_style('sw', plugins_url( 'assets/css/admin-styles.css' , __FILE__ ) );
	wp_enqueue_script('sw', plugins_url( 'assets/js/admin-scripts.js' , __FILE__ ), array('jquery', 'jquery-ui-core', 'jquery-ui-sortable'), null, false );
}



/**
 * enqueue frontend plugin scripts
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_frontend_scripts() {
	
	if (!is_singular('promo')) return;
		
	// enqueue admin scripts and styles
	wp_enqueue_style('sw', plugins_url( 'assets/css/front-styles.css' , __FILE__ ) );
	wp_enqueue_script('sw', plugins_url( 'assets/js/front-scripts.js' , __FILE__ ), array('jquery'), null, false );
}



//////////////////////////////////////////////////////////////////////////////////////// BACKEND	
	
	
	
/**
 * adds promos at 'right now' dashboard widget
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_right_now_promos() {
	
	$num_promos = wp_count_posts( 'promo' );
	echo '<tr>' . PHP_EOL;
	// Pages
	$num = number_format_i18n( $num_promos->publish );
	$text = _n( __('Promo', 'sw'), __('Promos', 'sw'), $num_promos->publish );
	if ( current_user_can( 'edit_pages' ) ) {
		$num = "<a href='edit.php?post_type=promo'>$num</a>" . PHP_EOL;
		$text = "<a href='edit.php?post_type=promo'>$text</a>" . PHP_EOL;
	}
	echo '<td class="first b b_pages">' . $num . '</td>' . PHP_EOL;
	echo '<td class="t pages">' . $text . '</td>' . PHP_EOL;
	echo '</tr><tr>' . PHP_EOL;
}



/**
 * stores all metas on init hook for increase performance
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_caching_metas() {
	global $pagenow;
	
	if ( !in_array($pagenow, array('post.php', 'edit.php', 'admin-ajax.php')) ) return;
	
	// date format
	$format = get_option('date_format') . ' : ' . get_option('time_format');
	wp_cache_set( 'date_time_format', $format );
	
	$post_id = (isset($_GET['post'])) ? $_GET['post'] : false;
	
	if (!$post_id && isset($_POST['post_id'])) $post_id = $_POST['post_id'];
	
	if (!$post_id  && isset($_POST['post_ID'])) $post_id = $_POST['post_ID'];
	
	if (!$post_id && isset($_GET['download_user_list'])) $post_id = $_GET['download_user_list'];
	
	if (!$post_id && isset($_GET['print_serie'])) $post_id = $_GET['print_serie'];
	
	if (!$post_id) return;
	
	sw_cache_metas($post_id);
}



/**
 * caching post metas in frontend process
 *
 * @param $promo_id int promo to get meta
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_cache_metas($promo_id = false) {
	global $post;
	
	if ( !$promo_id || ( is_object($promo_id) && !isset($promo_id->ID) ) ) {
		if ( isset($post) ) {
				$promo_id = $post->ID;
		} else {
			return;
		}
	}
	
	wp_cache_set('promo_code', get_post_meta($promo_id, SW_PREFIX .'promo_code', true));
	wp_cache_set('promo_form', get_post_meta($promo_id, SW_PREFIX .'promo_form', true));
	wp_cache_set('promo_user', get_post_meta($promo_id, SW_PREFIX .'promo_user', false));
	wp_cache_set('promo_winners', get_post_meta($promo_id, SW_PREFIX .'promo_winners', true));
}


	
/**
 * prints meta-box code generator and user list
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_manage_donwloads() {
	global $pagenow;
	
	if ( !in_array($pagenow, array('edit.php', 'post.php')) ) return;
	
	if ( isset($_GET['print_serie']) && !empty($_GET['print_serie']) ):
		
		$code = wp_cache_get('promo_code');
		
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
		
		$code = wp_cache_get('promo_code');
		$users = wp_cache_get('promo_user');
		
		$post_ = get_post($_GET['download_user_list']);
		
		if ( $post_ && $users && !empty($users) ) {
			
			header('Content-disposition: attachment; filename='.$post_->post_name.'-userlist.txt');
			header('Content-type: text/plain');
			
			foreach ($users as $user) {
				$user_ = get_userdata($user['user_id']);
				if ($user_):
					
					$code_ = ( $code['enabled'] === 'true' && $user['code'] ) ? ' - ' . $user['code'] : '';
					echo sw_format_date($user['date'])."h - $user_->display_name$code_" . PHP_EOL;
					
				endif;
			}
			
			exit;
		}
		
	endif;
}



/**
 * adds metabox to control codes, register form and users
 *
 * @package Swepstakes
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



/**
 * prints meta-box code generator
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_show_metabox_code($post) {
	
	if ($post->post_parent != 0) return;
	
	$users = wp_cache_get('promo_user');
	$code = wp_cache_get('promo_code');
	
	$code_enabled = (isset($code['enabled']) && !empty($code['enabled'])) ? $code['enabled'] : "";
	
	$code_base = (isset($code['base'])) ? $code['base'] : "";
	$code_start = (isset($code['start'])) ? $code['start'] : "1";
	$code_end = (isset($code['end'])) ? $code['end'] : "100";
	$code_digits = (isset($code['digits']) && $code['digits'] > 7 ) ? $code['digits'] : "8";
	
	?>
	<table class="form-table sw">
		<?php if ($users): ?>
		<tr><th colspan="2" style="padding: 0; margin: 0"><input type="hidden" value="<?php echo $code_enabled ?>" name="promo-code-enabled">
			<p class="description" style="margin: 0"><?php _e('<em>Currently there are participants, so these values <u>can not be changed</u></em>', 'sw'); ?>.</p>
		</th></tr>
		<?php else: ?>
			<tr><th colspan="2"><input type="checkbox" value="true" name="promo-code-enabled" id="promo-code-enabled" <?php checked($code_enabled, 'true') ?>><label for="promo-code-enabled">&nbsp;&nbsp;<?php _e('Activate promo codes series', 'sw') ?></label></th></tr>
		<?php endif; ?>
	</table>
	<table id="promo-code-table" class="form-table sw <?php echo $code_enabled ?>">
		<tr><th colspan="2">
			<?php if(!$users): ?>
			<p class="description"><?php  _e('Creates a serial code for this sweepstake', 'sw') ?>.</p>
			<?php endif; ?>
		</th></tr>
		<tr>
			<td><label for="promo-code-base"><?php _e('code', 'sw') ?></label></td>
			<td>
				<input type="text" name="promo-code-base" id="promo-code-base" class="sp-text-field" value="<?php echo $code_base ?>" <?php if ($users) echo 'readonly="readonly"';  ?>/><br/>
				<p class="description"><?php _e('PROMO2012', 'sw') ?></p>
			</td>
		</tr>
		<tr>
			<td><label><?php _e('range', 'sw') ?></label></td>
			<td>
				<label for="promo-code-start"><?php _e('start', 'sw') ?></label>&nbsp;
				<input name="promo-code-start" id="promo-code-start" type="number" step="1" min="1" value="<?php echo $code_start ?>" class="small-text" <?php if ($users) echo 'readonly="readonly"'; ?>>&nbsp;&nbsp;
				<label for="promo-code-end"><?php _e('end', 'sw') ?></label>&nbsp;
				<input name="promo-code-end" id="promo-code-end" type="number" step="1" min="1" max="10000" value="<?php echo $code_end ?>" class="small-text"<?php if ($users) echo 'readonly="readonly"'; ?>>
				<p class="description"><?php _e('For example: 1 - 1000', 'sw')  ?></p>
			</td>
		</tr>
		<tr>
			<td><label><?php _e('digits', 'sw') ?></label></td>
			<td>
				<input name="promo-code-digits" id="promo-code-digits" type="number" step="1" min="8" max="32" value="<?php echo $code_digits ?>" class="small-text"<?php if ($users) echo 'readonly="readonly"'; ?>>&nbsp;&nbsp;
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
 * @package Swepstakes
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
		'user_pass' => 'Password',
		'user_url' => 'Website',
		'aim' => 'Aim',
		'yim' => 'Yahoo IM',
		'jabber' => 'Jabber / Google Talk'
	);
	
	$code = wp_cache_get('promo_code');
	$form = wp_cache_get('promo_form');
	
	// default fields
	$form['fields']['user_login'] = 'Username';
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
						<?php if( in_array($key, array('user_email', 'promo_code', 'user_login'))): ?>
						&nbsp;&nbsp;
						<?php else: ?>
						<span><a href='#' class='ntdelbutton sw-remove-item' style='top:-3px;'>X</a></span>&nbsp;&nbsp;
						<?php endif; ?>
						<?php echo $name; ?><input type="hidden" name="promo-form-list[<?php echo $key ?>]" value="<?php echo $name ?>">
					</li>
					<?php endforeach; ?>
						
				</ul>
				<p class="description"><?php _e('Add and sort the fields you want display in the register form, all fields will be displayed as text input', 'sw') ?></p>
		</td></tr>
		<tr>
			<td>
				<label for="promo-form-terms"><?php _e('Terms page', 'sw') ?></label>
			</td>
			<td>
				<select name="promo-form-terms">
					<option value="0"><?php _e('Without Terms page', 'sw') ?></option>
					<?php $pages = get_posts(array('post_type' => 'page', 'numberposts' => -1)); ?>
					<?php foreach ($pages as $page): ?>
					<option value="<?php echo $page->ID ?>" <?php selected($page->ID, $form['terms']) ?>><?php echo apply_filters('the_title', $page->post_title) ?></option>
					<?php endforeach ?>
				</select>
				<p class="description"><?php _e('If you define a \'Terms\' page the customer will be asked to accept it before submit the form.', 'sw'); ?></p>
			</td>
		</tr>
	</table>
	<?php
}



/**
 * prints meta-box users
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_show_metabox_users() {
	global $post;
	
	$code = wp_cache_get('promo_code');
	$winners = wp_cache_get('promo_winners');
	$users = wp_cache_get('promo_user');
	
	if ( !isset($winners['number']) || empty($winners['number']) || $winners['number'] == 0 )
		$winners['number'] = 1;
	
	$tr_class = ( !isset($users) || empty($users) ) ? ' class="hide"' : "";
	
	$processed_winners = array();
	foreach ( $winners['winners'] as $winner_ ) {
		$user_ = get_userdata($winner_['user_id']);
		$temp_ = "<a href=\"/wp-admin/user-edit.php?user_id={$user_->ID}\" target=\"_blank\">".sw_process_username($user_)."</a>";
		if ( isset($code['enabled']) && $code['enabled'] === 'true' )
			$temp_ .= " (<code>{$winner_['code']}</code>)";
		$processed_winners[] = $temp_;
	}
	?>
	<table class="form-table sw">
		<tr><td>
			<p><?php  _e('Users who have participated in the draw:', 'sw') ?></p>
			<ul id="promo-user-list" class='the-tagcloud' style='padding-left: 15px; margin-top:10px; margin-bottom:0; background-color: #fafafa'>
				<?php if (!$users || empty($users)): ?>
					<li><span class="description"><?php _e('no participants yet', 'sw') ?></span></li>
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
			<a href="<?php echo add_query_arg('download_user_list', $post->ID, get_admin_url(1, "/post.php?post=$post->ID&action=edit")) ?>" class="button alignright"><?php _e('Download participants', 'sw') ?></a>
		</td></tr>
		<tr<?php echo $tr_class ?>><td>
			<p><?php _e('Winners', 'sw') ?></p>
			<p id="promo-winner-result" class='tagchecklist surveylist the-tagcloud' style='padding-left: 15px; margin-top:10px; margin-bottom:0; background-color: #fafafa'>
				<?php if (!$processed_winners || empty($processed_winners)): ?>
				<span class="description"><?php _e('no winner/s for now', 'sw'); ?></span>
				<?php else: ?>
				<?php echo implode(', ', $processed_winners); ?>
				<?php endif; ?>
			</p>
		</td></tr>
		<tr<?php echo $tr_class ?>><td>
			<span class="alignleft">
			<label for="promo-winner-number"><?php _e('Number of winners', 'sw') ?></label>&nbsp;&nbsp;
			<input name="promo-winner-number" id="promo-winner-number" type="number" step="1" min="1" max="<?php echo (count($users) < 1) ? 1 : count($users); ?>" value="<?php echo $winners['number'] ?>" class="small-text">
			</span>
			<input type="hidden" value="<?php echo wp_create_nonce('promo_winner') ?>" id="promo-winner-nonce"><input type="hidden" value="<?php echo $winners['number'] ?>" id="promo-winner-num"><a href="#" class="button-primary alignright" id="promo-select-winner"><?php _e('Random winner/s', 'sw') ?></a><a href="#" class="button-secondary alignright" id="promo-delete-winners"><?php _e('Delete winner/s', 'sw') ?></a>
			<p class="description" style="clear: both;"><?php _e('For example, a draw could have 3 winners, with 3 different lots', 'sw')  ?></p>
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
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_get_participant_by_hash($hash) {
	
	$users = wp_cache_get('promo_user');
	
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
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_process_username($data) {

	$user_name = $data->user_firstname;
	if ($data->user_lastname) $user_name .= " " . $data->user_lastname;
	if (!$data->user_firstname && !$data->user_lastname) $user_name = $data->user_login;
	
	return ucfirst($user_name);
}



/**
 * format date from string
 *
 * @param $string string with date-time
 *
 * @return formated string
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_format_date($string) {
	
	$format = wp_cache_get('date_time_format');
	
	return date_i18n( $format, strtotime($string) );
}



/**
 * encodes a string with length parameter (resource-intensive)
 *
 * @param $input string to be encripted
 * @param $length length of result
 * @param $charset avaiable source for encript
 *
 * @return ecripted string
 *
 * @package Swepstakes
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
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_promo_winner() {
	
	if (wp_verify_nonce( $_POST['nonce'], 'promo_winner' )) {
		
		$code = wp_cache_get('promo_code');
		$winners = wp_cache_get('promo_winners');
		$users = wp_cache_get('promo_user');
		
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
			wp_cache_set('promo_winners', $winners);
				
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



/**
 * delete current promo winners
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_delete_winner() {
	
	if (wp_verify_nonce( $_POST['nonce'], 'promo_winner' )) {
		
		$winners = wp_cache_get('promo_winners');
					
		//save participants data
		$winners['winners'] = array();
		update_post_meta($_POST['post_id'], SW_PREFIX.'promo_winners', $winners);
		wp_cache_set('promo_winners', $winners);
		
		$response = array( 'status' => 1, 'content' => "<span class=\"description\">". __('no winner/s for now', 'sw') . "</span>" );
		
	} else {
			
		$response = array( 'status' => 0, 'content' => '');
	}
		
	// response output
	header( "Content-Type: application/json" );
	echo json_encode($response);
	exit;
}



/**
 * saves post meta data
 *
 * @param $post_id id of saved post 
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_save_promo($post_id) {
	global $post_type;
	
	if ($post_type !== 'promo') return;
	
	$post_type_object = get_post_type_object($post_type);
		
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)						// check autosave
	|| (!isset($_POST['post_ID']) || $post_id != $_POST['post_ID'])			// check revision
	|| (isset($_POST['post_parent']) && $_POST['post_parent'] != 0)			// check post parent
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
	wp_cache_set('promo_code', $code);
	
	// save form data	
	$form = array(
		'fields' => $_POST['promo-form-list'],
		'terms' => $_POST['promo-form-terms']
	);
	update_post_meta($post_id, SW_PREFIX.'promo_form', $form);
	wp_cache_set('promo_form', $form);
	
	//save participants data
	$winners = wp_cache_get('promo_winners');
	
	if (!$winners) $winners = array(
		'number' => 1,
		'winners' => array()
	);
	$winners['number'] = $_POST['promo-winner-number'];
	update_post_meta($post_id, SW_PREFIX.'promo_winners', $winners);
	wp_cache_set('promo_winners', $winners);
}



/**
 * adds image thumbnail, to project, slider lists
 *
 * @package Swepstakes
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



/**
 * adds gear icon to mark blocked pages
 *
 * @package Swepstakes
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
				echo '<p class="disabled">' . __('no participants yet', 'sw') . '</p>';
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



/**
 * some columns admin styles
 *
 * @package Swepstakes
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



//////////////////////////////////////////////////////////////////////////////////////// FRONTEND



/**
 * frontend: promo content filter to show the form
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_promo_content($content) {
	global $post;
	
	echo $content;
	
	if ( is_singular('promo') && $post->post_parent == 0 ) sw_promo_process();
}



/**
 * show promo register process
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_promo_process() {
	global $post;
	
	if ( !$post ) return;
	
	// check if contest is finished
	//if (sw_check_finished()) return;
	
	// process forms
	if (is_user_logged_in()) {
		sw_promo_logged_form();
	} else {
		sw_promo_form();	
	}
}



/**
 * if there are winners contest is closed, show winners
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_check_finished() {
	
	$winners = wp_cache_get('promo_winners');
	
	// if there are winners contest is closed, show winners
	if ( count($winners['winners']) > 0 ) {
		
		printf(__("<h2 class='%s'>This sweepstakes is finished, and the winner/s is/are...</h2>", 'sw'), apply_filters('sw_header_class', 'page-title'));
		
		$processed_winners = array();
		foreach ($winners['winners'] as $winner) {
			$user_data = get_userdata($winner['user_id']);
			$processed_winners[] = sw_process_username($user_data);
		};
		
		?><p class="<?php echo apply_filters('sw_winners_class', 'sw-winners') ?>"><?php echo implode(', ', $processed_winners) ?></p><?php
		
		printf(__("<p class='%s'>If you are one of the lucky ones and have not received any mail, please contact the administrator.</p>", 'sw'), apply_filters('sw_text_class', ''));
		
		return true;
	}
	return false;
}



/**
 * show promo form for logged users
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_promo_logged_form() {
	global $post, $current_user;
	
	if (!$current_user) return;
	
	$user_name = sw_process_username($current_user);
	
	// metas
	$winners = wp_cache_get('promo_winners');
	$code = wp_cache_get('promo_code');
	$form =	wp_cache_get('promo_form');
	$users = wp_cache_get('promo_user');
	
	// print messages
	do_action('sw-before-form');
	
	// user is logged + promo code ( user can participate more than 1 time with different codes )
	if ( isset($code['enabled']) && $code['enabled'] === 'true' ) {
		
		printf(__("<h2 class='%s'>Hello <u>%s</u>, to participate on this promo add your code below</h2>", 'sw'), apply_filters('sw_header_class', 'page-title'), $user_name);
		
	    ?><form id="promo-form-login" action="<?php echo get_permalink($post->ID) ?>" method="post" class="<?php echo apply_filters("promo_form_class", "sw-form login"); ?>">
		<input type="hidden" name="sw-form-post_ID"  value="<?php echo $post->ID ?>"><?php
		wp_nonce_field('promo-form-nonce', 'sw-login-form');
		
		echo apply_filters('promo_form_field', '', 'promo_code', 'Promo Code', 1);
		
		echo apply_filters('promo_form_submit', '', 2);
	
		if (isset($form['terms']) && !empty($form['terms']) && $terms_page = get_page($form['terms']) ) 
			echo apply_filters('promo_form_terms', '', $terms_page, 3);
	
		?></form><?php 
	
	// user is logged + no promo code ( every user only can participate 1 time )
	} else {
		
		if ( sw_user_did_participate($current_user->ID) ) {
			
			printf(__("<h2 class='%s'>Hello <u>%s</u>, you\'ve participated in this promo</h2>", 'sw'), apply_filters('sw_header_class', 'page-title'), $user_name);
			
			printf(__("<p class='%s'>Wait for the draw for testing whether you are one of the lucky</p>", 'sw'), apply_filters('sw_text_class', ''));
			
			printf(__("<p class='%s'>Thank you!</p>", 'sw'), apply_filters('sw_footer_class', 'sw-sign'));
			
		} else {
			
			printf(__("<h2 class='%s'>Hello <u>%s</u>, to participate on this promo, click button below</h2>", 'sw'), apply_filters('sw_header_class', 'page-title'), $user_name);
		
		    ?><form id="promo-form-login" action="<?php echo get_permalink($post->ID) ?>" method="post" class="<?php echo apply_filters("promo_form_class", "sw-form login"); ?>">
			<input type="hidden" name="sw-form-post_ID"  value="<?php echo $post->ID ?>"><?php
			wp_nonce_field('promo-form-nonce', 'sw-login-form');
		
			echo apply_filters('promo_form_submit', '', 1);
	
			if (isset($form['terms']) && !empty($form['terms']) && $terms_page = get_page($form['terms']) ) 
				echo apply_filters('promo_form_terms', '', $terms_page, 2);
	
			?></form><?php	
		}
	
	};
}



/**
 * show promo register form
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_promo_form() {
	global $post;
	
	if (!isset($post)) return;
	
	$form = wp_cache_get('promo_form');
	$code = wp_cache_get('promo_code');
	
	// messages
	do_action('sw-before-form');
	
	printf(__("<h2 class='%s'>To participate in this promo, please choose</h2>", 'sw'), apply_filters('sw_header_class', 'page-title'));
	
	// switcher navigation
	?><nav id="promo-form-selector">
		<a id="promo-form-selector-register" href="#register" class="<?php echo apply_filters('sw_selector_register_class', 'active') ?>"><?php _e('Register') ?></a>
		<a id="promo-form-selector-login" href="#login" class="<?php echo apply_filters('sw_selector_login_class', '') ?>"><?php _e('Log in') ?></a>
	</nav><?php
	
	// register form
	?><form id="promo-form" action="<?php echo get_permalink($post->ID) ?>#register" method="post" class="<?php echo apply_filters("promo_form_class", "sw-form register"); ?>">
		<input type="hidden" name="sw-form-post_ID"  value="<?php echo $post->ID ?>"><?php
	
	wp_nonce_field('promo-form-nonce', 'sw-register-form');
	
	$index = 0;
	foreach ($form['fields'] as $field => $name) {
		$index ++;
		echo apply_filters('promo_form_field', '', $field, $name, $index);
	}
	$index ++;
	echo apply_filters('promo_form_submit', '', $index);
	
	if (isset($form['terms']) && !empty($form['terms']) && $terms_page = get_page($form['terms']) ) {
		$index ++;
		echo apply_filters('promo_form_terms', '', $terms_page, $index);
	}
	
	?></form><?php
	
	// login form
    ?><form id="promo-form-login" action="<?php echo get_permalink($post->ID) ?>#login" method="post" class="<?php echo apply_filters("promo_form_class", "sw-form login"); ?>" style="display: none">
	 <input type="hidden" name="sw-form-post_ID"  value="<?php echo $post->ID ?>"><?php
	wp_nonce_field('promo-form-nonce', 'sw-login-form');
	
	$index = 1;
	echo apply_filters('promo_form_field', '', 'user_email', 'Email', $index);
	
	$index++;
	echo apply_filters('promo_form_field', '', 'user_pass', 'Password', $index);
	
	if ( isset($code['enabled']) && $code['enabled'] === 'true' ) {
		$index++;
		echo apply_filters('promo_form_field', '', 'promo_code', 'Promo Code', $index);
	}
	
	$index++;
	echo apply_filters('promo_form_submit', '', $index);
	
	if (isset($form['terms']) && !empty($form['terms']) && $terms_page = get_page($form['terms']) ) {
		$index++;
		echo apply_filters('promo_form_terms', '', $terms_page, $index);
	}
	
	?></form><?php 
	
}



/**
 * process participant submitted form
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_process_register() {
	global $sw_processed_fields, $post;
		
	if ( isset($_POST['sw-login-form']) ) {
		
		$nonce = $_POST['sw-login-form'];
		$from = 'login';
		
	} else if ( isset($_POST['sw-register-form']) ) {
		
		$nonce = $_POST['sw-register-form'];
		$from = 'register';
		
	} else {
		
		return;
	}
	
	if ( wp_verify_nonce($nonce, 'promo-form-nonce') ) {
		
		// cached metas
		$code = wp_cache_get('promo_code');
		$form = wp_cache_get('promo_form');
		
		// resseting
		sw_reset_errors();
		sw_reset_messages();
		$sw_processed_fields = array();
		
		if ( isset($form['terms']) && !empty($form['terms']) )
			sw_validate_field('terms');
		
		// common field for all cases
		if ( isset($code['enabled']) && $code['enabled'] === 'true' ) {
			sw_validate_field('promo_code');
		} else {
			$sw_processed_fields['promo_code'] = '';
		}
		
		// --> user logged in
		if ( is_user_logged_in() ) {
			global $current_user;
			
			if (!sw_has_errors())
				sw_promo_register($post->ID ,$current_user->ID,  $sw_processed_fields['promo_code']);
			
			// reset processed fields
			$sw_processed_fields = array();
			
		} else {
			
			switch ($from) {
				
				// --> login
				case 'login':
					
					sw_validate_field('user_email');
					sw_validate_field('user_pass');
					
					if (sw_has_errors()) break;
					
				   	$user_found = get_user_by_email($sw_processed_fields['user_email']);
					 
					if (!$user_found) {
						sw_add_error(__('There is no user with that email', 'sw'));
						break;
					}
					
					$credentials = array(
						'user_login' => $user_found->user_login,
						'user_password' => $sw_processed_fields['user_pass'],
						'remember' => true
					);
					
					$auth = wp_signon($credentials, true);
					
					if (is_wp_error($auth)) {
						sw_add_error(__('Incorrect password', 'sw'));
						break;
					}
					
					if (!sw_has_errors())
						sw_promo_register($post->ID, $auth->ID, $sw_processed_fields['promo_code']);
					
					// redirect to save cookies and show result
					$url = add_query_arg(array('sw_message' => 1, 'sw_code' => $sw_processed_fields['promo_code']), home_url($_POST['_wp_http_referer']));
					
					// reset processed fields
					$sw_processed_fields = array();
					
					// redirect
					wp_safe_redirect($url);
					exit;
				break;
				
				// --> register
				case 'register':
					
					foreach ($form['fields'] as $field => $name) 
						sw_validate_field($field);
					
					if (sw_has_errors()) break;
						
					$user_data = $sw_processed_fields;
					
					if ( isset($user_data['promo_code']) )
						unset($user_data['promo_code']);
					
					if ( isset($user_data['terms']) )
						unset($user_data['terms']);
					
					if ( !isset($user_data['user_pass']) || empty($user_data['user_pass']) )
						$user_data['user_pass'] = wp_generate_password(13);
					
					// creates user with submitted data
					$user_id = wp_insert_user($user_data);
					
					if ( is_wp_error($user_id) ) {
					  sw_add_error($user_id->get_error_message());
					  break;
				  	}
					
					$wp_default_fields = array(
						'user_login',
						'user_email',
						'first_name',
						'last_name',
						'description',
						'user_pass',
						'user_url',
						'aim',
						'yim',
						'jabber'
					);
					
					// stores extra info
					foreach ($user_data as $field => $data) 
						if (!in_array($field, $wp_default_fields))
							update_user_option( $user_id, $field, $data, true );
					
					// saves default settings for user
					update_user_option( $user_id, 'default_password_nag', 'true', true );
					update_user_option( $user_id, 'show_admin_bar_front', 'false', true );
					
					// sends an email notification to admin + user
					sw_new_user_notification( $user_id, $user_data['user_pass'] );
					
					$credentials = array(
						'user_login' => $user_data['user_login'],
						'user_password' => $user_data['user_pass'],
						'remember' => true
					);
					
					// login user
					$auth = wp_signon($credentials, true);
					
					if (!sw_has_errors())
						sw_promo_register($post->ID, $auth->ID, $sw_processed_fields['promo_code']);
					
					// redirect to save cookies and show result
					$url = add_query_arg(array('sw_message' => 1, 'sw_code' => $sw_processed_fields['promo_code']), home_url($_POST['_wp_http_referer']));
					
					// reset processed fields
					$sw_processed_fields = array();
					
					// redirect
					wp_safe_redirect($url);
					exit;
					
					break;
			}
			
		}
		
	} else {
		sw_add_error(  __('Sorry, some error happened, reload the page and try again.', 'sw') );
	}
}



/**
 * Notify the blog admin of a new user, normally via email.
 *
 * @param int $user_id User ID
 * @param string $plaintext_pass Optional. The user's plaintext password
 *
 * @package Swepstakes
 * @since 0.1
 *
 */
function sw_new_user_notification($user_id, $plaintext_pass = '') {
	$user = new WP_User($user_id);

	$user_login = stripslashes($user->user_login);
	$user_email = stripslashes($user->user_email);

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
	$message .= sprintf(__('E-mail: %s'), $user_email) . "\r\n";

	@wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

	if ( empty($plaintext_pass) )
		return;

	$message  = sprintf(__('Username: %s'), $user_login) . "\r\n";
	$message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
	$message .= get_permalink(20) . "#login" . "\r\n";

	wp_mail($user_email, sprintf(__('[%s] Your username and password'), $blogname), $message);
}




/**
 * register user as participant in a promo
 *
 * @param $promo_id int id of promo
 * @param $user_id int id of user
 * @param @code_ string promo code
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_promo_register( $promo_id = false, $user_id = false, $code_ = '' ) {
	global $sw_processed_fields;
	
	if ( !$promo_id || !$user_id ) return false;
	
	$code =  wp_cache_get('promo_code');
	
	if (!$code) return;
		
	if ( isset($code['enabled']) && $code['enabled'] === 'true' && strlen($code_) != $code['digits'] ) return;
	
	if (!get_post($promo_id)) return;
	
	if (!get_userdata($user_id)) return;
	
	$user_register = array(
		'user_id' => $user_id,
		'date' => current_time('mysql'),
		'code' => $code_
	);
	
	add_post_meta($promo_id, SW_PREFIX .'promo_user', $user_register);
	wp_cache_set('promo_user', get_post_meta($promo_id, SW_PREFIX .'promo_user', false));
	
	if ( isset($code['enabled']) && $code['enabled'] === 'true' ) {
		
		sw_add_message(sprintf(__('Congratulations you have registered on this promo with this code: <code>%s</code>', 'sw'), $code_));
		
	} else {
		
		sw_add_message(__('Congratulations you have registered for this promo', 'sw'));
	}
}



/**
 * validates form field
 *
 * @param $field string name of request field
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_validate_field($field) {
	global $sw_processed_fields;
	
	$form_field = isset($_POST["sw-form-$field"]) ? trim($_POST["sw-form-$field"]) : '';
			
	if ( !isset($form_field) ) {
		sw_add_error( __('Sorry, some error happened, reload the page and try again.', 'sw') );
		return;
	}
	
	$sw_processed_fields[$field] = $form_field;
	
	$code = wp_cache_get('promo_code');
	$users = wp_cache_get('promo_user');
	$form = wp_cache_get('promo_form');
	
	switch ($field) {
		
		// user_email
		case 'terms':
			
			if ( $form_field !== 'true' ) 
				sw_add_error( __('You must accept the terms for proceed', 'sw'), 'terms' );
			
		break;
		
		// promo code
		case 'promo_code':
			
			$code_used = false;
			foreach ($users as $user) 
				if ( $user['code'] === $form_field ) $code_used = true;
			
			if ($code_used)
				sw_add_error( __('You can not participate with this code, is alredy in use.', 'sw'), 'promo_code' );
			
			$code_foundit = false;
			for ( $n = $code['start']; $n < ($code['end']+1); $n++ ) { 
				$hash = $code['base'] . '-' . $n;
				if ( sw_custom_hash($hash, $code['digits']) === $form_field ) $code_foundit = true;
			}
			
			if (!$code_foundit)
				sw_add_error( __('This code is invalid.', 'sw'), 'promo_code' );
			
			break;
			
		// user_email
		case 'user_email':
			
			if ( !is_email($form_field) ) {
				sw_add_error( sprintf(__('Invalid %s', 'sw'), sw_get_field_name($field)), $field );
			}
			
			break;
		
		// default field
		default:
		
			if ( strlen($form_field) < 3 ) {
				sw_add_error( sprintf(__('Invalid %s', 'sw'), sw_get_field_name($field)), $field );
			}
			
			break;
	}	
}



function sw_get_field_name($field) {
	
	$wp_default_fields = array(
		'first_name' => 'First Name',
		'last_name' => 'Last Name',
		'description' => 'Biographical Info',
		'user_pass' => 'Password',
		'user_url' => 'Website',
		'aim' => 'Aim',
		'yim' => 'Yahoo IM',
		'jabber' => 'Jabber / Google Talk'
	);
	$form = wp_cache_get('promo_form');
	
	if ( isset($form['fields']) && !empty($form['fields']) )
		$wp_default_fields = wp_parse_args($wp_default_fields, $form['fields']);
	
	if ( isset($wp_default_fields[$field]) )
		return __($wp_default_fields[$field]);
	
	return $field;
}



function sw_has_errors() {
	global $sw_errors;
	
	return (!empty($sw_errors));
}
function sw_reset_errors() {
	global $sw_errors;
	
	$sw_errors = array();
}
function sw_add_error($error = false, $ref = 'general') {
	global $sw_errors;
	
	if (!$error) return;
	
	$sw_errors[$ref] = $error;
}
function sw_print_errors() {
	
	if (!sw_has_errors()) return;
	
	global $sw_errors;
	
	echo "<ul class=\"" . apply_filters('sw_error_message_class', 'sw-message error') . "\">" .PHP_EOL;
	foreach ($sw_errors as $ref => $error)
		echo "<li>$error</li>" . PHP_EOL;
	echo "</ul>" . PHP_EOL;
}
function sw_has_messages() {
	global $sw_messages;
	
	return (!empty($sw_messages));
}

function sw_reset_messages() {
	global $sw_messages;
	
	$sw_messages = array();
}
function sw_add_message($message = false, $ref = 'general') {
	global $sw_messages;
	
	if (!$message) return;
	
	$sw_messages[$ref] = $message;
}
function sw_get_responses() {
	if ( isset($_GET['sw_message']) && !empty($_GET['sw_message']) ) {
		
		switch ($_GET['sw_message']) {
			
			// success
			case 1:
				
				if ( isset($_GET['sw_code']) && !empty($_GET['sw_code']) ) 
					sw_add_message(sprintf(__('Congratulations you have registered on this promo with this code: <code>%s</code>', 'sw'), $_GET['sw_code']));
				else
					sw_add_message(__('Congratulations you have registered for this promo', 'sw'));
				break;
			
			default:
				# code...
				break;
		}
		
	}
}
function sw_print_messages() {
	
	if (!sw_has_messages()) return;
	
	global $sw_messages;
	
	echo "<ul class=\"" . apply_filters('sw_message_class', 'sw-message') . "\">" .PHP_EOL;
	foreach ($sw_messages as $ref => $message)
		echo "<li>$message</li>" . PHP_EOL;
	echo "</ul>" . PHP_EOL;
}



/**
 * show promo register form
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_user_did_participate($user_id = false) {
	
	$promo_users = wp_cache_get('promo_user');
	
	if ( !$user_id || !$promo_users || empty($promo_users) ) return false;
	
	foreach ($promo_users as $user)
		if ( $user_id === $user['user_id'] )
			return true;
	
	return false;
}



/**
 * form filter: show term field
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_form_terms($output, $terms, $index) {
	global $sw_processed_fields;
	
	$processed_field = (isset($_POST['sw-form-terms'])) ? $_POST['sw-form-terms'] : '';
	
	$output = "<div class=\"". apply_filters('promo_form_terms_class', 'sw-form-field field-terms', $index) ."\">" .PHP_EOL;
	$output .= "<input type=\"checkbox\" name=\"sw-form-terms\" id=\"sw-form-terms\" class=\"checkbox sw-form-terms\" value=\"true\"" .checked($processed_field, 'true', false). ">";
	$output .= "<label for=\"sw-form-terms\" class=\"checkbox\"> " .__('I accept the', 'sw'). " <a href=\". get_permalink($terms->ID) .\" target=\"_blank\">" . apply_filters('the_title', $terms->post_title ) . "</a>.</label>" . PHP_EOL;
	$output .= "</div>" .PHP_EOL;
	
	return $output;
}



/**
 * form filter: show term submit
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_form_submit($output, $index) {
	global $sw_processed_fields;
	
	$output = "<div class=\"". apply_filters('promo_form_submit_class', 'sw-form-field field-submit', $index) ."\">" .PHP_EOL;
	$output .= "<input type=\"submit\" name=\"promo-form-submit\" id=\"promo-form-submit\" value=\"".__('Register')."\">" . PHP_EOL;
	$output .= "<span class=\"loader\">&nbsp;</span>" . PHP_EOL;
	$output .= "</div>" .PHP_EOL;
	
	return $output;
}



/**
 * form filter: show default field
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_form_field($output, $field, $name, $index) {
	global $sw_processed_fields;
	
	$processed_field = (isset($sw_processed_fields[$field])) ? $sw_processed_fields[$field] : '';
	
	$output = "<div class=\"". apply_filters('promo_form_field_class', 'sw-form-field', $index) ."\">" .PHP_EOL;
	$output .= "<label for=\"sw-form-$field\">".__($name)."</label>" .PHP_EOL;
	$output .= "<input type=\"text\" name=\"sw-form-$field\" id=\"sw-form-$field\" class=\"" . apply_filters('promo_form_input_class', "text $field") . "\" value=\"$processed_field\" tabindex=\"{$index}\" />" . PHP_EOL;
	$output .= "</div>" .PHP_EOL;
	
	return $output;
}



/**
 * form filter: show password field #################### validate password and strengh calculator
 *
 * @package Swepstakes
 * @since 0.1
 *
**/
function sw_form_field_password($output, $field, $name, $index) {
	global $sw_processed_fields;
	
	if ($field === 'user_pass') {
		
		$processed_field = (isset($sw_processed_fields[$field])) ? $sw_processed_fields[$field] : '';
		
		$output = "<div class=\"". apply_filters('promo_form_field_class', 'sw-form-field', $index) ."\">" .PHP_EOL;
		$output .= "<label for=\"sw-form-$field\">".__($name)."</label>" .PHP_EOL;
		$output .= "<input type=\"password\" name=\"sw-form-$field\" id=\"sw-form-$field\" class=\"" . apply_filters('promo_form_input_class', 'text') . "\" value=\"$processed_field\" tabindex=\"$index\" />" . PHP_EOL;
		$output .= "</div>" .PHP_EOL;
	
	}
	
	return $output;
}