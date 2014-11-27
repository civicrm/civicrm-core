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
{* template for adding form elements for soft credit form*}
{if $honor_block_is_active}
  {crmRegion name="contribution-soft-credit-block"}
    <legend>{$honor_block_title}</legend>
    <div class="crm-section honor_block_text-section">
      {$honor_block_text}
    </div>
    {if $form.soft_credit_type_id.html}
      <div class="crm-section {$form.soft_credit_type_id.name}-section">
        <div class="content" >
          {$form.soft_credit_type_id.html}
          <div class="description">{ts}Select an option to reveal honoree information fields.{/ts}</div>
        </div>
      </div>
    {/if}
  {/crmRegion}
{else}
<table class="form-layout-compressed crm-soft-credit-block">
  {section name='i' start=1 loop=$rowCount}
    {assign var='rowNumber' value=$smarty.section.i.index}
    <tr id="soft-credit-row-{$rowNumber}"
        class="crm-contribution-form-block-soft_credit_to {if $rowNumber gte $showSoftCreditRow}hiddenElement{/if}">
      <td>
        {$form.soft_credit_contact_id.$rowNumber.label}&nbsp;{$form.soft_credit_contact_id.$rowNumber.html|crmAddClass:twenty}
      </td>
      <td>
        {$form.soft_credit_amount.$rowNumber.label}&nbsp;{$form.soft_credit_amount.$rowNumber.html|crmAddClass:eight}
      </td>
      <td>
        {$form.soft_credit_type.$rowNumber.label}&nbsp;{$form.soft_credit_type.$rowNumber.html}
        &nbsp;<a class="crm-hover-button soft-credit-delete-link" href="#"><span class="icon delete-icon"></span></a>
      </td>
    </tr>
  {/section}
  <tr>
    <td>
      <a href="#" class="crm-hover-button" id="addMoreSoftCredit"><span class="icon add-icon"></span> {ts}another soft credit{/ts}</a>
    </td>
  </tr>
</table>
{/if}
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

    // FIXME: This could be much simpler as an entityRef field but pcp doesn't have a searchable api :(
    var pcpURL = CRM.url('civicrm/ajax/rest', 'className=CRM_Contact_Page_AJAX&fnName=getPCPList&json=1&context=contact&reset=1');
    $('#pcp_made_through_id').crmSelect2({
      placeholder: {/literal}'{ts escape="js"}- select -{/ts}'{literal},
      minimumInputLength: 1,
      ajax: {
        url: pcpURL,
        data: function(term) {
          return {term: term};
        },
        results: function(response) {
          return {results: response};
        }
      },
      initSelection: function(el, callback) {
        callback({id: $(el).val(), text: $('[name=pcp_made_through]', $form).val()});
      }
    })
      // This is just a cheap trick to store the name in case of a formrule error
      .on('change', function() {
        $('[name=pcp_made_through]', $form).val($(this).select2('data').text || '');
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
