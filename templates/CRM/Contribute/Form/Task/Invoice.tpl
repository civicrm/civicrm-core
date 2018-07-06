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

