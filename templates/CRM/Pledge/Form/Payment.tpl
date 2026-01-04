{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for updating pledge payment*}
<div class="crm-block crm-form-block crm-pledge-payment-form-block">
  <table class="form-layout-compressed">
    <tr>
      <td class="label">{ts}Status{/ts}</td>
      <td class="form-layout">{$status}</td>
    </tr>
    <tr>
      <td class="label">{$form.scheduled_date.label}</td>
      <td>{$form.scheduled_date.html}</td>
    </tr>
    <tr>
      <td class="label">{$form.scheduled_amount.label}</td>
      <td class="form-layout">
        {$form.currency.html}&nbsp;{$form.scheduled_amount.html}
        {if !$pledgePayment}
          {capture assign='linkTitle'}{ts}Adjust payment amount{/ts}{/capture}
          <a href="#" class="crm-hover-button action-item adjust-pledge-payment">
            {$linkTitle}
          </a>
          {help id="adjust-payment-amount" title=$linkTitle}
        {/if}
      </td>
    </tr>
    <tr id="adjust-option-type" class="crm-contribution-form-block-option_type">
      <td class="label"></td> <td>{$form.option_type.html}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </fieldset>
</div>
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $('#adjust-option-type').hide();

      $('a.adjust-pledge-payment').click(function(e) {
        e.preventDefault();
        $(this).hide();
        $('#adjust-option-type').show();
        $("#scheduled_amount").prop("readonly", false);
      });
    });
  </script>
{/literal}
