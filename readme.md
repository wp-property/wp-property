### WP-Property Plugin

WP-Property is the leading WordPress plugin for creating and managing highly customizable real estate, property management, and completely custom listing showcase websites. Packed full of features, it gives you possibilities that no other real estate system for WordPress does. Try it out, you will be impressed.

***
[![Issues - Bug](https://badge.waffle.io/wp-property/wp-property.png?label=bug&title=Bugs)](http://waffle.io/wp-property/wp-property)
[![Issues - Backlog](https://badge.waffle.io/wp-property/wp-property.png?label=backlog&title=Backlog)](http://waffle.io/wp-property/wp-property/)
[![Issues - Active](https://badge.waffle.io/wp-property/wp-property.png?label=in progress&title=Active)](http://waffle.io/wp-property/wp-property/)
***
[![Dependency Status](https://gemnasium.com/wp-property/wp-property.svg)](https://gemnasium.com/wp-property/wp-property)
[![Scrutinizer Quality](http://img.shields.io/scrutinizer/g/wp-property/wp-property.svg)](https://scrutinizer-ci.com/g/wp-property/wp-property)
[![Scrutinizer Coverage](http://img.shields.io/scrutinizer/coverage/g/wp-property/wp-property.svg)](https://scrutinizer-ci.com/g/wp-property/wp-property)
[![CircleCI](https://circleci.com/gh/wp-property/wp-property.png)](https://circleci.com/gh/wp-property/wp-property)
***

> Look the plugin on [WordPress.org](https://wordpress.org/plugins/wp-property/)

Although WP-Property is the highest rated and most downloaded WordPress real estate plugin, it can handle so much more than real estate. Showcase any kind of entity you want, from livestock, golf carts, to properties and products, experiencing unparalleled ease of use and flexibility on the way.

> Do you want to see the plugin in action? Just proceed to our [Madison](://madison.ci.usabilitydynamics.com/) and [Denali](http://denali.ci.usabilitydynamics.com/) Demo sites.

#### Dynamic Property Listings - No Coding Required!
* WP-Property seamlessly integrates with WordPress websites, no coding required!
* Specify search criteria and quickly sort results with a single click.
* Any custom attributes at your fingertips.
* Fully customizable dynamic filtering.

#### Unparalleled Flexibility – List ANY Product or Service!
* Built for real estate, useful for everything.
* Extremely flexible interface which lets you list products of any kind.
* List vehicles, hotel reservations, farm animals and much more.
* Versatile application, limited only by your imagination

#### More than a Plugin - A Real Estate Management System!
* Not just a mere plugin, but a whole real estate management system at your fingertips!
* Smooth operation, user friendly interface, comes with its own vast collection of property listing functionality and compatible premium features.
* Expandable, customizable and fully supported by us!

#### Features
* Flexible Extendable Filter on All Properties page.
* Fields such as price, bathrooms, bedrooms, features, address, work out of the box.
* Any amount of custom attributes (fields) and property types.
* Different attributes' fields inputs are available, e.g. Text Editor, Number, Currency, File and Image Upload, URL, Date and Color Pickers, etc.
* Free and Paid [Add-ons and Themes](https://www.usabilitydynamics.com/products#category-wp-property) available.
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
* Out of the box support for two property types, Building and Floorplan. More can be added via WP-Property API.
* Property types follow a hierarchical format, having the ability of inheriting settings - i.e. buildings (or communities) will automatically calculate the price range of all floor-plans below them.
* Free!

#### Widgets
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

#### Shortcodes
We have setup a ["Shortcode Cheatsheet" page](https://www.usabilitydynamics.com/tutorials/wp-property-help/wp-property-shortcode-cheat-sheet/) for your convenience.

* [property_overview]
* [property_search]
* [property_map]
* [property_attribute]
* [featured_properties]
* [list_attachments]

##### [property_overview]
Property Overview Shortcode. It is used to display a list of properties.  In it's most basic form, it will return all the published properties on your site.
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

##### [property_search]
Property Search. This shortcode renders a search form, much like the Property Search widget. In it's most basic form it will display the first 5 attributes that you have selected as being searchable in the Developer tab.
* [property_search searchable_attributes=bedrooms,bathrooms searchable_property_types=single_family_home per_page=10] A more complex example showing search options for bedrooms and bathrooms, return only Single Family Homes and limiting the search results to 10 per page.
* [property_search do_not_use_cache=true searchable_attributes=bedrooms,bathrooms] By default the search widget, and the search shortcode, cache the values used in dropdowns.  You can force the shortcode to avoid getting the values from cache and force it to generate the values on-the-fly when the page is opened.  This is generally not recommended because it slows down the page load-time, but may be useful when troubleshooting.
If you want to use address attributes in [property_search] you will have to add them using the Developer tab.  Watch screencast on adding address attributes to property_search shortcode.

##### [property_map]
Single Property Map. This shortcode displays property maps from single property pages.

##### [property_attribute]
Property Attribute. Property Attribute Shortcodes return the value of an attribute for a specific property. The current property is targeted by default. Properties other than the current one can be specified using their property ID number, as shown below.
* [property_attribute attribute=bedrooms] Get the number of bedrooms for current property.
* [property_attribute property_id=5 attribute=bathrooms] Get the number of bathrooms for a property with an ID of 5.
* [property_attribute attribute=status] Shows status for current property.
* [property_attribute attribute=map] Shows map for current property

##### [property_attributes]
Property Attributes. Renders List of Property Attributes.
* [property_attributes include=bedrooms,price] Renders only specified attributes: Bedrooms and Price
* [property_attributes exclude=bedrooms,price] Renders all attributes except of Bedrooms and Price

##### [property_meta]
Property Meta. Renders List of Property Meta fields.
* [property_meta] Renders all meta fields for current property.
* [property_meta include=school] Renders only specified meta: School

##### [property_terms]
Property Terms. Renders List of property terms for specific taxonomy.

##### [featured_properties]
Featured Property. This shortcode queries only those properties that have been given Featured status.
* [featured_properties type='all' stats='price'] Shows all featured properties, and display their prices.

##### [list_attachments]
List Attachments Shortcode is used to display attachments of a property, can also be used in a post. Ported over from List Attachments Shortcode plugin.  If plugin exists, the WP-Property version of shortcode is not loaded.

#### Add-ons and Themes
WP-Property recommends free [WP-Property: Terms](https://www.usabilitydynamics.com/product/wp-property-terms) Add-on which allows you to manage Taxonomies for your Properties! Just imagine that you are getting not just native WordpPress terms, but you can use terms as posts. Every term of WP-Property's taxonomy has own Single page, content, thumbnails and everything else what is available for native WordpPress Posts. It adds absolutely new level of managing your Real Estate site!

Is the current functionality still not enough to cover all your needs?
Learn more about the [WP-Property Add-ons and themes](https://www.usabilitydynamics.com/products#category-wp-property).

##### Translations
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

##### Installation
1. Download and activate the plugin through the 'Plugins' menu in WordPress.
2. Visit Properties -> Settings page and set "Property Page" to one of your pages. This will be used in the URL to show all your single property listings.  For example, if you have a page called "Apartments", and you select it, your property URLs will be built like this: http://yoursite.com/apartments/property-name-here
3. Check "Automatically overwrite this page's content with [property_overview]" or copy and paste [property_overview] into the body of your main property page.
4. Visit Appearance -> Widgets and set up the widgets you want to show on your different property type pages.
