{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
<div class="batch-entry form-item">
  <div class="help">
    {ts}Click Validate & Process below when you've entered all items for the batch. You can also Save & Continue Later at any time.{/ts}
    {if call_user_func(array('CRM_Core_Permission','check'), 'administer CiviCRM')}
      {capture assign=batchEntryProfileURL}{crmURL p="civicrm/admin/uf/group" q="reset=1&selectedChild=reserved-profiles"}{/capture}
      {ts 1=$batchEntryProfileURL}Add, remove or change the order of columns by editing the corresponding <a href="%1" target="_blank">Bulk Entry profile</a>.{/ts}
      {if $batchType EQ 1}
        {ts}Custom fields and a Personal Campaign Page field can be added if needed.{/ts}
      {/if}
    {/if}
  </div>
  {if $batchAmountMismatch}
    <div class="status message status-warning">
      <i class="crm-i fa-exclamation-triangle"></i> {ts}Total for amounts entered below does not match the expected batch total.{/ts}
    </div>
    <span class="crm-button crm-button_qf_Entry_upload_force-save">
      {$form._qf_Entry_upload_force.html}
    </span>
    <div class="clear"></div>
  {/if}
  <table class="form-layout-compressed batch-totals">
    <tr>
      <td class="label">
        <label>{ts}Total amount expected{/ts}</label>
      </td>
      <td class="right"><span class="batch-expected-total">{$batchTotal|crmMoney}</span></td>
    </tr>
    <tr>
      <td class="label">
        <label>{ts}Total amount entered{/ts}</label>
      </td>
      <td class="right">{$config->defaultCurrencySymbol} <span class="batch-actual-total"></span></td>
    </tr>
  </table>

  <div class="crm-copy-fields crm-grid-table" id="crm-batch-entry-table">
    <div class="crm-grid-header">
      <div class="crm-grid-cell">&nbsp;</div>
      <div class="crm-grid-cell">{ts}Contact{/ts}</div>
      {if $batchType eq 2}
        <div class="crm-grid-cell">&nbsp;</div>
      {/if}
      {if $batchType eq 3}
        <div class="crm-grid-cell">{ts}Open Pledges (Due Date - Amount){/ts}</div>
      {/if}
      {foreach from=$fields item=field key=fieldName}
        <div class="crm-grid-cell">
          {if $field.name|substr:0:11 ne 'soft_credit' and $field.name ne 'trxn_id'}
          <img src="{$config->resourceBase}i/copy.png"
               alt="{ts 1=$field.title}Click to copy %1 from row one to all rows.{/ts}"
               fname="{$field.name}" class="action-icon"
               title="{ts}Click here to copy the value in row one to ALL rows.{/ts}"/>
          {/if}{$field.title}
        </div>
      {/foreach}
    </div>

    {section name='i' start=1 loop=$rowCount}
      {assign var='rowNumber' value=$smarty.section.i.index}
      <div class="{cycle values="odd-row,even-row"} selector-rows crm-grid-row" entity_id="{$rowNumber}">
        <div class="compressed crm-grid-cell"><span class="batch-edit"></span></div>
        {* contact select/create option*}
        <div class="compressed crm-grid-cell">
          {$form.primary_contact_id.$rowNumber.html|crmAddClass:big}
        </div>

        {if $batchType eq 2}
          {$form.member_option.$rowNumber.html}
        {/if}
        {if $batchType eq 3}
          {$form.open_pledges.$rowNumber.html}
        {/if}
        {foreach from=$fields item=field key=fieldName}
          {assign var=n value=$field.name}
          {if $n eq 'soft_credit'}
            <div class="compressed crm-grid-cell">
              {$form.soft_credit_contact_id.$rowNumber.html|crmAddClass:big}
              {$form.soft_credit_amount.$rowNumber.label}&nbsp;{$form.soft_credit_amount.$rowNumber.html|crmAddClass:eight}
            </div>
          {elseif $n eq 'soft_credit_type'}
            <div class="compressed crm-grid-cell">{$form.soft_credit_type.$rowNumber.html}</div>
          {elseif $n eq 'contribution_soft_credit_pcp_id'}
            <div class="compressed crm-grid-cell">
              <div>{$form.pcp_made_through_id.$rowNumber.html}{$form.pcp_made_through.$rowNumber.html}</div>
              <div>{$form.pcp_display_in_roll.$rowNumber.label}&nbsp;{$form.pcp_display_in_roll.$rowNumber.html}</div>
              <div class="pcp_roll_display">{$form.pcp_roll_nickname.$rowNumber.label}&nbsp;{$form.pcp_roll_nickname.$rowNumber.html}</div>
              <div class="pcp_roll_display">{$form.pcp_personal_note.$rowNumber.label}&nbsp;{$form.pcp_personal_note.$rowNumber.html}</div>
            </div>
          {elseif in_array( $fields.$n.html_type, array('Radio', 'CheckBox'))}
            <div class="compressed crm-grid-cell">&nbsp;{$form.field.$rowNumber.$n.html}</div>
          {elseif $n eq 'total_amount'}
             <div class="compressed crm-grid-cell">
               {$form.field.$rowNumber.$n.html}
               {if $batchType eq 3 }
		 {ts}<span id={$rowNumber} class="pledge-adjust-option"><a href='#'>adjust payment amount</a></span>{/ts}
                 <span id="adjust-select-{$rowNumber}" class="adjust-selectbox">{$form.option_type.$rowNumber.html}</span>
               {/if}
             </div>
          {else}
            <div class="compressed crm-grid-cell">
              {$form.field.$rowNumber.$n.html}
              {if $fields.$n.html_type eq 'File' && !empty($form.field.$rowNumber.$fieldName.value.size)}
                {ts}Attached{/ts}: {$form.field.$rowNumber.$fieldName.value.name}
              {/if}
            </div>
          {/if}
        {/foreach}
      </div>
    {/section}
  </div>
  <div class="crm-submit-buttons">{if $fields}{$form._qf_Batch_refresh.html}{/if} &nbsp; {$form.buttons.html}</div>
</div>
{literal}
<script type="text/javascript">
CRM.$(function($) {
  var $form = $('form.{/literal}{$form.formClass}{literal}');
  $('.selector-rows').change(function () {
    var options = {
      'url': {/literal}"{crmURL p='civicrm/ajax/batch' h=0}"{literal}
    };
    $($form).ajaxSubmit(options);
  });

 $('input[id*="primary_contact_"]').change(function() {
 var temp = this.id.split('_');
   var ROWID = temp[3];
   if ($(this).val()) {
     updateContactInfo(ROWID,'primary_');
   }
 });

 $('select[id^="option_type_"]').each(function () {
    if ($(this).val() == 1) {
      $(this).attr('disabled', true);
      $(this).hide();
    }
  });

  $('#crm-container').on('keyup change', '*.selector-rows', function () {
    // validate rows
    checkColumns($(this));
  });

  // validate rows
  validateRow();

  //calculate the actual total for the batch
  calculateActualTotal();

  $('input[id*="_total_amount"]').bind('keyup change', function () {
    calculateActualTotal();
  });

  {/literal}{if $batchType eq 1 }{literal}
  // hide all dates if send receipt is checked
  hideSendReceipt();

  // hide the receipt date if send receipt is checked
  $('input[id*="][send_receipt]"]').change(function () {
    showHideReceipt($(this));
  });

  {/literal}{elseif $batchType eq 2}{literal}
  $('select[id^="member_option_"]').each(function () {
    if ($(this).val() == 1) {
      $(this).prop('disabled', true);
    }
  });

  // set payment info accord to membership type
  $('select[id*="_membership_type_0"]').change(function () {
    setPaymentBlock($(this), null);
  });

  $('select[id*="_membership_type_1"]').change(function () {
    setPaymentBlock($(this), $(this).val());
  });

  {/literal}{/if}{literal}

  // line breaks between radio buttons and checkboxes
  $('input.form-radio').next().after('<br />');
  $('input.form-checkbox').next().after('<br />');

  //set the focus on first element
  $('#primary_contact_1').focus();

});

function setPaymentBlock(form, memType) {
  var rowID = form.closest('div.crm-grid-row').attr('entity_id');
  var dataUrl = {/literal}"{crmURL p='civicrm/ajax/memType' h=0}"{literal};

  if (!memType) {
    memType = cj('select[id="field_' + rowID + '_membership_type_1"]').val();
  }

  cj.post(dataUrl, {mtype: memType}, function (data) {
    cj('#field_' + rowID + '_financial_type').val(data.financial_type_id);
    cj('#field_' + rowID + '_total_amount').val(data.total_amount).change();
  }, 'json');
}

function hideSendReceipt() {
  cj('input[id*="][send_receipt]"]').each(function () {
    showHideReceipt(cj(this));
  });
}

function showHideReceipt(elem) {
  var rowID = elem.closest('div.crm-grid-row').attr('entity_id');
  if (elem.prop('checked')) {
    cj('.crm-batch-receipt_date-' + rowID).hide();
  }
  else {
    cj('.crm-batch-receipt_date-' + rowID).show();
  }
}

function validateRow() {
  cj('.selector-rows').each(function () {
    checkColumns(cj(this));
  });
}

function checkColumns(parentRow) {
  // show valid row icon if all required data is field
  var validRow = 0;
  var inValidRow = 0;
  var errorExists = false;
  var rowID = parentRow.closest('div.crm-grid-row').attr('entity_id');

  parentRow.find('div .required').each(function () {
    //special case to handle contact autocomplete select
    var fieldId = cj(this).attr('id');
    // datepicker hasTimeEntry would not have an id - not sure why.
    if (typeof fieldId != 'undefined' && fieldId.substring(0, 16) == 'primary_contact_') {
      // if display value is set then make sure we also check if contact id is set
      if (!cj(this).val()) {
        inValidRow++;
      }
      else {
        var contactIdElement = cj('input[name="primary_contact_select_id[' + rowID + ']"]');
        if (cj(this).val() && !contactIdElement.val()) {
          inValidRow++;
          errorExists = true;
        }
        else if (cj(this).val() && contactIdElement.val()) {
          // this is hack to remove error span because we are skipping this for autocomplete fields
          cj(this).next('span.crm-error').remove();
        }
      }
    }
    else {
      if (!cj(this).val()) {
        inValidRow++;
      }
      else {
        if (cj(this).hasClass('error') && (cj(this).hasClass('valid') || cj(this).hasClass('required'))) {
          errorExists = true;
        }
        else {
          validRow++;
        }
      }
    }
  });

  // this means user has entered some data
  if (errorExists) {
    parentRow.find("div:first span").prop('class', 'batch-invalid');
  }
  else {
    if (inValidRow == 0 && validRow > 0) {
      parentRow.find("div:first span").prop('class', 'batch-valid');
    }
    else {
      parentRow.find("div:first span").prop('class', 'batch-edit');
    }
  }
}

function calculateActualTotal() {
  var total = 0;
  cj('input[id*="_total_amount"]').each(function () {
    if (cj(this).val()) {
      total += parseFloat(cj(this).val());
    }
  });

  cj('.batch-actual-total').html(formatMoney(total));
}

//money formatting/localization
function formatMoney(amount) {
  var c = 2;
  var t = '{/literal}{$config->monetaryThousandSeparator}{literal}';
  var d = '{/literal}{$config->monetaryDecimalPoint}{literal}';

  var n = amount,
    c = isNaN(c = Math.abs(c)) ? 2 : c,
    d = d == undefined ? "," : d,
    t = t == undefined ? "." : t, s = n < 0 ? "-" : "",
    i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
    j = (j = i.length) > 3 ? j % 3 : 0;

  return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

function updateContactInfo(blockNo, prefix) {
  var contactHiddenElement = 'input[id="' + prefix + 'contact_id_' + blockNo + '"]';
  var contactId = cj(contactHiddenElement).val();

  var returnProperties = '';
  var profileFields = [];
  {/literal}
  {if $contactFields}
  {foreach from=$contactFields item=val key=fldName}
  var fldName = {$fldName|@json_encode};
  {literal}
  if (returnProperties) {
    returnProperties = returnProperties + ',';
  }
  var fld = fldName.split('-');
  returnProperties = returnProperties + fld[0];
  profileFields[fld[0]] = fldName;
  {/literal}
  {/foreach}
  {/if}
  {literal}

  CRM.api('Contact', 'get', {
      'sequential': '1',
      'contact_id': contactId,
      'return': returnProperties },
    { success: function (data) {
      cj.each(data.values[0], function (key, value) {
        // set the values
        var actualFldName = profileFields[key];
        if (key == 'country' || key == 'state_province') {
          idFldName = key + '_id';
          value = data.values[0][idFldName];
        }
        setFieldValue(actualFldName, value, blockNo)
      });

      // for membership batch entry based on contact we need to enable / disable
      // add membership select
      {/literal}{if $batchType eq 2}{literal}
      CRM.api('Membership', 'get', {
          'sequential': '1',
          'contact_id': contactId,
        },
        { success: function (data) {
          if (data.count > 0) {
            //get the information on membership type
            var membershipTypeId = data.values[0].membership_type_id;
            var membershipJoinDate = data.values[0].join_date;
            var membershipStartDate = data.values[0].start_date;
            CRM.api('MembershipType', 'get', {
                'sequential': '1',
                'id': membershipTypeId
              },
              { success: function (data) {
                var memTypeContactId = data.values[0].member_of_contact_id;
                cj('select[id="member_option_' + blockNo + '"]').prop('disabled', false).val(2);
                cj('select[id="field_' + blockNo + '_membership_type_0"]').val(memTypeContactId).change();
                cj('select[id="field_' + blockNo + '_membership_type_1"]').val(membershipTypeId).change();
                cj('#field_' + blockNo + '_' + 'join_date').val(membershipJoinDate).trigger('change');
                cj('#field_' + blockNo + '_' + 'membership_start_date').val(membershipStartDate).trigger('change');
              }
              });
          }
        }
        });
      {/literal}{elseif $batchType eq 3}{literal}
      cj('#open_pledges_'+blockNo).empty();
         cj('#open_pledges_'+blockNo).append(cj('<option>', {
			    value: '',
			    text: '-select-'
			}));
	 CRM.api('Pledge', 'get', {
	  'sequential': 1,
	 'contact_id': contactId || 0
	 },
	{success: function(data) {
	 cj.each(data['values'], function(key, value) {
	  if (value['pledge_status'] != 'Completed') {
	   var date = cj.datepicker.parseDate('yy-mm-dd', value['pledge_next_pay_date']);
           var dateformat = "{/literal}{$config->dateInputFormat}{literal}";
	    cj('#open_pledges_'+ blockNo).append(cj('<option>', {
		value: value['pledge_id'],
		text: cj.datepicker.formatDate(dateformat, date) + ", " + value['pledge_next_pay_amount'] + ' ' + value['pledge_currency']
		}));
	     }
	   });
	 }
        });
      {/literal}{/if}{literal}
    }
    });
}

/**
 * This function is use to setdefault elements via ajax
 *
 * @param fname string field name
 * @return void
 */
function setFieldValue(fname, fieldValue, blockNo) {
  var elementId = cj('[name="field[' + blockNo + '][' + fname + ']"]');

  if (elementId.length == 0) {
    elementId = cj('input[type=checkbox][name^="field[' + blockNo + '][' + fname + ']"][type!=hidden]');
  }

  // if element not found than return
  if (elementId.length == 0) {
    return;
  }

  //check if it is date element
  var isDateElement = elementId.attr('format');

  //get the element type
  var elementType = elementId.attr('type');

  // set the value for all the elements, elements needs to be handled are
  // select, checkbox, radio, date fields, text, textarea, multi-select
  // wysiwyg editor, advanced multi-select ( to do )
  if (elementType == 'radio') {
    if (fieldValue) {
      elementId.filter("[value=" + fieldValue + "]").prop("checked", true);
    }
    else {
      elementId.removeProp('checked');
    }
  }
  else {
    if (elementType == 'checkbox') {
      // handle checkbox
      elementId.removeProp('checked');
      if (fieldValue) {
        cj.each(fieldValue, function (key, value) {
          cj('input[name="field[' + blockNo + '][' + fname + '][' + value + ']"]').prop('checked', true);
        });
      }
    }
    else {
      if (elementId.is('textarea')) {
        CRM.wysiwyg.setVal(elementId, fieldValue);
      }
      else {
        elementId.val(fieldValue);
      }
    }
  }

  // since we use different display field for date we also need to set it.
  // also check for date time field and set the value correctly
  if (isDateElement && fieldValue) {
    setDateFieldValue(fname, fieldValue, blockNo)
  }
}

function setDateFieldValue(fname, fieldValue, blockNo) {
  var dateValues = fieldValue.split(' ');

  var actualDateElement = cj('#field_' + blockNo + '_' + fname);
  var date_format = actualDateElement.attr('format');
  var altDateFormat = 'yy-mm-dd';

  var actualDateValue = cj.datepicker.parseDate(altDateFormat, dateValues[0]);

  // format date according to display field
  var hiddenDateValue = cj.datepicker.formatDate('mm/dd/yy', actualDateValue);

  actualDateElement.val(hiddenDateValue);

  var displayDateValue = actualDateElement.val();
  if (date_format != 'mm/dd/yy') {
    displayDateValue = cj.datepicker.formatDate(date_format, actualDateValue);
  }
  cj('[id^=field_' + blockNo + '_' + fname + '_display]').val(displayDateValue);

  // need to fix time formatting
  if (dateValues[1]) {
    cj('#field_' + blockNo + '_' + fname + '_time').val(dateValues[1].substr(0, 5));
  }
}

if (CRM.batch.type_id == 3){
  cj('select[id*="open_pledges_"]').change(function () {
    setPledgeAmount(cj(this), cj(this).val());
  });
  cj('.pledge-adjust-option').click(function(){
    var blockNo = cj(this).attr('id');
    cj('select[id="option_type_' + blockNo + '"]').show();
    cj('select[id="option_type_' + blockNo + '"]').removeAttr('disabled');
    cj('#field_' + blockNo + '_total_amount').removeAttr('readonly');
  });
}

function setPledgeAmount(form, pledgeID) {
  var rowID = form.closest('div.crm-grid-row').attr('entity_id');
  var dataUrl = CRM.url('civicrm/ajax/pledgeAmount');
  if (pledgeID) {
    cj.post(dataUrl, {pid: pledgeID}, function (data) {
    cj('#field_' + rowID + '_financial_type').val(data.financial_type_id).change();
    cj('#field_' + rowID + '_total_amount').val(data.amount).change();
    cj('#field_' + rowID + '_total_amount').attr('readonly', true);
    }, 'json');
  }
  else {
    cj('#field_' + rowID + '_total_amount').val('').change();
    cj('#field_' + rowID + '_financial_type').val('').change();
    cj('#field_' + rowID + '_total_amount').removeAttr('readonly');
  }
}

//end for pledge amount
</script>
{/literal}
{*include batch copy js js file*}
{include file="CRM/common/batchCopy.tpl"}
