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
{if $action eq 1 or $action eq 2 or $action eq 4 or $action eq 8}
    {include file="CRM/Custom/Form/Option.tpl"}
{else}
  {if $reusedNames}
      <div class="message status">
        <div class="icon inform-icon"></div> &nbsp; {ts 1=$reusedNames}These Multiple Choice Options are shared by the following custom fields: %1{/ts}
      </div>
  {/if}

  <div id="field_page">
    <p></p>
    <div class="form-item">
      {* handle enable/disable actions*}
      {include file="CRM/common/enableDisableApi.tpl"}
      <table class="crm-option-selector">
      <thead>
          <tr class="columnheader">
            <th class='crm-custom_option-label'>{ts}Label{/ts}</th>
            <th class='crm-custom_option-value'>{ts}Value{/ts}</th>
            <th class='crm-custom_option-default_value'>{ts}Default{/ts}</th>
            <th class='crm-custom_option-is_active'>{ts}Enabled?{/ts}</th>
            <th class='crm-custom_option-links'>&nbsp;</th>
            <th class='hiddenElement'>&nbsp;</th>
          </tr>
        </thead>
      </table>
      {literal}
      <script type="text/javascript">
      CRM.$(function($) {
        var crmOptionSelector;

        buildOptions();

        function buildOptions() {
          var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/optionlist" h=0 q="snippet=4&fid=$fid&gid=$gid"}'{literal};
          var $context = $('.crm-container');
          var ZeroRecordText = {/literal}'{ts escape="js"}None found.{/ts}'{literal};

          crmOptionSelector = $('table.crm-option-selector', $context).dataTable({
              "destroy"    : true,
              "bFilter"    : false,
              "bAutoWidth" : false,
              "aaSorting"  : [],
              "aoColumns"  : [
                              {sClass:'crm-custom_option-label'},
                              {sClass:'crm-custom_option-value'},
                              {sClass:'crm-custom_option-default_value'},
                              {sClass:'crm-custom_option-is_active'},
                              {sClass:'crm-custom_option-links'},
                              {sClass:'hiddenElement'}
                             ],
              "bProcessing": true,
              "asStripClasses" : [ "odd-row", "even-row" ],
              "sPaginationType": "full_numbers",
              "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
              "bServerSide": true,
              "bJQueryUI": true,
              "bSort" : false,
              "sAjaxSource": sourceUrl,
              "iDisplayLength": 10,
              "oLanguage": {
                             "sZeroRecords":   ZeroRecordText,
                             "sProcessing":    {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
                             "sLengthMenu":    {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
                             "sInfo":          {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
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
                $(nRow).addClass(cl).attr({id: 'OptionValue-' + id});
                $('td:eq(0)', nRow).wrapInner('<span class="crm-editable crmf-label" />');
                $('td:eq(0)', nRow).prepend('<span class="crm-i fa-arrows crm-grip" />');
                $('td:eq(2)', nRow).addClass('crmf-default_value');
                return nRow;
              },
              "fnDrawCallback": function() {
                // FIXME: trigger crmLoad and crmEditable would happen automatically
                $('.crm-editable').crmEditable();
              },

              "fnServerData": function ( sSource, aoData, fnCallback ) {
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


        var startPosition;
        var endPosition;
        var gid = {/literal}'{$optionGroupID}'{literal};

        $("table.crm-option-selector tbody").sortable({
          handle: ".fa-arrows",
          cursor: "move",
          start:function(event, ui) {
            var oSettings = $('table.crm-option-selector').dataTable().fnSettings();
            var index = oSettings._iDisplayStart;
            startPosition = index + ui.item.prevAll().length + 1;
          },
          update: function(event, ui) {
            var oSettings = $('table.crm-option-selector').dataTable().fnSettings();
            var index = oSettings._iDisplayStart;
            endPosition = index + ui.item.prevAll().length + 1;

            CRM.status({}, $.getJSON(CRM.url('civicrm/ajax/reorder'), {
              returnFormat:'JSON',
              start:startPosition,
              end: endPosition,
              gid: gid
            }))
            .success(function() {
              $("table.crm-option-selector tbody tr").each(function(i) {
                $(this).removeClass('odd even').addClass(i % 2 ? 'even' : 'odd');
              });
            });
          }
        });
      });

      </script>
      {/literal}

      <div class="action-link">
          {crmButton q="reset=1&action=add&fid=$fid&gid=$gid" class="action-item" icon="plus-circle"}{ts}Add Option{/ts}{/crmButton}
          {crmButton p="civicrm/admin/custom/group/field" q="reset=1&action=browse&gid=$gid" class="action-item cancel" icon="times"}{ts}Done{/ts}{/crmButton}
      </div>
    </div>
  </div>
{/if}
