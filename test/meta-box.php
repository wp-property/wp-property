<?php
namespace UsabilityDynamics\WPP {

  // Inline Image Uploader.
  new Meta_Box( array(
    'title'    => __( 'Conditional Images', 'wpp' ),
    'id'       => 'conditional_images',
    'context'  => 'normal',
    'priority' => 'high',
    'autosave' => false,
    'fields'   => array(
      array(
        'name' => __( 'Your images', 'wpp' ),
        'id'   => "wpp_img",
        'type' => 'plupload_image',
      ),
    ),
    'only_on'  => array(
      'id'       => array( 1, 12636 ),
      // 'slug'  => array( 'news', 'blog' ),
      // 'template' => array( 'fullwidth.php', 'simple.php' ),
      // 'parent'   => array( 10 )
    ),
  ));

  // Standard Fields.
  new Meta_Box( array(
    'title'      => __( 'Standard Fields', 'wpp' ),
    'id'         => 'standard_fields',
    'context'    => 'normal',
    'priority'   => 'high',
    'autosave'   => true,
    'fields'     => array(

      array(
        'type' => 'heading',
        'name' => __( 'Heading', 'wpp' ),
        'id'   => 'fake_id', // Not used but needed for plugin
      ),

      array(
        'name' => __( 'Number', 'wpp' ),
        'id'   => "wpp_number",
        'type' => 'number',
        'min'  => 0,
        'step' => 1,
      ),

      // Checkbox List
      array(
        'name'    => __( 'Checkbox list', 'wpp' ),
        'id'      => "wpp_checkbox_list",
        'type'    => 'checkbox_list',
        // Options of checkboxes, in format 'value' => 'Label'
        'options' => array(
          'value1' => __( 'Label1', 'wpp' ),
          'value2' => __( 'Label2', 'wpp' ),
        ),
      ),

      // Single Selection.
      array(
        'name'        => __( 'Select', 'wpp' ),
        'id'          => "wpp_select_advanced",
        'type'        => 'select_advanced',
        'options'     => array(
          'value1' => __( 'Label1', 'wpp' ),
          'value2' => __( 'Label2', 'wpp' ),
        ),
        'multiple'    => false,
        'placeholder' => __( 'Select an Item', 'wpp' ),
      ),

      // Taxonomy Multiple Selection.
      array(
        'name'    => __( 'Taxonomy Multiple Selection.', 'wpp' ),
        'id'      => "wpp_taxonomy1",
        'type'    => 'taxonomy',
        'options' => array(
          'taxonomy' => 'community_feature',
          'type'     => 'checkbox_list',
          'args'     => array()
        )
      ),

      array(
        'name'    => __( 'Taxonomy: checkbox_tree', 'wpp' ),
        'id'      => "wpp_taxonomy2",
        'type'    => 'taxonomy',
        'options' => array(
          'taxonomy' => 'community_feature',
          'type'     => 'checkbox_tree',
          'args'     => array()
        )
      ),

      array(
        'name'    => __( 'Taxonomy: select_tree', 'wpp' ),
        'id'      => "wpp_taxonomy3",
        'type'    => 'taxonomy',
        'options' => array(
          'taxonomy' => 'community_feature',
          'type'     => 'select_tree',
          'args'     => array()
        )
      ),

      array(
        'name'    => __( 'Taxonomy: select_advanced', 'wpp' ),
        'id'      => "wpp_taxonomy4",
        'type'    => 'taxonomy',
        'options' => array(
          'taxonomy' => 'community_feature',
          'type'     => 'select_advanced',
          'args'     => array()
        )
      ),

      array(
        'name'    => __( 'Multiple Taxonomy Select2', 'wpp' ),
        'id'      => "wpp_taxonomy5",
        'type'    => 'taxonomy',
        'multiple'    => true,
        'options' => array(
          'taxonomy' => 'community_feature',
          'type'     => 'select_advanced',
          'args'     => array()
        )
      ),

      array(
        'name'    => __( 'Taxonomy: select', 'wpp' ),
        'id'      => "wpp_taxonomy6",
        'type'    => 'taxonomy',
        'options' => array(
          'taxonomy' => 'community_feature',
          'type'     => 'select',
          'args'     => array()
        )
      ),

      array(
        'name'       => __( 'Property Parent', 'wpp' ),
        'id'         => "property_parent",
        'type'       => 'post',
        'post_type'  => 'property',
        'field_type' => 'select_advanced',
        'js_options' => array(
          // "ajax" => array( "url" =>  "http://api.rottentomatoes.com/api/public/v1.0/movies.json" )
        ),
        'query_args' => array(
          'post_status'    => 'publish',
          'posts_per_page' => '20',
        )
      ),

      array(
        'name'    => __( 'WYSIWYG / Rich Text Editor', 'wpp' ),
        'id'      => "wpp_wysiwyg",
        'type'    => 'wysiwyg',
        'raw'     => false,
        'std'     => __( 'WYSIWYG default value', 'wpp' ),

        // Editor settings, see wp_editor() function: look4wp.com/wp_editor
        'options' => array(
          'textarea_rows' => 4,
          'teeny'         => true,
          'media_buttons' => false,
        ),
      ),

      array(
        'type' => 'divider',
        'id'   => 'fake_divider_id', // Not used, but needed
      ),

      // Basic Text.
      array(
        'name'  => 'Staff Name',
        'desc'  => 'Format: First Last',
        'id'    => 'wpp_full_name',
        'type'  => 'text',
        'std'   => 'John Smith',
        'class' => 'full-name',
        'clone' => true,
      ),

      // Text with Validation.
      array(
        'name'  => 'Vehicle',
        'desc'  => 'This is a validated field.',
        'id'    => 'wpp_vehicle',
        'type'  => 'text',
        'std'   => 'Chevy',
        'class' => 'vehicle',
        'clone' => false
      ),

      // URL Field.
      array(
        'name' => 'URL',
        'id'   => 'wpp_url',
        'type' => 'text',
      )

    ),
    'validation' => array(
      'rules'    => array(
        "wpp_vehicle" => array(
          'required'  => true,
          'minlength' => 3,
        ),
      ),
      'messages' => array(
        "wpp_vehicle" => array(
          'required'  => __( 'A vehidle is required', 'wpp' ),
          'minlength' => __( 'Vehicle name must be at least 3 characters.', 'wpp' ),
        )
      )

    )
  ));


  // Address field with Google Map.
  new Meta_Box( array(
    'title'    => __( 'Google Map', 'wpp' ),
    'id'       => 'google_map',
    'context'  => 'normal',
    'priority' => 'high',
    'autosave' => false,
    'fields'   => array(
      array(
        'id'   => 'address',
        'name' => __( 'Address', 'wpp' ),
        'type' => 'text',
        'std'  => __( 'Hanoi, Vietnam', 'wpp' ),
      ),
      array(
        'id'            => 'loc',
        'name'          => __( 'Location', 'wpp' ),
        'type'          => 'map',
        'std'           => '-6.233406,-35.049906,15', // 'latitude,longitude[,zoom]' (zoom is optional)
        'style'         => 'width: 100%; height: 300px',
        'address_field' => 'address', // Name of text field where address is entered. Can be list of text fields, separated by commas (for ex. city, state)
      ),
    )
  ));

  // Advanced Fields.
  new Meta_Box( array(
    'title'    => __( 'Advanced Fields', 'wpp' ),
    'id'       => 'advanced_fields',
    'context'  => 'normal',
    'priority' => 'high',
    'autosave' => false,
    'fields'   => array(

      array(
        'name'       => __( 'Slider', 'wpp' ),
        'id'         => "wpp_slider",
        'type'       => 'slider',
        'prefix'     => __( '$', 'wpp' ),
        'suffix'     => __( ' USD', 'wpp' ),
        'js_options' => array(
          'min'  => 10,
          'max'  => 255,
          'step' => 5,
        )
      ),

      array(
        'name' => __( 'Number', 'wpp' ),
        'id'   => "wpp_number",
        'type' => 'number',
        'min'  => 0,
        'step' => 5,
      ),

      array(
        'name' => __( 'Range', 'wpp' ),
        'id'   => "wpp_range",
        'desc' => __( 'Range description', 'wpp' ),
        'type' => 'range',
        'min'  => 0,
        'max'  => 100,
        'step' => 5,
        'std'  => 0,
      ),

      array(
        'name' => __( 'oEmbed', 'wpp' ),
        'id'   => "wpp_oembed",
        'desc' => __( 'oEmbed description', 'wpp' ),
        'type' => 'OEmbed',
      ),

      array(
        'name'             => __( 'File Advanced Upload', 'wpp' ),
        'id'               => "wpp_file_advanced",
        'type'             => 'file_advanced',
        'max_file_uploads' => 4,
        'mime_type'        => 'application,audio,video', // Leave blank for all file types
      ),

      array(
        'name'             => __( 'Image Advanced Upload', 'wpp' ),
        'id'               => "wpp_imgadv",
        'type'             => 'image_Advanced',
        'max_file_uploads' => 4,
      ),

      array(
        'id'   => "wpp_button",
        'type' => 'button',
        'name' => ' ', // Empty name will "align" the button to all field inputs
      )

    )
  ));

}
