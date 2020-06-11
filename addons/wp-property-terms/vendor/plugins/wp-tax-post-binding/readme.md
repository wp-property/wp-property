CF Taxonomy Post Type Binding
================

### Description
A WordPress plugin that provides extended functionality for taxonomies such as post meta and featured image through creating a custom post type and associating it to that taxonomy.

### Usage
Plugin can be installed via default way as WordPress plugin or via composer dependency as library.

#### Example

```php

/**
 * Configuration
 */
$config = array(
  // Extend all taxonomies for passed post types:
  'post_types' => array( 'post', 'page' ),
  // Do not extend passed taxonomies:
  'exclude' => array( 'category' )
);

/**
 * Loader
 */
new \UsabilityDynamics\CFTPB\Loader( $config );

```
