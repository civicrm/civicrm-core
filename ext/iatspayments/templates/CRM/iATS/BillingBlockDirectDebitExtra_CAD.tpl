{*
 Extra fields for iats direct debit, template for CAD
*}

<script type="text/javascript" src="{crmResURL ext=com.iatspayments.civicrm file=js/dd_acheft.js}"></script>
<script type="text/javascript" src="{crmResURL ext=com.iatspayments.civicrm file=js/dd_cad.js}"></script>

    <div id="iats-direct-debit-extra">
      <div class="crm-section cad-instructions-section">
        <div class="label"><em>{ts domain='com.iatspayments.civicrm'}You can find your Transit number, Bank number and Account number by inspecting a cheque.{/ts}</em></div>
        <div class="content"><img width=500 height=303 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/CDN_cheque_500x.jpg}"></div>
        <div class="description"><em>{ts domain='com.iatspayments.civicrm'}Please enter them below without any punctuation or spaces.{/ts}</em></div>
        <div class="clear"></div>
      </div>
      <div class="crm-section cad-transit-number-section">
        <div class="label">{$form.cad_transit_number.label}</div>
        <div class="content">{$form.cad_transit_number.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section cad-bank-number-section">
        <div class="label">{$form.cad_bank_number.label}</div>
        <div class="content">{$form.cad_bank_number.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section bank-account-type-section">
        <div class="label">{$form.bank_account_type.label}</div>
        <div class="content">{$form.bank_account_type.html}</div>
        <div class="clear"></div>
      </div>
    </div>
{literal}<script type="text/javascript">
  cj(function ($) {
    iatsACHEFTRefresh();iatsACHEFTca();
  });
</script>
{/literal}
