SuluPricingBundle
=================

# Configuration

The following config lists all configurable variables and their defaults.

```
sulu_pricing:
    default_currency: 'EUR'
    default_locale: 'en'
    item_manager_service: 'sulu_sales_core.item_manager'
    priceformatter_digits: 2
```

# Run Tests

Temporarily a version needs to be set in composer.json so that sulu-sales
bundle can be installed:

```
"version": "0.4",
```

Then in terminal, run the following commands

```
composer update

Tests/app/console doctrine:schema:update --force
 
phpunit
```

**Afterwards don't forget to remove the version ;)**
