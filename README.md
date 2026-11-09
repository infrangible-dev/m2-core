# Infrangible Core

Library module with the helpers, base classes and source models that the other Infrangible Magento 2 modules are built
on. It has no frontend or admin functions of its own, apart from four console commands for configuration values.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.6 or newer
- PHP 8.2 or newer

## Installation

Usually the module is installed as a dependency of another Infrangible module. To install it on its own:

```bash
composer require infrangible/m2-core
bin/magento module:enable Infrangible_Core
bin/magento setup:upgrade
```

In production mode, also run `bin/magento setup:di:compile`.

### Optional Magento modules

Some helpers work with Magento modules that shops often remove or disable. These modules are only suggested in
`composer.json`, and their classes are only loaded when a method that needs them is called, so the rest of the module
works without them:

| Magento module                                   | Used by                                                       |
|--------------------------------------------------|---------------------------------------------------------------|
| Magento_Review                                   | `Helper\Review`                                               |
| Magento_CheckoutAgreements                       | `Helper\Agreement`                                            |
| Magento_CatalogRule                              | `Model\Config\Source\Operator`                                |
| Magento_ConfigurableProduct                      | `Helper\Product::getConfigurableAttributeCollection()`        |
| Magento_Bundle                                   | prices of bundle products in `Helper\Export`                  |
| Magento_Inventory, Magento_InventoryCatalogApi   | `Helper\Source` (returns `null` without these modules)        |

Calling a method that needs a missing module throws a `RuntimeException`.

## Console commands

```bash
# encrypt or decrypt a value with the encryption key of the installation
bin/magento config:encrypt --value "my secret"
bin/magento config:decrypt --value "0:3:..."

# export configuration values to a JSON file, and import them again
bin/magento config:export-json --path general/locale --file config.json
bin/magento config:import-json --file config.json
```

The export contains the values of the path (a config path or a part of it) in all scopes.

## For module developers

### Helpers

The classes in `Infrangible\Core\Helper` are injected like any other class. The most used ones:

| Helper                     | Purpose                                                                                |
|----------------------------|----------------------------------------------------------------------------------------|
| `Stores`                   | stores, websites, configuration values, locale, dates and numbers                      |
| `Registry`                 | wrapper of the Magento registry with graceful and overwriting registration             |
| `Database`                 | queries, inserts, updates and deletes on the default or a given connection             |
| `Instances`                | creating objects by class name, also of classes of optional modules (see below)        |
| `Attribute`, `EntityType`  | EAV attributes, attribute sets, options and values                                     |
| `Product`, `Category`      | loading, saving and querying products and categories                                   |
| `Url`, `UrlRewrite`, `Seo` | URLs, URL rewrites and URL suffixes                                                    |
| `Cms`, `Block`             | CMS pages and blocks, including import from XML files, rendering of layout blocks      |
| `Export`                   | product data for feeds and exports                                                     |
| `Setup`                    | data patches: attributes, attribute sets and groups                                    |

Further helpers cover addresses, carts, quotes, orders, invoices, shipments, credit memos, payments, carriers,
customers, reviews, checkout agreements, stock, inventory sources, tax classes, cache, indexers, files, CSV and XML.

### Optional dependencies

Classes of modules that a shop may not have must not be injected in constructors: setup:di:compile and the object
manager fail when such a class is missing. Create them when they are needed instead, and list the module in `suggest`
of `composer.json`:

```php
$review = $this->instanceHelper->createInstance(\Magento\Review\Model\Review::class);
$resource = $this->instanceHelper->getSharedInstance(\Magento\Review\Model\ResourceModel\Review::class);
```

Both methods return an object of the given class and throw a `RuntimeException` if the class does not exist.

### Base classes

| Class                                            | Purpose                                                                  |
|--------------------------------------------------|--------------------------------------------------------------------------|
| `Controller\Adminhtml\Json`                      | admin controller that answers Ajax requests with a JSON object           |
| `Controller\Adminhtml\Ajax`                      | `Json` with the response values `success` and `message`                  |
| `Console\Command\Command`, `Console\Command\Script` | console command whose work is done by a script class in an area of Magento |
| `Block\Adminhtml\System\Config\Form\AjaxButton`  | button in the configuration that calls an admin controller with Ajax      |

Admin controllers based on `Json` or `Ajax` are admin actions (`Magento\Backend\App\Action`): they get a
`Magento\Backend\App\Action\Context`, the user has to be logged in, and access is checked with the ACL resource in the
constant `ADMIN_RESOURCE`, which each controller should set.

### Source models

`Infrangible\Core\Model\Config\Source` contains source models for configuration fields: attributes of products,
categories, customers and addresses (by id or code), attribute sets, product types, CMS pages and blocks, categories,
regions, order states and statuses, active payment methods, active carriers and shipping methods, and the operators of
catalog price rules.

## Development

The module comes with an [Anodosys](https://github.com/anodosys/app) configuration (`ads.json`) for a local Magento
installation with the module mounted at `app/code/Infrangible/Core`. Start it with:

```bash
ads rise
```

Run the checks in the `cli` container:

```bash
# coding standard (Magento2 and PHP 8.2 compatibility)
cd /var/www/module && /var/www/magento/vendor/bin/phpcs

# static analysis (PHPStan level 9)
cd /var/www/magento && vendor/bin/phpstan analyse -c /var/www/module/phpstan.neon

# unit tests
cd /var/www/magento && vendor/bin/phpunit -c /var/www/module/phpunit.xml
```

PHPStan and PHPUnit have to be started in the Magento directory, because the `vendor` directory of the module is the
mounted `vendor` directory of Magento.

### Continuous integration and releases

Every push and pull request runs the checks above on GitHub Actions with Magento 2.4.6 (PHP 8.2), 2.4.7 (PHP 8.3),
2.4.8 (PHP 8.4) and 2.4.9 (PHP 8.5), using the shared workflow of
[infrangible-dev/workflows](https://github.com/infrangible-dev/workflows). Outside the development environment, the
PHPStan and PHPUnit configuration find Magento through the environment variable `MAGENTO_ROOT`.

To release a new version, raise `version` in `composer.json`, describe the changes in `CHANGELOG.md` and push to
`main`. When all checks pass, the workflow creates the tag and the GitHub release, and Packagist picks up the new
version. Tags are only created by the workflow.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

Infrangible Core is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
