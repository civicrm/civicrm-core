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
{* this template is used for adding/editing email settings.  *}
<h3>{if $action eq 1}{ts}New Email Settings{/ts}{elseif $action eq 2}{ts}Edit Email Settings{/ts}{else}{ts}Delete Email Settings{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-mail-settings-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{if $action eq 8}
  <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
  {ts}WARNING: Deleting this option will result in the loss of mail settings data.{/ts} {ts}Do you want to continue?{/ts}
  </div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{else}
    <table class="form-layout-compressed">

  <tr class="crm-mail-settings-form-block-name"><td class="label">{$form.name.label}</td><td>{$form.name.html}</td></tr>
  <tr><td class="label">&nbsp;</td><td class="description">{ts}Name of this group of settings.{/ts}</td></tr>

  <tr class="crm-mail-settings-form-block-server"><td class="label">{$form.server.label}</td><td>{$form.server.html}</td></tr>
  <tr><td class="label">&nbsp;</td><td class="description">{ts}Name or IP address of mail server machine.{/ts}</td></tr>

  <tr class="crm-mail-settings-form-block-username"><td class="label">{$form.username.label}</td><td>{$form.username.html}</td></tr>
  <tr><td class="label">&nbsp;</td><td class="description">{ts}Username to use when polling (for IMAP and POP3).{/ts}</td></tr>

  <tr class="crm-mail-settings-form-block-password"><td class="label">{$form.password.label}</td><td>{$form.password.html}</td></tr>
  <tr><td class="label">&nbsp;</td><td class="description">{ts}Password to use when polling (for IMAP and POP3).{/ts}</td></tr>

  <tr class="crm-mail-settings-form-block-localpart"><td class="label">{$form.localpart.label}</td><td>{$form.localpart.html}</td></tr>
  <tr><td class="label">&nbsp;</td><td class="description">{ts}Optional local part (e.g., 'civimail+' for addresses like civimail+s.1.2@example.com).{/ts}</td></tr>

  <tr class="crm-mail-settings-form-block-domain"><td class="label">{$form.domain.label}</td><td>{$form.domain.html}</td></tr>
  <tr><td class="label">&nbsp;</td><td class="description">{ts}Email address domain (the part after @).{/ts}</td></tr>

  <tr class="crm-mail-settings-form-block-return_path"><td class="label">{$form.return_path.label}</td><td>{$form.return_path.html}</td><tr>
        <tr><td class="label">&nbsp;</td><td class="description">{ts}Contents of the Return-Path header.{/ts}</td></tr>

  <tr class="crm-mail-settings-form-block-protocol"><td class="label">{$form.protocol.label}</td><td>{$form.protocol.html}</td></tr>
  <tr><td class="label">&nbsp;</td><td class="description">{ts}Name of the protocol to use for polling.{/ts}</td></tr>

  <tr class="crm-mail-settings-form-block-source"><td class="label">{$form.source.label}</td><td>{$form.source.html}</td></tr>
  <tr><td class="label">&nbsp;</td><td class="description">{ts}Folder to poll from when using IMAP (will default to INBOX when empty), path to poll from when using Maildir, etc..{/ts}</td></tr>

  <tr class="crm-mail-settings-form-block-is_ssl"><td class="label">{$form.is_ssl.label}</td><td>{$form.is_ssl.html}</td></tr>
  <tr><td class="label">&nbsp;</td><td class="description">{ts}Whether to use SSL for IMAP and POP3 or not.{/ts}</td></tr>

  <tr class="crm-mail-settings-form-block-is_default"><td class="label">{$form.is_default.label}</td><td>{$form.is_default.html}</td></tr>
  <tr><td class="label">&nbsp;</td><td class="description">{ts}How this mail account will be used. Only one box may be used for bounce processing. It will also be used as the envelope email when sending mass mailings.{/ts}</td></tr>
    </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
{/if}
</div>
