### Install Add-ons

##### Install composer autoload with existing requirements:

```
COMPOSER_CACHE_DIR=/dev/null composer install --no-dev
```

##### Install requirement ( dependency ):

More details: https://getcomposer.org/doc/03-cli.md#require

```
composer require wp-property/wp-property-agents:dev-feature
```

##### Update the ONLY requirement
More details: https://getcomposer.org/doc/03-cli.md#update

```
composer update wp-property/wp-property-agents
```

