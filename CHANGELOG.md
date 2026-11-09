# Changelog

## 5.0.0

### Security

- The admin controllers based on `Infrangible\Core\Controller\Adminhtml\Json` and `Ajax` extended the frontend action
  class, so Magento did not check the admin login and the ACL for them. They are now admin actions
  (`Magento\Backend\App\Action`).

### Breaking changes

- Requires Magento 2.4.6 or newer and PHP 8.2 or newer. Magento 2.2 to 2.4.5 and PHP 7.1 to 8.1 are no longer
  supported; use version 4.x for these.
- Controllers based on `Json` or `Ajax` have to get a `Magento\Backend\App\Action\Context` instead of a
  `Magento\Framework\App\Action\Context`, and should set the ACL resource in the constant `ADMIN_RESOURCE` (default:
  `Magento_Backend::admin`, every logged-in admin user).
- All class properties have native types. Classes that extend classes of this module and redeclare their properties
  have to declare the same types.
- Magento_Review, Magento_CheckoutAgreements, Magento_CatalogRule, Magento_ConfigurableProduct, Magento_Bundle and
  Magento_Inventory are optional (`suggest`). Their classes are no longer injected, which changes the constructors of
  `Helper\Review`, `Helper\Agreement`, `Helper\Product` and `Model\Config\Source\Operator`.
- Further constructor changes: `Helper\Csv` and `Helper\Files` get the Magento file driver, `Helper\Config` the Magento
  file driver instead of `FeWeDev\Base\Files`, `Helper\Carrier` and `Model\Config\Source\Payment\ActiveMethods`
  `FeWeDev\Base\Variables`, `Console\Command\Execution` the area list and the object manager config loader;
  `Model\Config\Source\Carrier\Active` no longer gets the store helper.
- Requires `fewedev/base` 2.4.
- `Helper\Source::getSourceItemFactory()`, `getSourceItemResourceFactory()` and `getSourceItemCollectionFactory()` are
  removed: Magento does not generate factories of optional modules during compilation. Use `newSourceItem()`,
  `saveSourceItem()` and `getSourceItemCollection()` instead.
- Methods throw specific exceptions instead of `Exception`, for example `CouldNotSaveException` and
  `CouldNotDeleteException` in `Helper\Database`, `NoSuchEntityException` in `Helper\Attribute`,
  `FileSystemException` and `InvalidArgumentException` in `Helper\Config` and `Helper\Csv`. They extend `Exception`,
  so existing `catch (Exception)` blocks still work.
- Methods have native parameter and return types and complete PHPDoc, including array types. Subclasses that
  override them have to declare compatible types.
- `Helper\Product::getParentIds()` with `$maintainAssociation` returns the parent ids by child id, like
  `getChildIds()`, instead of a list of `1`.

### Fixed

- `Helper\Category::getEntityIds()` joined the wrong tables and ordered by columns that do not exist.
- `Helper\Category::getChildEntityIds()` returned the cached result of another store.
- `Helper\Config`: exported configuration could not be imported again (scopes `website`/`store` instead of
  `websites`/`stores`), and numeric field keys failed on import.
- `Helper\Export`: store values of gallery images were joined on the wrong column, the stock filter of simple products
  used the wrong table, the bestseller rating was cached for all stores, missing children were added as `null`.
- `Helper\Csv` did not skip blank lines and failed on rows with more columns than the header.
- `Helper\Product`: `getTypes()` always failed, the tier prices and MSRP of configurable products were taken from the
  parent instead of the simple product, `getParentIds()` and `getWebsiteIds()` ran the query on the default connection
  instead of the given one.
- `Helper\Attribute`: the cache of attribute values did not work, and adding an option did not update it.
- `Helper\Source` looked up products by id instead of SKU and failed in production mode, because it used generated
  factories by name.
- `Helper\Stores`: `getLocale()` always failed, `import()` and `export()` failed with store ids from Magento,
  `removeConfigValue()` did not quote the path.
- `Helper\Seo`, `Database::isQueryLogging()`: properties were read before they were set.
- `Helper\Carrier::getActiveCarriers()` read the carriers of all stores from the configuration of the current store.
- `Helper\Cms`: the import methods returned XML elements instead of identifiers.
- `Helper\Cart::addItemCustomOptions()` failed for options without value.
- `Helper\Setup::addAttributeToSetAndGroup()` always returned `false`.
- The source models of category attributes (`Attribute\Category`, `Attribute\CategoryAttributeCode`) failed with a
  `TypeError`.
- `Model\Config\Source\Payment\ActiveMethods` added the label of a group to the labels of all following groups.
- `Model\Config\Source\Carrier\Method\Active` labelled all methods of a carrier without name with the carrier code.
- `Block\Adminhtml\System\Config\Form\AjaxButton` failed without `button_label` or `button_url`, and the button
  template did not escape its output.
- The help texts of `config:decrypt --key` and `config:export-json --file` described the wrong command.

### Added

- `Helper\Instances::createInstance()` and `getSharedInstance()` create objects of classes of optional modules,
  typed for static analysis.
- Unit tests for the helpers with logic of their own (instances, registry, CSV, configuration import and export,
  carriers), the JSON responses and access checks of the Ajax admin controllers, the encryptor and the source models
  of payment methods and shipping methods.
- PHPCS (Magento2 coding standard) and PHPStan (level 9) configuration.
- GitHub Actions pipeline that runs the checks with Magento 2.4.6 to 2.4.9 and releases new versions when they pass.
- Documentation in the README.
