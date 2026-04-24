# la_rrp – UVP / Recommended Retail Price Module

## Module description

`la_rrp` is a PrestaShop 8.1 module that stores a **UVP** (Odporúčaná predajná cena / Recommended Retail Price) per product and renders one of two contextual messages on the product detail page:

| Condition | Display |
|---|---|
| `current_price < uvp` | **Ušetríte** – savings amount and percentage |
| `current_price >= uvp` | **Garancia ceny** – green badge with tooltip |

When no UVP is set (or UVP = 0) nothing is displayed.

---

## Prerequisites

| Requirement | Version |
|---|---|
| PrestaShop | 8.0.0 – 8.9.99 |
| PHP | 8.1+ |
| MySQL / MariaDB | 5.7+ / 10.3+ |

---

## Installation

1. Upload the `la_rrp/` folder to `<prestashop>/modules/`.
2. Log in to the PrestaShop Back Office.
3. Go to **Modules → Module Manager**.
4. Search for **UVP** and click **Install**.
5. A `ps_product_uvp` table is created automatically.

---

## Configuration

Navigate to **Modules → Module Manager → UVP → Configure**:

| Setting | Default | Description |
|---|---|---|
| Enable module | Yes | Master switch |
| Show price guarantee badge | Yes | Whether to display the green guarantee badge |

---

## Setting UVP per product

Open any product in the Back Office. The **UVP (Recommended Retail Price)** field is added to the product form via the Symfony Form Extension. Enter the recommended retail price and save.

### CSV Import

Add a column named `uvp` (or `rrp` / `la_rrp`) to your import CSV. The value is read automatically via the `actionProductImportAfter` hook.

---

## Example output

### Savings case (`current_price = 80 €`, `uvp = 100 €`)

```
Odporúčaná cena: 100,00 €
Ušetríte: 20,00 € (20 %)
```

### Price guarantee case (`current_price = 100 €`, `uvp = 100 €`)

```
Odporúčaná cena: 100,00 €
✔ Garancia ceny   ← green badge
(tooltip on hover / click)
Pokiaľ nájdete u iného predajcu v SR nižšiu cenu…
```

---

## Development

```bash
# Install PHPUnit (requires composer)
composer require --dev phpunit/phpunit ^10

# Run unit tests
./vendor/bin/phpunit --testsuite "Unit Tests"
```

---

## License

Academic Free License 3.0 – see [LICENSE.md](LICENSE.md)