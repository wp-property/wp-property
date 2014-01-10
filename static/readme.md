Resource (JavaScript & CSS) Loading
===================================

The new ud.loader.js library adds an early ud.loader() function that allows JS and CSS files to be loaded via the browser. The UD library expands LazyLoad by adding
a conflict check for JavaScript files.

* ud.load.js({ 'ko': '//cdnjs.cloudflare.com/ajax/libs/knockout/2.1.0/knockout-min.js' }, function() {} );
* ud.load.css( '//cdn.usabilitydynamics.com/ud.select2.css' );

In the JavaScript example the 'ko' checks for a variable by same name in the global scope, if it is registered than the file will not be loaded.

KnockoutJS Usage and Extensions
===============================

KOJS is used for almost all sections on settings page as View. Uses global JS model as data-provider.
Model can be extended using hook 'view_model::wpp_settings::create' for ud.add_filter function.

* ud.add_filter( 'view_model::wpp_settings::create', function( model ) {
*   //do something with 'model' and return it in the end
*   return model;
* });

Hook 'view_model::wpp_settings::init' allows yout to run some code after ko.applyBindings() ran.

API Access
==========

API can be viewed using the API Explorer. It is useful for testing specific functionality and data models, and is essential for UD Services to interact with a client site.

* Get XML Schedule. /API_KEY/xmli/get_schedule?ID=29463
* Get XML Schedule Conditionals. /API_KEY/xmli/get_conditionals=29463


JavaScript Object Structure
===========================

JavaScript files build upon each other with the most root-level script being UD Global (ud.global.js). UD Global is extended by WPP Global (wpp.global.js).
For administrative pages UD Admin and WPP Admin are loaded, which both extend UD Global and WPP Global.
Although all functionality is extended into child objects by default, in this case ending up in the *wpp* object, scope-specific properties within the global *wpp* object may be used.
For instance, wpp.admin may be created to contain wpp.admin.notice which may conflict with a global function.

### Example: WPP Admin

The WPP Admin load order is as follows:

1. UD Global (ud.global.js) is loaded, creating the global *ud* object.
2. WPP Global (wpp.global.js) is loaded, cloning the *ud* object into the *wpp* object, thus inheriting all functionality.
3. UD Admin (ud.admin.js) is loaded, which extends the *ud* object, adding all ud.admin functionality to it.
4. WPP Admin (wpp.admin.js) is loaded, which extends the *wpp* object.

Inheritance: UD Global > UD Admin > WPP Global > WPP Admin > WPP Footer Model

Knockout / SaaS
===============

The Knockout-Live plugin is bundled into client.min.js, which has been slightly modified to fit our needs.
Adding a .live() annex to an obvervable will make it automatically update the SaaS connection.
Updates use the observable::update action. The plugin is initialized by calling ko.utils.apply_socket( socket ), which must be called before binding are applied.
The ko.utils.apply_socket() function accepts two arguments - the socket object and a callback function. If no socket is passed, the function will look for ud.saas.socket, and will use it
if it is connected.

Socket Connection to SaaS
=========================

New Connectection: wpp.saas.connect( 'http://saas.usabilitydynamics.com/' );
To monitor received socket messages, a console function can be bound to io::data: jQuery( document ).bind( 'io::data', function( e, data ) { console.info( 'io::data', data ); } );
Wheen a screen on SaaS, client.js executes a trigger: ud::saas::screen_set::{SCREEN_ID} which means that SaaS screen is set and authenticated.

Assets ( UD_Asset compiler )
======================
UD_Asset uses rewrite_rules, so be sure that you flushed rules on Settings/Permalinks page ( or if you use WP-Property 2.0 - on Property Settings page too ).

Usage:

1. Create new UD_Asset object.

It should be initialized before 'init', because it uses specific hooks, like 'rewrite_rules_array' and 'parse_request'. And object should be global.

* global $asset_object;
* 
* $asset_object = new UD_Asset( array(
*    'monitor' => true, // If true - it recompiles file if 'input' was changed or file doesn't exist, if false - it compiles file only if it doesn't exist.
*    'prefix' => 'wpp', // Prefix which will be used in dynamic asset's permalink. Default is 'ud'
*    'pathes' => array(
*        'lessc' => WPP_Path . 'third-party/lessphp/lessc.inc.php',
*        'jsmin' => WPP_Path . 'third-party/jsmin.php',
*    )
* ) );

2. Add the list of all assets.

2.1 Set assets:

* $assets = array( 
*
*   'unique_asset_key' => array(
*        'file' => '{full_dir_path_to_file}', // required. output file path.
*        'type' => 'css', // required. Available type: 'css', 'js'
*        'scope' => 'default' // optional. Default is 'default'. Available scopes: 'default', 'cdn'
*        'url' => '{url}' // optional. Url is generated automatically by UD_Asset. Set it only if you ned to use custom url
*        'compile_options' => '{array_of_params}' // required in the most cases ( e.g. asset must be compiled ). this setting must contains the list of options needed for compiler ( compile_less | compile_js ),
*    ),
*
*    ...
*
* )

2.2 Add assets list to UD_Asset object.

* global $asset_object;
* 
* $asset_object->set_assets( $assets );

3. Enqueue asset.

On enqueue neccessary asset ( wp_enqueue_script (_style)  ), get_dynamic_asset_url() must be used.

* global $asset_object;
* 
* $asset_object->get_dymaic_asset_url( '{unique_asset_key}' );

4. Be sure that all assets will be recompiled ( updated ) on specific actions, like plugin activation, plugin/theme updates, etc. Call the following method on specific hooks for it:

* global $asset_object;
*
* $asset_object->recompile_all_assets();


JavaScript Utilities
====================

ud.type_fix( object, args )
---------------------------
Converts a variable, or an object's values recursively, into the exepcted data type. String "true" and "false" get converted to boolean, numbers get converted to float, etc.
The second argument can specify extra settings, such as if empty values should be converted to "null";


Multisite Support ( Enterprise Edition )
========================================

Multisite is handled by WPP_MU class ( core/class_mu.php ).
Licenses Control menu is added to network menu when Multisite is available and can be only managed by super admin. It allows to set Premium Feature licenses to blogs.
Premium Feture will not be loaded on blogs which don't have license.

Control of licenses is implemented by:
1. Client side: WPP_MU::site_has_license( 'premium_feature_slug' ).
2. SaaS: every view_model initialization emit bloginfo data to SaaS, which includes current blog id and amount of active blogs on the client's site.
3. Interfaces: PF interfaces will not be loaded if blog doesn't has license.