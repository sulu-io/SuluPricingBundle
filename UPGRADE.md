# UPGRADE

## dev-develop

Added config variable `default_currency` and removed usage of parameter
'website_locale'.

You now need to add the following configuration to your `app/config/config.yml`
(otherwise the default is 'en'):

```
sulu_pricing:
    default_locale: 'en'
```

