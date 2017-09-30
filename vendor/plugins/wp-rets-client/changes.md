#### 0.3.2
* Added `insert_media` API/RPC endpoints and improved `insert_media` utility method to wipe all old attachments.
* Allowing manual override of `ud_site_id` and `ud_site_secret_token` via constants.
* Improved support for invoking `get_schedule_listings` via wp-rets or xmlrpc.
* Added option to `skipTermCounting` when doing `wpp.editProperty`
* Changed `wpp.scheduleStats` to returns counts of only published, private and future listings. 
* Added `wpp.trashProperty` method for quicker removal.

#### 0.3.1
* Added [/wp-json/wp-rets-client/v1/systemPing] and [/wp-json/wp-rets-client/v1/getProperty] endpoints.
* Added [/wp-json/wp-rets-client/v1/scheduleListings] endpoint and [wpp.scheduleListings] xml-rpc handler.
* Added [/wp-json/wp-rets-client/v1/scheduleStats] endpoint and [wpp.scheduleStats] xml-rpc handler.
* Added [/wp-json/wp-rets-client/v1/cleanup/status] and [/wp-json/wp-rets-client/v1/cleanup/process] endpoints.

#### 0.3.0
* Updating site registration to use [usabilitydynamics/lib-wp-saas-util].

#### 0.2.1
* Added changes.md
