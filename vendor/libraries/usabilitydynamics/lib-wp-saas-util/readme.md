### Usage

To register Site/Product:

```php
use UsabilityDynamics\SAAS_UTIL\Register;

// Stripe Product ID. Required.
$product_id = "prod_AB8hYwVjwdfiSJ";

// Any custom meta. Optional.
$meta = array(
 "name" => "WP-Property",
 "slug" => "wp-property",
 "version" => "2.2.0.1"
);

Register::product( $product_id, $meta );
```

### API Methods

The library also contains the set of API methods.
( Note, methods are getting/setting data ONLY for particular Site/Blog )

#### Subscriptions

To Get all available Subscriptions Plans for particular Product

```php
/**
 * @param string $product_id. Product ID
 * @return array | WP_Error
 */
Register:get_subscriptions( $product_id );
```

To Get all current active Subscriptions for particular Product

```php
/**
 * @param string $product_id Product ID
 * @return array | WP_Error
 */
Register:get_current_subscriptions( $product_id );
```

To add Subscription for particular Product

```php
/**
 * @param string $product_id. Product ID
 * @param string $subscription_id. Subscription ID
 * @return array | WP_Error
 */
Register:add_subscription( $product_id, $subscription_id );
```

To remove Subscription for particular Product.
( Note, if no subscriptions left, default one will be set! )

```php
/**
 * @param string $product_id Product ID
 * @param string $subscription Subscription ID
 * @return array | WP_Error
 */
Register:delete_subscription( $product_id, $subscription );
```

#### Billing

To return Billing Information for particular Product

```php
/**
 * @param string $product_id Product ID
 * @return array | WP_Error
 */
Register:get_billing( $product_id );
```

To add/update Billing Information

```php
/**
 * @param string $product_id
 * @param array $card
 * @return array | WP_Error
 */
Register:update_billing( $product_id, array(
  // Required
  "number" => "1111111111111111",
  "exp_month" => 12,
  "exp_year" => 19,
  // Optional
  "address_city" => "New-York",
  "address_country" => "",
  "address_line1" => "",
  "address_line2" => "",
  "address_state" => "NY",
  "address_zip" => ""
) );
```

To remove Billing Information.
( Note, it resets all subscriptions to free ones )

```php
/**
 * @param string $product_id
 * @return array | WP_Error
 */
Register:delete_billing( $product_id );
```

### Debugging

#### Site registration

Get current registration state or flush state. This will force registration to run again if it is currently in back-off state.

```
wp transient get ud_registration_state
```

When registration attempt is ran, we record the results of last run like so:
```
{
  "registration-backoff": true,
  "time": 1487593750,
  "request": {
    "url": "https://usabilitydynamics-node-product-api-staging.c.rabbit.ci/property/register/v1",
    "body": {
      "timestamp": 1487593745,
      "host": "usabilitydynamics-sandbox-uds-io-site-registration.c.rabbit.ci",
      "ud_site_secret_token": "6297079be7a51cfc3921ea6be05c6fff",
      "ud_site_public_key": false,
      "ud_site_id": false,
      "db_hash": "d3e39b81bcfeeb8f865d734c5b4ac6da-1daf5c23010c8917508c461e9ed0fd0f",
      "deployment_hash": "d30cd3cf49dbb705a9f5e9b5689a1400-d3e39b81bcfeeb8f865d734c5b4ac6da-1daf5c23010c8917508c461e9ed0fd0f",
      "home_url": "https://usabilitydynamics-sandbox-uds-io-site-registration.c.rabbit.ci",
      "xmlrpc_url": "https://usabilitydynamics-sandbox-uds-io-site-registration.c.rabbit.ci/xmlrpc.php",
      "rest_url": "https://usabilitydynamics-sandbox-uds-io-site-registration.c.rabbit.ci/wp-json"
    }
  },
  "response": {
    "ud_site_id": "150a364d-7a2a-4b8d-9b92-f63d66aaf7cd",
    "ud_site_public_key": "2e5ccc11-9917-46e5-8db0-c1117aab52e2",
    "ud_site_secret_token": "6297079be7a51cfc3921ea6be05c6fff"
  },
  "responseStatus": 200
}
```

To flush the state and trigger another registration on next load:
```
wp transient delete ud_registration_state
```

#### API URL
To override the registration API, set `UD_API_REGISTER_URL` constant. Example:

```
define( 'UD_API_REGISTER_URL', 'https://usabilitydynamics-node-product-api-staging.c.rabbit.ci/' );
```

#### Get Tokens:
Use wp-cli to get tokens:

```
wp option get ud_site_id
wp option get ud_site_public_key
wp option get ud_site_secret_token
```