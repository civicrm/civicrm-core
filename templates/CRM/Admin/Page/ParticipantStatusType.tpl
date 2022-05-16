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
  {include file="CRM/Admin/Form/ParticipantStatusType.tpl"}
{else}
  <div class="help">{ts}Manage event participant statuses below. Enable selected statuses to allow event waitlisting and/or participant approval.{/ts} {help id="id-disabled_statuses"}</div>

<div class="crm-block crm-content-block participant-status">
  {strip}
    {* handle enable/disable actions*}
    {include file="CRM/common/enableDisableApi.tpl"}
    <table cellpadding="0" cellspacing="0" border="0" class="row-highlight">
      <thead class="sticky">
        <th>{ts}Label{/ts}</th>
        <th>{ts}Name (Status ID){/ts}</th>
        <th>{ts}Class{/ts}</th>
        <th>{ts}Reserved?{/ts}</th>
        <th>{ts}Active?{/ts}</th>
        <th>{ts}Counted?{/ts}</th>
        <th>{ts}Order{/ts}</th>
        <th>{ts}Visibility{/ts}</th>
        <th></th>
      </thead>
      {foreach from=$rows item=row}
       <tr id="participant_status_type-{$row.id}" class="crm-entity crm-participant_{$row.id} {cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
          <td class="crmf-label crm-editable" data-field="label">{$row.label}</td>
          <td class="crmf-name">{$row.name} ({$row.id})</td>
          <td class="crmf-class {if empty($row.is_reserved)} crm-editable {/if}" data-type="select">{$row.class}</td>
          <td class="center crmf-is_reserved">{icon condition=$row.is_reserved}{ts}Reserved{/ts}{/icon}</td>
        <td id="row_{$row.id}_status" class="crmf-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td class="center crmf-is_counted">{icon condition=$row.is_counted}{ts}Counted{/ts}{/icon}</td>
          <td class="crmf-weight">{$row.weight|smarty:nodefaults}</td>
          <td class="crmf-visibility">{$row.visibility}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
      {/foreach}
    </table>
  {/strip}

  {if $action ne 1 and $action ne 2}
    <div class="action-link">
      {crmButton q="action=add&reset=1" icon="plus-circle"}{ts}Add Participant Status{/ts}{/crmButton}
      {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
    </div>
  {/if}
</div>
{/if}
