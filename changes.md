### 2.1.8 ( May 23, 2016 )
* Added default Search Input and Data Entry types for Property Attributes on first WP-Property install.
* Fixed default list of Property Types on first WP-Property install.
* Fixed issue with duplicated Currency symbol on Single Property page.

### 2.1.7 ( May 18, 2016 )
* Added Date picker for Property Search form.
* Added ability to remove all default attributes, meta attributes and property types from Developer tab on Settings page.
* Added label "Properties page" to page's title ( on backend's "Pages" page ) which is used as Properties Overview page.
* Removed ability to hide Overview meta box on "All Properties" page on backend.
* Extended backup of WP-Property settings with settings of WP-Property Add-ons.
* Loading $wp_properties object was moved to AJAX on Help tab on Settings page.
* Changed `get_images` method to sort images by ascending order and expose `menu_order`.
* Fixed empty view-link on Edit Property page.
* Fixed fatal error when [property_attribute] shortcode was added to non-Single Property page.
* Fixed CSS styles for Gallery meta box on Edit Property page.
* Fixed WordPress 4.5 issue with File Upload Data Entry.
* Fixed issue with showing empty Property Gallery widget when there were not images for the particular property.
* Fixed issue when private properties were not showing for administrator.
* Fixed issue when Property Search widget in Customizer had duplicated attributes.
* Fixed displaying of title, alt and caption options of property images.
* Fixed issue with pagination on property overview when they are two on the page
* Fixed multiple usage of Property Overview widget and shortcode on the same page.
* Warnings and Notices fixes.

### 2.1.6 ( February 24, 2016 )
* Fixed the bug when page content was always replaced with property_overview shortcode if wpp_search existed in request parameters.
* Fixed multi-checkbox issue on Property Search.
* Fixed issue with odd  br in Text Editor Data Entry field.
* Fixed Single checkbox became enabled when publishing new property.
* Fixed converting apostrophe to slash in Property Types.
* Date and Time attributes now take date and time format from Settings/General tab.

### 2.1.5 ( February 9, 2016 )
* Added ability to set default values for property's attributes.
* Added compatibility with WPML plugin.
* Removed 'Images Upload' data entry for attribute since it duplicated 'Image Upload'.
* Changed Agent definer from ID to email in wp-property export feed.
* Fixed inherited values for non text attributes.
* Fixed values rendering for 'Image Upload', 'File Upload' attributes on Single Property page.
* Fixed 'Next' button's event in numeric pagination for property overview.
* Warnings and Notices fixes.

### 2.1.4 ( November 26, 2015 )
* Property Attributes widget is not shown anymore if no data found.
* Property Meta widget is not shown anymore if no data found.
* Property Term widget is not shown anymore if no data found.
* Fixed default template for [featured_properties] shortcode.
* Fixed the bug related to showing properties with specific float numbers in [property_overview] shortcode.
* Fixed javascript error which broke pagination and sorting functionality in [property_overview] shortcode.
* Fixed showing of inherited multi-checkbox values.
* Fixed encoding issues in export feed.

### 2.1.3 ( November 4, 2015 )
* Added ability to set numeric or slider pagination for [property_overview] shortcode and Property Overview widget.
* Added ability to set default image which will be shown if property does not have any one.
* Added 'Child Properties' table's column on 'All Properties' page, which shows the list of all children for particular property.
* Improved hooks for list table on 'All Properties' page to have more flexibility with adding custom columns and bulk actions.

### 2.1.2 ( October 21, 2015 )
* Added ability to sort properties by modified date for Property Overview widget.
* Added option to export properties to CSV file on Help Tab of Settings page.
* Fixed replacing of plugin's settings data with default values on updating WP-Property settings in some cases.
* Fixed showing of Multi-Checkbox values.

### 2.1.1 ( October 14, 2015 )
* Added function get_property_type() which returns label of property type for current or particular property.
* Added automatic object cache and plugin cache flushing on plugin's settings updates.
* Added automatic object cache flushing on property updates.
* Added automatic plugin cache flushing on plugin activation.
* Updated localisation files.
* Updated Russian localisation.
* Refactored get_property function.
* Fixed 'Property/Properties' white labels.
* Fixed plugin's upgrade process.
* Fixed localisation of javascript files on multi site.
* Fixed rewrite rules breaking on plugin activation which has caused 404 errors on single property pages.

### 2.1.0 ( October 8, 2015 )
* Added compatibility with Page Builder by SiteOrigin plugin.
* Added ability to set single or page templates of current theme for rendering Single Property page instead of predefined property.php.
* Added ability to disable WP-Property Widget Sidebars.
* Added Dutch ( Netherlands ) localization.
* Added Property Overview widget based on [property_overview] shortcode.
* Added Property Attributes widget which renders the list of property attributes.
* Added Property Map widget based on [property_map] shortcode.
* Added List Attachments widget based on [list_attachments] shortcode.
* Added [property_meta] shortcode and Property Meta widget.
* Added [property_terms] shortcode and Property Terms widget.
* Added 'Sort By' and 'Sort Order' options for Child Properties widget.
* Extended functionality of [property_attributes] shortcode.
* Updated plugin initialisation logic.
* Refactored widgets structure and initialisation.
* Refactored shortcodes structure and initialisation.
* Child Properties, Featured Properties, Latest Properties and Other Properties widgets were deprecated and disabled on new plugin installation. But they still can be activated via Settings.
* Fixed the bug which prevented to update Add-ons via inline updater on Plugins page.
* Fixed address validation by provided Google Maps API key.

### 2.0.4 ( September 2, 2015 )
* Added filter which adds admin domain to the whitelist of hosts to redirect to, in case, admin and site domains are different.
* Added Chinese language to available address localizations.
* Fixed showing values for Multi Checkbox attributes on All Properties page ( Admin Panel ) and on Front End.
* Fixed default overview on All Properties pages. There was a potential issue when trashed properties were shown on default overview.
* Fixed warnings on Property saving process which prevent loading page.
* Fixed the bug when UsabilityDynamics Admin Notices could not be dismissed.

### 2.0.3 ( August 21, 2015 )
* Added ability to set Google Maps API key on WP-Property Settings page. Using of Google Maps API key increases google validation limits.
* Fixed the way of widgets initialization. Compatibility with WordPress 4.3 and higher.
* Fixed Warnings and issues with hidden Title and Checkbox columns on All Properties page for WordPress 4.3 and higher.
* Fixed Warnings which were breaking ajax request on pagination and filtering items on All Properties page for PHP 5.6.
* Fixed Warning on properties overview's default template.
* Fixed incorrect behaviour on custom 'Install Plugins' page after depended plugins ( Add-ons ) activation.

### 2.0.2 ( August 13, 2015 )
* Added Shortcodes UI library for providing better shortcodes UI management in future releases.
* Added ability to sort properties on Front End by modified date. Example: [property_overview sort_by=post_modified]
* Added ability to filter properties by custom attribute on 'All Properties' page ( Back End ) when it has Search Input 'Advanced Range Dropdown'.
* Fixed loading of localisation files. The bug persists in 2.0.0 and 2.0.1 versions.
* Fixed Warnings on Edit Property page when property attributes with Data Entry "Dropdown Selection" do not have any predefined value.
* Fixed Warnings on sending notification to user about created account.
* Fixed incorrect status information on "Revalidate all addresses using English localization" process in case query limit was exceeded.
* Fixed defined min and max values of 'Advanced Range Dropdown' fields on Property Search form.

### 2.0.1 ( August 6, 2015 )
* Fixed showing Attribute on Edit Property page which has 'Multi-Checkbox' Data Entry.
* Fixed registration of javascript files which might break logic in some cases in multiple places on back end and front end.
* Fixed Fatal Error which did occur when class 'RWMB_Field_Multiple_Values' was called too early.
* Fixed bug which broke sort order for properties in 2.0.0 version.
* Fixed typos 'YEs' in value for single checkbox attribute.
* Warnings and Notices fixes.

### 2.0.0 ( August 3, 2015 )
* Changed plugin initialization functionality.
* Added 'Advanced Range Dropdown' Search Input field which renders min and max select boxes. Feedback: http://feedback.usabilitydynamics.com/forums/95259-wp-property/suggestions/3557341-price-min-and-max-select-boxes-instead-of-text-inp
* Added Composer ( dependency manager ) modules and moved some functionality to composer modules ( vendors ).
* Added ability to clone listings.
* Added more flexibility over adding and customizing taxonomies. See free WP-Property Terms Add-on.
* Added optional revision control for properties.
* Added option to redirect certain property types to parent on front-end.
* Added ability to save float numbers for numeric attributes.
* Added Gallery Images meta box on Edit Property page got better images management.
* Added smart Manual Coordinates option. Now you can set coordinates directly on map.
* Added doing WP-Property Settings backup on upgrade to new version. Get information about backup: get_option('wpp_settings_backup');
* Moved premium features to separate plugins (Add-ons).
* Changed Licenses management.
* Moved 'Advanced Settings' to the 'Help' tab out of the 'Developer' tab on Settings page
* Cleaned up functionality of plugin.
* Refactored file structure of plugin.
* Refactored attributes and meta management and added new attribute types.
* Refactored 'All Properties' page and added smart filter on it.
* Changed settings export to use JSON extension.
* Fixed 'Get Direction' link on Google Map for properties which have manual coordinates.
* Fixed pagination bug related to sorting properties by prices with the same price value.
* Fixed double sharp for strict search in field's value.
* Fixed option 'Display in featured listings' on edit property page.
* Fixed "Add Row" issue for WP-Property Settings and WP-Property: Importer schedule mapping.
* Fixed Warnings and Notices.
* Depreciated / removed legacy UD log.