=== WP-Property - WordPress Powered Real Estate and Property Management ===
Contributors: usability_dynamics, andypotanin, jbrw1984, maxim.peshkov, Anton Korotkoff, ideric, MariaKravchenko, smoot328
Donate link: http://usabilitydynamics.com/product/wp-property/
Tags: property management, real estate, listings, properties, property, wp-property, real estate cms, wordpress real estate, listings, estate, MLS, IDX, RETS, XML Import
Requires at least: 4.0
Tested up to: 4.9.8
Stable tag: 2.3.8

== Description ==

WP-Property is WordPress plugin for creating and managing highly customizable real estate, property management, and completely custom listing showcase websites.

Although WP-Property can handle so much more than real estate. Showcase any kind of entity you want, from livestock, golf carts, to properties and products, experiencing unparalleled ease of use and flexibility on the way.

Is the current functionality still not enough to cover all your needs? Learn more about the WP-Property free <a href ="https://www.usabilitydynamics.com/product/wp-property/addons"> Add-ons </a>
and <a href ="https://www.usabilitydynamics.com/product/wp-property/themes"> Themes. </a>


> Do you want to see the plugin in action? Just proceed to our <a href ="https://demo.usabilitydynamics.com/avalon/"> Avalon demo site. </a>
 <a href ="https://www.usabilitydynamics.com/product/avalon"> Avalon theme</a> was created especially for WP-Property plugin and all its add-ons.

<strong>Dynamic Property Listings - No Coding Required!</strong><br>

* WP-Property seamlessly integrates with WordPress websites, no coding required!
* Specify search criteria and quickly sort results with a single click.
* Any custom attributes at your fingertips.
* Fully customizable dynamic filtering.

[vimeo http://vimeo.com/14280748]

<strong>Unparalleled Flexibility – List ANY Product or Service!</strong><br>

* Built for real estate, useful for everything.
* Extremely flexible interface which lets you list products of any kind.
* List vehicles, hotel reservations, farm animals and much more.
* Versatile application, limited only by your imagination

[vimeo http://www.vimeo.com/14473894]

<strong>More than a Plugin - A Real Estate Management System!</strong><br>

* Not just a mere plugin, but a whole real estate management system at your fingertips!
* Smooth operation, user friendly interface, comes with its own vast collection of property listing functionality and compatible premium features.
* Expandable, customizable!

> See the plugin on [GitHub](https://github.com/wp-property/wp-property)

= Features =
* Flexible Extendable Filter on All Properties page.
* Fields such as price, bathrooms, bedrooms, features, address, work out of the box.
* Different attributes' fields inputs are available, e.g. Text Editor, Number, Currency, File and Image Upload, URL, Date and Color Pickers, etc.
* Free [Add-ons and Themes](https://www.usabilitydynamics.com/products#category-wp-property) available.
* Flexible Search.
* Pagination and sorting works on search results.
* Property result pagination via AJAX.
* Property queries by custom attributes.
* Localized Google Maps.
* Customizable templates for different property types.
* SEO friendly URLs generated for every property, following the WordPress format.
* Customizable widgets:  Featured Properties, Property Search, Property Gallery, and Child Properties.
* Google Maps API to automatically validate physical addresses behind-the-scenes.
* Integrates with Media Library, avoiding the need for additional third-party Gallery plugins.
* Advanced image type configuration using UI.

= Widgets =
* Child Properties. Show child properties (if any) for currently displayed property
* Featured Properties. List of properties that were marked as Featured
* Latest Properties. List of the latest properties created on this site.
* Other Properties. Display a list of properties that share a parent with the currently displayed property.
* Property Attributes. Display a list of selected property attributes when loaded on a single property page.
* Property Search. Display a highly customizable property search form.
* Property Gallery. List of all images attached to the current property.
* Property Overview. Display a list of properties using flexible bunch of settings.
* Property Map. Displays property map of current or particular property.
* List Attachments. Displays attachments of current property.

= Shortcodes =
We have setup a ["Shortcode Cheatsheet" page](https://www.usabilitydynamics.com/product/wp-property/docs/wp-property-shortcode-cheatsheet) for your convenience.

* [property_overview]
* [property_search]
* [property_map]
* [property_attribute]
* [property_attributes]
* [property_meta]
* [property_terms]
* [featured_properties]
* [list_attachments]

<strong>[property_overview]</strong><br>
Property Overview Shortcode. It is used to display a list of properties.  In it's most basic form, it will return all the published properties on your site.<br>

* [property_overview pagination=on] Show all properties and enable pagination.
* [property_overview sorter=on] Will list all properties with sorting.
* [property_overview per_page=5] Show 5 properties on a page (10 properties is the default). If you use this shortcode - you don't need to use 'pagination=on'.
* [property_overview for_sale=true] or [property_overview type=building]
* [property_overview bathrooms=1,3 bedrooms=2-4] Use "," for setting specific values of attributes and "-" for ranges.
* [property_overview sorter=on sort_by=price sort_order=DESC pagination=on] Will list all properties sorted by price in DESC order (attribute "Price" should be checked as sorted in Properties->Settings->Developer page ) and paginate.

Usage of custom attributes added in the Developer tab for queries, example:<br>

* [property_overview house_color=blue,green] List all blue and green properties.
* [property_overview type=single_family_home year_built=1995-2010] List all single family homes built between 1995 and 2010.
* [property_overview type=apartment pets_allowed=yes] All apartments where "Pets Allowed" attribute is set to "yes".
* [property_overview template=detailed-overview] will load property-overview-detailed-overview.php from your theme folder.

<strong>[property_search]</strong><br>
Property Search. This shortcode renders a search form, much like the Property Search widget. In it's most basic form it will display the first 5 attributes that you have selected as being searchable in the Developer tab.

* [property_search searchable_attributes=bedrooms,bathrooms searchable_property_types=single_family_home per_page=10] A more complex example showing search options for bedrooms and bathrooms, return only Single Family Homes and limiting the search results to 10 per page.
* [property_search do_not_use_cache=true searchable_attributes=bedrooms,bathrooms] By default the search widget, and the search shortcode, cache the values used in dropdowns.  You can force the shortcode to avoid getting the values from cache and force it to generate the values on-the-fly when the page is opened.  This is generally not recommended because it slows down the page load-time, but may be useful when troubleshooting.
If you want to use address attributes in [property_search] you will have to add them using the Developer tab.  Watch screencast on adding address attributes to property_search shortcode.

<strong>[property_map]</strong><br>
Single Property Map. This shortcode displays property maps from single property pages.

<strong>[property_attribute]</strong><br>
Property Attribute. Property Attribute Shortcodes return the value of an attribute for a specific property. The current property is targeted by default. Properties other than the current one can be specified using their property ID number, as shown below.

* [property_attribute attribute=bedrooms] Get the number of bedrooms for current property.
* [property_attribute property_id=5 attribute=bathrooms] Get the number of bathrooms for a property with an ID of 5.
* [property_attribute attribute=status] Shows status for current property.
* [property_attribute attribute=map] Shows map for current property

<strong>[property_attributes]</strong><br>
Property Attributes. Renders List of Property Attributes.

* [property_attributes include=bedrooms,price] Renders only specified attributes: Bedrooms and Price
* [property_attributes exclude=bedrooms,price] Renders all attributes except of Bedrooms and Price

<strong>[property_meta]</strong><br>
Property Meta. Renders List of Property Meta fields.

* [property_meta] Renders all meta fields for current property.
* [property_meta include=school] Renders only specified meta: School

<strong>[property_terms]</strong><br>
Property Terms. Renders List of property terms for specific taxonomy.

* [property_terms taxonomy=features] Renders property terms for Features taxonomy.

<strong>[featured_properties]</strong><br>
Featured Property. This shortcode queries only those properties that have been given Featured status.

* [featured_properties type='all' stats='price'] Shows all featured properties, and display their prices.

<strong>[list_attachments]</strong><br>
List Attachments Shortcode is used to display attachments of a property, can also be used in a post. Ported over from List Attachments Shortcode plugin.  If plugin exists, the WP-Property version of shortcode is not loaded.

**Add-ons and Themes**

WP-Property recommends free [WP-Property: Terms](https://www.usabilitydynamics.com/product/wp-property-terms) Add-on which allows you to manage Taxonomies for your Properties! Just imagine that you are getting not just native WordpPress terms, but you can use terms as posts. Every term of WP-Property's taxonomy has own Single page, content, thumbnails and everything else what is available for native WordpPress Posts. It adds absolutely new level of managing your Real Estate site!

Is the current functionality still not enough to cover all your needs?
Learn more about the [WP-Property Add-ons and themes](https://www.usabilitydynamics.com/products#category-wp-property).

**Translations**

* English (UK)
* French (FR)
* Danish (DK)
* Dutch ( NL )
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
5. A Property Page

== Frequently Asked Questions ==

= My theme is not working... HELP =
Because there are so many different themes available for WP, not all of them will work flawlessly out of the box.  You can either customize your own theme, contact us for a theme customization quote or purchase one of our premium themes such as the Denali or Madison.

= Is the plugin available in different languages? =
Due to contributions from the plugin users we currently have it in Danish, German, English, Spanish, French, Hungarian, Italian, Brazilian, Russian, Slovak, Turkish translations. If you would like to translate the plugin in your own languages please email us the files when you are done so we can include them in future updates.

= Side Bar disappears =
This is a theme issue. Once again you can customize your own theme, email us and we can give you a quote or you can purchase the Denali premium theme.

= I like where this is going, but how do I get customization? =
If you submit a popular idea on UserVoice, we WILL integrate it sooner or later.  If you need something custom, and urgent, [contact us](https://www.usabilitydynamics.com/contact-us)

== Upgrade Notice ==

= 2.0.3 =
* Added compatibility with WordPress 4.3 and higher.

= 1.38 =
* Added compatibility with FEPS 2.0 premium feature.

= 1.37.3.1 =
* Fixed [property_overview] attributes settings.

= 1.37.2.1 =
* Fixed tab breaking on settings page.

= 1.35.1 =
* Security fixes

== Changelog ==

= 2.3.8 =
* Fixed sorting issue.
* Fixed warnings and notices

= 2.3.7 =
* Added checks on empty taxonomies
* Added ability to create a custom template for the search form
* Fixed issue with duplicate results on Property Overview page
* Fix issue with WPML translate attribute value string

= 2.3.5 =
* Updated MetaBox library.
* URL attributes now opens in a new tab.
* Added option to disable fancybox.

= 2.3.4 =
* Disabled legacy API key system.
* Fixed admin CSS conflicts with Comet Cache accordions.
* Fixed issues with Media tab on edit property page.
* Updated wp-tax-post-binding library.
* Added search by property id on backend and id number on edit property page.
* Added option to set up area dimension in settings/display tab.

= 2.3.3 =
* Fixed WPML compatibility issue with [property_attributes] shortcode.
* Fixed images issue in Slidehow add-on.
* Fixed compatibility issue with PHP 5.5 about asp_tags.

= 2.3.2 =
* Fixed issue when images weren't attaching to the property
* Reverted back Importer schedules filter on all properties page on backend

= 2.3.1 =
* Fixed issue with image ordering.
* Added notice to update Terms plugin if not compatible version installed.
* Updated libraries.

= 2.3.0 =
* Code cleaning, warnings, notices fixes.
* Fixed WPML compatibility issues.
* Updated Google Maps API settings and docs.
* Added pre-release updates option.
* Added default static Properties page. Removed default Property page which was generated on the fly.
* Fixed Property type was not showing in Search filters.
* Fixed Property Search issue with attributes which contain slashes.
* Fixed scroll to the top option on all properties page.
* Added Feedback form in the Settings tab.
* Added option to see who is editing the Settings tab right now.

= 2.2.0.3 =
* Fixed history pagination for [property_overview] shortcode.

= 2.2.0.2 =
* Added settings search in Settings tab.
* Updated/fixed users capabilities for Power Tools add-on.

= 2.2.0.1 =
* Compatibility with WordPress 4.7

= 2.2.0 =
* Fixed issue with Google Map functionality.
* Added options for Google API Keys (browser and server).

= 2.1.9 =
* Added Date Input range Search Input.
* Fixed Google Maps Api key issue.
* Fixed pagination on trashed properties.
* Added minutes to the Added and Updated fields on All properties page.

= 2.1.8 =
* Added default Search Input and Data Entry types for Property Attributes on first WP-Property install.
* Fixed default list of Property Types on first WP-Property install.
* Fixed issue with duplicated Currency symbol on Single Property page.

= 2.1.7 =
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

= 2.1.6 =
* Fixed the bug when page content was always replaced with property_overview shortcode if wpp_search existed in request parameters.
* Fixed multi-checkbox issue on Property Search.
* Fixed issue with odd  br in Text Editor Data Entry field.
* Fixed Single checkbox became enabled when publishing new property.
* Fixed converting apostrophe to slash in Property Types.
* Date and Time attributes now take date and time format from Settings/General tab.

= 2.1.5 =
* Added ability to set default values for property's attributes.
* Added compatibility with WPML plugin.
* Removed 'Images Upload' data entry for attribute since it duplicated 'Image Upload'.
* Changed Agent definer from ID to email in wp-property export feed.
* Fixed inherited values for non text attributes.
* Fixed values rendering for 'Image Upload', 'File Upload' attributes on Single Property page.
* Fixed 'Next' button's event in numeric pagination for property overview.
* Warnings and Notices fixes.

= 2.1.4 =
* Property Attributes widget is not shown anymore if no data found.
* Property Meta widget is not shown anymore if no data found.
* Property Term widget is not shown anymore if no data found.
* Fixed default template for [featured_properties] shortcode.
* Fixed the bug related to showing properties with specific float numbers in [property_overview] shortcode.
* Fixed javascript error which broke pagination and sorting functionality in [property_overview] shortcode.
* Fixed showing of inherited multi-checkbox values.
* Fixed encoding issues in export feed.

= 2.1.3 =
* Added ability to set numeric or slider pagination for [property_overview] shortcode and Property Overview widget.
* Added ability to set default image which will be shown if property does not have any one.
* Added 'Child Properties' table's column on 'All Properties' page, which shows the list of all children for particular property.
* Improved hooks for list table on 'All Properties' page to have more flexibility with adding custom columns and bulk actions.

= 2.1.2 =
* Added ability to sort properties by modified date for Property Overview widget.
* Added option to export properties to CSV file on Help Tab of Settings page.
* Fixed replacing of plugin's settings data with default values on updating WP-Property settings in some cases.
* Fixed showing of Multi-Checkbox values.

= 2.1.1 =
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

= 2.1.0 =
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

= 2.0.4 =
* Added filter which adds admin domain to the whitelist of hosts to redirect to, in case, admin and site domains are different.
* Added Chinese language to available address localizations.
* Fixed showing values for Multi Checkbox attributes on All Properties page ( Admin Panel ) and on Front End.
* Fixed default overview on All Properties pages. There was a potential issue when trashed properties were shown on default overview.
* Fixed warnings on Property saving process which prevent loading page.
* Fixed the bug when UsabilityDynamics Admin Notices could not be dismissed.

= 2.0.3 =
* Added ability to set Google Maps API key on WP-Property Settings page. Using of Google Maps API key increases google validation limits.
* Fixed the way of widgets initialization. Compatibility with WordPress 4.3 and higher.
* Fixed Warnings and issues with hidden Title and Checkbox columns on All Properties page for WordPress 4.3 and higher.
* Fixed Warnings which were breaking ajax request on pagination and filtering items on All Properties page for PHP 5.6.
* Fixed Warning on properties overview's default template.
* Fixed incorrect behaviour on custom 'Install Plugins' page after depended plugins ( Add-ons ) activation.

= 2.0.2 =
* Added Shortcodes UI library for providing better shortcodes UI management in future releases.
* Added ability to sort properties on Front End by modified date. Example: [property_overview sort_by=post_modified]
* Added ability to filter properties by custom attribute on 'All Properties' page ( Back End ) when it has Search Input 'Advanced Range Dropdown'.
* Fixed loading of localisation files. The bug persists in 2.0.0 and 2.0.1 versions.
* Fixed Warnings on Edit Property page when property attributes with Data Entry "Dropdown Selection" do not have any predefined value.
* Fixed Warnings on sending notification to user about created account.
* Fixed incorrect status information on "Revalidate all addresses using English localization" process in case query limit was exceeded.
* Fixed defined min and max values of 'Advanced Range Dropdown' fields on Property Search form.

= 2.0.1 =
* Fixed showing Attribute on Edit Property page which has 'Multi-Checkbox' Data Entry.
* Fixed registration of javascript files which might break logic in some cases in multiple places on back end and front end.
* Fixed Fatal Error which did occur when class 'RWMB_Field_Multiple_Values' was called too early.
* Fixed bug which broke sort order for properties in 2.0.0 version.
* Fixed typos 'YEs' in value for single checkbox attribute.
* Warnings and Notices fixes.

= 2.0.0 =
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

= 1.42.4 =
* Fixed issues with Front End Property Submission add-on.
* Fixed JavaScript issues in payment process of Front End Property Submission add-on.
* Code clean up and improvements.

= 1.42.3 =
* Added compatibility with Wordpress 4.2.

= 1.42.2 =
* Fixed 'exclude' parameter for draw_stats function. The issue also was related to showing specific property attributes in PDF Flyer.
* Fixed property object caching.
* Fixed bug on clicking 'Add to Featured' on 'All Properties' page.
* Fixed option 'Display in featured listings' on edit property page.
* Warnings and Notices fixes.

= 1.42.1 =
* Fixed property search for range input and range dropdown fields types.
* Fixed Warnings and Notices.

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
