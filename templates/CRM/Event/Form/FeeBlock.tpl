{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $priceSet}
  <div id='validate_pricefield' class='messages crm-error hiddenElement'></div>
{literal}
  <script type="text/javascript">

    var fieldOptionsFull = [];
    {/literal}
    {foreach from=$priceSet.fields item=fldElement key=fldId}
    {if $fldElement.options}
    {foreach from=$fldElement.options item=fldOptions key=opId}
    {if $fldOptions.is_full}
    {literal}
    fieldOptionsFull[{/literal}{$fldId}{literal}] = [];
    fieldOptionsFull[{/literal}{$fldId}{literal}][{/literal}{$opId}{literal}] = 1;
    {/literal}
    {/if}
    {/foreach}
    {/if}
    {/foreach}
    {literal}

    if ( fieldOptionsFull.length > 0 ) {
      CRM.$(function($) {
        $("input,#priceset select,#priceset").each(function () {
          if ( $(this).attr('price') ) {
            switch( $(this).attr('type') ) {
              case 'checkbox':
              case 'radio':
                $(this).click( function() {
                  validatePriceField(this);
                });
                break;

              case 'select-one':
                $(this).change( function() {
                  validatePriceField(this);
                });
                break;
              case 'text':
                $(this).bind( 'keyup', function() { validatePriceField(this) });
                break;
            }
          }
        });
      });

      function validatePriceField( obj ) {
        var namePart =  cj(obj).attr('name').split('_');
        var fldVal  =  cj(obj).val();
        if ( cj(obj).attr('type') == 'checkbox') {
          var eleIdpart = namePart[1].split('[');
          var eleId = eleIdpart[0];
        }
        else {
          var eleId  = namePart[1];
        }
        var showError = false;

        switch( cj(obj).attr('type') ) {
          case 'text':
            if ( fieldOptionsFull[eleId] && fldVal ) {
              showError = true;
              cj(obj).parent( ).parent( ).children('.label').addClass('crm-error');
            }
            else {
              cj(obj).parent( ).parent( ).children('.label').removeClass('crm-error');
              cj('#validate_pricefield').hide( ).html('');
            }
            break;

          case 'checkbox':
            var checkBoxValue = eleIdpart[1].split(']');
            if ( cj(obj).prop("checked") == true &&
                    fieldOptionsFull[eleId] &&
                    fieldOptionsFull[eleId][checkBoxValue[0]]) {
              showError = true;
              cj(obj).parent( ).addClass('crm-error');
            }
            else {
              cj(obj).parent( ).removeClass('crm-error');
            }
            break;

          default:
            if ( fieldOptionsFull[eleId] &&
                    fieldOptionsFull[eleId][fldVal]  ) {
              showError = true;
              cj(obj).parent( ).addClass('crm-error');
            }
            else {
              cj(obj).parent( ).removeClass('crm-error');
            }
        }

        if ( showError ) {
          cj('#validate_pricefield').show().html('<i class="crm-i fa-exclamation-triangle crm-i-red" aria-hidden="true"></i>{/literal} {ts escape='js'}This Option is already full for this event.{/ts}{literal}');
        }
        else {
          cj('#validate_pricefield').hide( ).html('');
        }
      }
    }

    // change the status to default 'partially paid' for partial payments
    var feeAmount, userModifiedAmount;
    var partiallyPaidStatusId = {/literal}{$partiallyPaidStatusId}{literal};

    cj('#total_amount')
            .focus(
                    function() {
                      feeAmount = cj(this).val();
                      feeAmount = Number(feeAmount.replace(/[^0-9\.]+/g,""));
                    }
            )
            .change(
                    function() {
                      userModifiedAmount = cj(this).val();
                      userModifiedAmount = Number(userModifiedAmount.replace(/[^0-9\.]+/g,""));
                      if (userModifiedAmount < feeAmount) {
                        cj('.crm-participant-form-block-status_id #status_id').val(partiallyPaidStatusId).change();
                      }
                    }
            );

    cj('form[name=Participant]').on("click", '.validate',
            function(e) {
              if (CRM.$('#total_amount').length == 0) {
                var $balance = CRM.$('#payment-info-balance');
                if ($balance.length > 0 && parseFloat($balance.attr('data-balance')) == 0) {
                  return true;
                }
              }
              var userSubmittedStatus = cj('.crm-participant-form-block-status_id #status_id').val();
              var statusLabel = cj('.crm-participant-form-block-status_id #status_id option:selected').text();
              if (userModifiedAmount < feeAmount && userSubmittedStatus != partiallyPaidStatusId) {
                var msg = "{/literal}{ts escape="js" 1="%1"}Payment amount is less than the amount owed. Expected participant status is 'Partially paid'. Are you sure you want to set the participant status to %1? Click OK to continue, Cancel to change your entries.{/ts}{literal}";
                var result = confirm(ts(msg, {1: statusLabel}));
                if (result == false) {
                  return false;
                }
              }
            }
    );
  </script>
{/literal}
{/if}

{include file="CRM/Event/Form/EventFees.tpl"}
