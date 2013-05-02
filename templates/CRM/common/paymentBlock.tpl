{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

function buildPaymentBlock( type ) {
    if ( type == 0 ) {
     if (cj("#billing-payment-block").length) {
           cj("#billing-payment-block").html('');
   }
        return;
    }

  var dataUrl = {/literal}"{crmURL p=$urlPath h=0 q='snippet=4&type='}"{literal} + type;

  {/literal}
    {if $urlPathVar}
      dataUrl = dataUrl + '&' + '{$urlPathVar}'
    {/if}

    {if $contributionPageID}
            dataUrl = dataUrl + '&id=' + '{$contributionPageID}'
        {/if}

    {if $qfKey}
      dataUrl = dataUrl + '&qfKey=' + '{$qfKey}'
    {/if}
  {literal}

  var fname = '#billing-payment-block';
  var response = cj.ajax({
                        url: dataUrl,
                        async: false
                        }).responseText;

    cj( fname ).html( response );
}

cj( function() {
    cj('.crm-group.payment_options-group').show();

    cj('input[name="payment_processor"]').change( function() {
        buildPaymentBlock( cj(this).val() );
    });
});

</script>
{/literal}
