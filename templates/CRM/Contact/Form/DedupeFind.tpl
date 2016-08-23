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
<div class="crm-block crm-form-block crm-dedupe-find-form-block">
<div class="help">
    {ts}You can search all contacts for duplicates or limit the results for better performance.
      If you limit by group then it will look for matches with that group both inside and outside of the group.
      You can also limit the contacts in the group to be matched by specifying the number of contacts to match. This can be done in conjunction with a group or separately and is recommended for performance reasons.
    {/ts}
</div>
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   <table class="form-layout-compressed">
     <tr class="crm-dedupe-find-form-block-group_id">
       <td class="label">{$form.group_id.label}</td>
       <td>{$form.group_id.html}</td>
     </tr>
       <tr class="crm-dedupe-find-form-block-limit">
        <td class="label">{$form.limit.label}</td>
        <td>{$form.limit.html}</td>
       </tr>
   </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
