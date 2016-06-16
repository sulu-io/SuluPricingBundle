SuluPricingBundle
=====================

# Run Tests

You temporarily need to set a verion in composer.json so that sulu-sales
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
