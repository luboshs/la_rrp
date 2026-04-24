# la_rrp – Technical Documentation

<!-- Copyright (C) 2024 la_rrp | License: AFL 3.0 -->

## Overview

The `la_rrp` module adds a **UVP (Odporúčaná predajná cena)** field to every
PrestaShop product and uses it to display one of two contextual messages on the
product detail page.

---

## Architecture

```
la_rrp/
├── la_rrp.php                         ← Main module class
├── index.php                          ← Directory protection
├── .htaccess                          ← Prevent direct PHP access
├── LICENSE.md
├── README.md
├── phpunit.xml
├── phpcs.xml
├── docs/
│   ├── documentation.md               ← This file
│   └── index.php
├── upgrade/
│   └── index.php
├── tests/
│   ├── bootstrap.php
│   ├── Unit/
│   │   └── LaRrpLogicTest.php
│   └── Integration/
│       └── (integration stubs)
└── views/
    ├── css/la_rrp.css
    ├── js/la_rrp.js
    └── templates/
        ├── front/displayProductPriceBlock.tpl
        └── admin/configure.tpl
```

---

## Database

### Table: `ps_product_uvp`

| Column | Type | Description |
|---|---|---|
| `id_product` | INT UNSIGNED PK | Foreign key to `ps_product` |
| `uvp` | DECIMAL(20,6) | Recommended retail price |

The table is created on module install and dropped on uninstall. The core
`ps_product` table is **never modified**.

---

## Hooks

| Hook | Purpose |
|---|---|
| `displayHeader` | Load CSS / JS on the product page only |
| `displayProductPriceBlock` (type=`after_price`) | Render UVP block |
| `actionProductFormBuilderModifier` | Add UVP field to Symfony product form |
| `actionAfterCreateProductFormHandler` | Persist UVP after product create |
| `actionAfterUpdateProductFormHandler` | Persist UVP after product update |
| `actionProductImportAfter` | Read UVP from CSV import row |

---

## Business Logic

```
if uvp == null || uvp <= 0:
    → display nothing

if current_price < uvp:
    discount = uvp - current_price
    discount_percent = round((discount / uvp) * 100)
    → display "Ušetríte: X € (Y %)"

else (current_price >= uvp):
    → display "Garancia ceny" badge + tooltip
```

Negative discounts are suppressed (guarded by `discount_percent > 0`).

---

## Configuration Keys

| Key | Default | Description |
|---|---|---|
| `LA_RRP_ENABLED` | `1` | Master on/off switch |
| `LA_RRP_SHOW_GUARANTEE` | `1` | Show guarantee badge |

---

## Security

- All user input is validated and cast before use.
- The config form is protected with a CSRF token generated via `Tools::encrypt()`.
- All Smarty variables are escaped with `|escape:'htmlall':'UTF-8'`.
- SQL queries use `(int)` and `(float)` casts; no raw user input reaches the DB.

---

## Upgrade path

Place upgrade scripts in `upgrade/upgrade-X.Y.Z.php`. Example:

```php
function upgrade_module_1_0_1($object): bool
{
    // Perform schema/config changes
    return true;
}
```

---

## Testing

```bash
./vendor/bin/phpunit --configuration phpunit.xml
```

Unit tests cover the core calculation logic and do not require a live
PrestaShop installation.
