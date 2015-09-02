#### 2.0.4 ( September 2, 2015 )
* Added filter which adds admin domain to the whitelist of hosts to redirect to, in case, admin and site domains are different.
* Added Chinese language to available address localizations.
* Fixed showing values for Multi Checkbox attributes on All Properties page ( Admin Panel ) and on Front End.
* Fixed default overview on All Properties pages. There was a potential issue when trashed properties were shown on default overview.
* Fixed warnings on Property saving process which prevent loading page.
* Fixed the bug when UsabilityDynamics Admin Notices could not be dismissed.

#### 2.0.3 ( August 21, 2015 )
* Added ability to set Google Maps API key on WP-Property Settings page. Using of Google Maps API key increases google validation limits.
* Fixed the way of widgets initialization. Compatibility with WordPress 4.3 and higher.
* Fixed Warnings and issues with hidden Title and Checkbox columns on All Properties page for WordPress 4.3 and higher.
* Fixed Warnings which were breaking ajax request on pagination and filtering items on All Properties page for PHP 5.6.
* Fixed Warning on properties overview's default template.
* Fixed incorrect behaviour on custom 'Install Plugins' page after depended plugins ( Add-ons ) activation.

#### 2.0.2 ( August 13, 2015 )
* Added Shortcodes UI library for providing better shortcodes UI management in future releases.
* Added ability to sort properties on Front End by modified date. Example: [property_overview sort_by=post_modified]
* Added ability to filter properties by custom attribute on 'All Properties' page ( Back End ) when it has Search Input 'Advanced Range Dropdown'.
* Fixed loading of localisation files. The bug persists in 2.0.0 and 2.0.1 versions.
* Fixed Warnings on Edit Property page when property attributes with Data Entry "Dropdown Selection" do not have any predefined value.
* Fixed Warnings on sending notification to user about created account.
* Fixed incorrect status information on "Revalidate all addresses using English localization" process in case query limit was exceeded.
* Fixed defined min and max values of 'Advanced Range Dropdown' fields on Property Search form.

#### 2.0.1 ( August 6, 2015 )
* Fixed showing Attribute on Edit Property page which has 'Multi-Checkbox' Data Entry.
* Fixed registration of javascript files which might break logic in some cases in multiple places on back end and front end.
* Fixed Fatal Error which did occur when class 'RWMB_Field_Multiple_Values' was called too early.
* Fixed bug which broke sort order for properties in 2.0.0 version.
* Fixed typos 'YEs' in value for single checkbox attribute.
* Warnings and Notices fixes.

#### 2.0.0 ( August 3, 2015 )
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