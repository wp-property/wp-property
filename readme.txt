=== WP-Property - WordPress Powered Real Estate and Property Management ===
Contributors: usability_dynamics, andypotanin, jbrw1984, maxim.peshkov, anton-korotkoff, ideric
Donate link: http://usabilitydynamics.com/products/wp-property/
Tags: property management, real estate, listings, properties, property, real estate cms, wordpress real estate, listings, estate, MLS, IDX, RETS, XML Import
Requires at least: 3.6
Tested up to: 4.0
Stable tag: 1.42.2


== Description ==

Developed by the same people who brought you [WP-Invoice](http://wordpress.org/extend/plugins/wp-invoice/), comes WP-Property. As always, integration is seamless, the system is expandable and customizable, functionality is rich, and we are here to support it.

[vimeo http://vimeo.com/14280748]

This is not a "collection" of plugins, but one full suite. You will not have to download and match together a plethora of other plugins, in the hopes of them working well together, to have all the features you need.

http://www.vimeo.com/14473894

Check out our [premium WordPress real estate theme](https://usabilitydynamics.com/products/wp-property/the-denali-premium-theme/).

Be sure to check out the  [WP-Property Forum](http://usabilitydynamics.com/products/wp-property/forum/) if you need help.

= Common Shortcodes =

We have setup a  ["Shortcode Cheatsheet" page](http://usabilitydynamics.com/help/wp-property-help/wp-property-shortcode-cheat-sheet/) for your convenience.

Include search form into page/post content.

* [property_search] Include search widget on content of a page or a post.
* [property_search searchable_attributes=bedrooms,bathrooms searchable_property_types=single_family_home per_page=10] Show search options for bedrooms and bathrooms; return only Single Family Homes, and show 10 results per page.

Shortcodes are used to display listings. Simply put a shortcode into the body of a post or page, and a list of properties will be displayed in its place on the front-end of your website.

* [property_overview pagination=on] Show all properties and enable pagination.
* [property_overview sorter=on] Will list all properties with sorting.
* [property_overview per_page=5] Show 5 properties on a page (10 properties is the default). If you use this shortcode - you don't need to use 'pagination=on'.
* [property_overview for_sale=true] or [property_overview type=building]
* [property_overview bathrooms=1,3 bedrooms=2-4] Use "," for setting specific values of attributes and "-" for ranges.
* [property_overview sorter=on sort_by=price sort_order=DESC pagination=on] Will list all properties sorted by price in DESC order (attribute "Price" should be checked as sorted in Properties->Settings->Developer page ) and paginate.

Usage of custom attributes added in the Developer tab for queries, example:

* [property_overview house_color=blue,green] List all blue and green properties.
* [property_overview type=single_family_home year_built=1995-2010] List all single family homes built between 1995 and 2010.
* [property_overview type=apartment pets_allowed=yes] All apartments where "Pets Allowed" attribute is set to "yes".
* [property_overview template=detailed-overview] will load property-overview-detailed-overview.php from your theme folder.

= New features =

* New shortcode: [property_attribute] to pull a single attribute without using PHP. Example: [property_attribute attribute=bedrooms] will return the number of bedrooms for current property. [property_attribute property_id=4 attribute=bathrooms] will return the number of bathrooms for property with ID of 5.
* New shortcode: [property_map] to pull a single attribute without using PHP. Example: [property_map width="100%" height="100px" zoom_level=11 property_id=5].  Leave property_id blank to get map for currently displayed property.
* Two different sorter styles available, buttons and dropdown.
* "Sort by:" text can be customized in [property_overview] shortcode using the sort_by_text argument.


= More features =

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

= Premium features =

 Learn more about the [WP-Property Premium Features](https://usabilitydynamics.com/products/wp-property/premium/).

* Supermap - an overview map of all your listings.
* Slideshow - home page slideshow and property specific slideshow.
* Agent Module - Create agents and assign them to properties. Create agent-specific listing pages by using [property_overview wpp_agents=1] where 1 is the agent ID.
* PDF Flyer - Instantly generate PDF flyers of your properties for printing.
* XML Import - Map XML files to your WPP attributes, setup schedules, and import third-party listings.
* Power Tools with Capability Management Price - Extra functionality which includes capability management, white labeling the control panel, and changes menu titles.


= Translations =

* English (UK)
* French (FR)
* Danish (DK)
* German (DE)
* Italian (IT)
* Portuguese (BR)
* Russian (RU)
* Spanish (ES)
* Hungarian (HU)
* Slovakian (SK)
* Turkish (TR)

== Installation ==

1. Download and activate the plugin through the 'Plugins' menu in WordPress.
2. Visit Properties -> Settings page and set "Property Page" to one of your pages. This will be used in the URL to show all your single property listings.  For example, if you have a page called "Apartments", and you select it, your property URLs will be built like this: http://yoursite.com/apartments/property-name-here
3. Check "Automatically insert property overview into property page content." or copy and paste [property_overview] into the body of your main property page.
4. Visit Appearance -> Widgets and set up the widgets you want to show on your different property type pages.

http://vimeo.com/14281223

http://vimeo.com/14281599

http://vimeo.com/14473894


== Screenshots ==

1. Properties Overview
2. Editing Property
3. Customize Front-end with Property Widgets
4. Property Listings
5. A Building  Page
6. Image from gallery enlarged

== Frequently Asked Questions ==

= What if I can't see the developer tab? =

The developer tab should appear within a few seconds after you install the plugin.  If it doesn't, go to Properties -> Settings -> Troubleshooting and click the "Check Updates" button.

= My theme is not working... HELP =

Because there are so many different themes available for WP, not all of them will work flawlessly out of the box.  You can either customize your own theme, contact us for a theme customization quote or purchase one of our premium themes such as the Denali.

= Is the plugin available in different languages? =

Due to contributions from the plugin users we currently have it in Italian, Russian and Portuguese translations. If you would like to translate the plugin in your own languages please email us the files when you are done so we can include them in future updates.

= Side Bar disappears =

This is a theme issue. Once again you can customize your own theme, email us and we can give you a quote or you can purchase the Denali.

= How do stylesheets work? =

The plugin uses your theme's stylesheet, but also has its own. Inside the plugin folder (wp-content/plugins/wp-property/templates) there is a file called "wp_properties.css". Copy that file to your template directory, and the plugin will automatically switch to using the settings in that file, and will not load the default one anymore. That way when you upgrade the plugin, your custom CSS will not be overwritten. Same goes for all the other template files.

= How do I upload property images? =

You would do it the same way as if you were editing a post or a page.  On the property editing page, click the Image icon above the content area, and upload images into the media library.  If you want the images to show up on the front-end, you may want to visit Appearance -> Widgets and setup the Property Gallery widget to show up on the property page.

= How do I suggest an idea? =

You can send us a message via our website, or, preferably, visit our [feedback.TwinCitiesTech.com](http://feedback.twincitiestech.com/forums/95259-wp-property) page to submit new, and vote on existing, ideas.

= I like where this is going, but how do I get customization? =

If you submit a popular idea on UserVoice, we WILL integrate it sooner or later.  If you need something custom, and urgent, [contact us](http://twincitiestech.com/contact-us/)

== Upgrade Notice ==

= 1.38 =
* Added compatibility with FEPS 2.0 premium feature.

= 1.37.3.1 =
* Fixed [property_overview] attributes settings.

= 1.37.2.1 =
* Fixed tab breaking on settings page.

= 1.35.1 =
* Security fixes

= 1.31.1 =
* "Area" attribute will only be appended with " sq ft." if it is set as numeric in the Developer tab.

= 1.24.0 =
* Changed property export function to export in JSON format.

= 1.20.0 =
* Major changes to the way pagination is handled.  Pagination is no longer loaded from a file but from wpp_draw_pagination().

= 1.17.2 =
* draw_property_search_form() has been changed where arguments passed to it are in array format.

= 1.15.7 =
* "For Sale" and "For Rent" attributes have been removed form the API.  These programmatically added attributes were causing some confusion amongst users.  If you need these attributes, add them using the Developer tab.

= 1.15 =
* Default property-pagination.php template is updated to reflect the below-the-content pagination.

= 1.14.3 =
* Fixed bug with Google Map zoom level not saving.
* Updated search widget query to only include published properties in value calculation
* Fixed bug with Google Map values not being mapped correctly to WPP attributes.

= 1.14.2 =
* Important change to 'wpp_property_stats_input_' filter, it no longer passed the $post object, but the $property array. Default API functions have been updated to reflect, but any custom functions will need to be updated.

= 1.08 =
* Some CSS changes to the default style sheet

= 1.06 =
* The default front-end CSS file has had a number of changes relating to pagination and sorting elements. #properties_pagination, #ajax_loader and #properties_sorter have been changed to .properties_pagination, .ajax_loader and .properties_sorter to allow multiple instances on a single page.

= 1.01 =
* Improved caching.

= 1.00 =
* Search form shortcode.

= 0.7261 =
* Improvements to the search widget.

= 0.7260 =
* Properties settings page moved under Properties menu
* Sorting has been added - be sure to check attributes as sortable in Developer tab

= 0.6.0 =
We are moving out of beta stages, but you may still experience bugs now and then.  We welcome all feedback.

= 0.5.3 =
We are still in early stages, so updates will be coming out routinely.  Please do not hesitate to send us feedback and suggestions.

== Changelog ==

= 1.42.2 =
* Fixed 'exclude' parameter for draw_stats function. The issue also was related to showing specific property attributes in PDF Flyer.
* Fixed property object caching.
* Fixed bug on clicking 'Add to Featured' on 'All Properties' page.
* Fixed option 'Display in featured listings' on edit property page.
* Warnings and Notices fixes.

= 1.42.1 =
* Fixed property search for range input and range dropdown fields types.

= 1.42.0 =
* Added strict search option for [property_search] shortcode and 'Property Search' widget.
* Removed taxonomies metaboxes on edit property page for users who do not have capabilities.
* Removed deprecated encode/decode mysql output functionality on post meta saving/getting.
* Improved [property_attribute] shortcode.
* Improved l10n (localization) script implementation, which reduces load time.
* Updated localization files.
* Fixed bug with saving shortcode with single quotes values in meta attribute's field.
* Fixed reversed order of images for 'Property Gallery' widget.
* Fixed duplicated images sizes issue.
* Fixed address format display for 'Child Properties', 'Other Properties' and 'Latest Properties' widgets.
* Fixed bug related to displaying attributes with the same labels.
* Fixed capabilities issue.
* Fixed Warnings and Notices.
* XML Importer. Added debug log functionality for better troubleshooting issues.
* XML Importer. Improved schedule's advanced options.
* XML Importer. Fixed issue related to saving schedule with 50 and more xpath map's attributes.
* XML Importer. Fixed importing empty values with line breaks.
* PDF Flyer. Added paragraphs for description in PDF Flyer.
* PDF Flyer. Improved PDF Lists management.
* PDF Flyer. Refactored PDF Flyer generation.
* PDF Flyer. Fixed the issue with primary and secondary images duplication.
* PDF Flyer. Fixed values for checkbox attributes when enabled 'Show Checkboxed Image' option.
* PDF Flyer. Fixed bug with getting an old PDF Flyer file instead of new one.
* PDF Flyer. Fixed PDF List attributes view bug.
* Slideshow. Fixed slideshow images management on edit property page.
* Slideshow. Fixed the bug with reset global slideshow images on saving second time.
* FEPS. Disabled 'required' option for 'images upload' attribute on FEPS form.
* FEPS. Fixed bug with reset slideshow images on property update.
* FEPS. Fixed bug with image removing.

= 1.41.4 =
* Added hooks to get_sortable_keys and get_properties functions.
* Fixed bug with sorting and pagination for [featured_properties] shortcode.
* Fixed issue with searching decimal numeric values on property search.
* Fixed address format option for Featured Properties widget.

= 1.41.3 =
* Fixed issue with not matching up properties types on XML import.
* Fixed bug with incorrect price sorting on property overview ( results page ) when any of properties has non-numeric or empty value for price attribute.

= 1.41.2 =
* Fixed issue with phRETS cookie file generating on connection to RETS servers.
* Added cleaner of old phRETS cookie files for temp dir.

= 1.41.1 =
* Fixed fatal error related to including phrets library on servers with PHP 5.2.X.

= 1.41.0 =
* Added hook to property_search form to have ability to add any custom fields.
* Improved [featured_properties] shortcode.
* Fixed search by more than one property ID ( e.g. [property_overview ID='1,2,3'] ).
* Fixed search by 'property_id' if attribute with the same slug exists.
* Fixed bug with child pages of default properties page: pages have been rendered by wordpress as property posts.
* Fixed bug with cookie file generating on connection to RETS servers.
* Fixed 'range input text' fields rendering for property search form.
* Fixed attribute 'sorter' in [property_overview] shortcode. To disable sorter, use [property_overview sorter=off] or [property_overview sorter=false].
* Removed limit (5) of shown children in 'Child Properties' metabox on Edit Property page.

= 1.40.1 =
* Reverted back Sidebar areas which have been removed in 1.40.0 release.

= 1.40.0 =
* Added option 'Enable Comments' on settings page. Adds comments support for property post type.
* Added 'Automatic Renew Subscription Plan' option for Front End Properties submissions (FEPS) premium feature.
* Added 'Featured' information to subscription plans in Front End Properties submissions (FEPS) premium feature.
* Added improvements and fixes to Twentytwelve theme's styles.
* Added hook to wpp_render_search_input function for ability to add field with custom type.
* Improved file caching.
* Refactored CSS files. Added LESS implementation.
* Fixed view styles on premium features settings pages. Compatibility with WordPress 3.8 and higher.
* Fixed condition in sort_stats_by_groups function.
* Fixed wrong condition in get_properties function for value which contains '-' symbol in it ( e.g. 'New-York' ).
* Fixed draw_stats function.
* Fixed 'Show XML Import History' and 'Delete Unattached Files' functionality on Help Tab in Settings.
* Fixed showing attributes' values of draft properties in dropdown list of the search form.
* Fixed showing Logs data when option 'Show Log' is enabled.
* Removed deprecated option "Load WP-Property scripts on all front-end pages".

= 1.39.0 =
* Added filter ud::template_part::path which allows to add/change templates storage directory.
* Added improvements to Front End Property Submissions (FEPS) premium feature.
* Added improvements to property search form.
* Added ability to filter properties by ID or property_id attribute for shortcode [property_overview]. Example: [property_overview ID="777"].
* Improved init of WPP_DEBUG_MODE for cron job.
* Fixed the bug with redirecting to PayPal on checkout related to Mozilla browser (FEPS).
* Fixed the bug with listing publishing after successful processed checkout on sponsored listings form (FEPS).
* Fixed fatal error on notification sending when WP-CRM plugin is installed and activated.
* Fixed attributes by groups sorting on single property page.
* Fixed Address Validation functionality.
* Fixed the issue with '+' symbol in values on settings saving.
* Fixed pagination issue of [property_overview] shortcode.
* Fix to base_url for obfuscated WordPress structure.
* White Label fixes.
* Updated Portuguese (BR) Localization.

= 1.38.4 =
* Fixed secure issues.

= 1.38.3.2 =
* Fixed fatal error related to redeclaration of class.

= 1.38.3.1 =
* Fixed typo which broke property_overview pagination.

= 1.38.3 =
* Admin Tools functionality moved from premium features to core.
* Fixed Yes/No values for checkbox type inputs.
* Fixed the issue related to huge settings data ( when there are more than ~150 property attributes ) on settings saving ( max_input_vars ).
* Fixed save_property action.
* Fixed address validation.
* Fixed property overview search by wpp_agents and added ability to add custom strict search using filter 'wpp::required_strict_search'.
* Fixed meta fields labels on single property page.
* Fixed different issues related to XML Importer premium feature.
* Fixed data reset on adding new form for Front End Property Submissions (FEPS) premium feature.
* Cleaned up php and javascript code.

= 1.38.2 =
* General code clean-up of PHP and JavaScript libraries, core and templates files.
* Added ablity to show decimals for numeric values.
* Added /static directory which contains auto-generated code documentation for developers - generated via YUIDoc syntax.
* Added a "makefile" for building WP-Property - build includes running unit tests and updating documentation.
* Added testing environment via Mocha (Node.js) for developers.
* Updated Portuguese translation thanks to Raphael Suzuki. (https://github.com/UsabilityDynamics/wp-property/pull/11)
* Removed CustomInputs third-party library.
* Improved Properties Export functionality.

= 1.38.1 =
* Added compatibility with Wordpress 3.6.
* Removed anonymous function which caused parse error in PHP <5.3.

= 1.38.0 =
* Added compatibility with Front End Property Submissions (FEPS) 2.0 premium feature.
* Added Hungarian language to address validation.
* Added debugger for get_properties() which prints args to Firebug when debug mode is enabled.
* Fixed admin_only functionality for property stats and meta.
* Fixed Filter metabox functionality on All Properties page.
* Fixed encoding issues for XMLI premium feature.
* Localization fixes.
* Removed $wp_properties['l10n'].
* Removed third-party library CustomInput.

= 1.37.6 =
* Added fonts for PDF Flyer premium feature
* Added ability to enable selecting parent property which already has parent
* Added ability to set custom coordinates ( only for FEPS premium feature ).
* Fixed search of non-numeric attributes where values contain +,-
* Fixed double property_type on search widget settings if property_type attribute exists
* Fixed fancybox images for [gallery] shortcode
* Fixed draw_stats() function ( issue related to showing property meta on single property page in Denali theme )
* Warning errors fixes

= 1.37.5 =
* Fixed issue with predefined values which contain ndash symbol.
* Fixed [supermap] shortcode's issue related to property_type attribute.
* Modified default sort of [property_overview] shortcode. Results are sorted by post_date in DESC order.
* Prohibited selecting parent property which already has parent.
* Fixed average aggregated value of parent property.
* Fixed and improved 'Check Updates' functionality.

= 1.37.4 =
* Fixed issue with en-dashes.
* Fixed searching by address attribute.
* Fixed warning message in Featured Properties widget
* Added strict_search attribute to shortcode propery_overview.

= 1.37.3.2 =
* Fixed taxonomy links.
* Fixed 'Display address' functionality.
* Fixed plugin capabilities functionality
* Fixed potential issue related to creating cookie file for CURL requests to RETS servers
* Added additional specific hooks.

= 1.37.3.1 =
* Fixed get properties functionality.

= 1.37.3 =
* Fixed double inclusion of JQuery UI files.
* Fixed get property data functionality.
* Fixed adding of meta boxes.
* Fixed agent role's capabilities.
* Fixed max execution time issue on properties exporting.
* Fixed [property_overview] shortcode.
* Updated get properties functionality.
* Administrators can see 'admin only' attributes on frontend now.
* Updated Italian localization.

= 1.37.2.1 =
* Fixed tab breaking on settings page.

= 1.37.2 =
* Namespaces fixes to prevent conflicts with third party plugins and themes
* Added ability to enable/disable sorting properties by title
* Added ability to show random featured properties
* FEPS: Fixed single property address & manual coordinates issue
* PDF Flyer: fixed issues related to non-Latin characters in post title
* PDF Flyer: added displaying currency and separator symbols
* PDF Flyer: QR_code was made clickable
* French Localization updates

= 1.37.1 =
* Address validation fixes
* Added ability to sort attributes in Property Attributes widget
* Added option for aggregating currency and number attributes
* XML Importer's cron fixes
* Fixed missing of tabs on settings page on the first plugin activation
* Fixed property_gallery shortcode
* Updated Portuguese and Russian localization files

= 1.37.0 =
* Changes in Address Validation due to OVER_QUERY_LIMIT issues
* Global Slideshow fixes
* Fixed property overview bottom pagination issue
* Fixed PropertyType Label in Property Search widget
* Fixed property search by address attributes
* Fixed issues with widgets shown inside tabbed area
* Corrected behavior when values were displayed as True from non-boolean fields if it starts from 1;
* Fixed OtherProperties Widget
* Fixed Search widget's selected values in dropdown fields on page result
* Fixed global $property var type which caused fatal errors in different places
* Fixed issue on Premium Features ( Settings page ) related to localization usage
* Fixed Regenerate All PDF Flyers and Lists process
* Fixed showing of currency symbols and number separators
* Added ability to show email as a link
* Fixed aggregating currency and number attributes for parent property which solves issues related to data sorting
* Updated localization files

= 1.36.3 =
* Fixed pagination slider on property_overview
* Fixed property restoring from trash in Wordpress 3.5
* Flush rules fixes.
* Added RSS feed for properties
* Fixed Attributes dragging on property search widget's form
* Fixed Featured properties shortcode
* Fixed themes specific CSS
* Fixed Fancybox on Supermap's infobox
* Added ability to sort slideshow images for single property
* Updated localization files

= 1.36.2 =
* Added WordPress 3.5 compatibility.
* Fixed new image size adding.
* Fixed revalidation of addresses.

= 1.36.1 =
* Fixed can_get_image() function what solves many issues with displaying images on PDF Flyer and PDF Lists including displaying of Location Map on PDF Flyer.
* Fixed Features and Community Features section which becomes hidden after Regenerating of all Flyers.
* Fixed displaying of Agents' information on PDF Flyer.
* Fixed generating of PDF Lists.
* Fixed parameter thumbnail_size of [featured_property] shortcode.

= 1.36.0 =
* Added WordPress 3.4 compatibility.
* When manual coordinates are set, and the address field is empty, the listing's formatted address will be reverse-located based on the set coordinates.
* Added object caching to WPP_F::get_attribute_data() function
* Added option to include / exclude listings in regular WordPress search results. (Main Tab).
* Added check for mb_detect_encoding function, to avoid fatal errors on certain servers.
* Added XML Importer function to view list of recently imported listings. (Help Tab).
* Added option on Settings/Display page "Show Checkboxed Image instead of "Yes" and hide "No" for Yes/No values" for "Property Page".
* Added Error Silencer to WP-Property PDF Flyer to avoid issues on PDF creation.
* Added JavaScript validation on Settings page for required fields.
* Added customizable CSS classes implementation.
* Refactoring of Contextual Help.
* Updated Brazilian Portuguese translation.
* Fix: Hidden attributes for some property type are shown as sorter buttons, if these attributes selected as sortable.
* Fix: If property attribute started with "1" and it was not marked as "numeric" it was displayed like marked checkbox.
* Fixed showing [property_map] shortcode on non-single property pages.
* Fixed view of single property page when visibility of property is 'password protected'.
* Fixed attribute thumbnail_size of [featured_properties] shortcode.
* Fixed hidden attributes for some property type are shown as sorter buttons, if these attributes selected as sortable.
* Fixed using of shortcodes e.g. [property_attribute] in property attribute value.
* Fixed property removing functionality on All Properties page.
* Real Estate Agent: fixed capabilities.
* XML Importer: modified to scan for orphan file attachments and added ability to delete all orphaned attachments. (Help Tab).
* XML Importer: fixed RETS by adding user agent shared password to options - also implemented better error handling.
* FEPS: Added WP-CRM plugin integration to FEPS - added [property_link] and [property_title] shortcodes for notifications and Contextual help.
* Property Gallery widget fixes.
* Property Agents widget fixes.
* TwentyEleven and TwentyTen theme compatibility improvements.

= 1.35.1 =
* Security fixes

= 1.35.0 =
* Fixed potential issue with conflicts of TCPDF libraries.
* Fixed sorting by numerical values on front end.
* Fixed issues with CSV importing.
* Added new WP-CRM notification events.
* Added option to disable the default new account notification sent out by FEPS.
* Real Estate Agents: Fix to agent image removing.
* XML Property Importer: Updated to support changing RETS version.
* FEPS: Added the ability to remove uploaded images before submitting.
* Super Map: Fixed potential issue with address attribute.

= 1.34.1 =
* Fixed 'Real Estate Agents' premium feature: reimplemented image adding
* Added default sort attribute 'Title' to property overview shortcode (it was missed if any property attribute was sortable)

= 1.34.0 =
* Added options to "Other Properties" widget to show properties of same time, if no parent property exists, and to shuffle results.
* Fixed bug with Property Features causing error when Microsoft Word apostrophe used
* Fixed the issue with overwriting 'address attribute' to validated value.
* Other simple fixes.

= 1.33.2 =
* Extra changes: reverted some fix which had conflicts.
* Added URL trimming for images.
* Fixed property attribute's value, which showed incorrect url (if url was set for property attribute).
* Fixes to WPP_F::isURL().

= 1.33.1 =
* Encoding and EOL fixes.
* Widgets' rendering and view fixes.
* Grouped Property Search view fixes.

= 1.33.0 =
* Update to detect plugin name so can be placed into different folder.
* Added $_POST to $_GET conversion for search results.
* Added new arument "in_new_window" (value:true|false) to [property_overview] shortcode.
* Added 'Clear Cache' functionality. See Settings page, Tab "Help".
* Removed depreciated setting "Display larger image, or slideshow, at the top of the property listing." which is not used anymore.
* Bug fix with property type viewing in "Latest properties" and "featured properties" widgets.
* Bug fix with property's attribute "city". Now "Searchable" can be manually set for it.
* Other minor fixes.

= 1.32.1 =
* Featured Properties widget restoration.

= 1.32.0 =
* Added "Property Attributes" widget. When placed on a property page, can be used to display a list of attributes related to the property.  The widget allows selection of specific widgets, as well as configuration of the sort order.
* Added input type selection for data entry, available: free text, dropdown selection and checkbox. (in Developer tab)
* Updated [property_attribute] to query taxonomies. New arguments: "make_terms_links" and "separator".
* Bug fix with back-end property overview page breaking when certain attributes were set to display in overview column.
* "Property Type" label can now be renamed by creating a custom attribute with property_type slug, and giving it a different label.
* "Features" taxonomy hidden from overview table when disabled via Power Tools feature.
* Removed some labels for "Property" to make application more universal.
* Update to prevent single property pages from taking over taxonomy archives on front-end.
* Updated post type registration to exclude property type from regular search results.
* Re-ordered contents of "Developer" tab placing Attributes at the top for convenience.
* Several property search improvements
* FEPS: Pre-defined values and input types are now set by the newly available "Data Entry" settings in the Developer tab.
* Developer notes: WPP_Core::init() broken up into WPP_Core::init_upper() and WPP_Core::init_lower(). Widget and widget area loading compartmentalized into WPP_F::widgets_init(). Back-end scripts consolidated into WPP_F::load_assets();

= 1.31.1 =
* Patch release.
* "Area" attribute will not only be appended with "sq ft." if it is set as numeric in the Developer tab.
* All "wpp_stat_filter_" filters are set in prepare_property_for_display(). Exceptions: wpp_render_search_input(), Sidebar Property Listing widgets, and back-end Property Overview rows.
* FEPS. Added JavaScript minification to avoid WordPress formatting of the scripts.
* PDF Flyer.  Improvements to avoid server issues when open_basedir restriction is in effect.

= 1.31.0 =
* New [property_attribute] shortcode arguments: before, after, if_empty, do_not_format, and strip_tags.
* Improvements to Parent Property editor.  Values aggregated from child properties are uneditable and include a notification message so it can be easily identified which attributes are upward inherited, and which are not.
* Improvement to Supermap and Property Map to display a list of child properties, when they exist, for the currently displayed property, and general redesign of the Google Map balloon Info Window.
* Bug fixes to "Other Properties" widget.
* Major improvements to template selection and general front-end page-type logic.
* Settings page UI modification to work with deployments not utilizing permalinks.
* WordPress 3.3 compatibility testing.
* Super Map: Added JavaScript code minification.
* XML Importer: A few bug fixes and more advanced removal of bad XML characters.

= 1.30.1 =
* Patch release to fix Google Map problem.
* Update the $property and $post variables so they are same on single property pages. For developers: $post is always an object, and $property is always an array.

= 1.30.0 =
* New shortcode: [property_attribute] to pull a single attribute without using PHP. Example: [property_attribute attribute=bedrooms] will return the number of bedrooms for current property. [property_attribute property_id=4 attribute=bathrooms] will return the number of bathrooms for property with ID of 5.
* New shortcode: [property_map] to pull a single attribute without using PHP. Example: [property_map width="100%" height="100px" zoom_level=11 property_id=5].  Leave property_id blank to get map for currently displayed property.
* New dynamic arguments available for [property_overview]: post_id, post_parent and property_type which are replaced with the current properties' data when passed into a shortcode on a property page.
* All property attributes can now contain and execute WordPress shortcodes.
* Property Type label can now be used to query properties, example [property_overview property_type='Single Family Home']
* UI Improvement: On search results sorting options are not displayed if only one property is returned.
* UI Improvement: Added "expand all" and "collapse all" to Property Stats editor table in Developer tab.
* Improved pagination slider to adjust it's width based on the width of the Forward and Next buttons to avoid overlapping issue with translated buttons.
* Improved property export to work with JSON and XML format.  Exports can also conduct basic queries using URL parameters. Instructions may be found in the "Help" tab.
* New Metabox: Child Properties: When editing a parent property, all children are displayed in a metabox.
* [featured_properties] shortcode improvements, new shortcode arguments that can be passed: property_type, per_page, sorter_type, thumbnail_size, pagination, hide_count, and stats.
* Property Overview shortcode can accept a ID in order to query a specific property.
* Improvements to default property-overview.php for better custom templating.
* Improved remote script and image loading.  Remote scripts (such as Google) are first verified by WPP_F::can_get_script() to make sure the are reachable, before being sent to browser. Images can be verified via WPP_F::can_get_image()
* Fixed a problem with child properties' permalinks.
* Fixed issue with "Next" pagination button not working with certain configurations.
* Added a check to prevent display addresses from returning with commas only when not enough attributes exist to display full address, yet formatting is applied.
* Added additional conditional JavaScript function checkers to avoid errors if Fancybox, Google Maps, etc. are not loaded for some reason.
* Added additional backwards-compatibility for thumbnails sizes.
* Added automatic image resizing to Google Map infobox thumbnail.
* XML Importer: Added button to sort all import rules.
* XML Importer: Added better limit control - pre and post-QC.
* XML Importer: Added setting to set minimum image size for images.


= 1.25.0 =
* Added [list_attachments] shortcode for displaying property, and regular post, attachments along file-type icons.  Included file-type icons are: Microsoft Word, Microsoft Excel, ZIP, Adobe PDF, and default.
* Added support for secure domains, external assets are loaded using https mode when necessary.
* Added sorter_type=none and hide_count=true to [property_overview] shortcode which let you hide the sorter or the result counts, respectively.
* Added wpp_alternating_row() and get_attribute() functions to simplify template creation.
* Added [property_gallery] shortcode. (Slideshow Premium Feature)

= 1.24.2 =
* Added out-of-the-box support for WPTouch plugin for displaying property pages on mobile devices.
* Bug fix with taxonomies displaying incorrect results on property pages.

= 1.24.1 =
* Bug fix with class admin tools.

= 1.24.0 =
* Added check to verify that the property type of the property being edited exists, if not a warning is displayed (in case property type was deleted by accident)
* Added a notice to developer tab when new attributes are being added that are used for address storage, and the attributes are displayed a read-only on property editing pages.
* Added a function to hide attributes from the back-end overview column when there are over 5, and added a button to toggle all the attributes over 5.
* Fixed issue with Denali theme not showing property-specific sideshow.
* Fixed bug that occurred with pagination when hashes() function was not found.
* Fixed to Attribute Grouping functions.
* Added "Help" tab tool to mass-set property type.
* Added some support for legacy property templates.
* Changed property export function to export in JSON format.
* Added conditional function checking to prevent all JS to break when Fancybox, and other functions, were not found.
* Improved attachment deleting function - now if the folder has no files upon deletion, it is deleted as well.
* Added wpp_property_page_vars filter for API ability to load extra variables into WPP templates.
* Added styling for FEPS (Front End Property Submissions)
* Tweaks to Supermap searching and custom Map Icon uploading. (Super Map)

= 1.23.0 =
* Fixed issue with grouped searchable attributes not generating and caching value ranges when Property Search widget was being saved.
* Added on-the-fly address revalidation.  If a property is being viewed, and the address has not been validated, WPP will do it on the spot.
* Property editing UI does not display a property type dropdown if only one property type is set up on the site.
* Added "Parent Selection" to "Hidden Attributes" selection, so property parent selection can be hidden if needed.
* XML Importer RETS support integration and major performance and efficiency improvements. (XML Property Importer)
* Added option to disable property taxonomies. (Power Tools with Capability Management)
* Custom map icon uploading and selection. (Super Map)
* Pagination support and search improvements for the Supermap. (Super Map)
* Fix to Property Search widget not properly caching values when toggling between grouped and non-grouped versions.

= 1.22.1 =
* Patch release, fixing single-property display issues when grouping not being enabled after upgrade.

= 1.22.0 =
* Grouping capability - attributes can now be placed into different groups, and ordered by groups.
* Search widget sortable attributes - attributed in the property search widget can now be sorted independent of the sort order in the Developer tab.
* Search widget and [property_search] shortcode can render attributes using groups.
* Added function to execute shortcodes in Text Widget, eliminating the need for reliance on a third-party plugin.
* Attribute groups create and place property attributes into different metaboxes on the property editing screen.
* Number and currency formatting on the property search widget.
* Property Overview can now use "post_parent" to get child properties of a parent property.
* Featured properties can now be queried using the [property_overview] shortcode.
* Control panel property overview filtering system improved to work with toggable columns.
* Fix to Property Gallery widget to now show caption container when there is no caption.

= 1.21.0 =
* Fixed issue with front-end pagination calculating pages incorrectly.
* Resolved bug that prevents premium features from being enabled when they were manually disabled, and then upgraded.
* Added "sort_by_text" and "sorter_type" arguments to the [property_overview] shortcode.
* Fixed issue with Fancybox not working with older versions of property-overview.php
* Added ability to use the old dropdown sorter in property_overview by passing sorter_type=dropdown into the [property_overview] shortcode.
* Fixed issue with currency ranges not being properly formatted.
* Updated WPP_F::get_properties() function to return only published properties when a status is not specified, fixing issue with Supermap showing trashed properties.
* Fixed issue with agents overview page not displaying pagination. (Agents Feature)
* Improved XML Export feed generation function, which now allows a limit argument to be passed via URL, and returns additional information such as number of properties in feed, and load time.
* Added hook for 'wpp_custom_sorter' for developers to use their custom front-end sorting mechanism.

= 1.20.1 =
* Release fixes issue with search dropdown values being incorrect when a search widget queries multiple property types.

= 1.20.0 =
* Redesigned pagination and sorting for front-end.
* New back-end UI for dynamic property filtering.
* Added new template functions such as have_properties(), returned_properties() and wpp_draw_pagination().
* Attributes marked as numeric or currency filter our bad characters as you type on the property editing screen.
* Fixed issue with price ranges in drop-downs and in property overview displaying incorrectly.
* draw_stats("show_true_as_image=true") will render a checkbox on front-end for attributes with value of "true".
* draw_stats("hide_false=true") will hide any attributes that have a value of 'false'.
* Added property capability management (Premium Feature).
* Added 'view_details' argument to the [property_overview] shortcode which renders a "View Details" button.
* Improved XML Importer cycle time nearly tripling import speed on large feeds (over 10,000 objects).
* Fixed issue with Supermap "auto-fix" button not working.

= 1.17.2 =
* Added additional TwentyTen style fixes.
* Search widget improvements - default sort attribute and order can be set.
* Added ability to do "open ended search"
* Changed draw_property_search_form() to allow default sort attribute and sort order.
* Added Danish translation
* Improved the UI on the settings page, especially in the Developer tab
* Added ability to mark attributes as numeric or monetary, which will make WPP format them properly when displayed on the front-end.
* Added function to (optionally) disable the WordPress update_post_caches() function from being ran on the property overview page
* Moved a lot of help text from the body of the settings page into the dropdown "Text" tab

= 1.17.1 =
* Fix to searching - when a single numeric value is in search, system looks for exact match, not "like" match as with strings.
* Minor fix with widgets - properties without thumbnails were showing up incorrectly.
* Modified WPP_F::revalidate_all_addresses() so an array of property IDs can be passed in to validate.

= 1.17 =
* After pagination, user's browser is automatically scrolled to the top of the paginated properties.
* Fixed phone number formatting issue where the number would not be displayed if it was not 10 characters.
* Added multi-checkbox search option.
* Added automatically generated ALT and TITLE to sidebar widgets for improved image SEO.
* Image captions and descriptions can not be displayed in Gallery widgets.
* Renamed jQuery Cookie file to jquery.smookie.js (to avoid some hosts from blocking the file for security reasons)
* Links in Property Meta are displayed as clickable links on front-end.
* revalidate_all_addresses() function updated to skip any properties with valid addresses, if argument passed.
* More tweaking and fixes to the sorting issues with numerical values (i.e. prices).


= 1.16.3 =
* Added cron.php to run imports from the command line
* Image fixes for the XML importer

= 1.16.2 =
* Fix to widget image sizes (overlapping issue).

= 1.16.1 =
* Option to automatically remove all associated property images when a property is deleted.  WordPress does not remove attached images when the post or page is deleted, so a custom function was added to do this for properties.
* Improvements to the default property-overview.php template.
* Fix to "Developer" tab, previous version did not show some labels.
* Semantic changes - "Properties" now labeled as "All Properties" on back-end.
* Improvements to automatic image regeneration function, wpp_get_image_link(), and an option to disable it completely.

= 1.16 =
* Added "Admin Only" attribute designator.  Using the Developer tab, an attribute can be marked as "Admin Only" and it will only be visible in control panel or to logged in administrators.
* Added an option to prevent loading the theme-specific stylesheet, if it exists in the first place. (Settings -> Display -> "Do not load theme-specific stylesheet.")
* Ensured compatibility with WordPress 3.2
* Changed "Properties" label to "All Properties" to stay consistent with new semantics.
* Fixed admin styles to match new admin design.
* Added styles for Twenty Eleven theme.
* Added on-the-fly image resizing.
* Updated Nivo Slider and global JS files to avoid version conflicts.

= 1.15.9 =
* New: HTML code now rendered in meta fields on single property page.
* Fixed issue spaces being removed from predefined values.
* Fixed issue with 1 showing up on front-end instead of 'Yes'
* Fixed issue with dashes in predefined values, they are now converted into HTML character.
* Fixed bug with sorting for [property_overview] shortcode.
* Added option to disable automatic feature download.
* Added option to complete hide "Hidden Attributes" from property editing page - as opposed to being grayed out.
* Added additional translation strings to property-pagination.php
* Added new Google Map languages: Arabic, Bulgarian, and Thai
* Added array_fill_keys() to support older versions of PHP (was causing errors when displaying [property_search] widget)

= 1.15.8 =
* Bug fix with warning being displayed on Settings page

= 1.15.7 =
* Changed core to use load_plugin_textdomain() to load localization settings.
* "For Sale" and "For Rent" attributes are no longer being added programmatically.
* Fixed default stylesheet for Supermap which was causing overflow issues with images.

= 1.15.6 =
* Property meta can now execute shortcodes!
* Made pagination backwards compatible to function with customized older versions of WP-Property themes
* Added Turkish translation.

= 1.15.5 =
* Properties -> Settings -> Plugins tab now displays the domain you should use when purchasing premium features.
* Fix to property_overview query that had issues with sorting.

= 1.15.4 =
* Property editing screen will show a checkbox for an attribute if the pre-defined values in Developer tab are set to 'true,false' or 'false,true' instead of a dropdown.
* Fix to pre-defined values being trimmed automatically, before an issue was caused by spaces before or after the value not being recognized in search.

= 1.15.3 =
* More advanced currency settings.
* [property_overview show_children=true/false] tag added.
* 'wpp_get_properties_query' filter added to the primary property query.
* Bug fix in: prepare_property_for_display();

= 1.15.2 =
* Fixed issue with certain attributes causing "No Result Found" search result.

= 1.15.1 =
* New feature to add predefined values that will create a drop-down on the property editing page.
* Added option to show pagination below the property_overview content as well as the top. (Enable on Property Settings page under "Display" Tab)
* Fixed Google map zooming issue.
* Fixed bug with search values from trashed properties being used in search drop-downs.
* Change to the property_overview shortcode to support date ranges in the form of mm/dd/yyyy for filtering.
* Add localization tags for "Type:"  and "Property Types".
* Fixed bug with Property Gallery header displaying even if there are no photos in gallery.

= 1.15 =
* Non-public release of 1.15.1

= 1.14.2  =
* Updated 'wpp_property_stats_input_' filter to pass full $property array to function.
* Fix to TwentyTen default styles.
* Added 'wpp_post_init' action that runs after WPP is fully loaded and initialized -> good place to hook in WPP pre-header functions.
* Updated Default API functions to reflect changes to 'wpp_property_stats_input_' filter update.
* Added "label" key back to default taxonomies in actions_hooks.php

= 1.14.1 =
* Public release of 1.14

= 1.14 (non-public version) =
* Fix to search and sorting.
* Fixed typo in property editing screen.
* Added filter: wpp_search_form_field_[slug] to filter elements in search widget / shortcode.
* Fixed issue with draft properties showing up poorly in back-end.
* Fixed ownership nag not going away after ownership of premium folder has been granted.
* Generated new POT file
* Fix to Google Maps link to use localization from back-end overview.
* Added backup / restore backup features for WP-Property configuration on Properties -> Settings -> Help

= 1.13.1 =
* Version fix.

= 1.13 =
* Public release of 1.12

= 1.12 =
* Included Slovakian translation.
* Small fix to UD_F::base_url() to return the correct URL when permalinks are not used and using the dynamically created property overview page.
* Various fixes to search logic.
* Updated Premium Folder permission error to identify when the issue really is permissions, or actually ownership.

= 1.11 =
* Search result pagination back-button support.
* Added function to automatically check for premium features on activation.
* Improved compatibility with sites not using permalinks
* Added $wp_query variable is_property_overview
* Added function is_property_overview_page() to check if current page is for property-overview
* Fixed issue with IE speed problem

= 1.10 =
* Bug fixed that prevented dashes from being used in attribute values.

= 1.09 =
* Property Features taxonomy bug fix.
* Fixed Google Maps panning issue (Infobox was being cut off)
* Search fix for "City"

= 1.08 =
* Compatibility with WordPress 3.1
* Added hook to add new taxonomies
* Added capacity to load theme-specific WPP stylesheets
* Fix multiple shortcode pagination bugs
* Added a 'plain_list' overview template
* Fixed FancyBox bug
* Fixed address backslash issue.
* Fixed incorrect Spanish translation issues.
* Fixed pagination issue with empty property showing up.
* Fixed sorting by title

= 1.07 =
* Added Child Theme Support
* MSIE 7 - 8 pagination error fix
* Google Maps infobox special character fix

= 1.06 =
* "wpp_settings_page_property_page" action hook added to settings page for UI expansion
* Multiple [property_overview] elements can now be used on a single page without interference.
* Option to disable phone number formatting
* [property_overview pagination=off] fix
* WIP: Multiple [property_overview] elements on one page - pagination fix

= 1.05 =
* Added function to re-validate all addresses, very useful for updating Google Maps localization in bulk.
* Added additional Property Page options. Watch the [Property Page configuration screencast](http://sites.twincitiestech.com/the-denali/wp-property-features/setting-up-property-page/)
* Added option to display default property page content on search result page
* Added Hungarian translation

= 1.04 =
* Add manual override for latitude and longitude
* Added do_action - 'save_property' for API access
* Added filter 'wpp_google_maps_localizations' to add Google Maps localization options

= 1.03 =
* default_api.php area filter internationalization fix
* Bug fix with [property_overview] not using "menu_order" to sort properties and defaulting to the first attribute
* Performance improvement in get_properties() function by commenting out log

= 1.02 =
* [property_overview template=my_template] will load  property-overview-my_template.php from your theme folder instead of property_overview.php
* CSS property overview bug fixed.
* Default phone number bug fixed.
* HTML Validation fixes.

= 1.01 =
* Caching improvements.
* draw_stats() function improvements. Args return_blank and make_link added.

= 1.00 =
* Search form available via shortcode.
* Similar properties widget added.
* Google Maps popup box customization.
* Additional search input options.