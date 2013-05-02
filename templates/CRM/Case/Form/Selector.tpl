{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="top"}
{/if}
{strip}
<table class="caseSelector">
  <tr class="columnheader">

  {if ! $single and $context eq 'Search' }
    <th scope="col" title="Select Rows">{$form.toggleSelect.html}</th>
  {/if}

  {if $single}
    <th scope="col">{ts}ID{/ts}</th>
  {else}
    <th></th>
  {/if}

  {foreach from=$columnHeaders item=header}
    <th scope="col">
      {if $header.sort}
        {assign var='key' value=$header.sort}
        {$sort->_response.$key.link}
      {else}
        {$header.name}
      {/if}
    </th>
  {/foreach}
  </tr>

  {counter start=0 skip=1 print=false}
  {foreach from=$rows item=row}

  <tr id='rowid{$list}{$row.case_id}' class="{cycle values="odd-row,even-row"} crm-case crm-case-status_{$row.case_status_id} crm-case-type_{$row.case_type_id}">
    {if $context eq 'Search' && !$single}
        {assign var=cbName value=$row.checkbox}
        <td>{$form.$cbName.html}</td>
    {/if}
    {if $single }
        <td class="crm-case-id crm-case-id_{$row.case_id}">{$row.case_id}</td>
    {/if}
    {if $context != 'case'}
        <td class="crm-case-id crm-case-id_{$row.case_id}">
        <span id="{$list}{$row.case_id}_show">
            <a href="#" onclick="cj('#caseDetails{$list}{$row.case_id}').show();
                                 buildCaseDetails('{$list}{$row.case_id}','{$row.contact_id}');
                                 cj('#{$list}{$row.case_id}_show').hide();
                                 cj('#minus{$list}{$row.case_id}_hide').show();
                                 cj('#{$list}{$row.case_id}_hide').show();
                                 return false;"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}open section{/ts}"/></a>
        </span>
        <span id="minus{$list}{$row.case_id}_hide">
            <a href="#" onclick="cj('#caseDetails{$list}{$row.case_id}').hide();
                                 cj('#{$list}{$row.case_id}_show').show();
                                 cj('#{$list}{$row.case_id}_hide').hide();
                                 cj('#minus{$list}{$row.case_id}_hide').hide();
                                 return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}open section{/ts}"/></a>
        </td>
    {/if}

    {if !$single}
      <td class="crm-case-id crm-case-id_{$row.case_id}"><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts}view contact details{/ts}">{$row.sort_name}</a>{if $row.phone}<br /><span class="description">{$row.phone}</span>{/if}<br /><span class="description">{ts}Case ID{/ts}: {$row.case_id}</span></td>
    {/if}

    <td class="crm-case-subject">{$row.case_subject}</td>
    <td class="{$row.class} crm-case-status_{$row.case_status}">{$row.case_status}</td>
    <td class="crm-case-case_type">{$row.case_type}</td>
    <td class="crm-case-case_role">{if $row.case_role}{$row.case_role}{else}---{/if}</td>
    <td class="crm-case-case_manager">{if $row.casemanager_id}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.casemanager_id`"}">{$row.casemanager}</a>{else}---{/if}</td>
    <td class="crm-case-case_recent_activity_type">{if $row.case_recent_activity_type}
  {$row.case_recent_activity_type}<br />{$row.case_recent_activity_date|crmDate}{else}---{/if}</td>
    <td class="crm-case-case_scheduled_activity_type">{if $row.case_scheduled_activity_type}
  {$row.case_scheduled_activity_type}<br />{$row.case_scheduled_activity_date|crmDate}{else}---{/if}</td>
    <td>{$row.action|replace:'xx':$row.case_id}{$row.moreActions|replace:'xx':$row.case_id}</td>
   </tr>
{if $context != 'case'}
   <tr id="{$list}{$row.case_id}_hide" class='{$rowClass}'>
     <td>
     </td>
{if $context EQ 'Search'}
     <td colspan="10" class="enclosingNested">
{else}
     <td colspan="9" class="enclosingNested">
{/if}
        <div id="caseDetails{$list}{$row.case_id}"></div>
     </td>
   </tr>
 <script type="text/javascript">
     cj('#{$list}{$row.case_id}_hide').hide();
     cj('#minus{$list}{$row.case_id}_hide').hide();
 </script>
{/if}
  {/foreach}

    {* Dashboard only lists 10 most recent cases. *}
    {if $context EQ 'dashboard' and $limit and $pager->_totalItems GT $limit }
      <tr class="even-row">
        <td colspan="10"><a href="{crmURL p='civicrm/case/search' q='reset=1'}">&raquo; {ts}Find more cases{/ts}... </a></td>
      </tr>
    {/if}

</table>
{/strip}

{*include activity view js file*}
{include file="CRM/common/activityView.tpl"}
<div id="view-activity" style="display:none;">
     <div id="activity-content"></div>
</div>
{if $context EQ 'Search'}
 <script type="text/javascript">
 {* this function is called to change the color of selected row(s) *}
    var fname = "{$form.formName}";
    on_load_init_checkboxes(fname);
 </script>
{/if}

{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="bottom"}
{/if}

{* Build case details*}
{literal}
<script type="text/javascript">

function buildCaseDetails( caseId, contactId )
{
  var dataUrl = {/literal}"{crmURL p='civicrm/case/details' h=0 q='snippet=4&caseId='}{literal}" + caseId +'&cid=' + contactId;
  cj.ajax({
            url     : dataUrl,
            dataType: "html",
            timeout : 5000, //Time in milliseconds
            success : function( data ){
                           cj( '#caseDetails' + caseId ).html( data );
                      },
            error   : function( XMLHttpRequest, textStatus, errorThrown ) {
                              console.error( 'Error: '+ textStatus );
                    }
         });
}
</script>

{/literal}
