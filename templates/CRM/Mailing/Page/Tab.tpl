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

<div class="crm-mailing-selector">
  <table class="contact-mailing-selector">
    <thead>
    <tr>
      <th class='crm-mailing-contact-subject'>{ts}Subject{/ts}</th>
      <th class='crm-mailing-contact_created'>{ts}Added By{/ts}</th>
      <th class='crm-contact-activity_contact nosort'>{ts}Recipients{/ts}</th>
      <th class='crm-mailing-contact-date'>{ts}Date{/ts}</th>
      <th class='crm-mailing_openstats'>{ts}Opens/ Clicks{/ts}</th>
      <th class='crm-mailing-contact-links nosort'>&nbsp;</th>
    </tr>
    </thead>
  </table>
</div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var oTable;

    buildMailingContact();

    function buildMailingContact() {
      var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/contactmailing" h=0 q="contact_id=$contactId"}'{literal};

      var ZeroRecordText = {/literal}'{ts escape="js"}No mailings found{/ts}.'{literal};

      oTable = $('.contact-mailing-selector').dataTable({
        "bFilter": false,
        "bAutoWidth": false,
        "aaSorting": [],
        "aoColumns": [
          {sClass: 'crm-mailing-contact-subject'},
          {sClass: 'crm-mailing-contact_created'},
          {sClass: 'crm-contact-activity_contact', bSortable:false},
          {sClass: 'crm-mailing-contact-date'},
          {sClass: 'crm-mailing_openstats', bSortable:false},
          {sClass: 'crm-mailing-contact-links', bSortable: false}
        ],
        "bProcessing": true,
        "sPaginationType": "full_numbers",
        "sDom": '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
        "bServerSide": true,
        "bJQueryUI": true,
        "sAjaxSource": sourceUrl,
        "iDisplayLength": 25,
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
        }
      });
    }
  });
</script>
{/literal}
