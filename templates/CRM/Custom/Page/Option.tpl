{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
  {if $customOption}
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
              <th class='nowrap crm-custom_option-weight'>{ts}Order{/ts}</th>
              <th class='crm-custom_option-is_active  nosort'>{ts}Enabled?{/ts}</th>
              <th class='crm-custom_option-links'>&nbsp;</th>
              <th class='hiddenElement'>&nbsp;</th>
            </tr>
          </thead>
          {if $count lte 10}
            {foreach from=$customOption item=row key=id}
              <tr id="OptionValue-{$id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class} crm-custom_option{if !$row.is_active} disabled{/if}">
                <td class="crm-custom_option-label crm-editable crmf-label">{$row.label}</td>
                <td class="crm-custom_option-value disabled-crm-editable" data-field="value" data-action="update">{$row.value}</td>
                <td class="crm-custom_option-default_value crmf-default_value">{$row.default_value}</td>
                <td class="nowrap crm-custom_option-weight crmf-weight">{$row.weight}</td>
                <td id="row_{$id}_status" class="crm-custom_option-is_active crmf-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                <td>{$row.action|replace:'xx':$id}</td>
            {/foreach}
           {/if}
        </table>
        {literal}
        <script type="text/javascript">
        CRM.$(function($) {
            var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/optionlist" h=0 q="snippet=4&fid=$fid&gid=$gid"}'{literal};
            var $context = $('#crm-main-content-wrapper');
            var count = {/literal}"{$count}"{literal};

            if (count > 10) {
              crmOptionSelector = $('table.crm-option-selector', $context).dataTable({
                  "destroy"    : true,
                  "bFilter"    : false,
                  "bAutoWidth" : false,
                  "aaSorting"  : [],
                  "aoColumns"  : [
                                  {sClass:'crm-custom_option-label'},
                                  {sClass:'crm-custom_option-value'},
                                  {sClass:'crm-custom_option-default_value', bSortable:false},
                                  {sClass:'crm-custom_option-weight'},
                                  {sClass:'crm-custom_option-is_active', bSortable:false},
                                  {sClass:'crm-custom_option-links', bSortable:false},
                                  {sClass:'hiddenElement', bSortable:false}
                                 ],
                  "bProcessing": true,
                  "asStripClasses" : [ "odd-row", "even-row" ],
                  "sPaginationType": "full_numbers",
                  "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
                  "bServerSide": true,
                  "bJQueryUI": true,
                  "sAjaxSource": sourceUrl,
                  "iDisplayLength": 10,
                  "oLanguage": {
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
                    $('td:eq(2)', nRow).addClass('crmf-default_value');
                    $('td:eq(3)', nRow).addClass('crmf-weight');
                    return nRow;
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
        });

        </script>
        {/literal}

        <div class="action-link">
            {crmButton q="reset=1&action=add&fid=$fid&gid=$gid" class="action-item" icon="circle-plus"}{ts}Add Option{/ts}{/crmButton}
            {crmButton p="civicrm/admin/custom/group/field" q="reset=1&action=browse&gid=$gid" class="action-item cancel" icon="close"}{ts}Done{/ts}{/crmButton}
        </div>
      </div>
    </div>

  {else}
    {if $action eq 16}
        <div class="messages status no-popup">
           <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
           {ts}None found.{/ts}
        </div>
    {/if}
  {/if}
{/if}
