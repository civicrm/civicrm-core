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
{* relationship selector *}

<div class="crm-contact-relationship-{$context}">
  <table class="crm-contact-relationship-selector-{$context}">
    <thead>
    <tr>
      <th class='crm-contact-relationship-type'>{ts}Relationship{/ts}</th>
      <th class='crm-contact-relationship-contact_name'>&nbsp;</th>
      <th class='crm-contact-relationship-start_date'>{ts}Start{/ts}</th>
      <th class='crm-contact-relationship-end_date'>{ts}End{/ts}</th>
      <th class='crm-contact-relationship-city'>{ts}City{/ts}</th>
      <th class='crm-contact-relationship-state'>{ts}State/Prov{/ts}</th>
      <th class='crm-contact-relationship-email'>{ts}Email{/ts}</th>
      <th class='crm-contact-relationship-phone'>{ts}Phone{/ts}</th>
      <th class='crm-contact-relationship-links nosort'></th>
    </tr>
    </thead>
  </table>
</div>

{literal}
<script type="text/javascript">
  var {/literal}{$context}{literal}oTable;

  CRM.$(function($) {
    buildContactRelationships{/literal}{$context}{literal}();
    function buildContactRelationships{/literal}{$context}{literal}() {
      var context = {/literal}"{$context}"{literal};
      var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/contactrelationships" h=0 q="context=$context&cid=$contactId"}'{literal};

      if (context == 'user') {
        var ZeroRecordText = {/literal}'{ts escape="js"}There are no related contacts / organizations on record for you.{/ts}'{literal};
      }
      else if (context == 'past') {
        var ZeroRecordText = {/literal}'{ts escape="js"}There are no past / disabled relationships for this contact.{/ts}'{literal};
      }
      else {
        var ZeroRecordText = {/literal}'{ts escape="js"}There are no relationships entered for this contact.{/ts}'{literal};
      }

      {/literal}{$context}{literal}oTable = $('table.crm-contact-relationship-selector-' + context).dataTable({
        "bFilter": false,
        "bAutoWidth": false,
        "aaSorting": [],
        "aoColumns": [
          {sClass: 'crm-contact-relationship-type'},
          {sClass: 'crm-contact-relationship-contact_name'},
          {sClass: 'crm-contact-relationship-start_date'},
          {sClass: 'crm-contact-relationship-end_date'},
          {sClass: 'crm-contact-relationship-city'},
          {sClass: 'crm-contact-relationship-state'},
          {sClass: 'crm-contact-relationship-email'},
          {sClass: 'crm-contact-relationship-phone'},
          {sClass: 'crm-contact-relationship-links', bSortable: false},
          {sClass: 'hiddenElement', bSortable: false},
          {sClass: 'hiddenElement', bSortable: false}
        ],
        "bProcessing": true,
        "sPaginationType": "full_numbers",
        "sDom": '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
        "bServerSide": true,
        "bJQueryUI": true,
        "sAjaxSource": sourceUrl,
        "iDisplayLength": 10,
        "oLanguage": {
          "sZeroRecords": ZeroRecordText,
          "sProcessing": {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
          "sLengthMenu": {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
          "sInfo": {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
          "sInfoEmpty": {/literal}"{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}"{literal},
          "sInfoFiltered": {/literal}"{ts escape='js'}(filtered from _MAX_ total entries){/ts}"{literal},
          "sSearch": {/literal}"{ts escape='js'}Search:{/ts}"{literal},
          "oPaginate": {
            "sFirst": {/literal}"{ts escape='js'}First{/ts}"{literal},
            "sPrevious": {/literal}"{ts escape='js'}Previous{/ts}"{literal},
            "sNext": {/literal}"{ts escape='js'}Next{/ts}"{literal},
            "sLast": {/literal}"{ts escape='js'}Last{/ts}"{literal}
          }
        },
        "fnDrawCallback": function () {
          {/literal}{if $context eq 'current'}{literal}
          if ($('#tab_rel').length) {
            CRM.tabHeader.updateCount($('#tab_rel'), currentoTable.fnSettings().fnRecordsTotal());
          }
          {/literal}{/if}{literal}
        },
        "fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull) {
          $(nRow).attr('id', 'relationship-'+ aData[9]);
          if (aData[10] == 0) {
            $(nRow).addClass('crm-entity disabled');
          }
          else {
            $(nRow).addClass('crm-entity');
          }
        }
      });    
    }
  });
</script>
{/literal}
