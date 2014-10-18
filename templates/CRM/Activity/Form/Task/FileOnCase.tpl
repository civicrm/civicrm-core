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
<div class="crm-form crm-form-block crm-file-on-case-form-block">
<div id="help">
   File on Case
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   <table class="form-layout-compressed">
      <tr class="crm-file-on-case-form-block-unclosed_cases">
         <td class="label">
           {$form.unclosed_case_id.label}
         </td>
         <td>
           {$form.unclosed_case_id.html}<br />
         </td>
      </tr>
     <tr>
        {include file="CRM/Activity/Form/Task.tpl"}
     </tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
<script type="text/javascript">
CRM.$(function($) {
  var $form = $("form.{/literal}{$form.formClass}{literal}");
  $('input[name=unclosed_case_id]', $form).crmSelect2({
    placeholder: {/literal}'{ts escape="js"}- select case -{/ts}'{literal},
    minimumInputLength: 1,
    formatResult: CRM.utils.formatSelect2Result,
    formatSelection: function(row) {
      return row.label;
    },
    ajax: {
      url: {/literal}"{crmURL p='civicrm/case/ajax/unclosed' h=0}"{literal},
      data: function(term) {
        return {term: term};
      },
      results: function(response) {
        return {results: response};
      }
    }
  });
});
{/literal}
</script>
