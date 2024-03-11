{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-block crm-form-block crm-mailing-approve-form-block">

<table class="form-layout">
  <tbody>
    <tr class="crm-mailing-approve-form-block-approval_status">
        <td class="label">{$form.approval_status_id.label}</td>
        <td>{$form.approval_status_id.html}</td>
    </tr>
    <tr class="crm-mailing-approve-form-block-approval_note">
        <td class="label">{$form.approval_note.label}</td>
        <td>{$form.approval_note.html}</td>
    </tr>
  </tbody>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>

<details class="crm-accordion-bold crm-plain_text_email-accordion">
    <summary>
        {ts}Preview Mailing{/ts}
    </summary>
    <div class="crm-accordion-body">
        <table class="form-layout">
          <tr class="crm-mailing-test-form-block-subject"><td class="label">{ts}Subject:{/ts}</td><td>{$preview.subject}</td></tr>
    {if $preview.attachment}
          <tr class="crm-mailing-test-form-block-attachment"><td class="label">{ts}Attachment(s):{/ts}</td><td>{$preview.attachment}</td></tr>
    {/if}
          {if $preview.viewURL}
    <tr><td class="label">{if $preview.type eq 'html'}{ts}Mailing HTML:{/ts}{else}{ts}Mailing Text:{/ts}{/if}</td><td><iframe height="300" src="{$preview.viewURL}" width="80%"><a href="{$preview.viewURL}" onclick="window.open(this.href); return false;">{ts}Mailing Text:{/ts}</a></iframe></td></tr>
          {/if}
        </table>
    </div>
</details>

</div>
