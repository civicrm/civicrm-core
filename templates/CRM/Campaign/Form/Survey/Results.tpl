{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-block crm-form-block crm-campaign-survey-results-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
      <table class="form-layout-compressed">
       <tr id='showoption'>
           <td colspan="2">
           <table class="form-layout-compressed">
               {* Conditionally show table for setting up selection options - for field types = radio, checkbox or select *}
               {include file="CRM/Campaign/Form/ResultOptions.tpl"}
           </table>
           </td>
       </tr>

       {* Create Report *}
       <tr id='showoption'>
         <td colspan="2">
           <div id="new-group" class="crm-accordion-wrapper ">
           <div class="crm-accordion-header">
             {ts}Create Report{/ts}
           </div><!-- /.crm-accordion-header -->
           <div class="crm-accordion-body">
           <table class="form-layout-compressed">
             <tr>
                <td class="label">{$form.create_report.label}</td>
                <td>{$form.create_report.html}</td>
             </tr>
             <tr>
                <td class="label">{$form.report_title.label}</td>
                <td>{$form.report_title.html|crmAddClass:big}</td>
             </tr>
            </table>
            </div><!-- /.crm-accordion-body -->
            </div><!-- /.crm-accordion-wrapper -->
         </td>
       </tr>
      </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

