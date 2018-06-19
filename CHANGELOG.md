CHANGELOG for Sulu Pricing Bundle
=================================

* 0.5.3 (2018-06-14)
    * BUGFIX Fixed price calculation for variants by using information from the parent.

* 0.5.2 (2016-12-14)
    * FEATURE Implemented price caluclation of variants parent product addon relation.

* 0.5.1 (2016-12-06)
    * BUGFIX  Fixed price calculation of addon items.

* 0.5.0 (2016-09-06)
    * FEATURE Adapted GroupedItemsPriceCalculator: Calculates now all prices
              (totalPrice, totalNetPrice, totalRecurringPrice, totalRecurringNetPrice, shippingCosts, taxes)
    * FEATURE Refactored PriceCalculationManager: Moved calculation to ItemPriceCalculator
    * FEATURE Moved manager to own folder

* 0.4.2 (2016-07-28)
    * BUGFIX  Fixed calculation of addon items

* 0.4.1 (2016-07-27)
    * BUGFIX  Fixed calculation of addon items
    * FEATURE Added config for defining default currency for price calculation

* 0.4.0 (2016-07-14)
    * FEATURE Added handling of recurring-prices
    * FEATURE Added handling of gross-prices
