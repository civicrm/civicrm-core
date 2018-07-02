{*
 Extra fields for iATS secure SWIPE
*}

<div id="iats-swipe">
      <div class="crm-section cad-instructions-section">
        <div class="label"><em>{ts domain='com.iatspayments.civicrm'}Get ready to SWIPE! Place your cursor in the Encrypted field below and swipe card.{/ts}</em></div>
        <div class="content"><img width=220 height=220 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/usb_reader.jpg}"></div>
        <div class="clear"></div>
      </div>
      <div class="crm-section encrypted-credit-card-section">
        <div class="label">{$form.encrypted_credit_card_number.label}</div>
        <div class="content">{$form.encrypted_credit_card_number.html}</div>
        <div class="clear"></div>
      </div>
</div>
{literal}<script type="text/javascript">
  cj(function ($) {
    iatsSWIPERefresh();
  });
</script>
{/literal}
