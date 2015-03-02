{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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

<div class="crm-core-form-recurringentity-block crm-accordion-wrapper" id="recurring-entity-block">
  <div class="crm-accordion-header">
    {ts 1=$recurringEntityType}Repeat %1{/ts}
  </div>
  <div class="crm-accordion-body">
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
    <table class="form-layout-compressed">
      <tr class="crm-core-form-recurringentity-block-repetition_start_date" id="tr-repetition_start_date">
        <td class="label">{$form.repetition_start_date.label}</td>
        <td>{include file="CRM/common/jcalendar.tpl" elementName=repetition_start_date}</td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-repetition_frequency">
        <td class="label">{$form.repetition_frequency_unit.label}&nbsp;<span class="crm-marker">*</span>  {help id="id-repeats" entityType=$recurringEntityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>{$form.repetition_frequency_interval.html} {$form.repetition_frequency_unit.html}</td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-start_action_condition">
        <td class="label">
          <label for="repeats_on">{$form.start_action_condition.label} {help id="id-repeats-on" entityType=$recurringEntityType file="CRM/Core/Form/RecurringEntity.hlp"}</label>
        </td>
        <td>
          {$form.start_action_condition.html}
        </td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-repeats_by">
        <td class="label">{$form.repeats_by.label} {help id="id-repeats-by-month" entityType=$recurringEntityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>{$form.repeats_by.1.html}&nbsp;&nbsp;{$form.limit_to.html}
        </td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-repeats_by">
        <td class="label">{help id="id-repeats-by-week" entityType=$recurringEntityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>{$form.repeats_by.2.html}&nbsp;&nbsp;{$form.entity_status_1.html}&nbsp;&nbsp;{$form.entity_status_2.html}
        </td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-ends">
        <td class="label">{$form.ends.label}&nbsp;<span class="crm-marker" title="This field is required.">*</span> {help id="id-ends-after" entityType=$recurringEntityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>{$form.ends.1.html}&nbsp;{$form.start_action_offset.html} {ts}occurrences{/ts}</td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-absolute_date">
        <td class="label"> {help id="id-ends-on" entityType=$recurringEntityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>{$form.ends.2.html}&nbsp;{include file="CRM/common/jcalendar.tpl" elementName=repeat_absolute_date}
        </td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-exclude_date">
        <td class="label">{$form.exclude_date_list.label} {help id="id-exclude-date" entityType=$recurringEntityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>{$form.exclude_date_list.html}</td>
      </tr>
    </table>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
  </div>
</div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $('form.{/literal}{$form.formClass}{literal}');

    $('#repetition_start_date_display').closest("tr").hide();
    /****** On load "Repeats By" and "Repeats On" blocks should be hidden if dropdown value is not week or month****** (Edit Mode)***/
    switch ($('#repetition_frequency_unit').val()) {
      case 'week':
        $('.crm-core-form-recurringentity-block-start_action_condition').show();
        $('.crm-core-form-recurringentity-block-repeats_by td').hide();
        break;
      case 'month':
        $('.crm-core-form-recurringentity-block-repeats_by td').show();
        $('.crm-core-form-recurringentity-block-start_action_condition').hide();
        break;
      default:
        $('.crm-core-form-recurringentity-block-start_action_condition').hide();
        $('.crm-core-form-recurringentity-block-repeats_by td').hide();
        break;
    }

    /***********On Load Set Ends Value (Edit Mode) **********/
    switch ($('input:radio[name=ends]:checked').val()) {
      case '1':
        $('#start_action_offset').prop('disabled', false);
        $('#repeat_absolute_date_display').prop('disabled', true).val('');
        break;
      case '2':
        $('#repeat_absolute_date_display').prop('disabled', false);
        $('#start_action_offset').prop('disabled', true).val('');
        break;
      default:
        $('#start_action_offset').prop('disabled', true);
        $('#repeat_absolute_date_display').prop('disabled', true);
        break;
    }

    /******On Load set Repeats by section******************/
    switch ($('input:radio[name=repeats_by]:checked').val()) {
      case '1':
        $('#limit_to').prop('disabled', false);
        $('#entity_status_1, #entity_status_2').prop('disabled', true);
        break;
      case '2':
        $('#entity_status_1, #entity_status_2').prop('disabled', false);
        $('#limit_to').prop('disabled', true);
        break;
      default:
          //Just in-case block shows up, disable it
          $('#limit_to, #entity_status_1, #entity_status_2').prop('disabled', true);
        break;
    }

    $('#repetition_frequency_unit').change(function () {
      switch ($(this).val()) {
        case 'hour':
          $('.crm-core-form-recurringentity-block-start_action_condition').hide();
          $('.crm-core-form-recurringentity-block-repeats_by td').hide();
          break;
        case 'day':
          $('.crm-core-form-recurringentity-block-start_action_condition').hide();
          $('.crm-core-form-recurringentity-block-repeats_by td').hide();
          break;
        case 'week':
          //Show "Repeats On" block when week is selected
          $('.crm-core-form-recurringentity-block-start_action_condition').show();
          $('.crm-core-form-recurringentity-block-repeats_by td').hide();
          break;
        case 'month':
          $('.crm-core-form-recurringentity-block-start_action_condition').hide();
          //Show "Repeats By" block when month is selected
          $('.crm-core-form-recurringentity-block-repeats_by td').show();
          break;
        case 'year':
          $('.crm-core-form-recurringentity-block-start_action_condition').hide();
          $('.crm-core-form-recurringentity-block-repeats_by td').hide();
          break;
      }
    });

    // For "Ends" block
    $('input:radio[name=ends]').click(function() {
      switch ($(this).val()) {
        case '1':
          $('#start_action_offset').prop('disabled', false);
          $('#repeat_absolute_date_display').val('');
          break;
        case '2':
          $('#repeat_absolute_date_display').prop('disabled', false);
          $('#start_action_offset').val('');
          break;
        default:
          $('#repeat_absolute_date_display').prop('disabled', true);
          break;
      }
    });

    //For "Repeats By" block
    $('input:radio[name=repeats_by]').click(function() {
      $('#limit_to').prop('disabled', $(this).val() != 1);
      $('#entity_status_1, #entity_status_2').prop('disabled', $(this).val() != 2);
    });

    $form.submit(function() {
      //Check form for values submitted
      if ($('input[name=ends]:checked').val() == 1) {
        if ($('#start_action_offset').val() == "") {
          $('#start_action_offset').crmError();
          return false;
        }
      } else if ($('input[name=ends]:checked').val() == 2) {
        if ($('#repeat_absolute_date_display').val() == "") {
          $('#repeat_absolute_date_display').crmError();
          return false;
        }
      }
    });

    function getDisplayDate(date) {
      return $.datepicker.formatDate(CRM.config.dateInputFormat, $.datepicker.parseDate('yy-mm-dd', date));
    }

    // Combine select2 and datepicker into a multi-select-date widget
    $('#exclude_date_list').crmSelect2({
      multiple: true,
      data: [],
      initSelection: function(element, callback) {
        var values = [];
        $.each($(element).val().split(','), function(k, v) {
          values.push({
            text: getDisplayDate(v),
            id: v
          });
        });
        callback(values);
      }
    })
      // Prevent select2 from opening and show a datepicker instead
      .on('select2-opening', function(e) {
        var $el = $(this);
        e.preventDefault();
        $('.select2-search-field input', $el.select2('container'))
          .datepicker({dateFormat: CRM.config.dateInputFormat})
          .datepicker('show')
          .off('.crmDate')
          .on('change.crmDate', function() {
            if ($(this).val()) {
              var date = $(this).datepicker('getDate'),
                data = $el.select2('data') || [];
              data.push({
                text: $.datepicker.formatDate(CRM.config.dateInputFormat, date),
                id: $.datepicker.formatDate('yy-mm-dd', date)
              });
              $el.select2('data', data);
            }
          });
      });

    //If there are changes in repeat configuration, enable save button
    //Dialog for preview repeat Configuration dates
    function previewDialog() {
      $('#allowRepeatConfigToSubmit').val(CRM.utils.initialValueChanged('.crm-core-form-recurringentity-block') ? '1' : '0');
      var payload = $form.serialize() + '&entity_table={/literal}{$entityTable}{literal}&entity_id={/literal}{$currentEntityId}{literal}',
        settings = CRM.utils.adjustDialogDefaults({
          url: CRM.url("civicrm/recurringentity/preview", payload)
        });
      CRM.confirm(settings)
        .on('crmConfirm:yes', function() {
          $form.submit();
        });
    }

    $('#_qf_Repeat_submit-top, #_qf_Repeat_submit-bottom').click(function (e) {
      previewDialog();
      e.preventDefault();
    });

    $('#_qf_Activity_upload-top, #_qf_Activity_upload-bottom').click( function (e) {
      if (CRM.utils.initialValueChanged('.crm-core-form-recurringentity-block')) {
        e.preventDefault();
        previewDialog();
      }
    });

  });

</script>
{/literal}
