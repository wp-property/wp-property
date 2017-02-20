### Usage

Run somewhere in init class:

```
$this->saas = new Register( 'invoice' );
```

### Debugging
Get current registration state or flush state. This will force registartion to run again if it is currently in back-off state.

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
      "rest_url": "https://usabilitydynamics-sandbox-uds-io-site-registration.c.rabbit.ci/wp-json",
      "user_id": 1,
      "message": "Doing site registration, please give me [ud_site_id] and [ud_site_public_key]."
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
To override the registartion API, set `UD_API_REGISTER_URL` constant. Example:

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