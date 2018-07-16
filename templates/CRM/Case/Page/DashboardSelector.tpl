{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{capture assign=expandIconURL}<img src="{$config->resourceBase}i/TreePlus.gif" alt="{ts}open section{/ts}"/>{/capture}
{strip}
<table class="case-selector-{$list} crm-ajax-table" data-page-length='10'>
<thead>
  <tr>
    <th data-data="activity_list" data-orderable="false" class="crm-case-activity_list"></th>
    <th data-data="contact_id" class="crm-case-contact">{ts}Contact{/ts}</th>
    <th data-data="subject" cell-class="crmf-subject crm-editable" class="crm-case-subject">{ts}Subject{/ts}</th>
    <th data-data="case_status" class="crm-case-status">{ts}Status{/ts}</th>
    <th data-data="case_type" class="crm-case-type">{ts}Type{/ts}</th>
    <th data-data="case_role" class="crm-case-role">{ts}My Role{/ts}</th>
    <th data-data="manager" data-orderable="false" class="crm-case-manager">{ts}Manager{/ts}</th>
    <th data-data="date" cell-class="crm-case-date">{if $list EQ 'upcoming'}{ts}Next Sched.{/ts}{elseif $list EQ 'recent'}{ts}Most Recent{/ts}{/if}</th>
    <th data-data="links" data-orderable="false" class="crm-case-links">&nbsp;</th>
  </tr>
</thead>
</table>

{literal}
  <script type="text/javascript">
    (function($) {
      var list =  {/literal}"{$list}"{literal};
      var selectorClass = '.case-selector-' + list;
      var filterClass = '.case-search-options-' + list;

      CRM.$('table' + selectorClass).data({
        "ajax": {
          "url": {/literal}'{crmURL p="civicrm/ajax/get-cases" h=0 q="snippet=4&all=`$all`"}'{literal},
          "data": function (d) {
            d.type = (!$("input[name='upcoming']").length) ? list : $("input[name='upcoming']").prop('checked') ? 'upcoming' : 'any';
            d.case_type_id = $(filterClass + ' select#case_type_id').val() || [];
            d.case_type_id = d.case_type_id.join(',');
            d.status_id = $(filterClass + ' select#case_status_id').val() || [];
            d.status_id = d.status_id.join(',');
          }
        }
      });
      $(function($) {
        $(filterClass + ' :input').change(function() {
          CRM.$('table' + selectorClass).DataTable().draw();
        });
      });
    })(CRM.$);
  </script>
{/literal}

{/strip}
{crmScript file='js/crm.expandRow.js'}
