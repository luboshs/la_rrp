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
 * Upgrade script for version 1.0.1.
 *
 * Add future schema / config changes here.
 *
 * @param La_rrp $object Module instance
 */
function upgrade_module_1_0_1(La_rrp $object): bool
{
    // Example: add a new configuration key
    return Configuration::updateValue('LA_RRP_BADGE_COLOR', '#2ecc71');
}
