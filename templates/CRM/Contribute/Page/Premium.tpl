{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
      <th>{ts}Financial Type{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th></th>
        </tr>
        {foreach from=$rows item=row}
        <tr class="{cycle values='odd-row,even-row'} {$row.class}{if NOT $row.is_active} disabled{/if}">
          <td class="crm-contribution-form-block-product_name">{$row.product_name}</td>
          <td class="crm-contribution-form-block-sku">{$row.sku}</td>
          <td class="crm-contribution-form-block-price">{$row.price }</td>
          <td class="crm-contribution-form-block-min_contribution">{$row.min_contribution}</td>
          <td class="crm-contribution-form-block-financial_type">{$row.financial_type_id}</td>
          <td class="nowrap crm-contribution-form-block-weight">{$row.weight}</td>
          <td class="crm-contribution-form-block-action">{$row.action}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}
    </div>
    {if $products}
      <div class="action-link">
        <a href="{crmURL p='civicrm/admin/contribute/addProductToPage' q="reset=1&action=update&id=$id"}">&raquo; {ts}Offer Another Premium on this Contribution Page{/ts}</a>
      </div>
    {/if}
</div>
{else}
  {if $showForm eq false}
    <div class="messages status no-popup">
      {if $products ne null }
        <div class="icon inform-icon"></div>
        {capture assign=crmURL}{crmURL p='civicrm/admin/contribute/addProductToPage' q="reset=1&action=update&id=$id"}{/capture}
        {ts 1=$crmURL}There are no premiums offered on this contribution page yet. You can <a href='%1'>add one</a>.{/ts}
      {else}
        <div class="icon inform-icon"></div>
        {ts 1=$managePremiumsURL}There are no active premiums for your site. You can <a href='%1'>create and/or enable premiums here</a>.{/ts}
      {/if}
    </div>
  {/if}
{/if}
