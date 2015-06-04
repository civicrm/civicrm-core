{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{literal}
<script type="text/javascript">

  CRM.$(function($) {
    function buildPaymentBlock(type) {
      {/literal}{if !$isBillingAddressRequiredForPayLater}{literal}
      if (type == 0) {
        $("#billing-payment-block").html('');
        return;
      }
      {/literal}{/if}

      {if $contributionPageID}
        {capture assign='contributionPageID'}id={$contributionPageID}&{/capture}
      {else}
        {capture assign='contributionPageID'}{/capture}
      {/if}
      {if $urlPathVar}
        {capture assign='urlPathVar'}{$urlPathVar}&{/capture}
      {else}
        {capture assign='urlPathVar'}{/capture}
      {/if}

      var dataUrl = "{crmURL p='civicrm/payment/form' h=0 q="`$urlPathVar``$contributionPageID`processor_id="}" + type;

      {literal}
      CRM.loadPage(dataUrl, {target: '#billing-payment-block'});
    }
  
    $('.crm-group.payment_options-group').show();
    $('[name=payment_processor_id]').on('change.paymentBlock', function() {
        buildPaymentBlock($(this).val());
    });
  });

</script>
{/literal}
