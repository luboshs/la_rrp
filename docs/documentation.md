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

---

## Template Developer Guide

### Product detail page (`displayProductPriceBlock`)

The module renders its own template
`views/templates/front/displayProductPriceBlock.tpl` and assigns the
following Smarty variables:

| Variable | Type | Description |
|---|---|---|
| `$la_rrp_uvp` | `float` | Raw UVP value. `0` when not set. |
| `$la_rrp_uvp_formatted` | `string` | UVP formatted with `number_format` (e.g. `49,90`). |
| `$la_rrp_currency_sign` | `string` | Currency sign for the active currency (e.g. `€`). |
| `$la_rrp_is_cheaper` | `bool` | `true` when `current_price < uvp`. |
| `$la_rrp_discount_formatted` | `string` | Savings amount formatted (e.g. `10,00`). Empty when not cheaper. |
| `$la_rrp_discount_percent` | `int` | Savings percentage (e.g. `20`). `0` when not cheaper. |
| `$la_rrp_show_guarantee` | `bool` | `true` when the guarantee badge is enabled in config. |

#### Minimal example

```smarty
{if $la_rrp_uvp > 0}
  <p>UVP: {$la_rrp_uvp_formatted|escape:'htmlall':'UTF-8'} {$la_rrp_currency_sign|escape:'htmlall':'UTF-8'}</p>

  {if $la_rrp_is_cheaper}
    <p>Ušetríte: {$la_rrp_discount_formatted|escape:'htmlall':'UTF-8'} {$la_rrp_currency_sign|escape:'htmlall':'UTF-8'} ({$la_rrp_discount_percent|escape:'htmlall':'UTF-8'} %)</p>
  {elseif $la_rrp_show_guarantee}
    <span class="guarantee-badge">&#10004; Garancia ceny</span>
  {/if}
{/if}
```

---

### Product listing pages (`actionPresentProductListing`)

For every product in a listing (category, search, homepage featured
products, manufacturer page) the module automatically injects two extra
keys into the `$product` array. **No module call from the template is
needed.**

| Key | Type | Description |
|---|---|---|
| `$product.uvp` | `float` | Raw UVP value. `0.0` when not set. |
| `$product.uvp_formatted` | `string` | UVP formatted with `Tools::displayPrice()` (e.g. `49,90 €`). Empty string when not set. |

#### Usage in a listing template

```smarty
{* Show UVP price *}
{if !empty($product.uvp) && $product.uvp > 0}
  <span class="product-uvp">UVP: {$product.uvp_formatted}</span>
{/if}

{* Show savings *}
{if !empty($product.uvp) && $product.uvp > $product.price_amount}
  <span class="product-savings">
    Ušetríte: {($product.uvp - $product.price_amount)|string_format:"%.2f"} €
  </span>
{/if}
```

> **Note:** Do **not** call `{$la_rrp_module->getProductUvp($product.id_product)}` from a
> listing template – it triggers a separate SQL query for every product.
> The injected `$product.uvp` key is always available and uses a single
> bulk query with an in-memory cache.

#### Which listing contexts are covered

| Context | Available |
|---|---|
| Category page | ✅ |
| Search results | ✅ |
| Homepage featured products | ✅ |
| Manufacturer / supplier page | ✅ |
| Product detail page | ⚠️ Use `$la_rrp_uvp` from `displayProductPriceBlock` instead |

---

### Debug helper

Add this anywhere in your template to inspect all available UVP values
for a product in a listing:

```smarty
{* Remove before going live *}
<pre>
uvp            = {$product.uvp}
uvp_formatted  = {$product.uvp_formatted}
</pre>
```
