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

{capture assign=reminderLink}{crmURL p='civicrm/admin/scheduleReminders' q='reset=1'}{/capture}
<div id="help">
  <p><div class="icon inform-icon"></div>&nbsp;{ts}Membership types are used to categorize memberships. You can define an unlimited number of types. Each type incorporates a 'name' (Gold Member, Honor Society Member...), a description, a minimum fee (can be $0), and a duration (can be 'lifetime'). Each member type is specifically linked to the membership entity (organization) - e.g. Bay Area Chapter.{/ts} {docURL page="user/membership/defining-memberships/"}</p>
  <p>{ts 1=$reminderLink}Configure membership renewal reminders using <a href="%1">Schedule Reminders</a>.{/ts} {docURL page="user/email/scheduled-reminders"}</p>
</div>

{if $rows}
<div id="membership_type">
  {strip}
  {* handle enable/disable actions*}
  {include file="CRM/common/enableDisableApi.tpl"}
  {include file="CRM/common/crmeditable.tpl"}
    <table id="options" class="row-highlight">
      <thead>
      <tr>
        <th>{ts}Membership{/ts}</th>
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
        <tr id="membership_type-{$row.id}" class="crm-entity {cycle values='odd-row,even-row'} {$row.class} crm-membership-type {if NOT $row.is_active} disabled{/if}">
          <td class="crm-membership-type-type_name crm-editable" data-field="name">{$row.name}</td>
          <td class="crm-memberhip-type-period_type">{$row.period_type}</td>
          <td class="crm-membership-type-fixed_period_start_day">{$row.fixed_period_start_day}</td>
          <td class="crm-membership-type-minimum_fee" align="right">{$row.minimum_fee|crmMoney}</td>
          <td class="crm-membership-type-duration_interval_unit">{$row.duration_interval} {$row.duration_unit}</td>
          <td class="crm-membership-type-auto-renew">{if $row.auto_renew EQ 2}{ts}Required{/ts}{elseif $row.auto_renew EQ 1}{ts}Optional{/ts}{else}{ts}No{/ts}{/if}</td>
          <td class="crm-membership-type-relationship_type_name">{$row.relationshipTypeName}</td>
          <td class="crm-membership-type-max_related" align="right">{$row.maxRelated}</td>
          <td class="crm-membership-type-visibility">{$row.visibility}</td>
          <td class="nowrap crm-membership_type-order">{$row.weight}</td>
          <td class="crm-membership-type-status_{$row.id}" id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
      {/foreach}
    </table>
  {/strip}

  {if $action ne 1 and $action ne 2}
    <div class="action-link">
      <a href="{crmURL p='civicrm/admin/member/membershipType/add' q="action=add&reset=1"}" id="newMembershipType" class="button"><span><div class="icon add-icon"></div>{ts}Add Membership Type{/ts}</span></a>
    </div>
  {/if}
</div>
{else}
  {if $action ne 1}
  <div class="messages status no-popup">
    <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
    {capture assign=crmURL}{crmURL p='civicrm/admin/member/membershipType/add' q="action=add&reset=1"}{/capture}{ts 1=$crmURL}There are no membership types entered. You can <a href='%1'>add one</a>.{/ts}
  </div>
  {/if}
{/if}
