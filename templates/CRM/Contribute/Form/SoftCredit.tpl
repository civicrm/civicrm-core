{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template for adding form elements for soft credit form*}
<table class="form-layout-compressed crm-soft-credit-block">
  {section name='i' start=1 loop=$rowCount}
    {assign var='rowNumber' value=$smarty.section.i.index}
    <tr id="soft-credit-row-{$rowNumber}"
        class="crm-contribution-form-block-soft_credit_to {if $rowNumber gte $showSoftCreditRow}hiddenElement{/if}">
      <td>
        {$form.soft_credit_contact_id.$rowNumber.label}<br>{$form.soft_credit_contact_id.$rowNumber.html|crmAddClass:twenty}
      </td>
      <td>
        {$form.soft_credit_amount.$rowNumber.label}<br>{$form.soft_credit_amount.$rowNumber.html|crmAddClass:eight}
      </td>
      <td>
        {$form.soft_credit_type.$rowNumber.label}<br>
        {$form.soft_credit_type.$rowNumber.html}
        &nbsp;<a class="crm-hover-button soft-credit-delete-link" href="#"><span class="icon delete-icon"></span></a>
      </td>
    </tr>
  {/section}
  <tr>
    <td>
      <a href="#" class="crm-hover-button" id="addMoreSoftCredit"><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}another soft credit{/ts}</a>
    </td>
  </tr>
</table>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $("form.{/literal}{$form.formClass}{literal}");
    $('#showPCP, #showSoftCredit').click(function(){
      return showHideSoftCreditAndPCP();
    });

    function showHideSoftCreditAndPCP() {
      $('.crm-contribution-pcp-block').toggle();
      $('.crm-contribution-pcp-block-link').toggle();
      $('.crm-contribution-form-block-soft_credit_to').toggle();
      return false;
    }

    $('#addMoreSoftCredit').on('click', function () {
      if ($('tr.crm-contribution-form-block-soft_credit_to').hasClass("hiddenElement")) {
        $('.crm-contribution-form-block-soft_credit_to tr.hiddenElement').filter(':first').show().removeClass('hiddenElement');
      }
      if ($('.crm-soft-credit-block tr.hiddenElement').length < 1) {
        $('#addMoreSoftCredit').hide();
      }
      return false;
    });

    $('.crm-soft-credit-block tr span').each(function () {
      if ($(this).hasClass('crm-error')) {
        $(this).parents('tr').show();
      }
    });

    $('.soft-credit-delete-link').click(function(){
      $(this).closest('tr').find('input').val('');
      $(this).closest('tr').addClass('hiddenElement').removeAttr('style');
      $('#addMoreSoftCredit').show();
      return false;
    });

    $('input[name^="soft_credit_contact_"]').on('change', function(){
      var rowNum = $(this).prop('id').replace('soft_credit_contact_id_','');
      var totalAmount = $('#total_amount').val();
      //assign total amount as default soft credit amount
      $('#soft_credit_amount_'+ rowNum).val(totalAmount);
      var thousandMarker = {/literal}{$config->monetaryThousandSeparator|json_encode}{literal};
      $('#soft_credit_type_'+ rowNum).select2('val', $('#sct_default_id').val());
      totalAmount = Number(totalAmount.replace(thousandMarker,''));
      if (rowNum > 1) {
        var scAmount = Number($('#soft_credit_amount_'+ (rowNum - 1)).val().replace(thousandMarker,''));
        if (scAmount < totalAmount) {
          //if user enters less than the total amount and adds another soft credit row,
          //the soft credit amount default will be left empty
          $('#soft_credit_amount_'+ rowNum).val('');
        }
      }
    });

  });
</script>
{/literal}
