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
{*
Notes:
- Any id's should be prefixed with civicase-audit to avoid name collisions.
- The idea behind the regex_replace is that for a css selector on a field, we can make it simple by saying the convention is to use the field label, but convert to lower case and strip out all except a-z and 0-9.
There's the potential for collisions (two different labels having the same shortened version), but it would be odd for the user to configure fields that way, and at most affects styling as opposed to crashing or something.
- Note the whole output gets contained within a <form> with name="Report".
*}
<script src="{$config->resourceBase}js/Audit/audit.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="{$config->resourceBase}css/Audit/style.css" />
<input type="hidden" name="currentSelection" value="1" />

<table class = "form-layout">
<tr>
   <td colspan=2>
    &nbsp;<input type="button" accesskey="P" value="Print Report" name="case_report" onClick="printReport({$caseId}, this );"/>&nbsp;&nbsp;
    &nbsp;<input type="button" accesskey="B" value="Back to Case" name="back" onClick="printReport({$caseId}, this );"/>&nbsp;&nbsp;
   </td>
</tr>
<tr>
<td>
<div id="civicase-audit">
<table><tr><td class="leftpane">
<table class="report">
<tr class="columnheader-dark">
<th>&nbsp;</th>
<th>{ts}Description{/ts}</th>
</tr>
{foreach from=$activities item=activity name=activityloop}
<tr class="activity{if $smarty.foreach.activityloop.first} selected{/if}" id="civicase-audit-activity-{$smarty.foreach.activityloop.iteration}">
  <td class="indicator">
    {if $activity.completed}
    <img src="{$config->resourceBase}i/spacer.gif" width="20" height="20">
    {else}
    <a href="#" onclick="selectActivity({$smarty.foreach.activityloop.iteration}); return false;">
    <img src="{$config->resourceBase}i/contribute/incomplete.gif" width="20" height="20" alt="{ts}Incomplete{/ts}" title="{ts}Incomplete{/ts}">
    </a>
    {/if}
  </td>
  <td>
  <a href="#" onclick="selectActivity({$smarty.foreach.activityloop.iteration}); return false;">
  {foreach from=$activity.leftpane item=field name=fieldloop}
    <span class="civicase-audit-{$field.label|lower|regex_replace:'/[^a-z0-9]+/':''} {$field.datatype}">
    {if $field.datatype == 'File'}<a href="{$field.value|escape}">{/if}
    {if $field.datatype == 'Date'}
      {if $field.includeTime}
        {$field.value|escape|replace:'T':' '|crmDate}
      {else}
        {$field.value|escape|truncate:10:'':true|crmDate}
      {/if}
    {else}
      {$field.value|escape}
    {/if}
    {if $field.datatype == 'File'}</a>{/if}
    </span><br>
  {/foreach}
  </a>
  </td>
</tr>
{/foreach}
</table>
</td>
<td class="separator">&nbsp;</td>
<td class="rightpane">
  <div class="rightpaneheader">
  {foreach from=$activities item=activity name=activityloop}
    <div class="activityheader" id="civicase-audit-header-{$smarty.foreach.activityloop.iteration}">
    <div class="auditmenu">
      <span class="editlink"><a target="editauditwin" href="{$activity.editurl}">{ts}Edit{/ts}</a></span>
      </div>
    {foreach from=$activity.rightpaneheader item=field name=fieldloop}
      <div class="civicase-audit-{$field.label|lower|regex_replace:'/[^a-z0-9]+/':''}">
      <label>{$field.label|escape}</label>
      <span class="{$field.datatype}">{if $field.datatype == 'File'}<a href="{$field.value|escape}">{/if}
      {if $field.datatype == 'Date'}
        {if $field.includeTime}
          {$field.value|escape|replace:'T':' '|crmDate}
        {else}
          {$field.value|escape|truncate:10:'':true|crmDate}
        {/if}
      {else}
        {$field.value|escape}
      {/if}
      {if $field.datatype == 'File'}</a>{/if}
      </span>
      </div>
    {/foreach}
    </div>
  {/foreach}
  </div>
  <div class="rightpanebody">
  {foreach from=$activities item=activity name=activityloop}
    <div class="activitybody" id="civicase-audit-body-{$smarty.foreach.activityloop.iteration}">
    {foreach from=$activity.rightpanebody item=field name=fieldloop}
      <div class="civicase-audit-{$field.label|lower|regex_replace:'/[^a-z0-9]+/':''}">
      <label>{$field.label|escape}</label>
      <span class="{$field.datatype}">{if $field.datatype == 'File'}<a href="{$field.value|escape}">{/if}
      {if $field.datatype == 'Date'}
        {if $field.includeTime}
          {$field.value|escape|replace:'T':' '|crmDate}
        {else}
          {$field.value|escape|truncate:10:'':true|crmDate}
        {/if}
            {elseif $field.label eq 'Details'}
                {$field.value}
      {else}
        {$field.value|escape}
      {/if}
      {if $field.datatype == 'File'}</a>{/if}
      </span>
      </div>
    {/foreach}
    </div>
  {/foreach}
  </div>
</td></tr></table>
</div>
</td>
</tr>
<tr>
   <td colspan=2>
    &nbsp;<input type="button" accesskey="P" value="Print Report" name="case_report" onclick="printReport({$caseId}, this );"/>&nbsp;&nbsp;
    &nbsp;<input type="button" accesskey="B" value="Back to Case" name="back" onClick="printReport({$caseId}, this );"/>&nbsp;&nbsp;
   </td>
</tr>
</table>
{literal}
<script type="text/javascript">
 function printReport( id, button ) {

       if ( button.name == 'case_report' ) {
            var dataUrl = {/literal}"{crmURL p='civicrm/case/report/print' h=0 q='caseID='}"{literal}+id;
            dataUrl     = dataUrl + '&cid={/literal}{$clientID}{literal}'+'&asn={/literal}{$activitySetName}{literal}';
            var redact  = '{/literal}{$_isRedact}{literal}'

            var isRedact = 1;
            if ( redact == 'false' ) {
                 isRedact = 0;
            }

            var includeActivities = '{/literal}{$includeActivities}{literal}';
            var all = 0;
            if( includeActivities == 'All' ) {
                all = 1;
            }

            dataUrl = dataUrl + '&redact='+isRedact + '&all='+ all;

       } else {

          var dataUrl = {/literal}"{crmURL p='civicrm/contact/view/case' h=0 q='reset=1&action=view&id='}"{literal}+id;
          dataUrl     = dataUrl + '&cid={/literal}{$clientID}{literal}'+'&selectedChild=case';
       }

       window.location.href =  dataUrl;
}
</script>
{/literal}
