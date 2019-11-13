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
    {include file="CRM/Custom/Form/Field.tpl"}
{elseif $action eq 8}
    {include file="CRM/Custom/Form/DeleteField.tpl"}
{elseif $action eq 1024 }
    {include file="CRM/Custom/Form/Preview.tpl"}
{else}
    {if $customField}

    <div id="field_page">
        {strip}
      {* handle enable/disable actions*}
        {include file="CRM/common/enableDisableApi.tpl"}
         <table id="options" class="row-highlight">
         <thead>
         <tr>
            <th>{ts}Field Label{/ts}</th>
            <th>{ts}Data Type{/ts}</th>
            <th>{ts}Field Type{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th>{ts}Req?{/ts}</th>
            <th>{ts}Searchable?{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$customField item=row}
        <tr id="CustomField-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if NOT $row.is_active} disabled{/if}">
            <td class="crm-editable" data-field="label">{$row.label}</td>
            <td>{$row.data_type}</td>
            <td>{$row.html_type}</td>
            <td class="nowrap">{$row.weight}</td>
            <td class="crm-editable" data-type="boolean" data-field="is_required">{if $row.is_required eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td class="crm-editable" data-type="boolean" data-field="is_searchable">{if $row.is_searchable eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td>{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </tbody>
        </table>
        {/strip}

     </div>

    {else}
        {if $action eq 16}
        <div class="messages status no-popup crm-empty-table">
          <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
          {ts}None found.{/ts}
        </div>
        {/if}
    {/if}
    <div class="action-link">
      {crmButton p='civicrm/admin/custom/group/field/add' q="reset=1&action=add&gid=$gid" id="newCustomField"  class="action-item" icon="plus-circle"}{ts}Add Custom Field{/ts}{/crmButton}
      {crmButton p="civicrm/admin/custom/group" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
    </div>
{/if}
