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

<div class="crm-activity-selector-{$context}">
  <div class="crm-accordion-wrapper crm-search_filters-accordion">
    <div class="crm-accordion-header">
    {ts}Filter by Activity Type{/ts}</a>
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <div class="no-border form-layout-compressed" id="searchOptions">
          <div class="crm-contact-form-block-activity_type_filter_id crm-inline-edit-field">
            {$form.activity_type_filter_id.label} {$form.activity_type_filter_id.html|crmAddClass:big}
          </div>
          <div class="crm-contact-form-block-activity_type_exclude_filter_id crm-inline-edit-field">
            {$form.activity_type_exclude_filter_id.label} {$form.activity_type_exclude_filter_id.html|crmAddClass:big}
          </div>
      </div>
    </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
  <table class="contact-activity-selector-{$context}">
    <thead>
    <tr>
      <th class='crm-contact-activity-activity_type'>{ts}Type{/ts}</th>
      <th class='crm-contact-activity_subject'>{ts}Subject{/ts}</th>
      <th class='crm-contact-activity-source_contact'>{ts}Added By{/ts}</th>
      <th class='crm-contact-activity-target_contact nosort'>{ts}With{/ts}</th>
      <th class='crm-contact-activity-assignee_contact nosort'>{ts}Assigned{/ts}</th>
      <th class='crm-contact-activity-activity_date'>{ts}Date{/ts}</th>
      <th class='crm-contact-activity-activity_status'>{ts}Status{/ts}</th>
      <th class='crm-contact-activity-links nosort'>&nbsp;</th>
      <th class='hiddenElement'>&nbsp;</th>
    </tr>
    </thead>
  </table>
</div>
{include file="CRM/Case/Form/ActivityToCase.tpl" contactID=$contactId}
{literal}
<script type="text/javascript">
var {/literal}{$context}{literal}oTable;
CRM.$(function($) {
  var context = {/literal}"{$context}"{literal};
  var filterSearchOnLoad = false;
  if (context == 'activity') {
    filterSearchOnLoad = true;
  }
  buildContactActivities{/literal}{$context}{literal}( filterSearchOnLoad );

  $('.crm-activity-selector-'+ context +' #activity_type_filter_id').change( function( ) {
    buildContactActivities{/literal}{$context}{literal}( true );
  });

  $('.crm-activity-selector-'+ context +' #activity_type_exclude_filter_id').change( function( ) {
    buildContactActivities{/literal}{$context}{literal}( true );
  });

  function buildContactActivities{/literal}{$context}{literal}( filterSearch ) {
    if ( filterSearch && {/literal}{$context}{literal}oTable ) {
      {/literal}{$context}{literal}oTable.fnDestroy();
    }

    var context = {/literal}"{$context}"{literal};
    var columns = '';
    var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/contactactivity" h=0 q="snippet=4&context=$context&cid=$contactId"}'{literal};

    var ZeroRecordText = {/literal}'{ts escape="js"}No matches found{/ts}'{literal};
    if ( $('.crm-activity-selector-'+ context +' select#activity_type_filter_id').val( ) ) {
      ZeroRecordText += {/literal}'{ts escape="js"} for Activity Type = "{/ts}'{literal} +  $('.crm-activity-selector-'+ context +' select#activity_type_filter_id :selected').text( ) + '"';
    }
    else {
      ZeroRecordText += '.';
    }

    {/literal}{$context}{literal}oTable = $('.contact-activity-selector-' + context ).dataTable({
      "bFilter"    : false,
      "bAutoWidth" : false,
      "aaSorting"  : [],
      "aoColumns"  : [
        {sClass:'crm-contact-activity-activity_type'},
        {sClass:'crm-contact-activity_subject'},
        {sClass:'crm-contact-activity-source_contact'},
        {sClass:'crm-contact-activity-target_contact', bSortable:false},
        {sClass:'crm-contact-activity-assignee_contact', bSortable:false},
        {sClass:'crm-contact-activity-activity_date'},
        {sClass:'crm-contact-activity-activity_status'},
        {sClass:'crm-contact-activity-links', bSortable:false},
        {sClass:'hiddenElement', bSortable:false}
      ],
      "bProcessing": true,
      "sPaginationType": "full_numbers",
      "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
      "bServerSide": true,
      "bJQueryUI": true,
      "sAjaxSource": sourceUrl,
      "iDisplayLength": 25,
      "oLanguage": {
        "sZeroRecords":  ZeroRecordText,
        "sProcessing":   {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
        "sLengthMenu":   {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
        "sInfo":         {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
        "sInfoEmpty":    {/literal}"{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}"{literal},
        "sInfoFiltered": {/literal}"{ts escape='js'}(filtered from _MAX_ total entries){/ts}"{literal},
        "sSearch":       {/literal}"{ts escape='js'}Search:{/ts}"{literal},
        "oPaginate": {
          "sFirst":    {/literal}"{ts escape='js'}First{/ts}"{literal},
          "sPrevious": {/literal}"{ts escape='js'}Previous{/ts}"{literal},
          "sNext":     {/literal}"{ts escape='js'}Next{/ts}"{literal},
          "sLast":     {/literal}"{ts escape='js'}Last{/ts}"{literal}
        }
      },
      "fnDrawCallback": function() { setSelectorClass{/literal}{$context}{literal}( context ); },
      "fnServerData": function ( sSource, aoData, fnCallback ) {
          aoData.push( {name:'contact_id', value: {/literal}{$contactId}{literal}},
        {name:'admin',   value: {/literal}'{$admin}'{literal}}
        );

        if ( filterSearch ) {
          aoData.push(
            {name:'activity_type_id', value: $('.crm-activity-selector-'+ context +' select#activity_type_filter_id').val()},
            {name:'activity_type_exclude_id', value: $('.crm-activity-selector-'+ context +' select#activity_type_exclude_filter_id').val()}
          );
        }
        $.ajax( {
          "dataType": 'json',
          "type": "POST",
          "url": sSource,
          "data": aoData,
          "success": fnCallback,
          // CRM-10244
          "dataFilter": function(data, type) { return data.replace(/[\n\v\t]/g, " "); }
        });
      }
    });
  }

  function setSelectorClass{/literal}{$context}{literal}( context ) {
    $('.contact-activity-selector-' + context + ' td:last-child').each( function( ) {
      $(this).parent().addClass($(this).text() );
    });
  }
});
</script>
{/literal}
