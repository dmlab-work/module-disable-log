# Changelog

## 0.0.1
- Per-source toggles (Stores → Configuration → DMLab → Disable Log, default scope, all off by default): customer login/logout log (`customer_log`), online-visitor tracking (`customer_visitor`), search-query log (`search_query`).
- Visitor tracking off: guest product index rows without a visitor or customer are not written to `report_viewed_product_index` / `report_compared_product_index`.
- Password change/reset still logs out the customer's other sessions with visitor tracking off (session validator checks the login time kept in the session).
- `around` plugins only; no core class patched or replaced. ACL `DmLab_DisableLog::config`.
