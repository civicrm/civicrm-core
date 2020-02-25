{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  {include file="CRM/Contribute/Form/Task.tpl"}
</div>
{if $selectedOutput ne 'email'}
  <div class="help">
    {ts}You may choose to email invoice to contributors OR download a PDF file containing one invoice per page to your local computer by clicking <strong>Process Invoice(s)</strong> . Your browser may display the file for you automatically, or you may need to open it for printing using any PDF reader (such as Adobe&reg; Reader).{/ts}
  </div>
{/if}

<table class="form-layout-compressed">
  {if $selectedOutput ne 'email'}
    <tr>
      <td class="label">{$form.output.email_invoice.label}</td>
      <td>{$form.output.email_invoice.html}</td>
    </tr>
  {/if}
  <tr id="selectEmailFrom" style="display: none" class="crm-contactEmail-form-block-fromEmailAddress crm-email-element">
    <td class="label">{$form.from_email_address.label}</td>
    <td>{$form.from_email_address.html} {help id="id-from_email" file="CRM/Contact/Form/Task/Email.hlp" isAdmin=$isAdmin}</td>
  </tr>
  <tr class="crm-email-element">
    <td class="label">{$form.email_comment.label}</td>
    <td>{$form.email_comment.html}</td>
  </tr>
  {if $selectedOutput ne 'email'}
    <tr>
      <td class="label">{$form.output.pdf_invoice.label}</td>
      <td>{$form.output.pdf_invoice.html}</td>
    </tr>
  {/if}
  <tr class="crm-pdf-element">
    <td class="label">{$form.pdf_format_id.label}</td>
    <td>{$form.pdf_format_id.html}</td>
  </tr>
</table>

<div class="spacer"></div>
<div class="crm-submit-buttons">
  {$form.buttons.html}
</div>


<script type="text/javascript">
  {literal}
  CRM.$(function ($) {
    var o = $('input[name="output"]');
    if (o.length > 1) {
      $('.crm-email-element').hide();
      showhideEmailElements();
      o.on('click', showhideEmailElements);
    }
    else {
      $('.crm-email-element').show();
    }

    function showhideEmailElements() {
      if ($('input[name="output"]:checked').val() == 'email_invoice') {
        $('.crm-email-element').show();
        $('.crm-pdf-element').hide();
      }
      else {
        $('.crm-pdf-element').show();
        $('.crm-email-element').hide();
      }
    }
  });
  {/literal}
</script>

