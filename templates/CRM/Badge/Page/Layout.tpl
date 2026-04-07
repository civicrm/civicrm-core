{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
  {ts}Badge Layout screen for creating custom labels{/ts}
</div>

{if $action eq 1 or $action eq 2 or $action eq 8}
  {include file="CRM/Badge/Form/Layout.tpl"}
{else}

  {if $rows}
    <div id="badge-layout" class="crm-content-block crm-block">
      {strip}
      {* handle enable/disable actions*}
        {include file="CRM/common/enableDisableApi.tpl"}
        {include file="CRM/common/jsortable.tpl"}
        <table id="options" class="display">
          <thead>
          <tr>
            <th id="sortable">{ts}Title{/ts}</th>
            <th id="nosort">{ts}Description{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th>{ts}Default?{/ts}</th>
            <th></th>
          </tr>
          </thead>
          {foreach from=$rows item=row}
            <tr id="print_label-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if} crm-badge-layout {if NOT $row.is_active} disabled{/if}">
              <td class="crm-badge-layout-title crm-editable" data-field="title">{$row.title}</td>
              <td class="crm-badge-layout-description crm-editable" data-field="description" data-type="textarea">{$row.description}</td>
              <td id="row_{$row.id}_status" class="crm-badge-layout-is_active">
                {if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}
              </td>
              <td class="crm-badge-layout-is_default">{icon condition=$row.is_default}{ts}Default{/ts}{/icon}&nbsp;
              </td>
              <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
            </tr>
          {/foreach}
        </table>
      {/strip}

      {if $action ne 1 and $action ne 2}
        <div class="action-link">
          {crmButton q="action=add&reset=1" id="newbadge-layout" icon="plus-circle"}{ts}New Badge Layout{/ts}{/crmButton}
        </div>
      {/if}
    </div>
  {else}
    <div class="messages status no-popup">
      <img src="{$config->resourceBase}i/Inform.gif" alt="{ts escape='htmlattribute'}status{/ts}"/>
      {capture assign=crmURL}{crmURL p='civicrm/admin/badgelayout' q="action=add&reset=1"}{/capture}
      {ts 1=$crmURL}There are no Badge Layout entered for this Contact. You can<a href='%1'>add one</a>.{/ts}
    </div>
  {/if}
{/if}
