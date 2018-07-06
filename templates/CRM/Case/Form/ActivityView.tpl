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
{* View Case Activities *}
<div class="crm-block crm-content-block crm-case-activity-view-block">
  {if $revs}
    {strip}
      <table class="crm-info-panel">
        <tr class="columnheader">
          <th>{ts}Created By{/ts}</th>
          <th>{ts}Created On{/ts}</th>
          <th>&nbsp;</th>
        </tr>
        {foreach from=$result item=row}
          <tr {if $row.id EQ $latestRevisionID}style="font-weight: bold;"{/if}>
            <td class="crm-case-activityview-form-block-name">{$row.name}</td>
            <td class="crm-case-activityview-form-block-date">{$row.date|crmDate}</td>
            <td class="crm-case-activityview-form-block-{$row.id}"><a class="open-inline-noreturn" href="{crmURL p='civicrm/case/activity/view' h=0 q="cid=$contactID&aid="}{$row.id}" title="{ts}View this revision of the activity record.{/ts}">{if $row.id != $latestRevisionID}View Prior Revision{else}View Current Revision{/if}</a></td>
          </tr>
        {/foreach}
      </table>
    {/strip}
  {else}
    {if $report}
      <table class="crm-info-panel" id="crm-activity-view-table">
        {foreach from=$report.fields item=row name=report}
          <tr class="crm-case-activity-view-{$row.label}">
            <td class="label">{$row.label}</td>
            {if $smarty.foreach.report.first AND ( $activityID OR $parentID OR $latestRevisionID )} {* Add a cell to first row with links to prior revision listing and Prompted by (parent) as appropriate *}
              <td>{$row.value}</td>
              <td style="padding-right: 50px; text-align: right; font-size: .9em;">
                {if $activityID}<a class="open-inline-noreturn" href="{crmURL p='civicrm/case/activity/view' h=0 q="cid=$contactID&aid=$activityID&revs=1"}">&raquo; {ts}List all revisions{/ts}</a>{if !$latestRevisionID}<br />{ts}(this is the current revision){/ts}{/if}<br />{/if}
                {if $latestRevisionID}<a class="open-inline-noreturn" href="{crmURL p='civicrm/case/activity/view' h=0 q="cid=$contactID&aid=$latestRevisionID"}">&raquo; {ts}View current revision{/ts}</a><br /><span style="color: red;">{ts}(this is not the current revision){/ts}</span><br />{/if}
                {if $parentID}<a class="open-inline-noreturn" href="{crmURL p='civicrm/case/activity/view' h=0 q="cid=$contactID&aid=$parentID"}">&raquo; {ts}Prompted by{/ts}</a>{/if}
              </td>
            {else}
              <td colspan="2">{if $row.label eq 'Details'}{$row.value|crmStripAlternatives|nl2br}{elseif $row.type eq 'Date'}{$row.value|crmDate}{else}{$row.value}{/if}</td>
            {/if}
          </tr>
        {/foreach}
        {* Display custom field data for the activity. *}
        {if $report.customGroups}
          {foreach from=$report.customGroups item=customGroup key=groupTitle name=custom}
            <tr class="crm-case-activityview-form-block-groupTitle form-layout">
              <td colspan="3">{$groupTitle}</td>
            </tr>
            {foreach from=$customGroup item=customField name=fields}
              <tr{if ! $smarty.foreach.fields.last} style="border-bottom: 1px solid #F6F6F6;"{/if}>
                <td class="label">{$customField.label}</td>
                <td>{$customField.value}</td>
              </tr>
            {/foreach}
          {/foreach}
        {/if}
      </table>
    {/if}
  {/if}
  <div class="crm-submit-buttons">
    {crmButton p='civicrm/case' q="reset=1" class='cancel' icon='times'}{ts}Done{/ts}{/crmButton}
  </div>
</div>
