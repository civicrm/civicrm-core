
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
      var funName = ( cj.inArray(processorId, recurPaymentProIds.split(',')) > -1 ) ? 'show' : 'hide';

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
