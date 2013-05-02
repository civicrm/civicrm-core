{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
                <td class="description label">{$form.create_report.label}</td>
                <td>{$form.create_report.html}</td>
             </tr>
             <tr>
                <td class="description label">{$form.report_title.label}</td>
                <td>{$form.report_title.html|crmAddClass:big}</td>
             </tr>
            </table>
            </div><!-- /.crm-accordion-body -->
            </div><!-- /.crm-accordion-wrapper -->
         </td>
       </tr>
      </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
