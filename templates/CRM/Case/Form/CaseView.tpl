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
{* CiviCase -  view case screen*}

{* here we are showing related cases w/ jquery dialog *}
<div class="crm-block crm-form-block crm-case-caseview-form-block">
{if $showRelatedCases}
<table class="report">
  <tr class="columnheader">
    <th>{ts}Client Name{/ts}</th>
    <th>{ts}Case Type{/ts}</th>
    <th></th>
  </tr>

  {foreach from=$relatedCases item=row key=caseId}
    <tr>
      <td class="crm-case-caseview-client_name label">{$row.client_name}</td>
      <td class="crm-case-caseview-case_type label">{$row.case_type}</td>
      <td class="label">{$row.links}</td>
    </tr>
  {/foreach}
</table>

  {else}

<h3>{ts}Case Summary{/ts}</h3>
<table class="report">
  {if $multiClient}
    <tr class="crm-case-caseview-client">
      <td colspan="5" class="label">
        {ts}Clients:{/ts}
        {foreach from=$caseRoles.client item=client name=clients}
          <a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$client.contact_id`"}" title="{ts}view contact record{/ts}">{$client.display_name}</a>{if not $smarty.foreach.clients.last}, &nbsp; {/if}
        {/foreach}
        <a href="#" title="{ts}add new client to the case{/ts}" onclick="addClient( );return false;">
          <span class="icon edit-icon"></span>
        </a>
        {if $hasRelatedCases}
          <div class="crm-block relatedCases-link"><a href='#' onClick='viewRelatedCases( {$caseID}, {$contactID} ); return false;'>{ts}Related Cases{/ts}</a></div>
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
          <div class="crm-block relatedCases-link"><a href='#' onClick='viewRelatedCases( {$caseID}, {$contactID} ); return false;'>{ts}Related Cases{/ts}</a></div>
        {/if}
      </td>
    {/if}
    <td class="crm-case-caseview-case_subject label">
      <span class="crm-case-summary-label">{ts}Case Subject{/ts}:</span>&nbsp;{$caseDetails.case_subject}
    </td>
    <td class="crm-case-caseview-case_type label">
      <span class="crm-case-summary-label">{ts}Case Type{/ts}:</span>&nbsp;{$caseDetails.case_type}&nbsp;<a href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseTypeId`"}" title="{ts}Change case type (creates activity record){/ts}"><span class="icon edit-icon"></span></a>
    </td>
    <td class="crm-case-caseview-case_status label">
      <span class="crm-case-summary-label">{ts}Status{/ts}:</span>&nbsp;{$caseDetails.case_status}&nbsp;<a href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseStatusId`"}" title="{ts}Change case status (creates activity record){/ts}"><span class="icon edit-icon"></span></a>
    </td>
    <td class="crm-case-caseview-case_start_date label">
      <span class="crm-case-summary-label">{ts}Start Date{/ts}:</span>&nbsp;{$caseDetails.case_start_date|crmDate}&nbsp;<a href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseStartDateId`"}" title="{ts}Change case start date (creates activity record){/ts}"><span class="icon edit-icon"></span></a>
    </td>
    <td class="crm-case-caseview-{$caseID} label">
      <span class="crm-case-summary-label">{ts}Case ID{/ts}:</span>&nbsp;{$caseID}
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

<table class="form-layout">
<tr class="crm-case-caseview-form-block-activity_type_id">
  <td>{$form.activity_type_id.label}<br />{$form.activity_type_id.html}&nbsp;<input type="button" accesskey="N" value="{ts}Go{/ts}" name="new_activity" onclick="checkSelection( this );"/></td>
  {if $hasAccessToAllCases}
    <td>
      <span class="crm-button"><div class="icon print-icon"></div><input type="button"  value="{ts}Print Case Report{/ts}" name="case_report_all" onclick="printCaseReport( );"/></span>
    </td>
  </tr>
  <tr>
    <td class="crm-case-caseview-form-block-timeline_id">{$form.timeline_id.label}<br />{$form.timeline_id.html}&nbsp;{$form._qf_CaseView_next.html}</td>
    <td class="crm-case-caseview-form-block-report_id">{$form.report_id.label}<br />{$form.report_id.html}&nbsp;<input type="button" accesskey="R" value="Go" name="case_report" onclick="checkSelection( this );"/></td>
    {else}
    <td></td>
  {/if}
</tr>

  {if $mergeCases}
    <tr class="crm-case-caseview-form-block-merge_case_id">
      <td colspan='2'><a href="#" onClick='cj("#merge_cases").toggle( ); return false;'>{ts}Merge Case{/ts}</a>
        <span id='merge_cases' class='hide-block'>
          {$form.merge_case_id.html}&nbsp;{$form._qf_CaseView_next_merge_case.html}
        </span>
      </td>
    </tr>
  {/if}

  {if call_user_func(array('CRM_Core_Permission','giveMeAllACLs'))}
    <tr class="crm-case-caseview-form-block-change_client_id">
      <td colspan='2'><a href="#" onClick='cj("#change_client").toggle( ); return false;'>{ts}Assign to Another Client{/ts}</a>
          <span id='change_client' class='hide-block'>
            {$form.change_client_id.html|crmAddClass:twenty}&nbsp;{$form._qf_CaseView_next_edit_client.html}
          </span>
      </td>
    </tr>
  {/if}
</table>

<div id="view-related-cases">
  <div id="related-cases-content"></div>
</div>

<div class="clear"></div>
{include file="CRM/Case/Page/CustomDataView.tpl"}

<div class="crm-accordion-wrapper collapsed crm-case-roles-block">
  <div class="crm-accordion-header">
    {ts}Case Roles{/ts}
  </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">

    {if $hasAccessToAllCases}
      <div class="crm-submit-buttons">
        <a class="button" href="#" onclick="addRole();return false;"><span><div class="icon add-icon"></div>{ts}Add new role{/ts}</span></a>
      </div>
    {/if}

    <table id="caseRoles-selector"  class="report-layout">
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

  {literal}
  <script type="text/javascript">
  var oTable;

  cj(function() {
    cj().crmAccordions();
    buildCaseRoles(false);
  });

  function deleteCaseRoles(caseselector) {
    cj('.case-role-delete').click(function(){
      var caseID = cj(this).attr('case_id');
      var relType  = cj(this).attr('rel_type');

      CRM.confirm(function() {
        var postUrl = {/literal}"{crmURL p='civicrm/ajax/delcaserole' h=0 }"{literal};
        cj.post( postUrl, {
          rel_type: relType, case_id: caseID, key: {/literal}"{crmKey name='civicrm/ajax/delcaserole'}"{literal}},
          function(data) {
            // reloading datatable
            var oTable = cj('#' + caseselector).dataTable();
            oTable.fnDraw();
          }
        );
      }
      ,{
        title: '{/literal}{ts escape="js"}Delete case role{/ts}{literal}',
        message: '{/literal}{ts escape="js"}Are you sure you want to delete this case role?{/ts}{literal}'
      });
      return false;
    });
  }

  function buildCaseRoles(filterSearch) {
    if(filterSearch) {
      oTable.fnDestroy();
    }
    var count   = 0;
    var columns = '';
    var sourceUrl = {/literal}"{crmURL p='civicrm/ajax/caseroles' h=0 q='snippet=4&caseID='}{$caseID}"{literal};
    sourceUrl = sourceUrl + '&cid={/literal}{$contactID}{literal}';
    sourceUrl = sourceUrl + '&userID={/literal}{$userID}{literal}';

    cj('#caseRoles-selector th').each( function( ) {
      if ( cj(this).attr('id') != 'nosort' ) {
        columns += '{"sClass": "' + cj(this).attr('class') +'"},';
      }
      else {
        columns += '{ "bSortable": false },';
      }
      count++;
    });

    columns    = columns.substring(0, columns.length - 1 );
    eval('columns =[' + columns + ']');

    oTable = cj('#caseRoles-selector').dataTable({
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
        cj.ajax({
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
    cj("#caseRoles-selector td:last-child").each( function( ) {
      cj(this).parent().addClass(cj(this).text() );
    });

    // also bind delete action once rows are rendered
    deleteCaseRoles('caseRoles-selector');
  }

  function printCaseReport( ) {
    var asn = 'standard_timeline';
    var dataUrl = {/literal}"{crmURL p='civicrm/case/report/print' q='all=1&redact=0' h='0'}"{literal};
    dataUrl     = dataUrl + '&cid={/literal}{$contactID}{literal}'
    + '&caseID={/literal}{$caseID}{literal}'
    + '&asn={/literal}' + asn + '{literal}';

    window.location = dataUrl;
  }
</script>
{/literal}
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
<div id="dialog">
  {ts}Begin typing last name of contact.{/ts}<br/>
  <input type="text" id="rel_contact"/>
  <input type="hidden" id="rel_contact_id" value="">
</div>

{literal}
<script type="text/javascript">
  var selectedContact = '';
  var caseID = {/literal}"{$caseID}"{literal};
  var contactUrl = {/literal}"{crmURL p='civicrm/ajax/rest' q='className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=newcontact' h=0 }"{literal};
  cj( "#change_client_id").autocomplete( contactUrl, { width : 250, selectFirst : false, matchContains:true
  }).result( function(event, data, formatted) { cj( "#contact_id" ).val( data[1] ); selectedContact = data[0];
    }).bind( 'click', function( ) { cj( "#contact_id" ).val(''); });

  cj("#dialog").hide( );

  function addClient( ) {
    cj("#dialog").show( );

    cj("#dialog").dialog({
      title: "Add Client to the Case",
      modal: true,
      bgiframe: true,
      close  : function(event, ui) { cj("#rel_contact").unautocomplete( ); },
      overlay: { opacity: 0.5, background: "black" },

      open:function() {
      var contactUrl = {/literal}"{crmURL p='civicrm/ajax/rest' q='className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=caseview' h=0 }"{literal};
        cj("#rel_contact").autocomplete( contactUrl, {
          width: 260,
          selectFirst: false,
          matchContains: true
        });

        cj("#rel_contact").focus();
        cj("#rel_contact").result(function(event, data, formatted) {
          cj("input[id=rel_contact_id]").val(data[1]);
        });
      },

      buttons: {
      "Done": function() {
        var postUrl = {/literal}"{crmURL p='civicrm/case/ajax/addclient' h=0 }"{literal};
        var caseID        = {/literal}"{$caseID}"{literal};
        var contactID = cj("#rel_contact_id").val( );

        if ( !cj("#rel_contact").val( ) || !contactID ) {
          cj("#rel_contact").crmError('{/literal}{ts escape="js"}Select valid contact from the list{/ts}{literal}.');
          return false;
        }
        cj.post( postUrl, {contactID: contactID,caseID: caseID,
          key: {/literal}"{crmKey name='civicrm/case/ajax/addclient'}"{literal} },
          function( data ) {
            //due to caching issues we use redirection rather than reload
            document.location = {/literal}'{crmURL q="action=view&reset=1&id=$caseID&cid=$contactID&context=$context" h=0 }'{literal};
          },
          'json'
        );
        },

        "Cancel": function() {
          cj(this).dialog("close");
          cj(this).dialog("destroy");
        }
      }
    });
  }

  function createRelationship( relType, contactID, relID, rowNumber, relTypeName ) {
    cj("#dialog").show( );

    cj("#dialog").dialog({
      title: "Assign Case Role",
      modal: true,
      bgiframe: true,
      close: function(event, ui) { cj("#rel_contact").unautocomplete( ); },
      overlay: {
        opacity: 0.5,
        background: "black"
      },

      open:function() {
        /* set defaults if editing */
        cj("#rel_contact").val("");
        cj("#rel_contact_id").val(null);
        if (contactID) {
          cj("#rel_contact_id").val(contactID);
          var contactName = cj('#caseRoles-selector').find('tr :eq('+ rowNumber +')').children(':eq(1)').text();
          cj("#rel_contact").val(contactName);
        }

        var contactUrl = {/literal}"{crmURL p='civicrm/ajax/rest' q='className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=caseview' h=0 }"{literal};

        cj("#rel_contact").autocomplete( contactUrl, {
          width: 260,
          selectFirst: false,
          matchContains: true
        });

        cj("#rel_contact").focus();
        cj("#rel_contact").result(function(event, data, formatted) {
          cj("input[id=rel_contact_id]").val(data[1]);
        });
      },

      buttons: {
        "Ok": function() {

          var sourceContact = {/literal}"{$contactID}"{literal};
          var caseID        = {/literal}"{$caseID}"{literal};

          var v1 = cj("#rel_contact_id").val( );

          if ( !cj("#rel_contact").val( ) || !v1 ) {
            cj("#rel_contact").crmError('{/literal}{ts escape="js"}Select valid contact from the list{/ts}{literal}.');
            return false;
          }

          var postUrl = {/literal}"{crmURL p='civicrm/ajax/relation' h=0 }"{literal};
          cj.post( postUrl, { rel_contact: v1, rel_type: relType, contact_id: sourceContact,
            rel_id: relID, case_id: caseID, key: {/literal}"{crmKey name='civicrm/ajax/relation'}"{literal} },
            function( data ) {
              if ( data.status == 'process-relationship-success' ) {
                // reloading datatable
                var oTable = cj('#caseRoles-selector').dataTable();
                oTable.fnDraw();
              }
              else {
                var relTypeAdminLink = {/literal}"{crmURL p='civicrm/admin/reltype' q='reset=1' h=0 }"{literal};
                var errorMsg = '{/literal}{ts escape="js" 1="' + relTypeName + '" 2="' + relTypeAdminLink + '"}The relationship type definition for the %1 case role is not valid for the client and / or staff contact types. You can review and edit relationship types at <a href="%2">Administer >> Option Lists >> Relationship Types</a>{/ts}{literal}.';

                //display error message.
                cj().crmError(errorMsg);
              }
            }, 'json'
          );

          cj(this).dialog("close");
          cj(this).dialog("destroy");
        },

        "Cancel": function() {
          cj(this).dialog("close");
          cj(this).dialog("destroy");
        }
      }
    });
  }

  function viewRelatedCases( mainCaseID, contactID ) {
    cj("#view-related-cases").show( );
    cj("#view-related-cases").dialog({
      title: "Related Cases",
      modal: true,
      width : "680px",
      height: 'auto',
      resizable: true,
      bgiframe: true,
      overlay: {
        opacity: 0.5,
        background: "black"
      },

      beforeclose: function(event, ui) {
        cj(this).dialog("destroy");
      },

      open:function() {
        var dataUrl = {/literal}"{crmURL p='civicrm/contact/view/case' h=0 q="snippet=4" }"{literal};
          dataUrl = dataUrl + '&id=' + mainCaseID + '&cid=' +contactID + '&relatedCases=true&action=view&context=case&selectedChild=case';

          cj.ajax({
            url     : dataUrl,
            async   : false,
            success : function(html){
              cj("#related-cases-content" ).html( html );
            }
          });
        },

      buttons: {
        "Done": function() {
          cj(this).dialog("close");
          cj(this).dialog("destroy");
        }
      }
    });
  }

cj(function(){
   cj("#view-activity").hide( );
});
</script>
{/literal}

  {if $hasAccessToAllCases}
  <div class="crm-accordion-wrapper collapsed crm-case-other-relationships-block">
    <div class="crm-accordion-header">
      {ts}Other Relationships{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">

      {if $clientRelationships}
        <div class="crm-submit-buttons">
          <a class="button" href="#" onClick="window.location='{crmURL p='civicrm/contact/view/rel' q="action=add&reset=1&cid=`$contactId`&caseID=`$caseID`"}'; return false;">
          <span><div class="icon add-icon"></div>{ts}Add client relationship{/ts}</a></span>
        </div>
        <table id="clientRelationships-selector"  class="report-layout">
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
          {capture assign=crmURL}{crmURL p='civicrm/contact/view/rel' q="action=add&reset=1&cid=`$contactId`&caseID=`$caseID`"}{/capture}
          {ts 1=$crmURL}There are no Relationships entered for this client. You can <a accesskey="N" href='%1'>add one</a>.{/ts}
        </div>
      {/if}
 {literal}
 <script type="text/javascript">
   cj(function( ) {
      buildCaseClientRelationships(false);
   });

 function buildCaseClientRelationships(filterSearch) {
   if (filterSearch) {
     oTable.fnDestroy();
   }
   var count   = 0;
   var columns = '';
   var sourceUrl = {/literal}"{crmURL p='civicrm/ajax/clientrelationships' h=0 q='snippet=4&caseID='}{$caseID}"{literal};
   sourceUrl = sourceUrl + '&cid={/literal}{$contactID}{literal}';
   sourceUrl = sourceUrl + '&userID={/literal}{$userID}{literal}';

    cj('#clientRelationships-selector th').each( function( ) {
      if ( cj(this).attr('id') != 'nosort' ) {
        columns += '{"sClass": "' + cj(this).attr('class') +'"},';
      }
      else {
        columns += '{ "bSortable": false },';
      }
      count++;
    });

    columns    = columns.substring(0, columns.length - 1 );
    eval('columns =[' + columns + ']');

    oTable = cj('#clientRelationships-selector').dataTable({
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
        cj.ajax( {
          "dataType": 'json',
          "type": "POST",
          "url": sSource,
          "data": aoData,
          "success": fnCallback
        } );
      }
    });
 }

  function setClientRelationshipsSelectorClass( ) {
    cj("#clientRelationships-selector td:last-child").each(function() {
      cj(this).parent().addClass(cj(this).text());
    });
  }
 </script>
 {/literal}
  <br />

  {if $globalRelationships}
    <div class="crm-submit-buttons">
      <a class="button" href="#"  onClick="window.location='{crmURL p='civicrm/group/search' q="reset=1&context=amtg&amtgID=`$globalGroupInfo.id`"}'; return false;">
      <span><div class="icon add-icon"></div>{ts 1=$globalGroupInfo.title}Add members to %1{/ts}</a></span>
    </div>
    <table id="globalRelationships-selector"  class="report-layout">
      <thead><tr>
        <th>{$globalGroupInfo.title}</th>
        <th>{ts}Phone{/ts}</th>
        <th>{ts}Email{/ts}</th>
      </tr></thead>
    </table>
    {elseif $globalGroupInfo.id}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>&nbsp;
      {capture assign=crmURL}{crmURL p='civicrm/group/search' q="reset=1&context=amtg&amtgID=`$globalGroupInfo.id`"}{/capture}
      {ts 1=$crmURL 2=$globalGroupInfo.title}The group %2 has no members. You can <a href='%1'>add one</a>.{/ts}
    </div>
  {/if}

 {literal}
 <script type="text/javascript">

 cj(function() {
    buildCaseGlobalRelationships(false);
 });

 function buildCaseGlobalRelationships(filterSearch) {
   if (filterSearch) {
     oTable.fnDestroy();
   }
   var count   = 0;
   var columns = '';
   var sourceUrl = {/literal}"{crmURL p='civicrm/ajax/globalrelationships' h=0 q='snippet=4&caseID='}{$caseID}"{literal};
   sourceUrl = sourceUrl + '&cid={/literal}{$contactID}{literal}';
   sourceUrl = sourceUrl + '&userID={/literal}{$userID}{literal}';

    cj('#globalRelationships-selector th').each( function( ) {
      if (cj(this).attr('id') != 'nosort') {
        columns += '{"sClass": "' + cj(this).attr('class') +'"},';
      }
      else {
        columns += '{ "bSortable": false },';
      }
      count++;
    });

    columns    = columns.substring(0, columns.length - 1 );
    eval('columns =[' + columns + ']');

    oTable = cj('#globalRelationships-selector').dataTable({
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
      "fnServerData": function ( sSource, aoData, fnCallback ) {
        cj.ajax( {
          "dataType": 'json',
          "type": "POST",
          "url": sSource,
          "data": aoData,
          "success": fnCallback
        } );
      }
    });
 }

  function setGlobalRelationshipsSelectorClass( ) {
    cj("#globalRelationships-selector td:last-child").each( function( ) {
      cj(this).parent().addClass(cj(this).text() );
    });
  }
 </script>
 {/literal}
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

{/if} {* other relationship section ends *}

<div id="addRoleDialog">
{$form.role_type.label}<br />
{$form.role_type.html}
<br /><br />
    {ts}Begin typing last name of contact.{/ts}<br/>
    <input type="text" id="role_contact"/>
    <input type="hidden" id="role_contact_id" value="">
</div>

{literal}
<script type="text/javascript">

cj("#addRoleDialog").hide( );
function addRole() {
  cj("#addRoleDialog").show( );

  cj("#addRoleDialog").dialog({
    title: "Add Role",
    modal: true,
    bgiframe: true,
    close: function(event, ui) { cj("#role_contact").unautocomplete( ); },
    overlay: {
      opacity: 0.5,
      background: "black"
    },

    open:function() {
      /* set defaults if editing */
      cj("#role_contact").val( "" );
      cj("#role_contact_id").val( null );

      var contactUrl = {/literal}"{crmURL p='civicrm/ajax/rest' q='className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=caseview' h=0 }"{literal};

      cj("#role_contact").autocomplete( contactUrl, {
        width: 260,
        selectFirst: false,
        matchContains: true
      });

      cj("#role_contact").focus();
      cj("#role_contact").result(function(event, data, formatted) {
        cj("input[id=role_contact_id]").val(data[1]);
      });
    },

    buttons: {
      "Ok": function() {
        var sourceContact = {/literal}"{$contactID}"{literal};
        var caseID        = {/literal}"{$caseID}"{literal};
        var relID         = null;

        var v2 = cj("#role_type").val();
        if (!v2) {
          cj("#role_type").crmError('{/literal}{ts escape="js"}Select valid type from the list{/ts}{literal}.');
          return false;
        }

        var v1 = cj("#role_contact_id").val( );
        if (!cj("#role_contact").val( ) || !v1) {
          cj("#role_contact").crmError('{/literal}{ts escape="js"}Select valid contact from the list{/ts}{literal}.');
          return false;
        }

        /* send synchronous request so that disabling any actions for slow servers*/
        var postUrl = {/literal}"{crmURL p='civicrm/ajax/relation' h=0 }"{literal};
        var data = 'rel_contact='+ v1 + '&rel_type='+ v2 + '&contact_id='+sourceContact + '&rel_id='+ relID
          + '&case_id=' + caseID + "&key={/literal}{crmKey name='civicrm/ajax/relation'}{literal}";
        cj.ajax({
          type     : "POST",
          url      : postUrl,
          data     : data,
          async    : false,
          dataType : "json",
          success  : function(values) {
            if (values.status == 'process-relationship-success') {
              // reloading datatable
              var oTable = cj('#caseRoles-selector').dataTable();
              oTable.fnDraw();
            }
            else {
              var relTypeName = cj("#role_type :selected").text();
              var relTypeAdminLink = {/literal}"{crmURL p='civicrm/admin/reltype' q='reset=1' h=0 }"{literal};
              var errorMsg = '{/literal}{ts escape="js" 1="' + relTypeName + '" 2="' + relTypeAdminLink + '"}The relationship type definition for the %1 case role is not valid for the client and / or staff contact types. You can review and edit relationship types at <a href="%2">Administer >> Option Lists >> Relationship Types</a>{/ts}{literal}.';

              //display error message.
              cj().crmError(errorMsg);
            }
          }
        });

        cj(this).dialog("close");
        cj(this).dialog("destroy");
      },

      "Cancel": function() {
        cj(this).dialog("close");
        cj(this).dialog("destroy");
      }
    }
  });
}

</script>
{/literal}
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
    <div class="crm-block crm-content-block crm-case-caseview-display-tags">{$tags}</div>
    {assign var="tagExits" value=1}
  {/if}

   {foreach from=$tagsetInfo_case item=displayTagset}
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

  <div class="crm-submit-buttons"><input type="button" class="form-submit" onClick="javascript:addTags()" value={if $tagExits}"{ts}Edit Tags{/ts}"{else}"{ts}Add Tags{/ts}"{/if} /></div>

 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

<div id="manageTags">
  <div class="label">{$form.case_tag.label}</div>
  <div class="view-value"><div class="crm-select-container">{$form.case_tag.html}</div>
    <br/>
    <div style="text-align:left;">{include file="CRM/common/Tag.tpl" tagsetType='case'}</div>
    <br/>
    <div class="clear"></div>
  </div>
</div>

{literal}
<script type="text/javascript">
cj("#manageTags select[multiple]").crmasmSelect({
  addItemTarget: 'bottom',
  animate: true,
  highlight: true,
  sortable: true,
  respectParents: true
});

cj("#manageTags").hide( );
function addTags() {
  cj("#manageTags").show( );

  cj("#manageTags").dialog({
    title: "{/literal}{ts escape='js'}Change Case Tags{/ts}{literal}",
    modal: true,
    height: 'auto',
    width: 'auto',
    buttons: {
      "Save": function() {
        var tagsChecked = '';
        var caseID      = {/literal}{$caseID}{literal};

        cj("#manageTags #tags option").each( function() {
          if (cj(this).prop('selected')) {
            if (!tagsChecked) {
              tagsChecked = cj(this).val() + '';
            }
            else {
              tagsChecked = tagsChecked + ',' + cj(this).val();
            }
          }
        });

        var tagList = '';
        cj("#manageTags input[name^=case_taglist]").each(function( ) {
          if (!tagsChecked) {
            tagsChecked = cj(this).val() + '';
          }
          else {
            tagsChecked = tagsChecked + ',' + cj(this).val();
          }
        });

        var postUrl = {/literal}"{crmURL p='civicrm/case/ajax/processtags' h=0 }"{literal};
        var data = 'case_id=' + caseID + '&tag=' + tagsChecked + '&key=' + {/literal}"{crmKey name='civicrm/case/ajax/processtags'}"{literal};

        cj.ajax({ type: "POST", url: postUrl, data: data, async: false });
        cj(this).dialog("close");
        cj(this).dialog("destroy");

        // Temporary workaround for problems with SSL connections being too
        // slow. The relationship doesn't get created because the page reload
        // happens before the ajax call.
        // In general this reload needs improvement, which is already on the list for phase 2.
        var sdate = (new Date()).getTime();
        var curDate = sdate;
        while(curDate-sdate < 2000) {
          curDate = (new Date()).getTime();
        }

        //due to caching issues we use redirection rather than reload
        document.location = {/literal}'{crmURL q="action=view&reset=1&id=$caseID&cid=$contactID&context=$context" h=0 }'{literal};
      },

      "Cancel": function() {
        cj(this).dialog("close");
        cj(this).dialog("destroy");
      }
    }
  });
}

</script>
{/literal}

{/if} {* end of tag block*}

{*include activity view js file*}
{include file="CRM/common/activityView.tpl"}

<div class="crm-accordion-wrapper crm-case_activities-accordion  crm-case-activities-block">
  <div class="crm-accordion-header">
    {ts}Case Activities{/ts}
  </div>
  <div id="activities" class="crm-accordion-body">
    <div id="view-activity">
      <div id="activity-content"></div>
    </div>
    <div class="crm-accordion-wrapper crm-accordion-inner crm-search_filters-accordion collapsed">
      <div class="crm-accordion-header">
        {ts}Search Filters{/ts}</a>
      </div><!-- /.crm-accordion-header -->
      <div class="crm-accordion-body">
        <table class="no-border form-layout-compressed" id="searchOptions">
          <tr>
            <td class="crm-case-caseview-form-block-repoter_id"colspan="2"><label for="reporter_id">{ts}Reporter/Role{/ts}</label><br />
              {$form.reporter_id.html|crmAddClass:twenty}
            </td>
            <td class="crm-case-caseview-form-block-status_id"><label for="status_id">{$form.status_id.label}</label><br />
              {$form.status_id.html}
            </td>
            <td style="vertical-align: bottom;">
              <span class="crm-button"><input class="form-submit default" name="_qf_Basic_refresh" value="Search" type="button" onclick="buildCaseActivities( true )"; /></span>
            </td>
          </tr>
          <tr>
            <td class="crm-case-caseview-form-block-activity_date_low">
              {$form.activity_date_low.label}<br />
            {include file="CRM/common/jcalendar.tpl" elementName=activity_date_low}
            </td>
            <td class="crm-case-caseview-form-block-activity_date_high">
              {$form.activity_date_high.label}<br />
            {include file="CRM/common/jcalendar.tpl" elementName=activity_date_high}
            </td>
            <td class="crm-case-caseview-form-block-activity_type_filter_id">
              {$form.activity_type_filter_id.label}<br />
              {$form.activity_type_filter_id.html}
            </td>
          </tr>
          {if $form.activity_deleted}
            <tr class="crm-case-caseview-form-block-activity_deleted">
              <td>
                {$form.activity_deleted.html}{$form.activity_deleted.label}
              </td>
            </tr>
          {/if}
        </table>
      </div><!-- /.crm-accordion-body -->
    </div><!-- /.crm-accordion-wrapper -->

    <table id="activities-selector"  class="nestedActivitySelector">
      <thead><tr>
        <th class='crm-case-activities-date'>{ts}Date{/ts}</th>
        <th class='crm-case-activities-subject'>{ts}Subject{/ts}</th>
        <th class='crm-case-activities-type'>{ts}Type{/ts}</th>
        <th class='crm-case-activities-with'>{ts}With{/ts}</th>
        <th class='crm-case-activities-assignee'>{ts}Reporter / Assignee{/ts}</th>
        <th class='crm-case-activities-status'>{ts}Status{/ts}</th>
        <th class='crm-case-activities-status' id="nosort">&nbsp;</th>
        <th class='hiddenElement'>&nbsp;</th>
      </tr></thead>
    </table>

  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

{literal}
<script type="text/javascript">
var oTable;

function checkSelection( field ) {
  var validationMessage = '';
  var validationField   = '';
  var successAction     = '';
  var forceValidation   = false;

  var clientName = new Array( );
  clientName = selectedContact.split('::');
  var fName = field.name;

  switch ( fName )  {
  case '_qf_CaseView_next' :
    validationMessage = '{/literal}{ts escape="js"}Please select an activity set from the list.{/ts}{literal}';
    validationField   = 'timeline_id';
    successAction     = "confirm('{/literal}{ts escape='js'}Are you sure you want to add a set of scheduled activities to this case?{/ts}{literal}');";
    break;

  case 'new_activity' :
    validationMessage = '{/literal}{ts escape="js"}Please select an activity type from the list.{/ts}{literal}';
    validationField   = 'activity_type_id';
    if ( document.getElementById('activity_type_id').value == 3 ) {
      successAction = "window.location='{/literal}{$newActivityEmailUrl}{literal}' + document.getElementById('activity_type_id').value";
    }
    else {
      successAction = "window.location='{/literal}{$newActivityUrl}{literal}' + document.getElementById('activity_type_id').value";
    }
    break;

  case 'case_report' :
    validationMessage = '{/literal}{ts escape="js"}Please select a report from the list.{/ts}{literal}';
    validationField   = 'report_id';
    successAction     = "window.location='{/literal}{$reportUrl}{literal}' + document.getElementById('report_id').value";
    break;

  case '_qf_CaseView_next_merge_case' :
    validationMessage = '{/literal}{ts escape="js"}Please select a case from the list to merge with.{/ts}{literal}';
    validationField   = 'merge_case_id';
    break;

  case '_qf_CaseView_next_edit_client' :
    validationMessage = '{/literal}{ts escape="js"}Please select a client for this case.{/ts}{literal}';
    if ( cj('#contact_id').val( ) == '{/literal}{$contactID}{literal}' ) {
      forceValidation = true;
      validationMessage = '{/literal}{ts 1="'+clientName[0]+'"}%1 is already assigned to this case. Please select some other client for this case.{/ts}{literal}';
    }
    validationField   = 'change_client_id';
    successAction     = "confirm( '{/literal}{ts 1="'+clientName[0]+'"}Are you sure you want to reassign this case and all related activities and relationships to %1?{/ts}{literal}' )";
    break;
  }

  if ( forceValidation || ( document.getElementById( validationField ).value == '' ) ) {
    cj('#'+validationField).crmError(validationMessage);
    return false;
  }
  else if ( successAction ) {
    return eval( successAction );
  }
}

cj(function( ) {
  buildCaseActivities(false);
});

function buildCaseActivities(filterSearch) {
  if (filterSearch) {
    oTable.fnDestroy();
  }
  var count   = 0;
  var columns = '';
  var sourceUrl = {/literal}"{crmURL p='civicrm/ajax/activity' h=0 q='snippet=4&caseID='}{$caseID}"{literal};
  sourceUrl = sourceUrl + '&cid={/literal}{$contactID}{literal}';
  sourceUrl = sourceUrl + '&userID={/literal}{$userID}{literal}';

  cj('#activities-selector th').each(function( ) {
    if (cj(this).attr('id') != 'nosort') {
      columns += '{"sClass": "' + cj(this).attr('class') +'"},';
    }
    else {
      columns += '{ "bSortable": false },';
    }
    count++;
  });

  columns    = columns.substring(0, columns.length - 1 );
  eval('columns =[' + columns + ']');

  oTable = cj('#activities-selector').dataTable({
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
    "fnDrawCallback": function() { setSelectorClass(); },
    "fnServerData": function ( sSource, aoData, fnCallback ) {

      if ( filterSearch ) {
        var activity_deleted = 0;
        if ( cj("#activity_deleted:checked").val() == 1 ) {
          activity_deleted = 1;
        }
        aoData.push(
          {name:'status_id', value: cj("select#status_id").val()},
          {name:'activity_type_id', value: cj("select#activity_type_filter_id").val()},
          {name:'activity_date_low', value: cj("#activity_date_low").val()},
          {name:'activity_date_high', value: cj("#activity_date_high").val() },
          {name:'activity_deleted', value: activity_deleted }
        );
      }
      cj.ajax( {
        "dataType": 'json',
        "type": "POST",
        "url": sSource,
        "data": aoData,
        "success": fnCallback
      } );
    }
  });
}

function setSelectorClass( ) {
  cj("#activities-selector td:last-child").each( function( ) {
    cj(this).parent().addClass(cj(this).text() );
  });
}

function printCaseReport( ) {
  var asn = 'standard_timeline';
  var dataUrl = {/literal}"{crmURL p='civicrm/case/report/print' q='all=1&redact=0' h='0'}"{literal};
  dataUrl     = dataUrl + '&cid={/literal}{$contactID}{literal}'
  + '&caseID={/literal}{$caseID}{literal}'
  + '&asn={/literal}' + asn + '{literal}';

  window.location = dataUrl;
}

</script>
{/literal}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

{literal}
<script type="text/javascript">
  cj(function() {
    cj().crmAccordions();
  });
</script>
{/literal}

{/if} {* view related cases if end *}
</div>
