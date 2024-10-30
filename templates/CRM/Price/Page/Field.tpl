{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if ($action eq 1 or $action eq 2 or $action eq 4) and !$isReserved}
  {include file="CRM/Price/Form/Field.tpl"}
{elseif $action eq 8 and !$usedBy and !$isReserved}
  {include file="CRM/Price/Form/DeleteField.tpl"}
{elseif $action eq 1024}
  {include file="CRM/Price/Form/Preview.tpl"}
{elseif $usedBy}
  <div id="price_set_used_by" class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {if $action eq 8}
      {ts 1=$usedPriceSetTitle}Unable to delete the '%1' Price Field - it is currently in use by one or more active events or contribution pages or contributions  or event templates.{/ts}
    {/if}

    {include file="CRM/Price/Page/table.tpl"}
  </div>
{/if}

{* priceField is set when e.g. in browse mode *}
{if $action NEQ 8 and !empty($priceField)}
<div class="crm-content-block crm-block">
  <div id="field_page">
  {strip}
  {* handle enable/disable actions*}
  {include file="CRM/common/enableDisableApi.tpl"}
    <table id="options" class="row-highlight">
      <thead>
       <tr>
          <th>{ts}Field Label{/ts}</th>
          <th>{ts}Field Type{/ts}</th>
          <th>{ts}Order{/ts}</th>
          <th>{ts}Req?{/ts}</th>
          <th>{ts}Enabled?{/ts}</th>
          <th>{ts}Active On{/ts}</th>
          <th>{ts}Expire On{/ts}</th>
          <th>{ts}Price{/ts}</th>
          {if $getTaxDetails}
            <th>{ts}Tax Label{/ts}</th>
            <th>{ts}Tax Amount{/ts}</th>
          {/if}
          <th></th>
      </tr>
      </thead>
      {foreach from=$priceField key=fid item=row}
      <tr id="price_field-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
        <td class="crm-editable" data-field="label">{$row.label}</td>
        <td>{$row.html_type_display}</td>
        <td class="nowrap">{$row.weight|smarty:nodefaults}</td>
        <td class="crm-editable" data-field="is_required" data-type="boolean">{if $row.is_required eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
        <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
        <td>{if $row.active_on}{$row.active_on|crmDate}{/if}</td>
        <td>{if $row.expire_on}{$row.expire_on|crmDate}{/if}</td>
        <td>{if $row.html_type eq "Text"}{$row.price|crmMoney}{else}<a class="action-item" href="{crmURL p="civicrm/admin/price/field/option" q="action=browse&reset=1&sid=$sid&fid=$fid"}">{if $isReserved}{ts}View Price Options{/ts}{else}{ts}Edit Price Options{/ts}{/if}</a>{/if}</td>
        {if $getTaxDetails}
            <td>{if $row.tax_rate != '' && $row.html_type eq "Text / Numeric Quantity"}
                    {$taxTerm} ({$row.tax_rate|string_format:"%.2f"}%)
                {/if}
      </td>
            <td>{if $row.html_type eq "Text / Numeric Quantity"}{$row.tax_amount|crmMoney}{/if}</td>
        {/if}
        <td class="field-action">{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
      </tr>
      {/foreach}
    </table>
  {/strip}
  </div>
  <div class="action-link">
    {if !$isReserved}
      {crmButton p="civicrm/admin/price/field/edit" q="reset=1&action=add&sid=$sid" id="newPriceField"  icon="plus-circle"}{ts}Add Price Field{/ts}{/crmButton}
    {/if}
    {crmButton p="civicrm/admin/price/field/edit" q="action=preview&sid=`$sid`&reset=1&context=field" icon="television"}{ts}Preview (all fields){/ts}{/crmButton}
  </div>
</div>
{else}
  {if $action eq 16}
    <div class="messages status no-popup crm-empty-table">
      {icon icon="fa-info-circle"}{/icon}
      {ts}None found.{/ts}
    </div>
    <div class="action-link">
      {crmButton p="civicrm/admin/price/field/edit" q="reset=1&action=add&sid=$sid" id="newPriceField"  icon="plus-circle"}{ts}Add Price Field{/ts}{/crmButton}
    </div>
  {/if}
{/if}
