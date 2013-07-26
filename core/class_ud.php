<?php
/**
 * UsabilityDynamics General UI Classes
 *
 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 *
 * @todo Add function to check that latest version of UD classes is loaded if others exists
 * @version 1.3
 * @author Andy Potanin <andy.potanin@twincitiestech.com>
 * @package UsabilityDynamics
*/


if(!class_exists('WPP_UD_UI')):

/**
 * UsabilityDynamics General UI Class
 *
 * Used for displaying common elements such as input fields. Uses the defined value of UD_PREFIX to prefixes
 *
 * Example:
 * <code>
 * echo WPP_UD_UI::checkbox("name=send_newsletter&group=settings&value=true&label=If checked, you will receive our newsletters.", $send_newsletter);
 * </code>
 *
 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 *
 * @version 1.3
 * @author Andy Potanin <andy.potanin@twincitiestech.com>
 * @package UsabilityDynamics
*/


class WPP_UD_UI {

/**
 * Display visual editor forms: TinyMCE, or HTML, or both.
 *
 * The amount of rows the text area will have for the content has to be between
 * 3 and 100 or will default at 12. There is only one option used for all users,
 * named 'default_post_edit_rows'.
 *
 * If the user can not use the rich editor (TinyMCE), then the switch button
 * will not be displayed.
 *
 * @since 2.1.0
 *
 * @param string $content Textarea content.
 * @param string $id Optional, default is 'content'. HTML ID attribute value.
 * @param string $prev_id Optional, default is 'title'. HTML ID name for switching back and forth between visual editors.
 * @param bool $media_buttons Optional, default is true. Whether to display media buttons.
 * @param int $tab_index Optional, default is 2. Tabindex for textarea element.
 */
function the_editor($content, $id = 'content', $prev_id = 'title', $media_buttons = true, $tab_index = 2) {
	$rows = get_option('default_post_edit_rows');
	if (($rows < 3) || ($rows > 100))
		$rows = 12;

	if ( @!current_user_can( 'upload_files' ) )
		$media_buttons = false;

 
	$class = '';
 ?>
	<div class="editor-toolbar" id="editor-toolbar-<?php echo $id; ?>">

	<div class="zerosize"><input accesskey="e" type="button" onclick="switchEditors.go('<?php echo $id; ?>')" /></div>
<?php	 
			$class = " class='theEditor'";
			add_filter('the_editor_content', 'wp_richedit_pre'); ?>
			<a id="edButtonHTML" class="hide-if-no-js" onclick="switchEditors.go('<?php echo $id; ?>', 'html');"><?php _e('HTML'); ?></a>
			<a id="edButtonPreview" class="active hide-if-no-js" onclick="switchEditors.go('<?php echo $id; ?>', 'tinymce');"><?php _e('Visual'); ?></a>
<?php	 
 

	if ( $media_buttons ) { ?>
		<div id="media-buttons" class="hide-if-no-js">
<?php	do_action( 'media_buttons' ); ?>
		</div>
<?php
	} ?>
	</div>
 
	<div id="quicktags-<?php echo $id; ?>"><?php
	wp_print_scripts( 'quicktags' ); ?>
	<script type="text/javascript">edToolbar()</script>
	</div>

<?php
	$the_editor = apply_filters('the_editor', "<div id='editorcontainer'><textarea rows='$rows'$class cols='40' name='$id' tabindex='$tab_index' id='$id'>%s</textarea></div>\n");
	$the_editor_content = apply_filters('the_editor_content', $content);

	printf($the_editor, $the_editor_content);

?>
	<script type="text/javascript">
	edCanvas = document.getElementById('<?php echo $id; ?>');
	</script>
<?php
}


 /** 
  * Determine if an item is in array and return checked
  *
  *
  * @since 1.5.17
  */
 function checked_in_array($item, $array) {
   
    if(is_array($array) && in_array($item, $array)) {
      echo ' checked="checked" ';
    }
        
 }
 
 
  /**
   * Formats phone number for display
   *
   * @since 1.0
   * @param string $phone_number
   * @return string $phone_number
   */
  function format_phone_number($phone_number) {

     $phone_number = ereg_replace("[^0-9]",'',$phone_number);
    if(strlen($phone_number) != 10) return(False);
    $sArea = substr($phone_number,0,3);
    $sPrefix = substr($phone_number,3,3);
    $sNumber = substr($phone_number,6,4);
    $phone_number = "(".$sArea.") ".$sPrefix."-".$sNumber;

    return $phone_number;
  }


  

	/**
	 * Display post categories form fields.
	 *
	 * @since 2.6.0
	 *
	 * @param object $post
	 */
	function object_taxonomy_meta_box( $post, $box ) {
	
		
		$defaults = array('taxonomy' => 'category');
		if ( !isset($box['args']) || !is_array($box['args']) )
			$args = array();
		else
			$args = $box['args'];
		extract( wp_parse_args($args, $defaults), EXTR_SKIP );
		$tax = get_taxonomy($taxonomy);

		
		?>
 		
		<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
			<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
				<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
				<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
			</ul>

			<div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
				<ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
					<?php $popular_ids = wp_popular_terms_checklist($taxonomy); ?>
				</ul>
			</div>

			<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
				<?php
				$name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy . ']';
				echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
				?>
				<ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
					<?php wp_terms_checklist($post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids ) ) ?>
				</ul>
			</div>
		<?php if ( !current_user_can($tax->cap->assign_terms) ) : ?>
		<p><em><?php _e('You cannot modify this taxonomy.'); ?></em></p>
		<?php endif; ?>
		<?php if ( current_user_can($tax->cap->edit_terms) ) : ?>
				<div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
					<h4>
						<a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js" tabindex="3">
							<?php
								/* translators: %s: add new taxonomy label */
								printf( __( '+ %s' ), $tax->labels->add_new_item );
							?>
						</a>
					</h4>
					<p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
						<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $tax->labels->add_new_item; ?></label>
						<input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" tabindex="3" aria-required="true"/>
						<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
							<?php echo $tax->labels->parent_item_colon; ?>
						</label>
						<?php wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;', 'tab_index' => 3 ) ); ?>
						<input type="button" id="<?php echo $taxonomy; ?>-add-submit" class="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add button category-add-sumbit" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" tabindex="3" />
						<?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy, false ); ?>
						<span id="<?php echo $taxonomy; ?>-ajax-response"></span>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}


	function user_display_name($user_id, $args = '') {
		$defaults = array('show_organization' => 'true');
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		if(empty($user_id))
			return;
		
		if($maybe_user_id = email_exists($user_id))
			$user = get_userdata($maybe_user_id);
		else
			$user = get_userdata($user_id);
 
			
		if($show_organization == 'true' && $user->user_organization)
			$rank_org  = (empty($user->user_rank) ? "" : ", $user->user_rank") . ", $user->user_organization";


		if(!empty($user->first_name) && !empty($user->last_name))
			$display_name = "$user->first_name $user->last_name" . $rank_org;
		elseif(!empty($user->display_name))
			$display_name = $user->display_name;
		else
			$display_name = $user->user_login;

		return $display_name;

	}



	function sidebar_size_css($col_width) { ?>
	<style type="text/css">
	  .inner-sidebar{ width: <?php echo $col_width; ?>px; }
	  .has-right-sidebar #post-body { margin-right:-<?php echo ($col_width + 50); ?>px; }
	  .has-right-sidebar #post-body-content  { margin-right:<?php echo ($col_width + 20); ?>px; }
	  .inner-sidebar #side-sortables { width:<?php echo ($col_width); ?>px; }
	 </style>
	<?php

	}

	function fix_metabox($metabox_name, $args = '') {

		$defaults = array('allow_drag' => 'false');
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

	?>
	<style type="text/css">
	#<?php echo $metabox_name; ?> .inside {margin:0;}
	#<?php echo $metabox_name; ?> .handlediv {display:none;}

	<?php if($allow_drag == 'true'): ?>
	#<?php echo $metabox_name; ?> h3{background: transparent; width: 200px; color: #CDCDCD;}

	<?php endif; ?>

	<?php if($allow_drag == 'false'): ?>
	#<?php echo $metabox_name; ?> .handlediv {display:none;}
	#<?php echo $metabox_name; ?> h3{display:none;}
	<?php endif; ?>
	
	#<?php echo $metabox_name; ?> {background: transparent;border: 0px !important;}
	#<?php echo $metabox_name; ?> .form-field label{font-size: 15px;}
	</style>
	<?php
	}
  
  /**
   * Return a link to a post or page from passed variable.
   *
   * Intended to allow for searching for most likely post/page based on passed string.
   * This is supposed to prevent broken links when a post_id changes for whatever reason.
   *
   *  If its a number, then assumes its the id
   *  If it resembles a slug, then get the first slug match
   *  If not a slug, search by
   *
   * @since 1.1
   * @uses get_permalink() to get the link once an id is identified
   * @link http://usabilitydynamics/codex/UD_UI/post_link
   *
   * @todo  This function needs work, searching should be more comprehensive, page/post popularity should be considered in logic
   *
   * @return string Front-end link to post/page/custom post
   */
  function post_link($title = false) {
    global $wpdb;

    if(!$title)
      return get_bloginfo('url');

    if(is_numeric($title))
      return get_permalink($title);

        if($id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name = '$title'  AND post_status='publish'"))
      return get_permalink($id);

    if($id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE LOWER(post_title) = '".strtolower($title)."'   AND post_status='publish'"))
      return get_permalink($id);



  }



  /**
   * Shorthand function for drawing checkbox input fields.
   *
   * Draws a checkbox using the plugin prefix.
   * Also creates a hidden input field for the opposite value of the checkbox.
   *
   * The list of checkbox arguments are 'status', 'orderby', 'comment_date_gmt','order', 'number', 'offset', and 'post_id'.
   *
   * List of default arguments are as follows:
   * 'id' - False
   * 'class' - False
   * 'value' - 'true'
   * 'label' - False
   * 'group' - False
   * 'maxlength' - False
   *
   * @since 1.0
   * @uses wp_parse_args() Creates an array from string $args.
   * @link http://usabilitydynamics/codex/UD_UI/checkbox
   *
   * @param string $args List of arguments to overwrite the defaults.
   * @param bool $checked Option, default is false. Whether checkbox is checked or not.
   * @return string Checkbox input field and hidden field with the opposive value
   */
  function checkbox($args = '', $checked = false) {
    $defaults = array(
      'name' => '', 
      'id' => false,
      'class' => false, 
      'group' => false,
      'special' => '',
      'value' => 'true', 
      'label' => false, 
      'maxlength' => false
    );
    
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    // Get rid of all brackets
    if(strpos("$name",'[') || strpos("$name",']')) {

      $class_from_name = $name;
    
      //** Remove closing empty brackets to avoid them being displayed as __ in class name */
      $class_from_name = str_replace('][]', '', $class_from_name);
      
      $replace_variables = array('][',']','[');
      $class_from_name = UD_PREFIX . str_replace($replace_variables, '_', $class_from_name);
    } else {
      $class_from_name = UD_PREFIX . $name;
    }


    // Setup Group
    if($group) {
      if(strpos($group,'|')) {
        $group_array = explode("|", $group);
        $count = 0;
        foreach($group_array as $group_member) {
          $count++;
          if($count == 1) {
            $group_string .= $group_member;
          } else {
            $group_string .= "[{$group_member}]";
          }
        }
      } else {
        $group_string = $group;
      }
    }


    if(is_array($checked)) {
      
      if(in_array($value, $checked)) {
        $checked = true;
      } else {
        $checked = false;      
      }
    } else {
      $checked = strtolower($checked);
      if($checked == 'yes')   $checked = 'true';
      if($checked == 'true')   $checked = 'true';
      if($checked == 'no')   $checked = false;
      if($checked == 'false') $checked = false;
    }


    $id          =   ($id ? $id : $class_from_name);

    $insert_id       =   ($id ? " id='$id' " : " id='$class_from_name' ");
    $insert_name    =   (isset($group_string) ? " name='".$group_string."[$name]' " : " name='$name' ");
    $insert_checked    =   ($checked ? " checked='checked' " : " ");
    $insert_value    =   " value=\"$value\" ";
    $insert_class     =   " class='$class_from_name $class ".UD_PREFIX."checkbox " . ($group ? UD_PREFIX . $group . '_checkbox' : ''). "' ";
    $insert_maxlength  =   ($maxlength ? " maxlength='$maxlength' " : " ");

    
    $opposite_value = '';
    
    // Determine oppositve value
    switch ($value) {
      case 'yes':
      $opposite_value = 'no';
      break;

      case 'true':
      $opposite_value = 'false';
      break;

      case 'open':
      $opposite_value = 'closed';
      break;

    }
    
    $return = '';

    // Print label if one is set
    if($label) $return .= "<label for='$id'>";

    // Print hidden checkbox if there is an opposite value */
    if($opposite_value) {
      $return .= '<input type="hidden" value="' . $opposite_value. '" ' . $insert_name . ' />';
    }

    // Print checkbox
    $return .= "<input type='checkbox' $insert_name $insert_id $insert_class $insert_checked $insert_maxlength  $insert_value $special />";
    if($label) $return .= " $label</label>";

    return $return;
  }


  /**
   * Shorthand function for drawing a textarea
   *
   *
   * @since 1.0
   * @uses wp_parse_args() Creates an array from string $args.
   * @link http://usabilitydynamics/codex/UD_UI/input
   *
   * @param string $args List of arguments to overwrite the defaults.
    * @return string Input field and hidden field with the opposive value
   */

  function textarea($args = '') {
    $defaults = array('name' => '', 'id' => false,  'checked' => false,  'class' => false, 'style' => false, 'group' => '','special' => '','value' => '', 'label' => false, 'maxlength' => false);
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );


    // Get rid of all brackets
    if(strpos("$name",'[') || strpos("$name",']')) {
      $replace_variables = array('][',']','[');
      $class_from_name = $name;
      $class_from_name = UD_PREFIX . str_replace($replace_variables, '_', $class_from_name);
    } else {
      $class_from_name = UD_PREFIX . $name;
    }


    // Setup Group
    if($group) {
      if(strpos($group,'|')) {
        $group_array = explode("|", $group);
        $count = 0;
        foreach($group_array as $group_member) {
          $count++;
          if($count == 1) {
            $group_string .= "$group_member";
          } else {
            $group_string .= "[$group_member]";
          }
        }
      } else {
        $group_string = "$group";
      }
    }

    $id          =   ($id ? $id : $class_from_name);

    $insert_id       =   ($id ? " id='$id' " : " id='$class_from_name' ");
    $insert_name    =   ($group_string ? " name='".$group_string."[$name]' " : " name=' ".UD_PREFIX."$name' ");
    $insert_checked    =   ($checked ? " checked='true' " : " ");
    $insert_style    =   ($style ? " style='$style' " : " ");
    $insert_value    =   ($value ? $value : "");
    $insert_class     =   " class='$class_from_name input_textarea $class' ";
    $insert_maxlength  =   ($maxlength ? " maxlength='$maxlength' " : " ");

    // Print label if one is set

    // Print checkbox
    $return .= "<textarea $insert_name $insert_id $insert_class $insert_checked $insert_maxlength $special $insert_style>$insert_value</textarea>";


    return $return;
  }


  /**
   * Shorthand function for drawing regular or hidden input fields.
   *
   * Returns an input field using the plugin-specific prefix.
   *
   * The list of input field arguments are 'name', 'group', 'special','value', 'type', 'hidden', 'style', 'readonly', and 'label'.
   *
   * List of default arguments are as follows:
   * 'hidden' - False
   * 'style' - False
   * 'readonly' - False
   * 'label' - False
   *
   * @since 1.3
   * @uses wp_parse_args() Creates an array from string $args.
   * @link http://usabilitydynamics/codex/UD_UI/input
   *
   * @param string $args List of arguments to overwrite the defaults.
   * @param string $value Value may be passed in arg array or seperately
    * @return string Input field and hidden field with the opposive value
   */
  function input($args = '', $value = false) {
    $defaults = array('name' => '', 'group' => '','special' => '','value' => $value, 'title' => '', 'type' => 'text', 'class' => false, 'hidden' => false, 'style' => false, 'readonly' => false, 'label' => false);
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    // Add prefix
    //$name = UD_PREFIX . "$name";

    if($class) {
      $class = UD_PREFIX . "$class";
    }


    // if [ character is present, we do not use the name in class and id field
    if(!strpos("$name",'[')) {
      $id = $name;
      $class_from_name = $name;
    }
    
    $return = '';

    if($label) $return .= "<label for='$name'>";
    $return .= "<input ".($type ?  "type=\"$type\" " : '')." ".($style ?  "style=\"$style\" " : '')." id=\"$id\" class=\"".($type ?  "" : "input_field")." $class_from_name $class ".($hidden ?  " hidden " : '').""  .($group ? "group_$group" : ''). " \"    name=\"" .($group ? $group."[".$name."]" : $name). "\"   value=\"".stripslashes($value)."\"   title=\"$title\" $special ".($type == 'forget' ?  " autocomplete='off'" : '')." ".($readonly ?  " readonly=\"readonly\" " : "")." />";
    if($label) $return .= " $label </label>";

    return $return;
  }


  /**
   * Inserts JavaScript functions for UI
    *
   * @todo May be best to be in an external file
   *
    * @param $args Optional, pass 'return' => true to return content. Echo on default
   * @since 1.1
   */

  function print_admin_scripts($args = '') {
    $defaults = array('return' => false);
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );


    ob_start();

    ?>
    <script type="text/javascript">
      jQuery(document).ready(function() {


      });
      /**
       * Displays a JSON response
       *
       * If a #message box does not exist on page, one is created after .wrap h2
       *
       * @param Array
       *
       */
      function ud_json_response(data) {

        // Create message element if does not exist
        if(jQuery('#message').length == 0) {
          var message_element = '<div style="display: none;" class="updated fade ud_inserted_element" id="message"></div>'
          jQuery(message_element).insertAfter('.wrap h2');
        }

        // Re-get the message element
        var message_element = jQuery(".wrap #message");

        // If data.success = 'false' change class to .error
        if(data.success == 'false') {
          jQuery(message_element).removeClass('updated');
          jQuery(message_element).addClass('error');
        }

        // If data.response = 'true' change class to .updated
        if(data.success == 'true') {
          jQuery(message_element).removeClass('error');
          jQuery(message_element).addClass('updated');
        }

        // Show and print message
        jQuery(message_element).show();
        jQuery(message_element).html("<p>" +data.message+"</p>");

      }

      /**
       * Displays a processing action
       *
       * If a #message box does not exist on page, one is created after .wrap h2
       *
       * @param Array
       *
       */
      function ud_json_processing(data) {

        // Create message element if does not exist
        if(jQuery('#message').length == 0) {
          var message_element = '<div style="display: none;" class="updated fade ud_inserted_element" id="message"></div>'
          jQuery(message_element).insertAfter('.wrap h2');
        }

        // Re-get the message element
        var message_element = jQuery(".wrap #message");
         jQuery(message_element).addClass('updated');


        // Show and print message
        jQuery(message_element).show();
        jQuery(message_element).html("<p><?php _('Processing...','wpp') ?></p>");

      }



    </script>


    <?php

    $content = ob_get_contents();
    ob_end_clean();

    if($return)
      return $content;

    echo $content;
  }

  /**
   * Loads UD admin scripts into admin header
    *
   * @uses add_action() Calls 'add_action'
   *
   */
  function use_ud_scripts() {
    add_action('admin_head', array('WPP_UD_UI', 'print_admin_scripts'));
  }

  /**
   * Displays the UD UI log page
    *
   * @todo Add button or link to delete log
   * @todo Add nonce to clear_log functions
   * @since 1.0
   * @uses WPP_UD_F::delete_log()
   * @uses WPP_UD_F::get_log()
   * @uses WPP_UD_F::nice_time()
   * @uses add_action() Calls 'admin_menu' hook with an anonymous (lambda-style) function which uses add_menu_page to create a UI Log page
   * @uses add_action() Calls 'admin_menu' hook with an anonymous (lambda-style) function which uses add_menu_page to create a UI Log page
   */

  function show_log_page() {

    if($_REQUEST['ud_action'] == 'clear_log')
      WPP_UD_F::delete_log();

    ?>
    <style type="text/css">

      .ud_event_row b { background:none repeat scroll 0 0 #F6F7DC;
padding:2px 6px;}
    </style>

    <div class="wrap">
    <h2><?php _e('UD Log Page for','wpp') ?> get_option('<?php echo UD_PREFIX . 'log'; ?>');
    <a href="<?php echo admin_url("admin.php?page=ud_log&ud_action=clear_log"); ?>" class="button"><?php _e('Clear Log','wpp') ?></a>
    </h2>


    <table class="widefat">
      <thead>
      <tr>
        <th style="width: 150px"><?php _e('Timestamp','wpp') ?></th>
        <th><?php _e('Type','wpp') ?></th>
        <th><?php _e('Event','wpp') ?></th>
        <th><?php _e('User','wpp') ?></th>
        <th><?php _e('Related Object','wpp') ?></th>
      </tr>
      </thead>

      <tbody>
      <?php foreach(UD_F::get_log() as $event): ?>
      <tr class="ud_event_row">
        <td><?php echo UD_F::nice_time($event[0]); ?></td>
        <td><?php echo $event[1]; ?></td>
        <td><?php echo $event[2]; ?></td>
        <td><?php $user_data = get_userdata($event[2]); echo $user_data->display_name; ?></td>
        <td><?php echo $event[4]; ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php

   }


}

endif; /* f(!class_exists('UD_UI')): */



if(!class_exists('WPP_UD_F')):


/**
 * General Shared Functions used in UsabilityDynamics and TwinCitiesTech.com plugins and themes.
 *
 * Used for performing various useful functions applicable to different plugins.
 *
 * @version 1.5
 * @link http://usabilitydynamics/codex/UD_F
 * @package UsabilityDynamics
 */

class WPP_UD_F {


  function start_timer() {  
    global $ud_start_timer;
    
    $mtime = microtime(); 
    $mtime = explode(" ",$mtime); 
    $mtime = $mtime[1] + $mtime[0]; 
    $ud_start_timer = $mtime; 
    
  }
  
  function end_timer($note = false) {
    global $ud_start_timer;
    
    $mtime = microtime(); 
    $mtime = explode(" ",$mtime); 
    $mtime = $mtime[1] + $mtime[0]; 
    $endtime = $mtime; 
    $totaltime = ($endtime - $ud_start_timer); 
    
    echo "\n <br />Time: {$note}: ".$totaltime;   

  }


  function get_column_names($table) {
    global $wpdb;

    $table_info = $wpdb->get_results("SHOW COLUMNS FROM $table");


    if(empty($table_info))
      return;


    foreach($table_info as $row) {

      $columns[] = $row->Field;
    }


    return $columns;



  }

  /**
   * Get a URL of a page.
   *
   *
   * @version 1.5
  **/
  function objectToArray($object) {

      if(!is_object( $object ) && !is_array( $object )) {
        return $object;
      }

      if(is_object($object) ) {
      $object = get_object_vars( $object );
      }

      return array_map(array('WPP_UD_F' , 'objectToArray'), $object );
   }


  /**
   * Get a URL of a page.
   *
   * @todo This function is dependant on $wp_properties but should be general - potanin@ud
   *
   * @version 1.6
  **/
  function base_url($page = '') {
    global $wpdb,  $wp_properties;

    $permalink = get_option('permalink_structure');

    if ( '' != $permalink) {
      // Using Permalinks

            if(!is_numeric($page)) { 
                $page = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts where post_name = '$page'");
            }
            
            // If the page doesn't exist, return default url (base_slug)
            if(empty($page)) {
                return site_url() . "/" . $wp_properties['configuration']['base_slug'] . '/';
            }
            
            return get_permalink($page);


    } else {
      // Not using permalinks

      // If a slug is passed, convert it into ID
      if(!is_numeric($page)) {
        $page_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts where post_name = '$page' AND post_status = 'publish' AND post_type = 'page'");

        // In case no actual page_id was found, we continue using non-numeric $page, it may be 'property'
        if(!$page_id)
          $query = '?p=' . $page;
        else
          $query = '?page_id=' . $page_id;

      } else {
        $page_id = $page;
        $query = '?page_id=' . $page_id;
      }

    $permalink = home_url($query);

    }

    return $permalink;

  }

  /**
   * Merges any number of arrays / parameters recursively,
   *
   * Replacing entries with string keys with values from latter arrays.
   * If the entry or the next value to be assigned is an array, then it
   * automagically treats both arguments as an array.
   * Numeric entries are appended, not replaced, but only if they are
   * unique
   *
   * @source http://us3.php.net/array_merge_recursive
   * @version 1.4
  **/

   static function array_merge_recursive_distinct () {
    $arrays = func_get_args();
    $base = array_shift($arrays);
    if(!is_array($base)) $base = empty($base) ? array() : array($base);
    foreach($arrays as $append) {
    if(!is_array($append)) $append = array($append);
    foreach($append as $key => $value) {
      if(!array_key_exists($key, $base) and !is_numeric($key)) {
      $base[$key] = $append[$key];
      continue;
      }
      if(@is_array($value) or @is_array($base[$key])) {
      $base[$key] = WPP_UD_F::array_merge_recursive_distinct($base[$key], $append[$key]);
      } else if(is_numeric($key)) {
      if(!in_array($value, $base)) $base[] = $value;
      } else {
      $base[$key] = $value;
      }
    }
    }
    return $base;
  }


  /**
   * Adds a post object to cache.
   *
   * Creates cache tables if not created, and adds an object
   *
   *
   * @version 1.4
  **/
  function add_cache($id = false, $type = false, $data = false) {
    global $wpdb;

    if(!is_numeric($id))
      return false;

    if(empty($data))
      return false;

    if(empty($type))
      return false;

    $tablename = $wpdb->prefix . "ud_cache";



    // Check if table exists
    if(WPP_UD_F::check_cache_table()) {

      // Remove old cahce
      $wpdb->query("DELETE FROM $tablename WHERE id = '$id'");


      if(!$wpdb->insert($tablename, array('id' => $id, 'type' => $type, 'data' => serialize($data))))
        return false;


      return true;
    }

    return false;

  }

   /**
   * Gets cache if it exists
   *
   * Creates cache tables if not created, and adds an object
   *
   *
   * @version 1.4
  **/
  function get_cache($id = false) {
    global $wpdb;

    if(!is_numeric($id))
      return false;


    $tablename = $wpdb->prefix . "ud_cache";

    // Check if table exists
    if(!WPP_UD_F::check_cache_table())
      return;

    $data = $wpdb->get_var("SELECT data FROM $tablename WHERE id = '$id'");

    $data = unserialize($data);

    if(is_array($data))
      return $data;


    return false;

  }




  /**
   * Checks if a database table exists, if not, create it
   *
   *
   * @version 1.4
  **/
  function check_cache_table($table_name = 'ud_cache') {
    global $wpdb;

    // Get tablename
    $table_name = $wpdb->prefix . $table_name;

    // If table already exists do nothing
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name)
      return true;

    WPP_UD_F::log(__("Cache table does not exist. Attempting to create:",'wpp'));

    // Table does not exist, make it
    $sql = "CREATE TABLE " . $table_name . " (
        id bigint(20) NOT NULL,
        type varchar(200) NOT NULL DEFAULT '',
        data text NOT NULL,
        modified TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
      );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    WPP_UD_F::log(sprintf(__('SQL Ran: %s , verifying existance.','wpp'), $sql ));


    // Verify it exists
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
      WPP_UD_F::log(__("Table created and exists.",'wpp'));
      return true;
    }

    WPP_UD_F::log(__("Table not created.",'wpp'));

    // Something went terribly wrong
    return false;

  }


   /**
   * Returns a URL to a post object based on passed variable.
   *
   * If its a number, then assumes its the id, If it resembles a slug, then get the first slug match.
   *
   * @since 1.0
   * @param string $title A page title, although ID integer can be passed as well
   * @return string The page's URL if found, otherwise the general blog URL
   */
  function post_link($title = false) {
    global $wpdb;

    if(!$title)
      return get_bloginfo('url');

    if(is_numeric($title))
      return get_permalink($title);

        if($id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name = '$title'  AND post_status='publish'"))
      return get_permalink($id);

    if($id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE LOWER(post_title) = '".strtolower($title)."'   AND post_status='publish'"))
      return get_permalink($id);

  }




  /**
   * Add an entry to the plugin-specifig log.
   *
   * Creates log if one does not exist.
   *
   * Example:
   * <code>
   * WPP_UD_F::log("Settings updated");
   * </code>
   *
   * $param string Event description

   * @version 1.0
    * @uses get_option()
   * @uses update_option()
   * @uses check_prefix()
    * @return bool True if event added succesfully.
   *
   */
  	function log($event, $type = 'default', $object_id = false) {
    UD_F::check_prefix();
    $current_user = wp_get_current_user();

    $log_name = UD_PREFIX . 'log';
    $current_user_id = $current_user->ID;

    $this_log = get_option($log_name);

    // If no session log created yet, create one
    if(!is_array($this_log)) {
      $this_log = array();
      array_push($this_log, array(time(), __("Log Started.",'wpp')));
    }

    // Insert event into session
    array_push($this_log, array(time(),$event, $current_user_id));

    update_option($log_name, $this_log);

    return true;
  }

  /**
   * Used to get the current plugin's log created via UD class
   *
   * If no log exists, it creates one, and then returns it in chronological order.
   *
   * Example to view log:
   * <code>
   * print_r(WPP_UD_F::get_log());
   * </code>
   *
   * $param string Event description
    * @uses get_option()
   * @uses update_option()
   * @uses check_prefix()
    * @return array Using the get_option function returns the contents of the log.
   *
   */
  function get_log() {
    WPP_UD_F::check_prefix();

    $log_name = UD_PREFIX . 'log';
    $this_log = get_option($log_name);

    // If no session log created yet, create one
    if(!is_array($this_log)) {
      $this_log = array();
      array_push($this_log, array(time(),__("Log Started.",'wpp')));
    }
    update_option($log_name, $this_log);

    return array_reverse(get_option($log_name));
  }

  /**
   * Delete UD log for this plugin.
   *
   *
   * @uses update_option()
   * @uses check_prefix()
    *
   */
  function delete_log() {
    WPP_UD_F::check_prefix();

    $log_name = UD_PREFIX . 'log';

    delete_option($log_name);

  }

  /**
   * Check if a prefix is defined, if not defines one automatically.
   *
   * @todo Needs functionality to generate the $auto_prefix based on plugin name, or something.
   * @param string $prefix Optional, set the prefix.
   * @return bool True is prefix exists or was set, false otherwise
   *
   */
  function check_prefix($prefix = false) {

    // Get pfix
    if(defined('UD_PREFIX'))
      return true;

    if($prefix) {
      define(UD_PREFIX, $auto_prefix);
      return true;
    }

    // Generate auto_prefix based on plugin's folder
    $auto_prefix = "ui_";

    // Set prefix using auto-generated one
    define(UD_PREFIX, $auto_prefix);

    // if prefix wasn't auto-generated:
    // return false


  }


  /**
   * Displays the numbers of days elapsed between a provided date and today.
   *
   * Dates can be passed in any formation recognizable by strtotime()
   *
   * @param string $date1 Date to use for calculation.
   * @param bool $return_number Optional, default is false. If true, forces function to return an integer of days difference.
   *
    * @uses get_option()
   * @uses update_option()
   * @uses strtotime()
   * @uses check_prefix()
   * @uses apply_filters() Calls 'UD_F_days_since' filter on provided date.
    * @return string|int Returns either a string value of days passed, integer if $return_number passed as true, or blank value if no date is passed.
   *
   */
  function days_since($date1, $return_number = false) {

    if(empty($date1)) return "";

    // In case a passed date cannot be ready by strtotime()
    $date1 = apply_filters('UD_F_days_since', $date1);

    $date2 = date("Y-m-d");
    $date1 = date("Y-m-d", strtotime($date1));


    // determine if future or past
    if(strtotime($date2) < strtotime($date1)) $future = true;

    $difference = abs(strtotime($date2) - strtotime($date1));
    $days = round(((($difference/60)/60)/24), 0);

    if($return_number)
      return $days;

    if($days == 0) __('Today', 'wpp');
    elseif($days == 1) { return($future ? __("Tomorrow ",'wpp') : __("Yesterday ",'wpp')); }
    elseif($days > 1 && $days <= 6) { return ($future ? sprintf(__(" in %d days ",'wpp'), $days) : sprintf(__("%d days ago",'wpp'), $days)); }
    elseif($days > 6) { return date(get_option('date_format'), strtotime($date1)); }
  }

  /**
   * Returns date and/or time using the WordPress date or time format.
   *
   * @param string $time Date or time to use for calculation.
     * @param string $args List of arguments to overwrite the defaults.
   *
   * @todo Add functionality to adjust for time zone
    * @uses wp_parse_args()
   * @uses get_option()
    * @return string|bool Returns formatted date or time, or false if no time passed.
   *
   */
  function nice_time($time, $args = false) {


     $defaults = array('format' => 'date_and_time');
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    if(!$time)
      return false;

    if($format == 'date')
      return date(get_option('date_format'), $time);

    if($format == 'time')
      return date(get_option('time_format'), $time);

    if($format == 'date_and_time')
      return date(get_option('date_format'), $time) . " " . date(get_option('time_format'), $time);



    return false;

  }

  /**
   * Creates Admin Menu page for UD Log
    *
   * @todo Need to make sure this will work if multiple plugins utilize the UD classes
   * @see function show_log_page
   * @since 1.0
   * @uses add_action() Calls 'admin_menu' hook with an anonymous (lambda-style) function which uses add_menu_page to create a UI Log page
   */
  function add_log_page() {
    add_action('admin_menu', create_function('', "add_menu_page(__('Log','wpp'), __('Log','wpp'), 10, 'ud_log', array('WPP_UD_UI','show_log_page'));"));
  }

  /**
   * Turns a passed string into a URL slug
    *
    * Argument 'check_existance' will make the function check if the slug is used by a WordPress post
     *
   * @param string $content
   * @param string $args Optional list of arguments to overwrite the defaults.
   * @since 1.0
   * @uses add_action() Calls 'admin_menu' hook with an anonymous (lambda-style) function which uses add_menu_page to create a UI Log page
   * @return string
   */
  static function create_slug($content, $args = false) {

    $defaults = array(
      'separator' => '-',
      'check_existance' => false
    );
    
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    $content = preg_replace('~[^\\pL0-9_]+~u', $separator, $content); // substitutes anything but letters, numbers and '_' with separator
    $content = trim($content, $separator);
    $content = iconv("utf-8", "us-ascii//TRANSLIT", $content); // TRANSLIT does the whole job
    $content = strtolower($content);
    $slug = preg_replace('~[^-a-z0-9_]+~', '', $content); // keep only letters, numbers, '_' and separator

    return $slug;
  }

  /**
   * Convert a slug to a more readable string
    *
   * @since 1.3
   * @return string
   */
  function de_slug($string) {

     $string = ucwords(str_replace("_", " ", $string));

    return $string;

  }
  /**
   * Returns a JSON response, and dies.
    *
    * Designed for AJAX functions.
     *
   * @todo Create option to disable database logging
   * @param bool $success
   * @param string $message
   * @since 1.1

   * @return string
   */
  function json($success, $message) {

     if($success)
      $r_success = 'true';

    if(!$success)
      $r_success = 'false';

    $return = array(
      'success' => $r_success,
      'message' => $message);

    // Log in database
    WPP_UD_F::log(__("Automatically logged failed JSON response: ",'wpp').$message);

    echo json_encode($return);
    die();

  }


  /**
   * Returns location information from Google Maps API call
    *
      *
   * @since 1.2
   * @return object
   */
   function geo_locate_address($address = false, $localization = "en", $return_obj_on_fail = false) {

    if(!$address) {
      return false;
    }

    if(is_array($address)) {
      return false;
    }

    $address = urlencode($address);

    $url = str_replace(" ", "+" ,"http://maps.google.com/maps/api/geocode/json?address={$address}&sensor=true&language=$localization");

    $obj = (json_decode(wp_remote_fopen($url)));
    

    if($obj->status != "OK") {
    
      // Return Google result if needed instead of just false
      if($return_obj_on_fail)
        return $obj;
      
      return false;
      
    }

    //print_r($obj->results);
    $results = $obj->results;
    $results_object = $results[0];
     $geometry = $results_object->geometry;

    $return->formatted_address = $results_object->formatted_address;
    $return->latitude = $geometry->location->lat;
    $return->longitude = $geometry->location->lng;

    // Cycle through address component objects picking out the needed elements, if they exist
    foreach($results_object->address_components as $ac) {

      // types is returned as an array, look through all of them
      foreach($ac->types as $type) {
        switch($type){

          case 'street_number':
            $return->street_number = $ac->long_name;
          break;

          case 'route':
            $return->route = $ac->long_name;
          break;

          case 'locality':
              $return->city = $ac->long_name;
          break;

          case 'administrative_area_level_3':
            if(empty($return->city))
            $return->city = $ac->long_name;
          break;

          case 'administrative_area_level_2':
            $return->county = $ac->long_name;
          break;

          case 'administrative_area_level_1':
            $return->state = $ac->long_name;
            $return->state_code = $ac->short_name;
          break;

          case 'country':
            $return->country = $ac->long_name;
            $return->country_code = $ac->short_name;
          break;

          case 'postal_code':
            $return->postal_code = $ac->long_name;
          break;

          case 'sublocality':
            $return->district = $ac->long_name;
          break;

        }
      }


    }

    $return = apply_filters('geo_locate_address', $return, $results_object, $address, $localization);

    //print_r($return);

    return $return;


   }


  /**
   * Gmail-like time ago
   *
   *
   * @since 1.4
   */
   function time_ago($time) {

    if(!$time)
      return;

    $minutes_ago = round((time() - $time) / 60);

    if($minutes_ago > 60) {
      return sprintf(__('%s hours ago','wpp'),round($minutes_ago / 60));
    } else {
      return sprintf(__('%s minutes ago','wpp'),$minutes_ago);
    }



   }



  /**
   * Convert a string to a url-like slug
   *
   *
   * @since 1.4
   */
   function slug_to_label($slug = false) {

    if(!$slug)
      return;

    $slug = str_replace("_", " ", $slug);
    $slug = ucwords($slug);
    return $slug;


   }


  /**
   * Removes all metaboxes from given page
   *
   * Should be called by function in add_meta_boxes_$post_type
   * Cycles through all metaboxes
   *
   * @since 1.1
   */
   function remove_object_ui_elements($post_type, $remove_elements) {
    global $wp_meta_boxes, $_wp_post_type_features;

		if(!is_array($remove_elements)) {
			$remove_elements = array($remove_elements);
    }
      
    if(is_array($wp_meta_boxes[$post_type])) {
      foreach($wp_meta_boxes[$post_type] as $context_slug => $priority_array) {

        foreach($priority_array as $priority_slug => $meta_box_array) {

          foreach($meta_box_array as $meta_box_slug => $meta_bog_data) {

            if(in_array($meta_box_slug, $remove_elements))
              unset($wp_meta_boxes[$post_type][$context_slug][$priority_slug][$meta_box_slug]);
          }
        }
      }
    }


    if(is_array($_wp_post_type_features[$post_type])) {
      // Remove features
      foreach($_wp_post_type_features[$post_type] as $feature => $enabled) {

        if(in_array($feature, $remove_elements))
          unset($_wp_post_type_features[$post_type][$feature]);

      }
    }
   }


/**
 * The letter l (lowercase L) and the number 1
 * have been removed, as they can be mistaken
 * for each other.
 */
  function createRandomPassword() {
    $chars = "abcdefghijkmnopqrstuvwxyz023456789";
    srand((double)microtime()*1000000);
    $i = 0;
    $pass = '' ;

    while ($i <= 7) {
      $num = rand() % 33;
      $tmp = substr($chars, $num, 1);
      $pass = $pass . $tmp;
      $i++;
    }

    return $pass;

  }

	/**
	 * Validate email  an address
	 *
	 * @since 1.1
	 */  
   function check_email_address($email) {
      // First, we check that there's one @ symbol,
      // and that the lengths are right.
      if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
      // Email invalid because wrong number of characters
      // in one section or wrong number of @ symbols.
      return false;
      }
      // Split it into sections to make life easier
      $email_array = explode("@", $email);
      $local_array = explode(".", $email_array[0]);
      for ($i = 0; $i < sizeof($local_array); $i++) {
      if
    (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&
    ?'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",
    $local_array[$i])) {
        return false;
      }
      }
      // Check if domain is IP. If not,
      // it should be valid domain name
      if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
      $domain_array = explode(".", $email_array[1]);
      if (sizeof($domain_array) < 2) {
        return false; // Not enough parts to domain
      }
      for ($i = 0; $i < sizeof($domain_array); $i++) {
        if
    (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|
    ?([A-Za-z0-9]+))$",
    $domain_array[$i])) {
        return false;
        }
      }
      }
      return true;
  }
  

	/**
	 * Creates a 'dynamic' page and sets up metaboxes
	 *
	 * Page should be added via admin_menu, this function sets everything up for dynamic use before headers
	 * Enqueues JS for metaboxes
	 *
	 * @since 1.1
	 */
	 function create_dynamic_page($args = false) {
		$defaults = array('slug' => false);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );


		wp_enqueue_script('post');
		wp_enqueue_script('editor');
		wp_enqueue_script('media-upload');
		do_action('add_meta_boxes', $post_type, $post);


	 }



  /*
  That it is an implementation of the function money_format for the
  platforms that do not it bear. 

  The function accepts to same string of format accepts for the
  original function of the PHP. 

  (Sorry. my writing in English is very bad) 

  The function is tested using PHP 5.1.4 in Windows XP
  and Apache WebServer.
  */
  function money_format($format, $number)
  {
    $regex  = '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?'.
              '(?:#([0-9]+))?(?:\.([0-9]+))?([in%])/';
    if (setlocale(LC_MONETARY, 0) == 'C') {
        setlocale(LC_MONETARY, '');
    }
    $locale = localeconv();
    preg_match_all($regex, $format, $matches, PREG_SET_ORDER);
    foreach ($matches as $fmatch) {
        $value = floatval($number);
        $flags = array(
            'fillchar'  => preg_match('/\=(.)/', $fmatch[1], $match) ?
                           $match[1] : ' ',
            'nogroup'   => preg_match('/\^/', $fmatch[1]) > 0,
            'usesignal' => preg_match('/\+|\(/', $fmatch[1], $match) ?
                           $match[0] : '+',
            'nosimbol'  => preg_match('/\!/', $fmatch[1]) > 0,
            'isleft'    => preg_match('/\-/', $fmatch[1]) > 0
        );
        $width      = trim($fmatch[2]) ? (int)$fmatch[2] : 0;
        $left       = trim($fmatch[3]) ? (int)$fmatch[3] : 0;
        $right      = trim($fmatch[4]) ? (int)$fmatch[4] : $locale['int_frac_digits'];
        $conversion = $fmatch[5];

        $positive = true;
        if ($value < 0) {
            $positive = false;
            $value  *= -1;
        }
        $letter = $positive ? 'p' : 'n';

        $prefix = $suffix = $cprefix = $csuffix = $signal = '';

        $signal = $positive ? $locale['positive_sign'] : $locale['negative_sign'];
        switch (true) {
            case $locale["{$letter}_sign_posn"] == 1 && $flags['usesignal'] == '+':
                $prefix = $signal;
                break;
            case $locale["{$letter}_sign_posn"] == 2 && $flags['usesignal'] == '+':
                $suffix = $signal;
                break;
            case $locale["{$letter}_sign_posn"] == 3 && $flags['usesignal'] == '+':
                $cprefix = $signal;
                break;
            case $locale["{$letter}_sign_posn"] == 4 && $flags['usesignal'] == '+':
                $csuffix = $signal;
                break;
            case $flags['usesignal'] == '(':
            case $locale["{$letter}_sign_posn"] == 0:
                $prefix = '(';
                $suffix = ')';
                break;
        }
        if (!$flags['nosimbol']) {
            $currency = $cprefix .
                        ($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']) .
                        $csuffix;
        } else {
            $currency = '';
        }
        $space  = $locale["{$letter}_sep_by_space"] ? ' ' : '';

        $value = number_format($value, $right, $locale['mon_decimal_point'],
                 $flags['nogroup'] ? '' : $locale['mon_thousands_sep']);
        $value = @explode($locale['mon_decimal_point'], $value);

        $n = strlen($prefix) + strlen($currency) + strlen($value[0]);
        if ($left > 0 && $left > $n) {
            $value[0] = str_repeat($flags['fillchar'], $left - $n) . $value[0];
        }
		if(is_array($value)) {
			$value = implode($locale['mon_decimal_point'], $value);
			if ($locale["{$letter}_cs_precedes"]) {
				$value = $prefix . $currency . $space . $value . $suffix;
			} else {
				$value = $prefix . $value . $space . $currency . $suffix;
			}
		}
        if ($width > 0) {
            $value = str_pad($value, $width, $flags['fillchar'], $flags['isleft'] ?
                     STR_PAD_RIGHT : STR_PAD_LEFT);
        }

        $format = str_replace($fmatch[0], $value, $format);
    }
    return $format;
  }

  
}

endif; /* f(!class_exists('WPP_UD_F')): */



/** UD_UI was changed to WPP_UD_UI, prevent breaking any old code that may not have been updated */
if(!class_exists('UD_UI')) {
  class UD_UI extends WPP_UD_UI { }
}

if(!class_exists('UD_F')) {
  class UD_F extends WPP_UD_F { }
}

