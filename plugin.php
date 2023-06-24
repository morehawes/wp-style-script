<?php
/*
Plugin Name: Post CSS/JavaScript
Description: Joe Code!
Author: Joseph Hawes
Version: 1.0
*/

$pcssjs_post_types = array('page', 'post', 'lsvr_kba', 'waymark_map');

$pcssjs_fields = array(
	'pcssjs_js' => array(
		'id' => 'pcssjs_js',
		'title' => 'JavaScript',
		'type' => 'textarea'
	), 
	'pcssjs_css' => array(
		'id' => 'pcssjs_css',
		'title' => 'CSS',		
		'type' => 'textarea'
	)
);

/**
 * ======================================================== 
 * ====================== FRONT ===========================
 * ========================================================
 */

function pcssjs_head_css() {
	global $post, $pcssjs_fields;
	
	if(isset($post->ID) && $css = get_post_meta($post->ID, $pcssjs_fields['pcssjs_css']['id'], true)) {
		echo '<style type="text/css">' . $css . '</style>' . "\n";		
	}	
}
add_action('wp_head','pcssjs_head_css');

function pcssjs_footer_js() {
	global $post, $pcssjs_fields;

	if(isset($post->ID) && $js = get_post_meta($post->ID, $pcssjs_fields['pcssjs_js']['id'], true)) {
		echo '<script>' . $js . '</script>' . "\n";		
	}	
}
add_action('wp_footer', 'pcssjs_footer_js');

/**
 * ======================================================== 
 * ====================== ADMIN  ==========================
 * ========================================================
 */

/**
 * Setup admin
 */
function pcssjs_admin_init() {
	//Permissions
	if(current_user_can('manage_options')) {
		//Add custom fields
		add_action('admin_head-post-new.php', 'pcssjs_create_custom_fields_box');
		add_action('admin_head-post.php', 'pcssjs_create_custom_fields_box');

		//Save custom fields
		add_action('save_post', 'pcssjs_save_custom_fields', 10, 2);

/*
		//Add CSS
		wp_register_style('pcssjs_admin_css', plugins_url('assets/css/admin.css', dirname(__FILE__)), array(), pcssjs_get_config('plugin_version'));
		wp_enqueue_style('pcssjs_admin_css');	

		//Add JS
		wp_register_script('pcssjs_admin_js', plugins_url('assets/js/admin.js', dirname(__FILE__)), array('jquery'), pcssjs_get_config('plugin_version'));
		wp_enqueue_script('pcssjs_admin_js');
*/
	}
}
add_action('admin_init', 'pcssjs_admin_init');

/**
 * ================= CUSTOM FIELDS ========================
 */
 
/**
 * Create the custom fields box
 */
function pcssjs_create_custom_fields_box() {
	global $pcssjs_post_types;
	
	foreach($pcssjs_post_types as $post_type) {
		add_meta_box('pcssjs-custom-fields', 'Joe Code', 'pcssjs_create_custom_field_form', $post_type, 'normal', 'high');
	}
}

/**
 * Create the custom field form
 */
function pcssjs_create_custom_field_form() {	
	global $post, $pcssjs_fields;
	
	$out = '<div id="pcssjs-custom-field-container">' . "\n";
	
	foreach($pcssjs_fields as $field) {
		$out .= pcssjs_create_custom_field_input($field, get_post_meta($post->ID, $field['id'], true));		
	}	

	$out .= '</div> <!-- END #pcssjs-custom-field-container -->' . "\n";

	echo $out;
}

/**
 * Create the custom fields inputs
 */
function pcssjs_create_custom_field_input($field, $set_value = false) {
	$out = '';
	
	//Container
	$out .= '<div class="control-group" id="' . $field['id'] . '-container">' . "\n";

	//Label
	$out .= '	<label class="control-label" for="' . $field['id'] . '">' . $field['title'] .  '</label>' . "\n";
	$out .= '	<div class="controls">' . "\n";				

	//Default type
	if(! array_key_exists('type', $field)) {
		$field['type'] = 'text';
	}
	
	//Create input
	$out .= pcssjs_create_input($field, $set_value);

	//Tip
	if(isset($field['tip']) && $field['tip']) {
		$out .= ' <a class="pcssjs-tooltip" data-title="' . $field['tip'] . '';
		if(array_key_exists('tip_link', $field)) {
			$out .= ' Click for more details." href="' . $field['tip_link'] . '" target="_blank"';					
		} else {
			$out .= '" href="#" onclick="return false;"';
		}
		$out .= '>?</a>';
	}
	
	$out .= '	</div>' . "\n";								
	$out .= '</div>' . "\n";
	
	return $out;
}

/**
 * Save the custom field data
 */
function pcssjs_save_custom_fields($post_id, $post) {
	global $pcssjs_fields;
	
	//Ensure the user clicked the Save/Publish button
	//Credit: https://tommcfarlin.com/wordpress-save_post-called-twice/
	if(! (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id))) {	
		foreach($pcssjs_fields as $field) {
			//Has value
			if(isset($_POST[$field['id']]) && $_POST[$field['id']]) {
				update_post_meta($post_id, $field['id'], $_POST[$field['id']]);			
			//No value
			} else {
				delete_post_meta($post_id, $field['id']);
			}
		}
	}
}

/**
 * Build a HTML input
 */
function pcssjs_create_input($field, $set_value) {
	$out = '';
	
	switch($field['type']) {
		case 'select' :
			$out .= '		<select name="' . $field['id'] . '" id="' . $field['id'] . '">' . "\n";
			foreach($field['options'] as $value => $description) {
				//Always use strings
				$value = (string)$value;
				
				$out .= '			<option value="' . $value . '"';
				//Has this value already been set
				if($set_value === $value) {
					$out .= ' selected="selected"';
				//Do we have a default?
				}	elseif($set_value === false && (array_key_exists('default', $field) && $field['default'] == $value)) {
					$out .= ' selected="selected"';				
				}		
				$out .= '>' . $description . '</option>' . "\n";
			}
			$out .= '		</select>' . "\n";
			break;
		case 'checkbox' :
			//Value submitted?
			$checked = false;

			if($set_value && ($set_value == 'true' || $set_value == $field['value'])) {
				$checked = true;
			} elseif($field['default'] == 'true') {
				$checked = true;								
			}
			$value = ($field['value']) ? $field['value'] : 'true';
			$out .= '		<input type="checkbox" name="' . $field['id'] . '" value="' . $value . '" id="' . $field['id'] . '"';
			if($checked) {
				$out .= ' checked="checked"';			
			}
			$out .= ' />' . "\n";			
			break;
		case 'radio' :
			foreach($field['options'] as $value => $description) {
				$checked = false;

				//Always use strings
				$value = (string)$value;
				
				//If we have a stored value
				if($set_value === $value) {
					$checked = true;
				//Otherwise is this the default value?
				} elseif($set_value === false && $value == $field['default']) {
					$checked = true;
				}
				$out .= '<div class="radio">' . "\n";
				$out .= '	<input type="radio" name="' . $field['id'] . '" value="' . $value . '"';
				if($checked) {
					$out .= ' checked="checked"';			
				}				
				$out .= ' />' . "\n";						
				$out .= $description . '<br />' . "\n";						
				$out .= '</div>' . "\n";
			}
			break;						
		case 'textarea' :
			$out .= '		<textarea name="' . $field['id'] . '" id="' . $field['id'] . '">';
			//Do we have a value for this post?
			if($value = htmlspecialchars($set_value)) {
				$out .= $value;
			//Do we have a default?
			}	elseif(array_key_exists('default', $field)) {
				$out .= $field['default'];
			}
			$out .= '</textarea>' . "\n";
			break;
		case 'text' :
		default :
			$out .= '		<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '"';
			//Do we have a value for this post?
			if($value = htmlspecialchars($set_value)) {
				$out .= ' value="' . $value . '"';
			//Do we have a default?
			}	elseif(array_key_exists('default', $field)) {
				$value = $field['default'];
				
				$out .= ' value="' . $value . '"';			
			}
			$out .= ' />' . "\n";
			break;
	}	
	
	return $out;
}