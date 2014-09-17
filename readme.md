### WP-Property Plugin

Property and Real Estate Management Plugin for WordPress.  Create a directory of real estate / rental properties and integrate them into you WordPress CMS.

***
[![Issues - Bug](https://badge.waffle.io/usabilitydynamics/wp-property.png?label=bug&title=Bugs)](http://waffle.io/usabilitydynamics/wp-property)
[![Issues - Backlog](https://badge.waffle.io/usabilitydynamics/wp-property.png?label=backlog&title=Backlog)](http://waffle.io/usabilitydynamics/wp-property/)
[![Issues - Active](https://badge.waffle.io/usabilitydynamics/wp-property.png?label=in progress&title=Active)](http://waffle.io/usabilitydynamics/wp-property/)
***
[![Dependency Status](https://gemnasium.com/usabilitydynamics/wp-property.svg)](https://gemnasium.com/usabilitydynamics/wp-property)
[![Scrutinizer Quality](http://img.shields.io/scrutinizer/g/usabilitydynamics/wp-property.svg)](https://scrutinizer-ci.com/g/usabilitydynamics/wp-property)
[![Scrutinizer Coverage](http://img.shields.io/scrutinizer/coverage/g/usabilitydynamics/wp-property.svg)](https://scrutinizer-ci.com/g/usabilitydynamics/wp-property)
[![Packagist Vesion](http://img.shields.io/packagist/v/usabilitydynamics/wp-property.svg)](https://packagist.org/packages/usabilitydynamics/wp-property)
[![CircleCI](https://circleci.com/gh/usabilitydynamics/wp-property.png)](https://circleci.com/gh/usabilitydynamics/wp-property)
***

### Installing via ComposerJS

To add WP-Property via ComposerJS to an existing project:
```
composer require usabilitydynamics/wp-property dev-master
```

For development, use following to checkout WP-Property and build:
```
composer create-project --stability=dev usabilitydynamics/wp-property
```

### Features
* New shortcode: [property_attribute] to pull a single attribute without using PHP. Example: [property_attribute attribute=bedrooms] will return the number of bedrooms for current property. [property_attribute property_id=4 attribute=bathrooms] will return the number of bathrooms for property with ID of 5.
* New shortcode: [property_map] to pull a single attribute without using PHP. Example: [property_map width="100%" height="100px" zoom_level=11 property_id=5].  Leave property_id blank to get map for currently displayed property.
* Two different sorter styles available, buttons and dropdown.
* "Sort by:" text can be customized in [property_overview] shortcode using the sort_by_text argument.
* Add predefined values for any attribute in Admin Tab that will create a dropdown input field on the property editing page.
* Pagination back-button support.
* Slovakian translation.
* Search form shortcode.
* Pagination and sorting works on search results
* Major improvements to search widget and search function
* Property result pagination via AJAX
* Property queries by custom attributes
* Localized Google Maps
* Translations into Italian, Portuguese and Russian.
* Customizable templates for different property types.
* Fields such as price, bathrooms, bedrooms, features, address, work out of the box.
* SEO friendly URLs generated for every property, following the WordPress format.
* Customizable widgets:  Featured Properties, Property Search, Property Gallery, and Child Properties.
* Google Maps API to automatically validate physical addresses behind-the-scenes.
* Integrates with Media Library, avoiding the need for additional third-party Gallery plugins.
* Advanced image type configuration using UI.
* Out of the box support for two property types, Building and Floorplan.   More can be added via WP-Property API.
* Property types follow a hierarchical format, having the ability of inheriting settings - i.e. buildings (or communities) will automatically calculate the price range of all floor-plans below them.
* Free!