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
{* Template for "Change Case Type" activities *}
   <div class="crm-block crm-form-block crm-case-changecasetype-form-block">
    <tr class="crm-case-changecasetype-form-block-case_type_id">
      <td class="label">{$form.case_type_id.label}</td>
  <td>{$form.case_type_id.html}</td>
    </tr>
    <tr class="crm-case-changecasetype-form-block-is_reset_timeline">
  <td class="label">{$form.is_reset_timeline.label}</td>
  <td>{$form.is_reset_timeline.html}</td>
    </tr>
    <tr class="crm-case-changecasetype-form-block-reset_date_time" id="resetTimeline">
        <td class="label">{$form.reset_date_time.label}</td>
        <td>{include file="CRM/common/jcalendar.tpl" elementName=reset_date_time}</td>
    </tr>

{include file="CRM/common/showHideByFieldValue.tpl"
trigger_field_id    ="is_reset_timeline"
trigger_value       = true
target_element_id   ="resetTimeline"
target_element_type ="table-row"
field_type          ="radio"
invert              = 0
}
  </div>
