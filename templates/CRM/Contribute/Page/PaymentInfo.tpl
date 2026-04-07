{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $show eq 'payments'}
{literal}
<script type='text/javascript'>
CRM.$(function($) {
  if ($("#payment-info").length) {
    var dataUrl = {/literal}'{crmURL p="civicrm/payment/view" h=0 q="action=browse&id=$componentId&cid=`$contactId`&component=$component&context=payment_info&snippet=4"}'{literal};
    $.ajax({
      url: dataUrl,
      async: false,
      success: function(html) {
        $("#payment-info").html(html).trigger('crmLoad');
      }
    });
  }
});
</script>
{/literal}
{/if}
{if $context eq 'payment_info'}
  {literal}
  <script type='text/javascript'>
  CRM.$(function($) {
    $('#paymentInfoTotalPaid').text("{/literal}{$paymentInfo.paid|crmMoney:$paymentInfo.currency}{literal}").parent().addClass('crm-grid-row').removeClass('hiddenElement');
    $('#paymentInfoAmountDue').text("{/literal}{$paymentInfo.balance|crmMoney:$paymentInfo.currency}{literal}").parent().addClass('crm-grid-row').removeClass('hiddenElement');
    {/literal}
      {if $paymentInfo.balance > 0}
        // Add bold on the row so that the theme can more easily do something more meaningful
        {literal}$('#paymentInfoAmountDue').parent().addClass('bold');{/literal}
      {/if}
    {literal}
  });
  </script>
{/literal}
{/if}
