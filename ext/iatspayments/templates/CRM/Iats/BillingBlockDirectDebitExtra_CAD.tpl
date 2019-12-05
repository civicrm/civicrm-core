{*
 Extra fields for iats direct debit, template for CAD
*}
    <div id="iats-direct-debit-extra">
      <div class="crm-section cad-instructions-section">
        <div class="label"><em>{ts domain='com.iatspayments.civicrm'}You can find your Transit number, Bank number and Account number by inspecting a cheque.{/ts}</em></div>
        <div class="content"><img width=500 height=303 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/Iats/CDN_cheque_500x.jpg}"></div>
        <div class="description"><em>{ts domain='com.iatspayments.civicrm'}Please enter them below without any punctuation or spaces.{/ts}</em></div>
        <div class="clear"></div>
      </div>
      <div class="crm-section cad-transit-number-section">
        <div class="label">{ts domain='com.iatspayments.civicrm'}{$form.cad_transit_number.label}{/ts}</div>
        <div class="content">{$form.cad_transit_number.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section cad-bank-number-section">
        <div class="label">{ts domain='com.iatspayments.civicrm'}{$form.cad_bank_number.label}{/ts}</div>
        <div class="content">{$form.cad_bank_number.html}</div>
        <div class="clear"></div>
      </div>
    </div>
<script type="text/javascript">
  var ddAcheftJs = "{crmResURL ext=com.iatspayments.civicrm file=js/dd_acheft.js}";
  var ddCadJs = "{crmResURL ext=com.iatspayments.civicrm file=js/dd_cad.js}";
{literal}
  CRM.$(function ($) {
    $.getScript(ddAcheftJs);
    $.getScript(ddCadJs);
  });
{/literal}
</script>
