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
<div class="crm-form crm-form-block crm-pick-option-form-block">
<div class="help">
   Select Group of Contacts
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   <table class="form-layout-compressed">
      <tr class="crm-pick-option-form-block-with_contact">
         <td class="label">{$form.with_contact.label}</td>
         <td>{$form.with_contact.html}</td>
      </tr>
      <tr  class="crm-pick-option-form-block-assigned_to">
        <td class="label">{$form.assigned_to.label}</td>
        <td>{$form.assigned_to.html}</td>
      </tr>
      <tr  class="crm-pick-option-form-block-created_by">
        <td class="label">{$form.created_by.label}</td>
        <td>{$form.created_by.html}</td>
     </tr>
     <tr>
        {include file="CRM/Activity/Form/Task.tpl"}
     </tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
