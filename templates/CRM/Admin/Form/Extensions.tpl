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
{* this template is used for install /uninstall extensions  *}
<h3>{$title}</h3>
<div class="crm-block crm-form-block crm-admin-optionvalue-form-block">
   {if $action eq 8}
      <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
          {ts}WARNING: Uninstalling this extension might result in the loss of all records which use the extension.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone. Please review the extension information below before you make final decision.{/ts} {ts}Do you want to continue?{/ts}
      </div>
   {/if}
   {if $action eq 1}
      <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
          {ts}Installing this extension will provide you with new functionality. Please make sure that the extension you're installing comes from a trusted source.{/ts} {ts}Do you want to continue?{/ts}
      </div>
   {/if}
   {if $action eq 2}
      <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
          {ts}Downloading this extension will provide you with new functionality. Please make sure that the extension you're installing or upgrading comes from a trusted source.{/ts} {ts}Do you want to continue?{/ts}
      </div>
   {/if}
   {if $action eq 8 or $action eq 1 or $action eq 2}
        {include file="CRM/Admin/Page/ExtensionDetails.tpl"}
   {/if}
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
