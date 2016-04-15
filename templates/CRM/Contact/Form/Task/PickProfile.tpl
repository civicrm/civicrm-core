{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{*Update multiple contacts*}
<div class="crm-block crm-form-block crm-contact-task-pickprofile-form-block">
 <table class="form-layout-compressed">
    <tr class="crm-contact-task-pickprofile-form-block-uf_group_id">
       <td class="label">{$form.uf_group_id.label}</td>
       <td>{$form.uf_group_id.html}</td>
    </tr>
    <tr>
        <td class="label"></td>
        <td>
            {include file="CRM/Contact/Form/Task.tpl"}
        </td>
    </tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>

