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
<div class="crm-block crm-form-block crm-contact-task-mailing-label-form-block">
<div class="messages status no-popup">{include file="CRM/Contact/Form/Task.tpl"}</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<table class="form-layout-compressed"> 
     <tr class="crm-contact-task-mailing-label-form-block-label_name">
        <td class="label">{$form.label_name.label}</td>
        <td>{$form.label_name.html} {help id="id-select-label" file="CRM/Contact/Form/Task/Label.hlp"}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-location_type_id">
        <td class="label">{$form.location_type_id.label}</td>
        <td>{$form.location_type_id.html}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-do_not_mail">
        <td></td> <td>{$form.do_not_mail.html} {$form.do_not_mail.label}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-merge_same_address">
        <td></td><td>{$form.merge_same_address.html} {$form.merge_same_address.label}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-merge_same_household">
        <td></td><td>{$form.merge_same_household.html} {$form.merge_same_household.label}</td>
     </tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
