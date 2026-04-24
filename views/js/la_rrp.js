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

/**
 * Toggle the price guarantee tooltip on click/keyboard for touch devices.
 * On desktop the CSS :hover rule already handles visibility.
 */
(function () {
    'use strict';

    /**
     * Initialise badge click / keyboard toggle behaviour.
     */
    function initLaRrpBadge() {
        var badges = document.querySelectorAll('.js-la-rrp-badge');

        badges.forEach(function (badge) {
            badge.addEventListener('click', function () {
                toggleTooltip(badge);
            });

            badge.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleTooltip(badge);
                }
            });
        });
    }

    /**
     * Toggle the tooltip that immediately follows the badge element.
     *
     * @param {HTMLElement} badge
     */
    function toggleTooltip(badge) {
        var tooltip = badge.nextElementSibling;

        if (!tooltip || !tooltip.classList.contains('js-la-rrp-tooltip')) {
            return;
        }

        var isVisible = tooltip.classList.contains('is-visible');

        tooltip.classList.toggle('is-visible', !isVisible);
        badge.setAttribute('aria-expanded', String(!isVisible));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLaRrpBadge);
    } else {
        initLaRrpBadge();
    }
}());
