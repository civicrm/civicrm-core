{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing email settings.  *}
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

  <tr class="crm-mail-settings-form-block-activity_status"><td class="label">{$form.activity_status.label}</td><td>{$form.activity_status.html}</td></tr>
    </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
{/if}
</div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $('form.{/literal}{$form.formClass}{literal}');
    function showActivityStatus() {
      $('.crm-mail-settings-form-block-activity_status', $form).toggle($(this).val() === '0');
    }
    $('select[name=is_default]').each(showActivityStatus).change(showActivityStatus);
  });
</script>
{/literal}
