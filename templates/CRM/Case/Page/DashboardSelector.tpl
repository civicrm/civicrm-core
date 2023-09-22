{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{strip}
<table class="case-selector-{$list} crm-ajax-table" data-page-length='10'>
<thead>
  <tr>
    <th data-data="activity_list" data-orderable="false" class="crm-case-activity_list"></th>
    <th data-data="sort_name" class="crm-case-contact">{ts}Contact{/ts}</th>
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

      // Determine the url `type` parameter which is a combination of `list` and the `upcoming` checkbox.
      // @todo This seems fragile. It makes `list` serve double-duty, and also relies on the fact that when list=all-cases that can only happen for users with all cases permission, which itself is what determines whether the upcoming checkbox is present.
      var computeGetCasesType = function(selectorListType) {
        return (!$("input[name='upcoming']").length) ?
          (selectorListType == 'my-cases' ? 'any' : selectorListType) :
          ($("input[name='upcoming']").prop('checked') ? 'upcoming' : 'any');
      }

      CRM.$('table' + selectorClass).data({
        "ajax": {
          "url": {/literal}'{crmURL p="civicrm/ajax/get-cases" h=0 q="snippet=4&all=`$all`"}'{literal},
          "data": function (d) {
            d.type = computeGetCasesType(list);
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
