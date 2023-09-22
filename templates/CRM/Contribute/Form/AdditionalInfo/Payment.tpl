{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* build recurring contribution block. *}
{if $buildRecurBlock}
{literal}
  <script type="text/javascript" >

    function toggleRecur() {
      var isRecur = cj('input[id="is_recur"]:checked');
      var frequencyUnit = cj('#frequency_unit');
      var frequencyInterval = cj('#frequency_interval');
      var installments = cj('#installments');
      if (isRecur.val() > 0) {
        frequencyUnit.prop('disabled', false).addClass('required');
        frequencyInterval.prop('disabled', false).addClass('required');
        installments.prop('disabled', false);
      }
      else {
        frequencyInterval.val('');
        installments.val('');
        frequencyUnit.prop('disabled', true).removeClass('required');
        frequencyInterval.prop('disabled', true).removeClass('required');
        installments.prop('disabled', true);
      }
    }

    function buildRecurBlock(processorId) {
      if (!processorId) processorId = cj("#payment_processor_id").val();
      var recurPaymentProIds = {/literal}'{$recurringPaymentProcessorIds}'{literal};
      var funName = (cj.inArray(processorId, recurPaymentProIds.split(',')) > -1) ? 'show' : 'hide';

      var priceSet = cj("#price_set_id");
      if (priceSet && priceSet.val()) {
        funName = 'hide';
        //reset the values of recur block.
        if (cj('input:radio[name="is_recur"]:checked').val()) {
          cj("#installments").val('');
          cj("#frequency_interval").val('');
          cj('input:radio[name="is_recur"]')[0].checked = true;
        }
      }
      toggleRecur();
      eval('cj("#recurringPaymentBlock").' + funName + "()");
    }

    CRM.$(function($) {
      buildRecurBlock(null);
      toggleRecur();

      cj('input[id="is_recur"]').on('change', function() {
        toggleRecur();
      });
    });

  </script>
{/literal}
{/if}
