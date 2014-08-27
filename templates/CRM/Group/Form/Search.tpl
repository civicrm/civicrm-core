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
<div class="crm-block crm-form-block crm-group-search-form-block">

<h3>{ts}Find Groups{/ts}</h3>
<table class="form-layout">
  <tr>
    <td>
      {$form.title.label}<br />
      {$form.title.html}<br />
      <span class="description font-italic">
          {ts}Complete OR partial group name.{/ts}
      </span>
    </td>
    <td>
      {$form.created_by.label}<br />
      {$form.created_by.html}<br />
      <span class="description font-italic">
          {ts}Complete OR partial creator name.{/ts}
      </span>
    </td>
    <td id="group_type-block">
      {$form.group_type.label}<br />
      {$form.group_type.html}<br />
      <span class="description font-italic">
          {ts}Filter search by group type(s).{/ts}
      </span>
    </td>
    <td>
      {$form.visibility.label}<br />
      {$form.visibility.html}<br />
      <span class="description font-italic">
          {ts}Filter search by visibility.{/ts}
      </span>
    </td>
    <td>
      {$form.group_status.label}<br />
      {$form.group_status.html}
    </td>
  </tr>
  <tr>
     <td>{$form.buttons.html}</td><td colspan="2">
  </tr>
</table>
</div>
<div class="css_right">
  <a class="crm-hover-button action-item" href="{crmURL q="reset=1&update_smart_groups=1"}">{ts}Update Smart Group Counts{/ts}</a> {help id="update_smart_groups"}
</div>
<table class="crm-group-selector">
  <thead>
    <tr>
      <th class='crm-group-name'>{ts}Name{/ts}</th>
      <th class='crm-group-count'>{ts}Count{/ts}</th>
      <th class='crm-group-created_by'>{ts}Created By{/ts}</th>
      <th class='crm-group-description'>{ts}Description{/ts}</th>
      <th class='crm-group-group_type'>{ts}Group Type{/ts}</th>
      <th class='crm-group-visibility'>{ts}Visibility{/ts}</th>
      {if $showOrgInfo}
      <th class='crm-group-org_info'>{ts}Organization{/ts}</th>
      {/if}
      <th class='crm-group-group_links nosort'>&nbsp;</th>
      <th class='hiddenElement'>&nbsp;</th>
    </tr>
  </thead>
</table>

{* handle enable/disable actions*}
{include file="CRM/common/enableDisableApi.tpl"}
{include file="CRM/common/crmeditable.tpl"}

{literal}
<script type="text/javascript">
CRM.$(function($) {
  // for CRM-11310 and CRM-10635 : processing just parent groups on initial display
  // passing '1' for parentsOnlyArg to show parent child heirarchy structure display
  // on initial load of manage group page and
  // also to handle search filtering for initial load of same page.
  buildGroupSelector(true, 1);
  $('#_qf_Search_refresh').click( function() {
    buildGroupSelector( true );
  });
  // Add livePage functionality
  $('#crm-container')
    .on('click', 'a.button, a.action-item[href*="action=update"], a.action-item[href*="action=delete"]', CRM.popup)
    .on('crmPopupFormSuccess', 'a.button, a.action-item[href*="action=update"], a.action-item[href*="action=delete"]', function() {
        // Refresh datatable when form completes
      	var $context = $('#crm-main-content-wrapper');
        $('table.crm-group-selector', $context).dataTable().fnDraw();
    });

  function buildGroupSelector( filterSearch, parentsOnlyArg ) {
    if ( filterSearch ) {
      if (typeof crmGroupSelector !== 'undefined') {
        crmGroupSelector.fnDestroy();
      }
      var parentsOnly = 0;
      var ZeroRecordText = '<div class="status messages">{/literal}{ts escape="js"}No matching Groups found for your search criteria. Suggestions:{/ts}{literal}<div class="spacer"></div><ul><li>{/literal}{ts escape="js"}Check your spelling.{/ts}{literal}</li><li>{/literal}{ts escape="js"}Try a different spelling or use fewer letters.{/ts}{literal}</li><li>{/literal}{ts escape="js"}Make sure you have enough privileges in the access control system.{/ts}{literal}</li></ul></div>';
    } else {
        var parentsOnly = 1;
        var ZeroRecordText = {/literal}'{ts escape="js"}<div class="status messages">No Groups have been created for this site.{/ts}</div>'{literal};
    }

    // this argument should only be used on initial display i.e onPageLoad
    if (typeof parentsOnlyArg !== 'undefined') {
      parentsOnly = parentsOnlyArg;
    }

    var columns = '';
    var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/grouplist" h=0 q="snippet=4"}'{literal};
    var showOrgInfo = {/literal}"{$showOrgInfo}"{literal};
    var $context = $('#crm-main-content-wrapper');

    crmGroupSelector = $('table.crm-group-selector', $context).dataTable({
        "bFilter"    : false,
        "bAutoWidth" : false,
        "aaSorting"  : [],
        "aoColumns"  : [
                        {sClass:'crm-group-name'},
                        {sClass:'crm-group-count'},
                        {sClass:'crm-group-created_by'},
                        {sClass:'crm-group-description', bSortable:false},
                        {sClass:'crm-group-group_type'},
                        {sClass:'crm-group-visibility'},
                        {sClass:'crm-group-group_links', bSortable:false},
                        {/literal}{if $showOrgInfo}{literal}
                        {sClass:'crm-group-org_info', bSortable:false},
                        {/literal}{/if}{literal}
                        {sClass:'hiddenElement', bSortable:false}
                       ],
        "bProcessing": true,
        "asStripClasses" : [ "odd-row", "even-row" ],
        "sPaginationType": "full_numbers",
        "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
        "bServerSide": true,
        "bJQueryUI": true,
        "sAjaxSource": sourceUrl,
        "iDisplayLength": 25,
        "oLanguage": { "sZeroRecords":  ZeroRecordText,
                       "sProcessing":    {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
                       "sLengthMenu":    {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
                       "sInfo":          {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
                       "sInfoEmpty":     {/literal}"{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}"{literal},
                       "sInfoFiltered":  {/literal}"{ts escape='js'}(filtered from _MAX_ total entries){/ts}"{literal},
                       "sSearch":        {/literal}"{ts escape='js'}Search:{/ts}"{literal},
                       "oPaginate": {
                            "sFirst":    {/literal}"{ts escape='js'}First{/ts}"{literal},
                            "sPrevious": {/literal}"{ts escape='js'}Previous{/ts}"{literal},
                            "sNext":     {/literal}"{ts escape='js'}Next{/ts}"{literal},
                            "sLast":     {/literal}"{ts escape='js'}Last{/ts}"{literal}
                        }
                    },
        "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
          var id = $('td:last', nRow).text().split(',')[0];
          var cl = $('td:last', nRow).text().split(',')[1];
          $(nRow).addClass(cl).attr({id: 'row_' + id, 'data-id': id, 'data-entity': 'group'});
          $('td:eq(0)', nRow).wrapInner('<span class="crm-editable crmf-title" />');
          $('td:eq(1)', nRow).addClass('right');
          $('td:eq(3)', nRow).wrapInner('<span class="crm-editable crmf-description" data-type="textarea" />');
          if (parentsOnly) {
            if ($(nRow).hasClass('crm-group-parent')) {
              $(nRow).find('td:first').prepend('{/literal}<span class="collapsed show-children" title="{ts}show child groups{/ts}"/></span>{literal}');
            }
          }
          return nRow;
        },
        "fnDrawCallback": function() {
          $('.crm-editable').crmEditable();
        },
        "fnServerData": function ( sSource, aoData, fnCallback ) {
            aoData.push( {name:'showOrgInfo', value: showOrgInfo },
                         {name:'parentsOnly', value: parentsOnly }
                       );
            if ( filterSearch ) {
                var groupTypes = '';
                $('#group_type-block input').each(function(index) {
                if ($(this).prop('checked')) {
                  if (groupTypes) {
                    groupTypes = groupTypes + ',' + $(this).attr('id').substr(11);
                  }
                  else {
                    groupTypes = $(this).attr('id').substr(11);
                  }
                }
                });

                var groupStatus = '';
                if ( $('.crm-group-search-form-block #group_status_1').prop('checked') ) {
                    groupStatus = '1';
                }

                if ( $('.crm-group-search-form-block #group_status_2').prop('checked') ) {
                    if ( groupStatus ) {
                        groupStatus = '3';
                    } else {
                        groupStatus = '2';
                    }
                }

                aoData.push(
                    {name:'title', value: $('.crm-group-search-form-block #title').val()},
                    {name:'created_by', value: $('.crm-group-search-form-block #created_by').val()},
                    {name:'group_type', value: groupTypes },
                    {name:'visibility', value: $('.crm-group-search-form-block #visibility').val()},
                    {name:'status', value: groupStatus }
                );
            }
            $.ajax( {
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": aoData,
                "success": fnCallback
            } );
        }
    });
  }

  // show hide children
  var $context = $('#crm-main-content-wrapper');
  $('table.crm-group-selector', $context).on( 'click', 'span.show-children', function(){
    var showOrgInfo = {/literal}"{$showOrgInfo}"{literal};
    var rowID = $(this).parents('tr').prop('id');
    var parentRow = rowID.split('_');
    var parent_id = parentRow[1];
    var group_id = '';
    if ( parentRow[2]) {
      group_id = parentRow[2];
    }
    var levelClass = 'level_2';
    // check enclosing td if already at level 2
    if ( $(this).parent().hasClass('level_2') ) {
      levelClass = 'level_3';
    }
    if ( $(this).hasClass('collapsed') ) {
      $(this).removeClass("collapsed").addClass("expanded").attr("title",{/literal}"{ts escape='js'}hide child groups{/ts}"{literal});
      showChildren( parent_id, showOrgInfo, group_id, levelClass );
    }
    else {
      $(this).removeClass("expanded").addClass("collapsed").attr("title",{/literal}"{ts escape='js'}show child groups{/ts}"{literal});
      $('.parent_is_' + parent_id).find('.show-children').removeClass("expanded").addClass("collapsed").attr("title",{/literal}"{ts escape='js'}show child groups{/ts}"{literal});
      $('.parent_is_' + parent_id).hide();
      $('.parent_is_' + parent_id).each(function(i, obj) {
        // also hide children of children
        var gID = $(this).find('td:nth-child(2)').text();
        $('.parent_is_' + gID).hide();
      });
    }
  });
  function showChildren( parent_id, showOrgInfo, group_id, levelClass) {
    var rowID = '#row_' + parent_id;
    if ( group_id ) {
      rowID = '#row_' + parent_id + '_' + group_id;
    }
    if ( $(rowID).next().hasClass('parent_is_' + parent_id ) ) {
      // child rows for this parent have already been retrieved so just show them
      $('.parent_is_' + parent_id ).show();
    } else {
      var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/grouplist" h=0 q="snippet=4"}'{literal};
      $.ajax( {
          "dataType": 'json',
          "url": sourceUrl,
          "data": {'parent_id': parent_id, 'showOrgInfo': showOrgInfo},
          "success": function(response){
            var appendHTML = '';
            $.each( response, function( i, val ) {
              appendHTML += '<tr id="row_'+ val.group_id +'_'+parent_id+'" data-entity="group" data-id="'+ val.group_id +'" class="parent_is_' + parent_id + ' crm-row-child ' + val.class.split(',')[1] + '">';
              if ( val.is_parent ) {
                appendHTML += '<td class="crm-group-name ' + levelClass + '">' + '{/literal}<span class="collapsed show-children" title="{ts}show child groups{/ts}"/></span>{literal}<span class="crm-editable crmf-title">' + val.group_name + '</span></td>';
              }
              else {
                appendHTML += '<td class="crm-group-name ' + levelClass + '"><span class="crm-no-children"></span><span class="crm-editable crmf-title">' + val.group_name + '</span></td>';
              }
              appendHTML += '<td class="right">' + val.count + "</td>";
              appendHTML += "<td>" + val.created_by + "</td>";
              appendHTML += '<td><span class="crm-editable crmf-description" data-type="textarea">' + (val.group_description || '') + "</span></td>";
              appendHTML += "<td>" + val.group_type + "</td>";
              appendHTML += "<td>" + val.visibility + "</td>";
              if (showOrgInfo) {
                appendHTML += "<td>" + val.org_info + "</td>";
              }
              appendHTML += "<td>" + val.links + "</td>";
              appendHTML += "</tr>";
            });
            $( rowID ).after( appendHTML );
            $( rowID ).next().trigger('crmLoad');
            $('.crm-editable').crmEditable();
          }
      });
    }
  }
});

</script>
{/literal}
