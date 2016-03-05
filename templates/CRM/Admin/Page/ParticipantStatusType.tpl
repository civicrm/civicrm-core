{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{if $action eq 1 or $action eq 2 or $action eq 8}
  {include file="CRM/Admin/Form/ParticipantStatusType.tpl"}
{else}
  <div class="help">{ts}Manage event participant statuses below. Enable selected statuses to allow event waitlisting and/or participant approval.{/ts} {help id="id-disabled_statuses"}</div>

<div class="crm-section participant-status">
  {strip}
    {* handle enable/disable actions*}
    {include file="CRM/common/enableDisableApi.tpl"}
    <table cellpadding="0" cellspacing="0" border="0">
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
       <tr id="participant_status_type-{$row.id}" class="crm-entity crm-participant_{$row.id} {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
          <td class="crmf-label crm-editable" data-field="label">{$row.label}</td>
          <td class="crmf-name">{$row.name} ({$row.id})</td>
          <td class="crmf-class crm-editable" data-type="select">{$row.class}</td>
          <td class="center crmf-is_reserved">{if $row.is_reserved}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Reserved{/ts}" />{/if}</td>
        <td id="row_{$row.id}_status" class="crmf-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td class="center crmf-is_counted">{if $row.is_counted} <img src="{$config->resourceBase}i/check.gif" alt="{ts}Counted{/ts}" />{/if}</td>
          <td class="crmf-weight">{$row.weight}</td>
          <td class="crmf-visibility">{$row.visibility}</td>
          <td>{$row.action|replace:'xx':$row.id}</td>
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
