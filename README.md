# DmLab_DisableLog

> Per-source admin toggles to switch off Magento's high-frequency core logging.

[![CI](https://github.com/dmlab-work/module-disable-log/actions/workflows/ci.yml/badge.svg)](https://github.com/dmlab-work/module-disable-log/actions/workflows/ci.yml) [![codecov](https://codecov.io/gh/dmlab-work/module-disable-log/graph/badge.svg)](https://codecov.io/gh/dmlab-work/module-disable-log) [![Packagist Version](https://img.shields.io/packagist/v/dmlab/module-disable-log)](https://packagist.org/packages/dmlab/module-disable-log) [![Downloads](https://img.shields.io/packagist/dt/dmlab/module-disable-log)](https://packagist.org/packages/dmlab/module-disable-log) [![PHP](https://img.shields.io/packagist/dependency-v/dmlab/module-disable-log/php)](https://packagist.org/packages/dmlab/module-disable-log) [![License](https://img.shields.io/packagist/l/dmlab/module-disable-log)](LICENSE) ![Magento](https://img.shields.io/badge/Magento-2.4-orange)

Unlike Yireo DisableLog2, it also switches off search-term logging (`search_query`), next to customer logins and online visitors.
Magento writes to the database on ordinary page loads for all three, and those tables keep growing.
Switch each source off from the admin; nothing changes until you do.
With visitor tracking off, a password change or reset still logs out the customer's other sessions.

![Disable Log configuration](.github/images/config.png)

## Install
```bash
composer require dmlab/module-disable-log
bin/magento module:enable DmLab_DisableLog
bin/magento setup:upgrade
```

## Requirements
Magento 2.4.7-p9+ · PHP 8.2–8.5

2.4.7-p9 is the first 2.4.7 release that keeps the login time in the customer session, which the session validator needs.

## Configuration

**Stores → Configuration → DMLab → Disable Log** (default scope, ACL `DmLab_DisableLog::config`).

| Toggle | Config path | Stops |
|---|---|---|
| Disable Customer Login/Logout Log | `dmlab_disable_log/general/disable_customer_log` | `customer_log` writes (last login/logout times) |
| Disable Online Visitor Tracking | `dmlab_disable_log/general/disable_visitor_log` | `customer_visitor` writes |
| Disable Search Query Log | `dmlab_disable_log/general/disable_search_log` | `search_query` writes (new terms, popularity, result counts) |

All toggles default to **No**, are independent and reversible, and apply to every area (storefront, admin, API):

```bash
bin/magento config:set dmlab_disable_log/general/disable_visitor_log 1
```

## Side effects

- **Customer log** — "Last Logged In" and online status in the admin customer view stop updating.
- **Visitor tracking** — **Customers → Now Online** stays empty; guest "Add to Compare" stops working;
  guest recently viewed/compared rows without a visitor or customer are no longer written to
  `report_viewed_product_index` / `report_compared_product_index`; the admin customer view shows
  **Online** until logout.
- **Search log** — **Marketing → SEO & Search → Search Terms**, **Reports → Search Terms** and popular
  search terms stop updating.

## Architecture

`around` plugins on the core writers return early when their toggle is on; no core class is patched or replaced:

- `Magento\Customer\Model\Logger::log()`
- `Magento\Customer\Model\ResourceModel\Visitor::save()`
- `Magento\Reports\Model\ResourceModel\Product\Index\AbstractIndex::save()` (rows with no visitor/customer)
- `Magento\Search\Model\Query::saveIncrementalPopularity()` and `saveNumResults()`

A customer session validator applies the password-change session cutoff (against the login time kept in the
session) to sessions without a `customer_visitor` row, also after visitor tracking is re-enabled.

Tests: `vendor/bin/phpunit -c Test/Unit/phpunit.xml`.

## License

[OSL-3.0](LICENSE)
