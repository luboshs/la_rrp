{*
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
 *}

{if $la_rrp_uvp > 0}
  <div class="la-rrp-block">

    <div class="product-uvp">
      <span class="product-uvp__label">{l s='Odporúčaná cena:' mod='la_rrp'}</span>
      <span class="product-uvp__value">
        {$la_rrp_uvp_formatted|escape:'htmlall':'UTF-8'}
        {$la_rrp_currency_sign|escape:'htmlall':'UTF-8'}
      </span>
    </div>

    {if $la_rrp_is_cheaper}

      <div class="la-rrp-discount">
        {l s='Ušetríte:' mod='la_rrp'}
        <strong>
          {$la_rrp_discount_formatted|escape:'htmlall':'UTF-8'}
          {$la_rrp_currency_sign|escape:'htmlall':'UTF-8'}
          ({$la_rrp_discount_percent|escape:'htmlall':'UTF-8'}&nbsp;%)
        </strong>
      </div>

    {elseif $la_rrp_show_guarantee}

      <div class="price-badge guarantee js-la-rrp-badge" role="button" tabindex="0"
           aria-expanded="false" aria-controls="la-rrp-tooltip">
        &#10004; {l s='Garancia ceny' mod='la_rrp'}
      </div>

      <div id="la-rrp-tooltip" class="price-guarantee-tooltip js-la-rrp-tooltip" aria-live="polite">
        {l s='Pokiaľ nájdete u iného predajcu v SR nižšiu cenu, a bude to možné (neplatí pre špeciálne výpredaje a akcie), cenu Vám dorovnáme a navyše dostanete poštovné zadarmo!' mod='la_rrp'}
      </div>

    {/if}

  </div>
{/if}
