{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=crmURL}{crmURL p='civicrm/admin/member/membershipStatus' q="action=add&reset=1"}{/capture}
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Member/Form/MembershipStatus.tpl"}
{else}
  <div class="help">
    <p>{ts}CiviMember automatically calculates the current status of each contact's membership based on the status names and rules configured here. The status 'rule' tells CiviMember what status to assign based on the start and end dates of a given membership. For example, the default <strong>Grace</strong> status rule says: 'assign Grace status if the membership period ended sometime within the past month.'{/ts} {docURL page="user/membership/defining-memberships/"}
    <p>{ts 1=$crmURL}The status rules provided by default may be sufficient for your organization. However, you can easily change the status names and/or adjust the rules by clicking the Edit links below. Or you can <a href='%1'>add a new status and rule</a>.{/ts}
  </div>

  {if $rows}
<div class="crm-content-block crm-block">
  <div id="ltype">
    <p></p>
    <div id="membership_status_id">
        {strip}
        {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
        <table cellpadding="0" cellspacing="0" border="0" class="row-highlight">
        <thead class="sticky">
            <th>{ts}Status{/ts}</th>
            <th>{ts}Start Event{/ts}</th>
            <th>{ts}Start Adjustment{/ts}</th>
            <th>{ts}End Event{/ts}</th>
            <th>{ts}End Adjustment{/ts}</th>
            <th>{ts}Member{/ts}</th>
            <th>{ts}Admin{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th>{ts}Reserved?{/ts}</th>
            <th></th>
        </thead>
        {foreach from=$rows item=row}
        <tr id="membership_status-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {if !empty($row.class)}{$row.class}{/if} {if NOT $row.is_active} disabled{/if} crmf">
          <td class="crmf-label crm-editable" >{$row.label}</td>
          <td class="nowrap crmf-start_event crm-editable" data-type="select" data-empty-option="{ts escape='htmlattribute'}- none -{/ts}">{if !empty($row.start_event)}{$row.start_event}{/if}</td>
          <td class="nowrap crmf-start_event_adjust_unit_interval">{if !empty($row.start_event_adjust_unit_interval)}{$row.start_event_adjust_unit_interval}{/if}</td>
          <td class="nowrap crmf-end_event crm-editable" data-type="select" data-empty-option="{ts escape='htmlattribute'}- none -{/ts}">{if !empty($row.end_event)}{$row.end_event}{/if}</td>
          <td class="nowrap crmf-end_event_adjust_interval">{if !empty($row.end_event_adjust_interval)}{$row.end_event_adjust_interval}{/if}</td>
          <td class="crmf-is_current_member crm-editable" data-type="boolean">{if $row.is_current_member eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td class="crmf-is_admin crm-editable" data-type="boolean">{if $row.is_admin eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td class="nowrap crmf-weight">{$row.weight|smarty:nodefaults}</td>
          <td class="crmf-is_reserved">{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{if !empty($row.action)}{$row.action|smarty:nodefaults|replace:'xx':$row.id}{/if}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}

        {if $action ne 1 and $action ne 2}
        <div class="action-link">
          {crmButton q="action=add&reset=1" id="newMembershipStatus"  icon="plus-circle"}{ts}Add Membership Status{/ts}{/crmButton}
          {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
        </div>
        {/if}
    </div>
  </div>
</div>
  {else}
    {if $action ne 1}
      <div class="messages status no-popup">
         <img src="{$config->resourceBase}i/Inform.gif" alt="{ts escape='htmlattribute'}status{/ts}"/>{ts 1=$crmURL}There are no custom membership status entered. You can <a href='%1'>add one</a>.{/ts}
      </div>
    {/if}
  {/if}
{/if}
