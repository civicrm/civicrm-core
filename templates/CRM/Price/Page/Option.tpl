{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{if ($action eq 1 or $action eq 2 or $action eq 4 or $action eq 8  and !$usedBy) and !$isReserved}
  {include file="CRM/Price/Form/Option.tpl"}

{elseif $usedBy}
  <div class='spacer'></div>
  <div id="price_set_used_by" class="messages status no-popup">
    <div class="icon inform-icon"></div>
    {if $action eq 8}
      {ts 1=$usedPriceSetTitle}Unable to delete the '%1' Price Field Option - it is currently in use by one or more active events  or contribution pages or contributions.{/ts}
    {/if}

    {if $usedBy.civicrm_event or $usedBy.civicrm_contribution_page}
      {include file="CRM/Price/Page/table.tpl"}
    {/if}

  </div>
{else}


  {if $customOption}

    <div id="field_page">
      <p></p>
      {strip}
        {* handle enable/disable actions*}
        {include file="CRM/common/enableDisableApi.tpl"}
        {include file="CRM/common/crmeditable.tpl"}
        <table id="options" class="row-highlight">
          <thead>
          <tr>
            <th>{ts}Option Label{/ts}</th>
            <th>{ts}Option Amount{/ts}</th>
            <th>{ts}Default{/ts}</th>
            <th>{ts}Financial Type{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          {foreach from=$customOption item=row}
            <tr id="price_field_value-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
              <td class="crm-price-option-label crm-editable" data-field="label">{$row.label}</td>
              <td class="crm-price-option-value">{$row.amount|crmMoney}</td>
              <td class="crm-price-option-is_default">{if $row.is_default}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Default{/ts}" />{/if}</td>
              <td class="nowrap crm-price-option-financial-type-id">{$row.financial_type_id}</td>
              <td class="nowrap crm-price-option-order">{$row.weight}</td>
              <td id="row_{$row.id}_status" class="crm-price-option-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td>{$row.action|replace:'xx':$row.id}</td>
            </tr>
          {/foreach}
          </tbody>
        </table>
      {/strip}
    </div>

  {else}
    {if $action eq 16}
      <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {ts}None found.{/ts}
      </div>
    {/if}
  {/if}
  {if $addMoreFields && !$isReserved}
    <div class="action-link">
      <a href="{crmURL q="reset=1&action=add&fid=$fid&sid=$sid"}" class="button"><span><div class="icon add-icon"></div> {ts 1=$fieldTitle}New Option for '%1'{/ts}</span></a>
      <a href="{crmURL p="civicrm/admin/price/field" q="reset=1&sid=$sid"}" class="button cancel no-popup"><span><div class="icon ui-icon-close"></div> {ts}Done{/ts}</span></a>
    </div>
  {/if}
{/if}
