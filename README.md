SuluPricingBundle
=====================

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
