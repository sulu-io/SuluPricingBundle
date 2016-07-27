SuluPricingBundle
=================

# Configuration

The following config lists all configurable variables and their defaults.

```
sulu_pricing:
    priceformatter_digits: 2
    default_currency: 'EUR'
    item_manager_service: 'sulu_sales_core.item_manager'
```

# Run Tests

Temporarily a version needs to be set in composer.json so that sulu-sales
bundle can be installed:
```
"version": "0.1",
```

Then in terminal, run the following commands
```
composer update

Tests/app/console doctrine:schema:update --force
 
phpunit
```

**Afterwards don't forget to remove the version ;)**
