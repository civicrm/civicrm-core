{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-group-search-form-block">
  <details class="crm-accordion-light crm-search_builder-accordion" open>
    <summary>
      {ts}Find Groups{/ts}
    </summary>
    <div class="crm-accordion-body">
      <div id="searchForm">
        <table class="form-layout">
          <tr>
            <td>
              {$form.title.label}<br />
              {$form.title.html}
            </td>
            {if !empty($form.created_by)}
              <td>
                {$form.created_by.label}<br />
                {$form.created_by.html}
              </td>
            {/if}
            <td>
              {$form.visibility.label}<br />
              {$form.visibility.html}
            </td>
          </tr>
          <tr>
            {if !empty($form.group_type_search)}
              <td id="group_type-block">
                {$form.group_type_search.label}<br />
                {$form.group_type_search.html}
              </td>
            {/if}
            {if !empty($form.group_status)}
              <td>
                {$form.group_status.label}<br />
                {$form.group_status.html}
              </td>
            {/if}
            {if !empty($form.component_mode)}
              <td>
                {$form.component_mode.label}<br />
                {$form.component_mode.html}
              </td>
            {/if}
          </tr>
          {if !empty($form.saved_search)}
            <tr>
              <td>
                {$form.saved_search.label} <br/>{$form.saved_search.html}
              </td>
              <td colspan="2">
              </td>
            </tr>
          {/if}
        </table>
      </div>
    </div>
  </details>
<div class="css_right">
  <a class="crm-hover-button action-item" href="{crmURL q="reset=1&update_smart_groups=1"}">{ts}Update Smart Group Counts{/ts}</a> {help id="update_smart_groups"}
</div>
{crmPermission has='edit groups'}
  {assign var='editableClass' value='crm-editable'}
{/crmPermission}
<table class="crm-group-selector crm-ajax-table" data-order='[[0,"asc"]]'>
  <thead>
    <tr>
      <th data-data="title" cell-class="crm-group-name {$editableClass} crmf-title" class='crm-group-name'>{ts}Name{/ts}</th>
      <th data-data="count" cell-class="crm-group-count right" class='crm-group-count'>{ts}Count{/ts}</th>
      <th data-data="created_by" cell-class="crm-group-created_by" class='crm-group-created_by'>{ts}Created By{/ts}</th>
      <th data-data="description" data-orderable="false" cell-class="crm-group-description crmf-description {$editableClass}" class='crm-group-description'>{ts}Description{/ts}</th>
      <th data-data="group_type" cell-class="crm-group-group_type" class='crm-group-group_type'>{ts}Group Type{/ts}</th>
      <th data-data="visibility" cell-class="crm-group-visibility crmf-visibility {$editableClass}" cell-data-type="select" class='crm-group-visibility'>{ts}Visibility{/ts}</th>
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
    var ZeroRecordText = {/literal}'{ts escape="js"}<div class="status messages">None found.{/ts}</div>'{literal};
    var smartGroupText = {/literal}'<span>({ts escape="js"}Smart Group{/ts})</span>'{literal};
    $('table.crm-group-selector').data({
      "ajax": {
        "url": {/literal}'{crmURL p="civicrm/ajax/grouplist" h=0 q="snippet=4"}'{literal},
        "data": function (d) {

          var groupTypes = '';
          $('input[id*="group_type_search_"]:checked').each(function(e) {
            groupTypes += $(this).attr('id').replace(/group_type_search_/, groupTypes == '' ? '' : ',');
          });

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
          d.savedSearch = $('.crm-group-search-form-block select#saved_search').val(),
          d.component_mode = $(".crm-group-search-form-block select#component_mode").val(),
          d.showOrgInfo = {/literal}{if $showOrgInfo}"{$showOrgInfo}"{else}"0"{/if}{literal},
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
        CRM.loadScript(CRM.config.resourceBase + 'js/jquery/jquery.crmEditable.js').done(function () {
          if (parentsOnly) {
            $('tbody tr.crm-group-parent', settings.nTable).each(function () {
              $(this).find('td:first')
                .prepend('{/literal}<span class="collapsed show-children" title="{ts}show child groups{/ts}"/></span>{literal}')
                .find('div').css({'display': 'inline'});
            });
          }
          $('tbody tr.crm-smart-group > td.crm-group-name', settings.nTable).append(smartGroupText);
        });
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
      var showOrgInfo = {/literal}{if $showOrgInfo}true{else}false{/if}{literal};
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
          var gID = $(this).data('id');
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
                val.row_classes = [
                  'crm-entity',
                  'parent_is_' + parent_id,
                  'crm-row-child'
                ];
                if ('DT_RowClass' in val) {
                  val.row_classes = val.row_classes.concat(val.DT_RowClass.split(' ').filter((item) => val.row_classes.indexOf(item) < 0));
                  if (val.DT_RowClass.indexOf('crm-smart-group') == -1) {
                    smartGroupText = '';
                  }
                }
                appendHTML += '<tr id="row_'+val.group_id+'_'+parent_id+'" data-entity="group" data-id="'+val.group_id+'" class="' + val.row_classes.join(' ') + '">';
                if ( val.is_parent ) {
                  appendHTML += '<td class="crm-group-name crmf-title ' + levelClass + '">' + '{/literal}<span class="collapsed show-children" title="{ts}show child groups{/ts}"/></span><div class="crmf-title {$editableClass}" style="display:inline">{literal}' + val.title + '</div>' + smartGroupText + '</td>';
                }
                else {
                  appendHTML += '<td class="crm-group-name' + levelClass + '"><div class="crmf-title {/literal}{$editableClass}{literal}"><span class="crm-no-children"></span>' + val.title + '</div>' + smartGroupText + '</td>';
                }
                appendHTML += '<td class="right">' + val.count + "</td>";
                appendHTML += "<td>" + val.created_by + "</td>";
                appendHTML += '<td class="{/literal}{$editableClass}{literal} crmf-description">' + (val.description || '') + "</td>";
                appendHTML += "<td>" + val.group_type + "</td>";
                appendHTML += '<td class="{/literal}{$editableClass}{literal} crmf-visibility" data-type="select">' + val.visibility + "</td>";
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
