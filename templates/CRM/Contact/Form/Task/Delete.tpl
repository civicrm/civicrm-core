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
{* Confirmation of contact deletes  *}
<div class="crm-block crm-form-block crm-contact-task-delete-form-block">
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>&nbsp;
      {if $restore}
    {ts}Are you sure you want to restore the selected contact(s)? The contact(s) and all related data will be fully restored.{/ts}
      {elseif $trash}
        {ts}Are you sure you want to delete the selected contact(s)?{/ts} {ts}The contact(s) and all related data will be moved to trash and only users with the relevant permission will be able to restore it.{/ts}
      {else}
        {ts}Are you sure you want to delete the selected contact(s)?{/ts} {ts}The contact(s) and all related data will be permanently removed.{/ts} {ts}This action cannot be undone.{/ts}
      {/if}
  </div>


    <h3>{include file="CRM/Contact/Form/Task.tpl"}</h3>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>
