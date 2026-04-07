{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=managePremiumsURL}{crmURL p='civicrm/admin/contribute/managePremiums' q="reset=1"}{/capture}
{if $rows}
<div id="ltype">
    <div class="description">
        <p>{ts 1=$managePremiumsURL}The premiums listed below are currently offered on this Contribution Page. If you have other premiums which are not already being offered on this page, you will see a link below to offer another premium. Use <a href='%1'>Contributions &raquo; Premiums</a> to create or enable additional premium choices which can be used on any Contribution page.{/ts}</p>
    </div>
    <div class="form-item">
        {strip}
        <table>
        <tr class="columnheader">
            <th>{ts}Name{/ts}</th>
            <th>{ts}SKU{/ts}</th>
            <th>{ts}Market Value{/ts}</th>
            <th>{ts}Min Contribution{/ts}</th>
            <th>{ts}Actual Cost{/ts}</th>
            <th>{ts}Financial Type{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th></th>
        </tr>
        {foreach from=$rows item=row}
        <tr class="{cycle values='odd-row,even-row'}{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
          <td class="crm-contribution-form-block-product_name">{$row.product_name|escape}</td>
          <td class="crm-contribution-form-block-sku">{$row.sku|escape}</td>
          <td class="crm-contribution-form-block-price">{$row.price|crmMoney}</td>
          <td class="crm-contribution-form-block-min_contribution">{$row.min_contribution|crmMoney}</td>
          <td class="crm-contribution-form-block-cost">{$row.cost|crmMoney}</td>
          <td class="crm-contribution-form-block-financial_type">{$row.financial_type|escape}</td>
          <td class="nowrap crm-contribution-form-block-weight">{$row.weight|smarty:nodefaults}</td>
          <td class="crm-contribution-form-block-action">{$row.action|smarty:nodefaults}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}
    </div>
    {if $products}
      <div class="action-link">
        <a href="{crmURL p='civicrm/admin/contribute/addProductToPage' q="reset=1&action=update&id=$id"}"><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}Offer Another Premium on this Contribution Page{/ts}</a>
      </div>
    {/if}
</div>
{else}
  <div class="messages status no-popup">
    {if $products ne null}
      {icon icon="fa-info-circle"}{/icon}
      {capture assign=crmURL}{crmURL p='civicrm/admin/contribute/addProductToPage' q="reset=1&action=update&id=$id"}{/capture}
      {ts 1=$crmURL}There are no premiums offered on this contribution page yet. You can <a href='%1'>add one</a>.{/ts}
    {else}
      {icon icon="fa-info-circle"}{/icon}
      {if !$premiumsExist}
      {ts 1=$managePremiumsURL}There are no active premiums for your site. You can <a href='%1'>create and/or enable premiums here</a>.{/ts}
      {else}
        {ts}Premiums can be added after specifying Premiums Settings above and saving.{/ts}
      {/if}
    {/if}
  </div>
{/if}
