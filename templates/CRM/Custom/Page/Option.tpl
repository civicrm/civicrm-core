{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 1 or $action eq 2 or $action eq 4 or $action eq 8}
    {include file="CRM/Custom/Form/Option.tpl"}
{else}
  {if $reusedNames}
      <div class="message status">
        {icon icon="fa-info-circle"}{/icon} {ts 1=$reusedNames}These Multiple Choice Options are shared by the following custom fields: %1{/ts}
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
	          <th class='crm-custom_option-description'>{ts}Description{/ts}</th>
            <th class='crm-custom_option-default_value'>{ts}Default{/ts}</th>
            <th class='crm-custom_option-is_active'>{ts}Enabled?{/ts}</th>
            <th class='crm-custom_option-links'>&nbsp;</th>
            <th class='hiddenElement'><span class="sr-only">{ts}Actions{/ts}</span></th>
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
                              {sClass:'crm-custom_option-description'},
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
                $('td:eq(0)', nRow).prepend('<span class="crm-i fa-arrows crm-grip" role="img" aria-hidden="true"/>');
                $('td:eq(3)', nRow).addClass('crmf-default_value').html(CRM.utils.formatIcon('fa-check', ts('Default'), nRow.cells[3].innerText));
                return nRow;
              },
              "fnDrawCallback": function() {
                $(this).trigger('crmLoad');
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
          {crmButton q="reset=1&action=map&fid=$fid&gid=$gid" class="action-item open-inline-noreturn" icon="sort-alpha-asc"}{ts}Alphabetize Options{/ts}{/crmButton}
          {if !$isOptionGroupLocked}
            {crmButton q="reset=1&action=add&fid=$fid&gid=$gid" class="action-item" icon="plus-circle"}{ts}Add Option{/ts}{/crmButton}
          {/if}
          {crmButton p="civicrm/admin/custom/group/field" q="reset=1&action=browse&gid=$gid" class="action-item cancel" icon="times"}{ts}Done{/ts}{/crmButton}
      </div>
    </div>
  </div>
{/if}
