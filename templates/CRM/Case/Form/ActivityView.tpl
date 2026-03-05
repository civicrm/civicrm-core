{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* View Case Activities *}
<div class="crm-block crm-content-block crm-case-activity-view-block">
  {if $report}
    <table class="crm-info-panel" id="crm-activity-view-table">
      {foreach from=$report.fields item=row name=report}
        <tr class="crm-case-activity-view-{$row.label}">
          <td class="label">{$row.label}</td>
          {if $smarty.foreach.report.first AND ( $activityID OR $parentID )} {* Add a cell to first row with links to Prompted by (parent) as appropriate *}
            <td>{$row.value}</td>
            <td style="padding-right: 50px; text-align: right; font-size: .9em;">
              {if $parentID}<a class="open-inline-noreturn" href="{crmURL p='civicrm/case/activity/view' h=0 q="cid=$contactID&aid=$parentID"}"><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {ts}Prompted by{/ts}</a>{/if}
            </td>
          {else}
            <td colspan="2">
              {if $row.label eq 'Details'}{$row.value|crmStripAlternatives|nl2brIfNotHTML|purify}{elseif $row.type eq 'Date'}{$row.value|crmDate}{else}{$row.value}{/if}</td>
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
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom" linkButtons=$actionLinks}</div>
</div>

{include file="CRM/Case/Form/ActivityToCase.tpl"}
