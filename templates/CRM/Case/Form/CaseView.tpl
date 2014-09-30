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
{* CiviCase -  view case screen*}

<div class="crm-block crm-form-block crm-case-caseview-form-block">

{* here we are showing related cases w/ jquery dialog *}
{if $showRelatedCases}
  {include file="CRM/Case/Form/ViewRelatedCases.tpl"}

{* Main case view *}
{else}

<h3>{ts}Summary{/ts}</h3>
<table class="report crm-entity case-summary" data-entity="case" data-id="{$caseID}" data-cid="{$contactID}">
  {if $multiClient}
    <tr class="crm-case-caseview-client">
      <td colspan="5" class="label">
        {ts}Clients:{/ts}
        {foreach from=$caseRoles.client item=client name=clients}
          <a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$client.contact_id`"}" title="{ts}view contact record{/ts}">{$client.display_name}</a>{if not $smarty.foreach.clients.last}, &nbsp; {/if}
        {/foreach}
        <a href="#addClientDialog" class="crm-hover-button case-miniform" title="{ts}Add Client{/ts}" data-key="{crmKey name='civicrm/case/ajax/addclient'}">
          <span class="icon add-icon"></span>
        </a>
        <div id="addClientDialog" class="hiddenElement">
          <input name="add_client_id" placeholder="{ts}- select contact -{/ts}" class="huge" />
        </div>
        {if $hasRelatedCases}
          <div class="crm-block relatedCases-link"><a class="crm-hover-button crm-popup medium-popup" href="{$relatedCaseUrl}">{$relatedCaseLabel}</a></div>
        {/if}
      </td>
    </tr>
  {/if}
  <tr>
    {if not $multiClient}
      <td>
        <table class="form-layout-compressed">
          {foreach from=$caseRoles.client item=client}
            <tr class="crm-case-caseview-display_name">
              <td class="label-left bold" style="padding: 0px; border: none;">{$client.display_name}</td>
            </tr>
            {if $client.phone}
              <tr class="crm-case-caseview-phone">
                <td class="label-left description" style="padding: 1px">{$client.phone}</td>
              </tr>
            {/if}
            {if $client.birth_date}
              <tr class="crm-case-caseview-birth_date">
                <td class="label-left description" style="padding: 1px">{ts}DOB{/ts}: {$client.birth_date|crmDate}</td>
              </tr>
            {/if}
          {/foreach}
        </table>
        {if $hasRelatedCases}
          <div class="crm-block relatedCases-link"><a class="crm-hover-button crm-popup medium-popup" href="{$relatedCaseUrl}">{$relatedCaseLabel}</a></div>
        {/if}
      </td>
    {/if}
    <td class="crm-case-caseview-case_subject label">
      <span class="crm-case-summary-label">{ts}Subject{/ts}:</span>&nbsp;{$caseDetails.case_subject}
    </td>
    <td class="crm-case-caseview-case_type label">
      <span class="crm-case-summary-label">{ts}Type{/ts}:</span>&nbsp;{$caseDetails.case_type}&nbsp;<a class="crm-hover-button crm-popup"  href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseTypeId`"}" title="{ts}Change case type (creates activity record){/ts}"><span class="icon edit-icon"></span></a>
    </td>
    <td class="crm-case-caseview-case_status label">
      <span class="crm-case-summary-label">{ts}Status{/ts}:</span>&nbsp;{$caseDetails.case_status}&nbsp;<a class="crm-hover-button crm-popup"  href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseStatusId`"}" title="{ts}Change case status (creates activity record){/ts}"><span class="icon edit-icon"></span></a>
    </td>
    <td class="crm-case-caseview-case_start_date label">
      <span class="crm-case-summary-label">{ts}Open Date{/ts}:</span>&nbsp;{$caseDetails.case_start_date|crmDate}&nbsp;<a class="crm-hover-button crm-popup"  href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseStartDateId`"}" title="{ts}Change case start date (creates activity record){/ts}"><span class="icon edit-icon"></span></a>
    </td>
    <td class="crm-case-caseview-{$caseID} label">
      <span class="crm-case-summary-label">{ts}ID{/ts}:</span>&nbsp;{$caseID}
    </td>
  </tr>
</table>
  {if $hookCaseSummary}
  <div id="caseSummary">
    {foreach from=$hookCaseSummary item=val key=div_id}
      <div id="{$div_id}"><label>{$val.label}</label><div class="value">{$val.value}</div></div>
    {/foreach}
  </div>
  {/if}

<div class="case-control-panel">
  <div>
    <p>
      {$form.add_activity_type_id.html}
      {if $hasAccessToAllCases} &nbsp;
        {$form.timeline_id.html}{$form._qf_CaseView_next.html} &nbsp;
        {$form.report_id.html}
      {/if}
    </p>
  </div>
  <div>
    <p>
      {if $hasAccessToAllCases}
        <a class="crm-hover-button action-item no-popup" href="{crmURL p='civicrm/case/report/print' q="all=1&redact=0&cid=$contactID&caseID=$caseId&asn=standard_timeline"}"><span class="icon print-icon"></span> {ts}Print Report{/ts}</a>
      {/if}

      {if $mergeCases}
        <a href="#mergeCasesDialog" class="action-item no-popup crm-hover-button case-miniform"><span class="icon ui-icon-copy"></span>{ts}Merge Case{/ts}</a>
        {$form._qf_CaseView_next_merge_case.html}
        <span id="mergeCasesDialog" class="hiddenElement">
          {$form.merge_case_id.html}
        </span>
      {/if}

      {if call_user_func(array('CRM_Core_Permission','giveMeAllACLs'))}
        <a class="action-item crm-hover-button medium-popup" href="{crmURL p='civicrm/contact/view/case/editClient' h=1 q="reset=1&action=update&id=$caseID&cid=$contactID"}"><span class="icon ui-icon-person"></span> {ts}Assign to Another Client{/ts}</a>
      {/if}
    </p>
  </div>
</div>

<div class="clear"></div>
{include file="CRM/Case/Page/CustomDataView.tpl"}

<div class="crm-accordion-wrapper collapsed crm-case-roles-block">
  <div class="crm-accordion-header">
    {ts}Roles{/ts}
  </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">

    {if $hasAccessToAllCases}
      <div class="crm-submit-buttons">
        <a class="button case-miniform" href="#addCaseRoleDialog" data-key="{crmKey name='civicrm/ajax/relation'}" rel="#caseRoles-selector-{$caseID}"><div class="icon add-icon"></div>{ts}Add new role{/ts}</a>
      </div>
      <div id="addCaseRoleDialog" class="hiddenElement">
        <div>{$form.role_type.label}</div>
        <div>{$form.role_type.html}</div><br />
        <div><label for="add_role_contact_id">{ts}Assign To{/ts}:</label></div>
        <div><input name="add_role_contact_id" placeholder="{ts}- select contact -{/ts}" class="huge" /></div>
      </div>
    {/if}

    <div id="editCaseRoleDialog" class="hiddenElement">
      <div><label for="edit_role_contact_id">{ts}Change To{/ts}:</label></div>
      <div><input name="edit_role_contact_id" placeholder="{ts}- select contact -{/ts}" class="huge" /></div>
    </div>

    <table id="caseRoles-selector-{$caseID}"  class="report-layout">
      <thead><tr>
        <th>{ts}Case Role{/ts}</th>
        <th>{ts}Name{/ts}</th>
        <th>{ts}Phone{/ts}</th>
        <th>{ts}Email{/ts}</th>
        {if $relId neq 'client' and $hasAccessToAllCases}
          <th id="nosort">{ts}Actions{/ts}</th>
        {/if}
      </tr></thead>
    </table>

    <div id="deleteCaseRoleDialog" class="hiddenElement">
     {ts}Are you sure you want to delete this case role?{/ts}
    </div>

  {literal}
  <script type="text/javascript">
  var oTable;

  CRM.$(function($) {
    buildCaseRoles(false);
    function buildCaseRoles(filterSearch) {
      if(filterSearch) {
        oTable.fnDestroy();
      }
      var count   = 0;
      var columns = '';
      var sourceUrl = {/literal}"{crmURL p='civicrm/ajax/caseroles' h=0 q='snippet=4&caseID='}{$caseID}"{literal};
      sourceUrl = sourceUrl + '&cid={/literal}{$contactID}{literal}';
      sourceUrl = sourceUrl + '&userID={/literal}{$userID}{literal}';

      $('#caseRoles-selector-{/literal}{$caseID}{literal} th').each( function( ) {
        if ( $(this).attr('id') != 'nosort' ) {
          columns += '{"sClass": "' + $(this).attr('class') +'"},';
        }
        else {
          columns += '{ "bSortable": false },';
        }
        count++;
      });

      columns    = columns.substring(0, columns.length - 1 );
      eval('columns =[' + columns + ']');

      oTable = $('#caseRoles-selector-{/literal}{$caseID}{literal}').dataTable({
        "bFilter"    : false,
        "bAutoWidth" : false,
        "aaSorting"  : [],
        "aoColumns"  : columns,
        "bProcessing": true,
        "bJQueryUI": true,
        "asStripClasses" : [ "odd-row", "even-row" ],
        "sPaginationType": "full_numbers",
        "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
        "bServerSide": true,
        "sAjaxSource": sourceUrl,
        "iDisplayLength": 10,
        "fnDrawCallback": function() { setCaseRolesSelectorClass(); },
        "fnServerData": function ( sSource, aoData, fnCallback ) {
          $.ajax({
            "dataType": 'json',
            "type": "POST",
            "url": sSource,
            "data": aoData,
            "success": fnCallback
          });
        }
      });   
    }

    function setCaseRolesSelectorClass( ) {
      $("#caseRoles-selector-{/literal}{$caseID}{literal} td:last-child").each( function( ) {
        $(this).parent().addClass($(this).text() );
      });
    }
  });
</script>
{/literal}
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

  {if $hasAccessToAllCases}
  <div class="crm-accordion-wrapper collapsed crm-case-other-relationships-block">
    <div class="crm-accordion-header">
      {ts}Other Relationships{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      {capture assign=relUrl}{crmURL p='civicrm/contact/view/rel' q="action=add&reset=1&cid=`$contactId`&caseID=`$caseID`"}{/capture}
      {if $clientRelationships}
        <div class="crm-submit-buttons">
          <a class="button" href="{$relUrl}">
          <div class="icon add-icon"></div>{ts}Add client relationship{/ts}</a>
        </div>
        <table id="clientRelationships-selector-{$caseID}"  class="report-layout">
          <thead><tr>
            <th>{ts}Client Relationship{/ts}</th>
            <th>{ts}Name{/ts}</th>
            <th>{ts}Phone{/ts}</th>
            <th>{ts}Email{/ts}</th>
          </tr></thead>
        </table>
        {else}
        <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
          {capture assign=link}class="action-item" href="{$relUrl}"{/capture}
          {ts 1=$link}There are no Relationships entered for this client. You can <a %1>add one</a>.{/ts}
        </div>
      {/if}
 {literal}
 <script type="text/javascript">
   CRM.$(function($) {
   buildCaseClientRelationships(false);
   function buildCaseClientRelationships(filterSearch) {
     if (filterSearch) {
       oTable.fnDestroy();
     }
     var count   = 0;
     var columns = '';
     var sourceUrl = {/literal}"{crmURL p='civicrm/ajax/clientrelationships' h=0 q='snippet=4&caseID='}{$caseID}"{literal};
     sourceUrl = sourceUrl + '&cid={/literal}{$contactID}{literal}';
     sourceUrl = sourceUrl + '&userID={/literal}{$userID}{literal}';

     $('#clientRelationships-selector-{/literal}{$caseID}{literal} th').each( function( ) {
       if ( $(this).attr('id') != 'nosort' ) {
         columns += '{"sClass": "' + $(this).attr('class') +'"},';
       }
       else {
         columns += '{ "bSortable": false },';
       }
       count++;
     });

     columns    = columns.substring(0, columns.length - 1 );
     eval('columns =[' + columns + ']');

     oTable = $('#clientRelationships-selector-{/literal}{$caseID}{literal}').dataTable({
       "bFilter"    : false,
       "bAutoWidth" : false,
       "aaSorting"  : [],
       "aoColumns"  : columns,
       "bProcessing": true,
       "bJQueryUI": true,
       "asStripClasses" : [ "odd-row", "even-row" ],
       "sPaginationType": "full_numbers",
       "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
       "bServerSide": true,
       "sAjaxSource": sourceUrl,
       "iDisplayLength": 10,
       "fnDrawCallback": function() { setClientRelationshipsSelectorClass(); },
       "fnServerData": function (sSource, aoData, fnCallback) {
         $.ajax( {
           "dataType": 'json',
           "type": "POST",
           "url": sSource,
           "data": aoData,
           "success": fnCallback
         });
       }
     });   
   }

   function setClientRelationshipsSelectorClass( ) {
     $("#clientRelationships-selector-{/literal}{$caseID}{literal} td:last-child").each(function() {
       $(this).parent().addClass($(this).text());
      });
    }
  });
 </script>
 {/literal}
  <br />
  {if !empty($globalGroupInfo.id)}
    <div class="crm-submit-buttons">
      <a class="button case-miniform" href="#addMembersToGroupDialog" rel="#globalRelationships-selector-{$caseId}" data-group_id="{$globalGroupInfo.id}">
        <div class="icon add-icon"></div>{ts 1=$globalGroupInfo.title}Add members to %1{/ts}
      </a>
    </div>
    <div id="addMembersToGroupDialog" class="hiddenElement">
      <input name="add_member_to_group_contact_id" placeholder="{ts}- select contacts -{/ts}" class="huge" />
    </div>
    <table id="globalRelationships-selector-{$caseId}"  class="report-layout">
      <thead><tr>
        <th>{$globalGroupInfo.title}</th>
        <th>{ts}Phone{/ts}</th>
        <th>{ts}Email{/ts}</th>
      </tr></thead>
    </table>
  {/if}

 {literal}
 <script type="text/javascript">
   CRM.$(function($) {
     buildCaseGlobalRelationships(false);
     function buildCaseGlobalRelationships(filterSearch) {
       if (filterSearch) {
         oTable.fnDestroy();
       }
       var count   = 0;
       var columns = '';
       var sourceUrl = {/literal}"{crmURL p='civicrm/ajax/globalrelationships' h=0 q='snippet=4&caseID='}{$caseID}"{literal};
       sourceUrl = sourceUrl + '&cid={/literal}{$contactID}{literal}';
       sourceUrl = sourceUrl + '&userID={/literal}{$userID}{literal}';

       $('#globalRelationships-selector-{/literal}{$caseID}{literal} th').each( function( ) {
         if ($(this).attr('id') != 'nosort') {
           columns += '{"sClass": "' + $(this).attr('class') +'"},';
         }
         else {
           columns += '{ "bSortable": false },';
         }
         count++;
       });

       columns    = columns.substring(0, columns.length - 1 );
       eval('columns =[' + columns + ']');

       oTable = $('#globalRelationships-selector-{/literal}{$caseID}{literal}').dataTable({
         "bFilter"    : false,
         "bAutoWidth" : false,
         "aaSorting"  : [],
         "aoColumns"  : columns,
         "bProcessing": true,
         "bJQueryUI": true,
         "asStripClasses" : [ "odd-row", "even-row" ],
         "sPaginationType": "full_numbers",
         "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
         "bServerSide": true,
         "sAjaxSource": sourceUrl,
         "iDisplayLength": 10,
         "fnDrawCallback": function() { setGlobalRelationshipsSelectorClass(); },
         "oLanguage": {
           "sEmptyTable": {/literal}'{ts escape='js' 1=$globalGroupInfo.title}The group %1 has no members.{/ts}'{literal}
         },
         "fnServerData": function ( sSource, aoData, fnCallback ) {
           $.ajax( {
             "dataType": 'json',
             "type": "POST",
             "url": sSource,
             "data": aoData,
             "success": fnCallback
           });
         }
       });
     }

     function setGlobalRelationshipsSelectorClass( ) {
       $("#globalRelationships-selector-{/literal}{$caseID}{literal} td:last-child").each( function( ) {
         $(this).parent().addClass($(this).text() );
       });
     }
   });
 </script>
 {/literal}
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

{/if} {* other relationship section ends *}
{include file="CRM/Case/Form/ActivityToCase.tpl"}

{* pane to display / edit regular tags or tagsets for cases *}
{if $showTags OR $showTagsets}

<div id="casetags" class="crm-accordion-wrapper  crm-case-tags-block">
 <div class="crm-accordion-header">
  {ts}Case Tags{/ts}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
  {assign var="tagExits" value=0}
  {if $tags}
    <div class="crm-block crm-content-block crm-case-caseview-display-tags">&nbsp;&nbsp;{$tags}</div>
    {assign var="tagExits" value=1}
  {/if}

   {foreach from=$tagsetInfo.case item=displayTagset}
     {if $displayTagset.entityTagsArray}
       <div class="crm-block crm-content-block crm-case-caseview-display-tagset">
         &nbsp;&nbsp;{$displayTagset.parentName}:
         {foreach from=$displayTagset.entityTagsArray item=val name="tagsetList"}
           &nbsp;{$val.name}{if !$smarty.foreach.tagsetList.last},{/if}
         {/foreach}
       </div>
       {assign var="tagExits" value=1}
     {/if}
   {/foreach}

   {if !$tagExits }
     <div class="status">
       {ts}There are no tags currently assigned to this case.{/ts}
     </div>
   {/if}

  <div class="crm-submit-buttons">
    <a class="button case-miniform" href="#manageTagsDialog" data-key="{crmKey name='civicrm/case/ajax/processtags'}">{if $tagExits}{ts}Edit Tags{/ts}{else}{ts}Add Tags{/ts}{/if}</a>
  </div>

 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

<div id="manageTagsDialog" class="hiddenElement">
  <div class="label">{$form.case_tag.label}</div>
  <div class="view-value"><div class="crm-select-container">{$form.case_tag.html}</div>
    <br/>
    <div style="text-align:left;">{include file="CRM/common/Tagset.tpl" tagsetType='case'}</div>
    <br/>
    <div class="clear"></div>
  </div>
</div>

{/if} {* end of tag block*}

{include file="CRM/Case/Form/ActivityTab.tpl"}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

  {include file="CRM/Case/Form/ActivityChangeStatusJs.tpl"}
{/if} {* view related cases if end *}
</div>

