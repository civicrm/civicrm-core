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
{* template to remove tags from contact  *}
<div class="crm-form-block crm-block crm-contact-task-removefromtag-form-block">
  <h3>
    {ts}Tag Contact(s) (Remove){/ts}
  </h3>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout-compressed">
    <tr class="crm-contact-task-removefromtag-form-block-tag">
      <td>
        <div class="listing-box">
        {foreach from=$form.tag item="tag_val"}
           <div class="{cycle values="odd-row,even-row"}">
             {$tag_val.html}
          </div>
        {/foreach}
        </div>
      </td>
    </tr>
    <tr>
      <td>
        {include file="CRM/common/Tagset.tpl"}
      </td>
    </tr>

    <tr><td>{include file="CRM/Contact/Form/Task.tpl"}</td></tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
