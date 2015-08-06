#### 2.0.1
* Fixed showing Attribute on Edit Property page which has 'Multi-Checkbox' Data Entry.
* Fixed registration of javascript files which might break logic in some cases in multiple places on back end and front end.
* Fixed Fatal Error which did occur when class 'RWMB_Field_Multiple_Values' was called too early.
* Fixed bug which broke sort order for properties in 2.0.0 version.
* Fixed typos 'YEs' in value for single checkbox attribute.
* Warnings and Notices fixes.

#### 2.0.0
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