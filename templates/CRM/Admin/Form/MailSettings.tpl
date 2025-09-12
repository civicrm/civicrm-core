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
  {if $action eq 8}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts}WARNING: Deleting this option will result in the loss of mail settings data.{/ts} {ts}Do you want to continue?{/ts}
    </div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
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

      <tr class="crm-mail-settings-form-block-return_path"><td class="label">{$form.return_path.label} {help id='return_path'}</td><td>{$form.return_path.html}</td><tr>
      <tr><td class="label">&nbsp;</td><td class="description">{ts}Contents of the Return-Path header.{/ts}</td></tr>

      <tr class="crm-mail-settings-form-block-protocol"><td class="label">{$form.protocol.label}</td><td>{$form.protocol.html}</td></tr>
      <tr><td class="label">&nbsp;</td><td class="description">{ts}Name of the protocol to use for polling.{/ts}</td></tr>

      <tr class="crm-mail-settings-form-block-source"><td class="label">{$form.source.label}</td><td>{$form.source.html}</td></tr>
      <tr><td class="label">&nbsp;</td><td class="description">{ts}Folder to poll from when using IMAP (will default to INBOX when empty), path to poll from when using Maildir, etc..{/ts}</td></tr>

      <tr class="crm-mail-settings-form-block-is_ssl"><td class="label">{$form.is_ssl.label}</td><td>{$form.is_ssl.html}</td></tr>
      <tr><td class="label">&nbsp;</td><td class="description">{ts}Whether to use SSL for IMAP and POP3 or not.{/ts}</td></tr>

      <tr class="crm-mail-settings-form-block-is_default"><td class="label">{$form.is_default.label}</td><td>{$form.is_default.html}</td></tr>
      <tr><td class="label">&nbsp;</td><td class="description">{ts}How this mail account will be used. Only one box may be used for bounce processing. It will also be used as the envelope email when sending mass mailings.{/ts}</td></tr>

      <tr class="crm-mail-settings-form-block-is_non_case_email_skipped"><td class="label">&nbsp;</td><td>{$form.is_non_case_email_skipped.html}{$form.is_non_case_email_skipped.label} {help id='is_non_case_email_skipped'}</td></tr>

      <tr class="crm-mail-settings-form-block-is_contact_creation_disabled_if_no_match"><td class="label">&nbsp;</td><td>{$form.is_contact_creation_disabled_if_no_match.html}{$form.is_contact_creation_disabled_if_no_match.label} {help id='is_contact_creation_disabled_if_no_match'}</td></tr>

      <tr class="crm-mail-settings-form-block-activity_type_id"><td class="label">{$form.activity_type_id.label} {help id='activity_type_id'}</td><td>{$form.activity_type_id.html}</td></tr>

      <tr class="crm-mail-settings-form-block-activity_status"><td class="label">{$form.activity_status.label}</td><td>{$form.activity_status.html}</td></tr>

      {include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignTrClass="crm-mail-settings-form-block-campaign_id"}

      <tr class="crm-mail-settings-form-block-activity_source"><td class="label">{$form.activity_source.label} {help id='activity_source'}</td><td>{$form.activity_source.html}</td></tr>
      <tr class="crm-mail-settings-form-block-activity_targets"><td class="label">{$form.activity_targets.label}</td><td>{$form.activity_targets.html}</td></tr>
      <tr class="crm-mail-settings-form-block-activity_assignees"><td class="label">{$form.activity_assignees.label}</td><td>{$form.activity_assignees.html}</td></tr>

      <tr class="crm-mail-settings-form-block-is_active"><td class="label">{$form.is_active.label}</td><td>{$form.is_active.html}</td></tr>
    </table>

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  {/if}
</div>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $('form.{/literal}{$form.formClass}{literal}');
    function showActivityFields() {
      var fields = [
        '.crm-mail-settings-form-block-activity_status',
        '.crm-mail-settings-form-block-is_non_case_email_skipped',
        '.crm-mail-settings-form-block-is_contact_creation_disabled_if_no_match',
        '.crm-mail-settings-form-block-activity_type_id',
        '.crm-mail-settings-form-block-campaign_id',
        '.crm-mail-settings-form-block-activity_source',
        '.crm-mail-settings-form-block-activity_targets',
        '.crm-mail-settings-form-block-activity_assignees',
        '.crm-mail-settings-form-block-is_active',
      ];
      $(fields.join(', '), $form).toggle($(this).val() === '0');
    }
    $('select[name="is_default"]').each(showActivityFields).change(showActivityFields);
  });
</script>
{/literal}
