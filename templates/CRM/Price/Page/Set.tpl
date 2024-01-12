{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 1 or $action eq 2 or $action eq 4}
    {include file="CRM/Price/Form/Set.tpl"}
{elseif $action eq 1024}
    {include file="CRM/Price/Form/Preview.tpl"}
{elseif $action eq 8 and !$usedBy}
    {include file="CRM/Price/Form/DeleteSet.tpl"}
{else}
    <div class="help">
      {ts}Price sets allow you to set up flexible multi-option pricing schemes for your contribution, event and membership pages. Use a price set if the standard pricing options are insufficient for your needs.{/ts}
    </div>

    {if $usedBy}
    <div class='spacer'></div>
    <div id="price_set_used_by" class="messages status no-popup">
       {icon icon="fa-info-circle"}{/icon}
        {if $action eq 8}
            {ts 1=$usedPriceSetTitle}Unable to delete the '%1' price set - it is currently in use by one or more active events or contribution pages or contributions or event templates.{/ts}
        {/if}

        {include file="CRM/Price/Page/table.tpl"}
    </div>
    {/if}

    {if $rows}
    <div id="price_set" class="crm-content-block crm-block">
        {strip}
  {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
  {include file="CRM/common/jsortable.tpl"}
        <table id="price_set" class="display crm-price-set-listing">
        <thead>
        <tr>
            <th id="sortable">{ts}Set Title{/ts}</th>
            <th id="nosort">{ts}Used For{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th></th>
        </tr>
        </thead>
        {foreach from=$rows item=row}
      <tr id="price_set-{$row.id}" class="crm-entity crm-price-set_{$row.id} {cycle values="even-row,odd-row"}{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
          <td class="crmf-title crm-editable">{$row.title}</td>
          <td class="crmf-extends">{$row.extends}</td>
          <td class="crmf-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>

        {if NOT ($action eq 1 or $action eq 2)}
        <div class="action-link">
            {crmButton p='civicrm/admin/price/edit' q="action=add&reset=1" id="newPriceSet"  icon="plus-circle"}{ts}Add Set of Price Fields{/ts}{/crmButton}
        </div>
        {/if}

        {/strip}
    </div>
    {else}
      {if $action ne 1} {* When we are adding an item, we should not display this message *}
        {capture assign=infoTitle}{ts}No price sets have been added yet.{/ts}{/capture}
        {assign var="infoType" value="no-popup"}
        {capture assign=crmURL}{crmURL p='civicrm/admin/price/edit' q='action=add&reset=1'}{/capture}
        {capture assign=infoMessage}{ts 1=$crmURL}You can <a href='%1'>create one here</a>.{/ts}{/capture}
        {include file="CRM/common/info.tpl"}
      {/if}
    {/if}
{/if}
