#### Description
Set of Utilities to extend default Meta Box plugin functionality in WordPress.

#### Utilities

##### Tabs

To create tabs for your meta box, you need to add 2 parameters to your meta box configuration:

* title  
* tab_style - default|box|left
* tabs - list of tabs with each tab having a `label` and `icon` fields.
* fields - standard meta-box fields with a `tab` field


```php
add_filter( 'rwmb_meta_boxes', function ( $meta_boxes ) {

   $meta_boxes[] = array(
       'title'     => __( 'Meta Box Tabs Demo', 'rwmb' ),
       'tabs'      => array(
           'contact' => array(
               'label' => __( 'Contact', 'rwmb' ),
               'icon'  => 'dashicons-email', // Dashicon
           ),
           'social'  => array(
               'label' => __( 'Social Media', 'rwmb' ),
               'icon'  => 'dashicons-share', // Dashicon
           ),
           'note'  => array(
               'label' => __( 'Note', 'rwmb' ),
               'icon'  => 'http://i.imgur.com/nJtag1q.png', // Custom icon, using image
           ),
       ),

       // Tab style: 'default', 'box' or 'left'. Optional
       'tab_style' => 'default',

       'fields'    => array(
           array(
               'name' => __( 'Name', 'rwmb' ),
               'id'   => 'name',
               'type' => 'text',

               // Which tab this field belongs to? Put tab key here
               'tab'  => 'contact',
           ),
           array(
               'name' => __( 'Email', 'rwmb' ),
               'id'   => 'email',
               'type' => 'email',

               // Which tab this field belongs to? Put tab key here
               'tab'  => 'contact',
           ),
           array(
               'name' => __( 'Facebook', 'rwmb' ),
               'id'   => 'facebook',
               'type' => 'text',

               // Which tab this field belongs to? Put tab key here
               'tab'  => 'social',
           ),
           array(
               'name' => __( 'Google+', 'rwmb' ),
               'id'   => 'google',
               'type' => 'text',

               // Which tab this field belongs to? Put tab key here
               'tab'  => 'social',
           ),
           array(
               'name' => __( 'Twitter', 'rwmb' ),
               'id'   => 'twitter',
               'type' => 'text',

               // Which tab this field belongs to? Put tab key here
               'tab'  => 'social',
           ),
           array(
               'name' => __( 'Note', 'rwmb' ),
               'id'   => 'note',
               'type' => 'textarea',

               // Which tab this field belongs to? Put tab key here
               'tab'  => 'note',
           ),
       ),
   );

   return $meta_boxes;
});
```