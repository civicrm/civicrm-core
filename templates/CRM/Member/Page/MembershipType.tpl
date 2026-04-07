{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{capture assign=reminderLink}{crmURL p='civicrm/admin/scheduleReminders' q='reset=1'}{/capture}
<div class="help">
  <p>{icon icon="fa-info-circle"}{/icon} {ts}Membership types are used to categorize memberships. You can define an unlimited number of types. Each type incorporates a 'name' (Gold Member, Honor Society Member...), a description, a minimum fee (can be $0), and a duration (can be 'lifetime'). Each member type is specifically linked to the membership entity (organization) - e.g. Bay Area Chapter.{/ts} {docURL page="user/membership/defining-memberships/"}</p>
  <p>{ts 1=$reminderLink}Configure membership renewal reminders using <a href="%1">Schedule Reminders</a>.{/ts} {docURL page="user/email/scheduled-reminders"}</p>
</div>

{if $rows}
<div id="membership_type" class="crm-content-block crm-block">
  {strip}
  {* handle enable/disable actions*}
  {include file="CRM/common/enableDisableApi.tpl"}
    <table id="options" class="row-highlight">
      <thead>
      <tr>
        <th>{ts}Membership{/ts}</th>
        <th>{ts}Frontend Title{/ts}</th>
        <th>{ts}Period{/ts}</th>
        <th>{ts}Fixed Start{/ts}</th>
        <th>{ts}Minimum Fee{/ts}</th>
        <th>{ts}Duration{/ts}</th>
        <th>{ts}Auto-renew Option{/ts}</th>
        <th>{ts}Related{/ts}</th>
        <th>{ts}Max{/ts}</th>
        <th>{ts}Visibility{/ts}</th>
        <th>{ts}Order{/ts}</th>
        <th>{ts}Enabled?{/ts}</th>
        <th></th>
      </tr>
      </thead>
      {foreach from=$rows item=row}
        <tr id="membership_type-{$row.id}" class="crm-entity {cycle values='odd-row,even-row'} crm-membership-type {if NOT $row.is_active} disabled{/if}">
          <td class="crm-editable" data-field="title">{$row.title}</td>
          <td class="crm-editable" data-field="frontend_title">{$row.frontend_title}</td>
          <td class="crmf-period_type crm-editable" data-type="select">{$row.period_type}</td>
          <td class="crmf-fixed_period_start_day">{$row.fixed_period_start_day}</td>
          <td class="crmf-minimum_fee" align="right">{$row.minimum_fee|crmMoney}</td>
          <td class="crmf-duration_interval_unit">{$row.duration_interval} {$row.duration_unit}</td>
          <td class="crmf-auto_renew">{if $row.auto_renew EQ 2}{ts}Required{/ts}{elseif $row.auto_renew EQ 1}{ts}Optional{/ts}{else}{ts}No{/ts}{/if}</td>
          <td class="crmf-relationship_type">{$row.relationshipTypeName}</td>
          <td class="crmf-max_related" align="right">{$row.max_related}</td>
          <td class="crmf-visibility crm-editable" data-type="select">{$row.visibility}</td>
          <td class="nowrap crmf-weight">{$row.weight|smarty:nodefaults}</td>
          <td class="crmf-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
      {/foreach}
    </table>
  {/strip}

  {if $action ne 1 and $action ne 2}
    <div class="action-link">
      {crmButton p='civicrm/admin/member/membershipType/add' q="action=add&reset=1" id="newMembershipType"  icon="plus-circle"}{ts}Add Membership Type{/ts}{/crmButton}
    </div>
  {/if}
</div>
{else}
  {if $action ne 1}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {capture assign=crmURL}{crmURL p='civicrm/admin/member/membershipType/add' q="action=add&reset=1"}{/capture}{ts 1=$crmURL}There are no membership types entered. You can <a href='%1'>add one</a>.{/ts}
  </div>
  {/if}
{/if}
