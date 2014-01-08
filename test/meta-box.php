<?php


new Meta_Box(array(
  'title'    => 'Media',
  'pages'    => array( 'movie', 'slider' ),
  'fields' => array(
    array(
      'name' => 'URL',
      'id'   => 'wpp_url',
      'type' => 'text',
    ),
  )
));

new Meta_Box(array(
  'id'       => 'personal',
  'title'    => 'Personal Information',
  'pages'    => array( 'post', 'page' ),
  'context'  => 'normal',
  'priority' => 'high',
  'fields'   => array(
    array(
      'name'  => 'Full name',
      'desc'  => 'Format: First Last',
      'id'    => 'wpp_fname',
      'type'  => 'text',
      'std'   => 'Anh Tran',
      'class' => 'custom-class',
      'clone' => true,
    ),
  )
));

new Meta_Box(array(
  'pages'    => array( 'post', 'page' ),
  'title'  => __( 'Google Map', 'rwmb' ),
  'fields' => array(
    array(
      'id'            => 'address',
      'name'          => __( 'Address', 'rwmb' ),
      'type'          => 'text',
      'std'           => __( 'Hanoi, Vietnam', 'rwmb' ),
    ),
    array(
      'id'            => 'loc',
      'name'          => __( 'Location', 'rwmb' ),
      'type'          => 'map',
      'std'           => '-6.233406,-35.049906,15',
      'style'         => 'width: 500px; height: 500px',
      'address_field' => 'address'
    )
  ),
));