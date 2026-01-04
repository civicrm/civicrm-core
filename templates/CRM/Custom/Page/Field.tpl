{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
  {if $customField}

    <div id="field_page">
        {strip}
      {* handle enable/disable actions*}
        {include file="CRM/common/enableDisableApi.tpl"}
         <table id="options" class="row-highlight">
         <thead>
         <tr>
            <th>{ts}ID{/ts}</th>
            <th>{ts}Field Label{/ts}</th>
            <th>{ts}Data Type{/ts}</th>
            <th>{ts}Field Type{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th>{ts}Req?{/ts}</th>
            <th>{ts}Searchable?{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th><span class="sr-only">{ts}Actions{/ts}</span></th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$customField item=row}
        <tr id="CustomField-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if NOT $row.is_active} disabled{/if}">
            <td>{$row.id}</td>
            <td class="crm-editable" data-field="label">{$row.label}</td>
            <td>{$row.data_type}</td>
            <td>{$row.html_type}</td>
            <td class="nowrap">{$row.weight|smarty:nodefaults}</td>
            <td class="crm-editable" data-type="boolean" data-field="is_required">{if !empty($row.is_required)} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td class="crm-editable" data-type="boolean" data-field="is_searchable">{if !empty($row.is_searchable)} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td>{if !empty($row.is_active)} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </tbody>
        </table>
        {/strip}

     </div>

    {else}
        <div class="messages status no-popup crm-empty-table">
          <img src="{$config->resourceBase}i/Inform.gif" alt="{ts escape='htmlattribute'}status{/ts}"/>
          {ts}None found.{/ts}
        </div>
    {/if}
    <div class="action-link">
      {crmButton p='civicrm/admin/custom/group/field/add' q="reset=1&gid=$gid" id="newCustomField"  class="action-item" icon="plus-circle"}{ts}Add Custom Field{/ts}{/crmButton}
      {crmButton p="civicrm/admin/custom/group" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
    </div>
