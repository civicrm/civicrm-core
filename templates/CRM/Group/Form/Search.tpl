{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
      {$form.group_type_search.label}<br />
      {$form.group_type_search.html}<br />
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
</table>
</div>
<div class="css_right">
  <a class="crm-hover-button action-item" href="{crmURL q="reset=1&update_smart_groups=1"}">{ts}Update Smart Group Counts{/ts}</a> {help id="update_smart_groups"}
</div>
<table class="crm-group-selector crm-ajax-table" data-order='[[0,"asc"]]'>
  <thead>
    <tr>
      <th data-data="title" cell-class="crm-group-name crm-editable crmf-title" class='crm-group-name'>{ts}Name{/ts}</th>
      <th data-data="count" cell-class="crm-group-count right" class='crm-group-count'>{ts}Count{/ts}</th>
      <th data-data="created_by" cell-class="crm-group-created_by" class='crm-group-created_by'>{ts}Created By{/ts}</th>
      <th data-data="description" data-orderable="false" cell-class="crm-group-description crmf-description crm-editable" class='crm-group-description'>{ts}Description{/ts}</th>
      <th data-data="group_type" cell-class="crm-group-group_type" class='crm-group-group_type'>{ts}Group Type{/ts}</th>
      <th data-data="visibility" cell-class="crm-group-visibility crmf-visibility crm-editable" cell-data-type="select" class='crm-group-visibility'>{ts}Visibility{/ts}</th>
      {if $showOrgInfo}
        <th data-data="org_info" data-orderable="false" cell-class="crm-group-org_info" class='crm-group-org_info'>{ts}Organization{/ts}</th>
      {/if}
      <th data-data="links" data-orderable="false" cell-class="crm-group-group_links" class='crm-group-group_links'>&nbsp;</th>
    </tr>
  </thead>
</table>

{* handle enable/disable actions*}
{include file="CRM/common/enableDisableApi.tpl"}

{literal}
<script type="text/javascript">
  (function($) {
    // for CRM-11310 and CRM-10635 : processing just parent groups on initial display
    // passing '1' for parentsOnlyArg to show parent child hierarchy structure display
    // on initial load of manage group page and
    // also to handle search filtering for initial load of same page.
    var parentsOnly = 1
    var ZeroRecordText = {/literal}'{ts escape="js"}<div class="status messages">No Groups have been created for this site.{/ts}</div>'{literal};
    $('table.crm-group-selector').data({
      "ajax": {
        "url": {/literal}'{crmURL p="civicrm/ajax/grouplist" h=0 q="snippet=4"}'{literal},
        "data": function (d) {

          var groupTypes = ($('.crm-group-search-form-block #group_type_search_1').prop('checked')) ? '1' : '';
          if (groupTypes) {
            groupTypes = ($('.crm-group-search-form-block #group_type_search_2').prop('checked')) ? groupTypes + ',2' : groupTypes;
          } else {
            groupTypes = ($('.crm-group-search-form-block #group_type_search_2').prop('checked')) ? '2' : '';
          }

          var groupStatus = ($('.crm-group-search-form-block #group_status_1').prop('checked')) ? 1 : '';
          if (groupStatus) {
            groupStatus = ($('.crm-group-search-form-block #group_status_2').prop('checked')) ? 3 : groupStatus;
          } else {
            groupStatus = ($('.crm-group-search-form-block #group_status_2').prop('checked')) ? 2 : '';
          }

          d.title = $(".crm-group-search-form-block input#title").val(),
          d.created_by = $(".crm-group-search-form-block input#created_by").val(),
          d.group_type = groupTypes,
          d.visibility = $(".crm-group-search-form-block select#visibility").val(),
          d.status = groupStatus,
          d.showOrgInfo = {/literal}"{$showOrgInfo}"{literal},
          d.parentsOnly = parentsOnly
        }
      },
      "language": {
        "zeroRecords": ZeroRecordText
      },
      "drawCallback": function(settings) {
        //Add data attributes to cells
        $('thead th', settings.nTable).each( function( index ) {
          $.each(this.attributes, function() {
            if(this.name.match("^cell-")) {
              var cellAttr = this.name.substring(5);
              var cellValue = this.value;
              $('tbody tr', settings.nTable).each( function() {
                $('td:eq('+ index +')', this).attr( cellAttr, cellValue );
              });
            }
          });
        });
        //Reload table after draw
        $(settings.nTable).trigger('crmLoad');
        if (parentsOnly) {
          $('tbody tr.crm-group-parent', settings.nTable).each( function() {
            $(this).find('td:first')
              .prepend('{/literal}<span class="collapsed show-children" title="{ts}show child groups{/ts}"/></span>{literal}')
              .find('div').css({'display': 'inline'});
          });
        }
      }
    });
    $(function($) {
      $('.crm-group-search-form-block :input').change(function(){
        parentsOnly = 0;
        ZeroRecordText = '<div class="status messages">{/literal}{ts escape="js"}No matching Groups found for your search criteria. Suggestions:{/ts}{literal}<div class="spacer"></div><ul><li>{/literal}{ts escape="js"}Check your spelling.{/ts}{literal}</li><li>{/literal}{ts escape="js"}Try a different spelling or use fewer letters.{/ts}{literal}</li><li>{/literal}{ts escape="js"}Make sure you have enough privileges in the access control system.{/ts}{literal}</li></ul></div>';
        $('table.crm-group-selector').DataTable().draw();
      });
    });
    $('#crm-container')
      .on('click', 'a.button, a.action-item[href*="action=update"], a.action-item[href*="action=delete"]', CRM.popup)
      .on('crmPopupFormSuccess', 'a.button, a.action-item[href*="action=update"], a.action-item[href*="action=delete"]', function() {
          // Refresh datatable when form completes
          $('table.crm-group-selector').DataTable().draw();
      });
    // show hide children
    var context = $('#crm-main-content-wrapper');
    $('table.crm-group-selector', context).on( 'click', 'span.show-children', function(){
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
        //FIXME Is it possible to replace all this with a datatables call?
        $.ajax( {
            "dataType": 'json',
            "url": {/literal}'{crmURL p="civicrm/ajax/grouplist" h=0 q="snippet=4"}'{literal},
            "data": {'parent_id': parent_id, 'showOrgInfo': showOrgInfo},
            "success": function(response){
              var appendHTML = '';
              $.each( response.data, function( i, val ) {
                appendHTML += '<tr id="row_'+val.group_id+'_'+parent_id+'" data-entity="group" data-id="'+val.group_id+'" class="crm-entity parent_is_'+parent_id+' crm-row-child">';
                if ( val.is_parent ) {
                  appendHTML += '<td class="crm-group-name crmf-title ' + levelClass + '">' + '{/literal}<span class="collapsed show-children" title="{ts}show child groups{/ts}"/></span><div class="crmf-title crm-editable" style="display:inline">{literal}' + val.title + '</div></td>';
                }
                else {
                  appendHTML += '<td class="crm-group-name  crmf-title crm-editable ' + levelClass + '"><span class="crm-no-children"></span>' + val.title + '</td>';
                }
                appendHTML += '<td class="right">' + val.count + "</td>";
                appendHTML += "<td>" + val.created_by + "</td>";
                appendHTML += '<td class="crm-editable crmf-description">' + (val.description || '') + "</td>";
                appendHTML += "<td>" + val.group_type + "</td>";
                appendHTML += '<td class="crm-editable crmf-visibility" data-type="select">' + val.visibility + "</td>";
                if (showOrgInfo) {
                  appendHTML += "<td>" + val.org_info + "</td>";
                }
                appendHTML += "<td>" + val.links + "</td>";
                appendHTML += "</tr>";
              });
              $( rowID ).after( appendHTML );
              $( '.parent_is_'+parent_id ).trigger('crmLoad');
            }
        });
      }
    }
  })(CRM.$);
</script>
{/literal}
