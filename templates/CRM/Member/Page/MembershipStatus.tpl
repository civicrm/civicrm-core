{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
            <th>{ts}End Event{/ts}</th>
            <th>{ts}Member{/ts}</th>
            <th>{ts}Admin{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th>{ts}Reserved?{/ts}</th>
            <th></th>
        </thead>
        {foreach from=$rows item=row}
        <tr id="membership_status-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class} {if NOT $row.is_active} disabled{/if} crmf">
          <td class="crmf-label crm-editable" >{$row.label}</td>
          <td class="crmf-start_event crm-editable" data-type="select" data-empty-option="{ts}- none -{/ts}">{$row.start_event}</td>
          <td class="crmf-end_event crm-editable" data-type="select" data-empty-option="{ts}- none -{/ts}">{$row.end_event}</td>
          <td class="crmf-is_current_member crm-editable" data-type="boolean">{if $row.is_current_member eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td class="crmf-is_admin crm-editable" data-type="boolean">{if $row.is_admin eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td class="nowrap crmf-weight">{$row.weight}</td>
          <td class="crmf-is_reserved">{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|replace:'xx':$row.id}</td>
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
         <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>{ts 1=$crmURL}There are no custom membership status entered. You can <a href='%1'>add one</a>.{/ts}
      </div>
    {/if}
  {/if}
{/if}
