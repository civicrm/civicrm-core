{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* this template is used for adding Credit Cart and billing details *}
<div id="id-creditCard" class="section-shown">
    {include file='CRM/Core/BillingBlock.tpl'}
</div>

{* build recurring contribution block. *}
{if $buildRecurBlock}
{literal}
<script type="text/javascript" >

   function enablePeriod( )
   {
       var frUnit = cj( '#frequency_unit' );
       var frInerval = cj( '#frequency_interval' );
       var installments = cj( '#installments' );
       isDisabled = false;

       if ( cj( 'input:radio[name="is_recur"]:checked').val() == 0 )  {
          isDisabled = true;
          frInerval.val( '' );
          installments.val( '' );
       }

       frUnit.prop( 'disabled', isDisabled );
       frInerval.prop( 'disabled', isDisabled );
       installments.prop( 'disabled', isDisabled );
   }

   function buildRecurBlock( processorId ) {

       if ( !processorId ) processorId = cj( "#payment_processor_id" ).val( );
       var recurPaymentProIds = {/literal}'{$recurringPaymentProcessorIds}'{literal};
       var funName = 'hide';
       if ( recurPaymentProIds.indexOf( processorId ) != -1 ) funName = 'show';

       var priceSet = cj("#price_set_id");
       if ( priceSet && priceSet.val( ) ) {
          funName = 'hide';
          //reset the values of recur block.
    if ( cj( 'input:radio[name="is_recur"]:checked').val() ) {
        cj("#installments").val('');
        cj("#frequency_interval").val('');
        cj( 'input:radio[name="is_recur"]')[0].checked = true;
    }
       }


       enablePeriod( );
       eval( 'cj( "#recurringPaymentBlock" ).' + funName + "( )" );
   }

   CRM.$(function($) {
       buildRecurBlock( null );
       enablePeriod( );
   });

</script>
{/literal}
{/if}
