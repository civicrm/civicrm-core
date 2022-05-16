{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/LocationType.tpl"}
{else}
  <div class="help">
    {ts}Location types provide convenient labels to differentiate contacts' location(s). Administrators may define as many additional types as appropriate for your constituents (examples might be Main Office, School, Vacation Home...).{/ts}
  </div>

<div class="crm-block crm-content-block">
  {if $rows}
  <div id="ltype">
    {strip}
  {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
    {include file="CRM/common/jsortable.tpl"}
    <table id="options" class="display">
    <thead>
      <tr>
          <th id="sortable">{ts}Name{/ts}</th>
          <th>{ts}Display Name{/ts}</th>
          <th>{ts}vCard{/ts}</th>
          <th id="nosort">{ts}Description{/ts}</th>
          <th>{ts}Enabled?{/ts}</th>
          <th>{ts}Default?{/ts}</th>
          <th></th>
      </tr>
    </thead>
    {foreach from=$rows item=row}
    <tr id="location_type-{$row.id}"  data-action="setvalue" class="{cycle values="odd-row,even-row"} {$row.class} crm-entity {if NOT $row.is_active} disabled{/if}">
        <td class="crmf-name">{$row.name}</td>
        <td class="crmf-display_name crm-editable">{$row.display_name}</td>
        <td class="crmf-vcard_name crm-editable">{$row.vcard_name}</td>
        <td class="crmf-description crm-editable">{$row.description}</td>
        <td id="row_{$row.id}_status" class="crmf-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
        <td class="crmf-is_default">{if $row.is_default}{icon condition=$row.is_default}{ts}Default{/ts}{/icon}&nbsp;{/if}</td>
        <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
    </tr>
    {/foreach}
    </table>
    {/strip}
  </div>
  {else}
    <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {ts}None found.{/ts}
    </div>
  {/if}
  <div class="action-link">
    {crmButton q="action=add&reset=1" id="newLocationType" icon="plus-circle"}{ts}Add Option{/ts}{/crmButton}
    {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
  </div>
</div>
{/if}
