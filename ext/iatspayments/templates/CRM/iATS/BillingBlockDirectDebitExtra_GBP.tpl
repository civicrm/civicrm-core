{*
 iATS direct debit UK customization
 Extra fields
 Requires js/dd_uk.js to to all it's proper work
*}
<div id="iats-direct-debit-gbp-declaration">
  <fieldset class="iats-direct-debit-gbp-declaration">
  <legend>Declaration</legend>
  <div class="crm-section">
    <div class="label">{$form.payer_validate_declaration.label}</div>
    <div class="content">{$form.payer_validate_declaration.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="content"><strong>{ts domain='com.iatspayments.civicrm'}Note: {/ts}</strong>{ts domain='com.iatspayments.civicrm'}All Direct Debits are protected by a guarantee. In future, if there is a change to the date, amount of frequency of your Direct Debit, we will always give you 5 working days notice in advance of your account being debited. In the event of any error, you are entitled to an immediate refund from your Bank of Building Society. You have the right to cancel at any time and this guarantee is offered by all the Banks and Building Societies that accept instructions to pay Direct Debits. A copy of the safeguards under the Direct Debit Guarantee will be sent to you with our confirmation letter.{/ts}
    </div>
    <div><br/></div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.payer_validate_contact.label}</div>
    <div class="content"><strong>{ts domain='com.iatspayments.civicrm'}Contact Information: {/ts}</strong>{$form.payer_validate_contact.html}</div>
    <div class="clear"></div>
    <div class="content">
  <img width=166 height=61 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/bacs.png}">
  <img width=148 height=57 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/direct-debit.jpg}">
  <img width=134 height=55 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/iats.jpg}">
</div>
  </div>
  </fieldset>
</div>

<div id="iats-direct-debit-extra">
  <div class="crm-section gbp-instructions-section">
    <div class="label"><em>{ts domain='com.iatspayments.civicrm'}You can find your Account Number and Sort Code by inspecting a cheque.{/ts}</em></div>
    <div class="content"><img width=500 height=303 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/GBP_cheque_500x.jpg}"></div>
  </div>
</div>
<div>
  <div id="iats-direct-debit-logos"></div>
  <img width=166 height=61 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/bacs.png}">
  <img width=148 height=57 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/direct-debit.jpg}">
  <img width=134 height=55 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/iats.jpg}">
</div>
<div id="iats-direct-debit-start-date">
  <div class="crm-section payer-validate-start-date">
    <div class="label">{$form.payer_validate_start_date.label}</div>
    <div class="content">{$form.payer_validate_start_date.html}</div>
    <div class="content">{ts domain='com.iatspayments.civicrm'}You may select a later start date if you wish.{/ts}</div>
    <div class="clear"></div>
  </div>
</div>
<div id="iats-direct-debit-gbp-payer-validate">
  <div class="crm-section payer-validate-address">
    <div class="label">{$form.payer_validate_address.label}</div>
    <div class="content">{$form.payer_validate_address.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section payer-validate-service-user-number">
    <div class="label">{$form.payer_validate_service_user_number.label}</div>
    <div class="content">{$form.payer_validate_service_user_number.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section payer-validate-reference">
    <div class="label">{$form.payer_validate_reference.label}</div>
    <div class="content">{$form.payer_validate_reference.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section payer-validate-instruction">
    <div class="label">{$form.payer_validate_instruction.label}</div>
    <div class="content">{$form.payer_validate_instruction.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section payer-validate-date">
    <div class="label">{$form.payer_validate_date.label}</div>
    <div class="content">{$form.payer_validate_date.html}</div>
    <div class="clear"></div>
  </div>
  <input name="payer_validate_url" type="hidden" value="{crmURL p='civicrm/iatsjson' q='reset=1'}">
</div>
<div id="iats-direct-debit-gbp-continue">
  <div class="messages crm-error">
    <div class="icon red-icon alert-icon"></div>
    {ts}Please fix the following errors in the form fields above:{/ts}
    <ul id="payer-validate-required">
    </ul>
  </div>
</div>
{literal}<script type="text/javascript">
  cj(function ($) {
    iatsUKDDRefresh();
  });
</script>
{/literal}
