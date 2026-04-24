<?php
/**
 * Copyright (C) 2024 la_rrp
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 *
 * @author    la_rrp
 * @copyright 2024 la_rrp
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Main module class for la_rrp (UVP – Recommended Retail Price).
 *
 * Registers a UVP (Odporúčaná predajná cena) per product, shows a savings
 * message or a "Price Guarantee" badge on the product detail page, and
 * provides an admin field in the Symfony-based product form (PS 8.x).
 */
class La_rrp extends Module
{
    /**
     * Custom DB table name (without prefix).
     */
    const TABLE_PRODUCT_UVP = 'product_uvp';

    /**
     * Configuration key prefix.
     */
    const CFG_ENABLED = 'LA_RRP_ENABLED';
    const CFG_SHOW_GUARANTEE = 'LA_RRP_SHOW_GUARANTEE';

    public function __construct()
    {
        $this->name = 'la_rrp';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'la_rrp';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '8.9.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('UVP – Recommended Retail Price');
        $this->description = $this->l(
            'Displays RRP/UVP price comparison and a Price Guarantee badge on product pages.'
        );
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
    }

    /**
     * Install the module: register hooks, create DB table, write config defaults.
     */
    public function install(): bool
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayProductPriceBlock')
            && $this->registerHook('actionProductFormBuilderModifier')
            && $this->registerHook('actionAfterCreateProductFormHandler')
            && $this->registerHook('actionAfterUpdateProductFormHandler')
            && $this->registerHook('actionProductImportAfter')
            && $this->installDb()
            && $this->installConfig();
    }

    /**
     * Uninstall the module: drop DB table, remove config values.
     */
    public function uninstall(): bool
    {
        return parent::uninstall()
            && $this->uninstallDb()
            && $this->uninstallConfig();
    }

    /**
     * Create the product_uvp table.
     */
    private function installDb(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE_PRODUCT_UVP . '` (
            `id_product` INT(11) UNSIGNED NOT NULL,
            `uvp`        DECIMAL(20, 6) NOT NULL DEFAULT 0.000000,
            PRIMARY KEY (`id_product`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        return (bool) Db::getInstance()->execute($sql);
    }

    /**
     * Drop the product_uvp table.
     */
    private function uninstallDb(): bool
    {
        return (bool) Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::TABLE_PRODUCT_UVP . '`'
        );
    }

    /**
     * Write default configuration values.
     */
    private function installConfig(): bool
    {
        return Configuration::updateValue(self::CFG_ENABLED, 1)
            && Configuration::updateValue(self::CFG_SHOW_GUARANTEE, 1);
    }

    /**
     * Delete all module configuration keys.
     */
    private function uninstallConfig(): bool
    {
        return Configuration::deleteByName(self::CFG_ENABLED)
            && Configuration::deleteByName(self::CFG_SHOW_GUARANTEE);
    }

    // -------------------------------------------------------------------------
    // Back-office configuration page
    // -------------------------------------------------------------------------

    /**
     * Render the module configuration page (called by PrestaShop admin).
     */
    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submitLaRrpConfig')) {
            $output .= $this->processConfigForm();
        }

        return $output . $this->renderConfigForm();
    }

    /**
     * Validate and persist the submitted configuration form.
     */
    private function processConfigForm(): string
    {
        $token = Tools::getValue('la_rrp_token');

        if (!$token || $token !== $this->getFormToken()) {
            return $this->displayError($this->l('Invalid security token.'));
        }

        Configuration::updateValue(self::CFG_ENABLED, (int) Tools::getValue(self::CFG_ENABLED));
        Configuration::updateValue(self::CFG_SHOW_GUARANTEE, (int) Tools::getValue(self::CFG_SHOW_GUARANTEE));

        return $this->displayConfirmation($this->l('Settings saved successfully.'));
    }

    /**
     * Build and return the HelperForm HTML for the configuration page.
     */
    private function renderConfigForm(): string
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = 'configuration';
        $helper->module = $this;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->identifier = 'id_configuration';
        $helper->submit_action = 'submitLaRrpConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->fields_value[self::CFG_ENABLED] = (int) Configuration::get(self::CFG_ENABLED);
        $helper->fields_value[self::CFG_SHOW_GUARANTEE] = (int) Configuration::get(self::CFG_SHOW_GUARANTEE);
        $helper->fields_value['la_rrp_token'] = $this->getFormToken();

        $form = [
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Settings'),
                        'icon' => 'icon-cogs',
                    ],
                    'input' => [
                        [
                            'type' => 'switch',
                            'label' => $this->l('Enable module'),
                            'name' => self::CFG_ENABLED,
                            'is_bool' => true,
                            'values' => [
                                ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                                ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                            ],
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Show price guarantee badge'),
                            'name' => self::CFG_SHOW_GUARANTEE,
                            'is_bool' => true,
                            'values' => [
                                ['id' => 'guarantee_on', 'value' => 1, 'label' => $this->l('Yes')],
                                ['id' => 'guarantee_off', 'value' => 0, 'label' => $this->l('No')],
                            ],
                        ],
                        [
                            'type' => 'hidden',
                            'name' => 'la_rrp_token',
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-default pull-right',
                    ],
                ],
            ],
        ];

        return $helper->generateForm($form);
    }

    /**
     * Return a deterministic CSRF token for the configuration form.
     */
    private function getFormToken(): string
    {
        return Tools::encrypt('la_rrp_config_' . $this->name);
    }

    // -------------------------------------------------------------------------
    // Front-office hooks
    // -------------------------------------------------------------------------

    /**
     * Hook: displayHeader – enqueue CSS and JS on the product page only.
     */
    public function hookDisplayHeader(): void
    {
        if (!(int) Configuration::get(self::CFG_ENABLED)) {
            return;
        }

        if (!isset($this->context->controller->php_self)
            || $this->context->controller->php_self !== 'product') {
            return;
        }

        $this->context->controller->addCSS($this->_path . 'views/css/la_rrp.css');
        $this->context->controller->addJS($this->_path . 'views/js/la_rrp.js');
    }

    /**
     * Hook: displayProductPriceBlock – render UVP block after the product price.
     *
     * Business logic:
     *   – UVP missing / 0 → nothing displayed
     *   – current_price < uvp  → show savings amount and percentage
     *   – current_price >= uvp → show Price Guarantee badge
     *
     * @param array<string,mixed> $params
     */
    public function hookDisplayProductPriceBlock(array $params): string
    {
        if (!(int) Configuration::get(self::CFG_ENABLED)) {
            return '';
        }

        if (!isset($params['type']) || $params['type'] !== 'after_price') {
            return '';
        }

        $productId = $this->extractProductId($params);

        if ($productId <= 0) {
            return '';
        }

        $uvp = $this->getProductUvp($productId);

        if ($uvp <= 0) {
            return '';
        }

        $currentPrice = (float) Product::getPriceStatic($productId, true);

        if ($currentPrice <= 0) {
            return '';
        }

        $isCheaper = $currentPrice < $uvp;
        $discount = $uvp - $currentPrice;
        $discountPercent = (int) round(($discount / $uvp) * 100);

        // Guard: never show a negative discount
        if ($isCheaper && $discountPercent <= 0) {
            return '';
        }

        $currencySign = $this->context->currency->sign;

        $this->context->smarty->assign([
            'la_rrp_uvp' => $uvp,
            'la_rrp_uvp_formatted' => number_format($uvp, 2, ',', ' '),
            'la_rrp_discount_formatted' => number_format(max(0.0, $discount), 2, ',', ' '),
            'la_rrp_discount_percent' => max(0, $discountPercent),
            'la_rrp_currency_sign' => $currencySign,
            'la_rrp_is_cheaper' => $isCheaper,
            'la_rrp_show_guarantee' => (bool) Configuration::get(self::CFG_SHOW_GUARANTEE),
        ]);

        return $this->display(__FILE__, 'views/templates/front/displayProductPriceBlock.tpl');
    }

    // -------------------------------------------------------------------------
    // Admin product-form hooks (Symfony Form Extension, PS 8.x)
    // -------------------------------------------------------------------------

    /**
     * Hook: actionProductFormBuilderModifier – add UVP field to product form.
     *
     * @param array<string,mixed> $params
     */
    public function hookActionProductFormBuilderModifier(array $params): void
    {
        /** @var \Symfony\Component\Form\FormBuilderInterface $formBuilder */
        $formBuilder = $params['form_builder'];
        $productId = isset($params['id']) ? (int) $params['id'] : 0;
        $uvp = $productId ? $this->getProductUvp($productId) : 0.0;

        $formBuilder->add(
            'la_rrp_uvp',
            \Symfony\Component\Form\Extension\Core\Type\TextType::class,
            [
                'label' => $this->l('UVP (Recommended Retail Price)'),
                'required' => false,
                'attr' => [
                    'placeholder' => '0.00',
                ],
                'data' => $uvp > 0 ? number_format($uvp, 6, '.', '') : '',
            ]
        );

        $params['data']['la_rrp_uvp'] = $uvp > 0 ? number_format($uvp, 6, '.', '') : '';
    }

    /**
     * Hook: actionAfterCreateProductFormHandler – persist UVP after product create.
     *
     * @param array<string,mixed> $params
     */
    public function hookActionAfterCreateProductFormHandler(array $params): void
    {
        $this->saveProductUvpFromFormParams($params);
    }

    /**
     * Hook: actionAfterUpdateProductFormHandler – persist UVP after product update.
     *
     * @param array<string,mixed> $params
     */
    public function hookActionAfterUpdateProductFormHandler(array $params): void
    {
        $this->saveProductUvpFromFormParams($params);
    }

    /**
     * Extract UVP from form handler params and persist it.
     *
     * @param array<string,mixed> $params
     */
    private function saveProductUvpFromFormParams(array $params): void
    {
        $productId = isset($params['id']) ? (int) $params['id'] : 0;

        if ($productId <= 0) {
            return;
        }

        $formData = $params['form_data'] ?? [];
        $uvpRaw = $formData['la_rrp_uvp'] ?? null;

        if ($uvpRaw === null) {
            return;
        }

        $uvp = (float) str_replace(',', '.', (string) $uvpRaw);
        $uvp = $uvp >= 0 ? $uvp : 0.0;

        $this->saveProductUvp($productId, $uvp);
    }

    // -------------------------------------------------------------------------
    // Import hook
    // -------------------------------------------------------------------------

    /**
     * Hook: actionProductImportAfter – read UVP from a CSV import row.
     *
     * Supported CSV column names: uvp, rrp, la_rrp (case-insensitive).
     *
     * @param array<string,mixed> $params
     */
    public function hookActionProductImportAfter(array $params): void
    {
        $product = $params['object'] ?? $params['product'] ?? null;

        if (!($product instanceof Product)) {
            return;
        }

        $productId = (int) $product->id;

        if ($productId <= 0) {
            return;
        }

        $data = $params['data'] ?? [];
        $uvpRaw = null;

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), ['uvp', 'rrp', 'la_rrp'], true)) {
                $uvpRaw = $value;
                break;
            }
        }

        if ($uvpRaw === null) {
            return;
        }

        $uvp = (float) str_replace(',', '.', (string) $uvpRaw);
        $uvp = $uvp >= 0 ? $uvp : 0.0;

        $this->saveProductUvp($productId, $uvp);
    }

    // -------------------------------------------------------------------------
    // Database helpers
    // -------------------------------------------------------------------------

    /**
     * Return the UVP for a product, or 0.0 if none is stored.
     */
    public function getProductUvp(int $productId): float
    {
        if ($productId <= 0) {
            return 0.0;
        }

        $result = Db::getInstance()->getValue(
            'SELECT `uvp`
            FROM `' . _DB_PREFIX_ . self::TABLE_PRODUCT_UVP . '`
            WHERE `id_product` = ' . (int) $productId
        );

        return $result !== false ? (float) $result : 0.0;
    }

    /**
     * Upsert the UVP for a product (INSERT … ON DUPLICATE KEY UPDATE).
     */
    public function saveProductUvp(int $productId, float $uvp): bool
    {
        if ($productId <= 0) {
            return false;
        }

        return (bool) Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . self::TABLE_PRODUCT_UVP . '`
                (`id_product`, `uvp`)
            VALUES (' . (int) $productId . ', ' . (float) $uvp . ')
            ON DUPLICATE KEY UPDATE `uvp` = ' . (float) $uvp
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Extract the product ID from hook params (supports object or array).
     *
     * @param array<string,mixed> $params
     */
    private function extractProductId(array $params): int
    {
        if (!isset($params['product'])) {
            return 0;
        }

        $product = $params['product'];

        if ($product instanceof Product) {
            return (int) $product->id;
        }

        if (is_array($product)) {
            if (isset($product['id_product'])) {
                return (int) $product['id_product'];
            }

            if (isset($product['id'])) {
                return (int) $product['id'];
            }
        }

        return 0;
    }
}
