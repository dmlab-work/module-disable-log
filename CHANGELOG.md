# Changelog

## 0.0.2
- Requires Magento 2.4.7-p9+ (`magento/module-customer ^103.0.7-p9`) and PHP 8.2–8.5; PHPUnit 11.5 or 12.5.
- CI: unit tests on PHP 8.2–8.5; CI installs no longer counted as Packagist downloads.
- composer.json: `homepage` and `support` links, extra keywords.
- README restructured: problem first, install, requirements, configuration, architecture.

## 0.0.1
- Per-source toggles (Stores → Configuration → DMLab → Disable Log, default scope, all off by default): customer login/logout log (`customer_log`), online-visitor tracking (`customer_visitor`), search-query log (`search_query`).
- Visitor tracking off: guest product index rows without a visitor or customer are not written to `report_viewed_product_index` / `report_compared_product_index`.
- Password change/reset still logs out the customer's other sessions with visitor tracking off (session validator checks the login time kept in the session).
- `around` plugins only; no core class patched or replaced. ACL `DmLab_DisableLog::config`.
