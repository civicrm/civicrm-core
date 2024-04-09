{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<details class="crm-core-form-recurringentity-block crm-accordion-bold" id="recurring-entity-block" {if $recurringFormIsEmbedded && !$scheduleReminderId}{else}open{/if}>
  <summary>
    {ts 1=$recurringEntityType}Repeat %1{/ts}
  </summary>
  <div class="crm-accordion-body">
    <table class="form-layout-compressed">
      <tr class="crm-core-form-recurringentity-block-repetition_frequency">
        <td class="label">{$form.repetition_frequency_unit.label}&nbsp;<span class="crm-marker">*</span></td>
        <td>{$form.repetition_frequency_interval.html} {$form.repetition_frequency_unit.html}</td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-start_action_condition">
        <td class="label">
          <label for="repeats_on">{$form.start_action_condition.label}</label>
        </td>
        <td>
          {$form.start_action_condition.html}
        </td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-repeats_by">
        <td class="label">{$form.repeats_by.label}&nbsp;<span class="crm-marker">*</span></td>
        <td>{$form.repeats_by.1.html} {$form.limit_to.html}
        </td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-repeats_by">
        <td class="label"></td>
        <td>{$form.repeats_by.2.html} {$form.entity_status_1.html} {$form.entity_status_2.html}
        </td>
      </tr>
      <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
      <tr class="crm-core-form-recurringentity-block-repetition_start_date" id="tr-repetition_start_date">
        <td class="label">{$form.repetition_start_date.label}</td>
        <td>{$form.repetition_start_date.html}</td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-ends">
        <td class="label">{$form.ends.label}&nbsp;<span class="crm-marker">*</span></td>
        <td>{$form.ends.1.html} {$form.start_action_offset.html} {ts}occurrences{/ts}</td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-absolute_date">
        <td class="label"> </td>
        <td>{$form.ends.2.html} {$form.repeat_absolute_date.html}</td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-exclude_date">
        <td class="label">{$form.exclude_date_list.label}</td>
        <td>{$form.exclude_date_list.html}</td>
      </tr>
    </table>
    {if !$recurringFormIsEmbedded}
      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
      </div>
    {/if}
  </div>
</details>
{literal}
<script type="text/javascript">
(function (_) {
  CRM.$(function($) {
    var $form = $('form.{/literal}{$form.formClass}{literal}'),
      defaultDate = null;

    // Prevent html5 errors
    $form.attr('novalidate', 'novalidate');

    function changeFrequencyUnit() {
      switch ($(this).val()) {
        case 'week':
          //Show "Repeats On" block when week is selected
          $('.crm-core-form-recurringentity-block-start_action_condition', $form).show();
          $('.crm-core-form-recurringentity-block-repeats_by td', $form).hide();
          break;
        case 'month':
          //Show "Repeats By" block when month is selected
          $('.crm-core-form-recurringentity-block-start_action_condition', $form).hide();
          $('.crm-core-form-recurringentity-block-repeats_by td', $form).show();
          break;
        default:
          $('.crm-core-form-recurringentity-block-start_action_condition', $form).hide();
          $('.crm-core-form-recurringentity-block-repeats_by td', $form).hide();
      }
    }
    $('#repetition_frequency_unit', $form).each(changeFrequencyUnit).change(changeFrequencyUnit);

    function disableEnds() {
      $("#repeat_absolute_date, #start_action_offset").prop('disabled', true).removeClass('required');

      if ($('input[name=ends][value=2]').prop('checked')) {
        $("#repeat_absolute_date").prop('disabled', false).addClass('required').focus();
      }
      else if ($('input[name=ends][value=1]').prop('checked')) {
        $('#start_action_offset').prop('disabled', false).addClass('required').focus();
      }
    }

    $('input[name=ends]').click(function() {
      disableEnds();
    });
    disableEnds();

    function validate() {
      var valid = $(':input', '#recurring-entity-block').valid(),
        modified = CRM.utils.initialValueChanged('#recurring-entity-block');
      $('#allowRepeatConfigToSubmit', $form).val(valid && modified ? '1' : '0');
      return valid;
    }

    function getDisplayDate(date) {
      return $.datepicker.formatDate(CRM.config.dateInputFormat, $.datepicker.parseDate('yy-mm-dd', date));
    }

    // Combine select2 and datepicker into a multi-select-date widget
    $('#exclude_date_list', $form).crmSelect2({
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
      .on('select2-opening', function(e) {
        var $el = $(this),
          $input = $('.select2-search-field input', $el.select2('container'));
        // Prevent select2 from opening and show a datepicker instead
        e.preventDefault();
        if (!$input.data('datepicker')) {
          $input
            .datepicker({
              beforeShow: function() {
                var existingSelections = _.pluck($el.select2('data') || [], 'id');
                return {
                  changeMonth: true,
                  changeYear: true,
                  defaultDate: defaultDate,
                  beforeShowDay: function(date) {
                    // Don't allow the same date to be selected twice
                    var dateStr = $.datepicker.formatDate('yy-mm-dd', date);
                    if (_.includes(existingSelections, dateStr)) {
                      return [false, '', '{/literal}{ts escape='js'}Already selected{/ts}{literal}'];
                    }
                    return [true, '', ''];
                  }
                };
              }
            })
            .datepicker('show')
            .on('change.crmDate', function() {
              if ($(this).val()) {
                var date = defaultDate = $(this).datepicker('getDate'),
                data = $el.select2('data') || [];
                data.push({
                  text: $.datepicker.formatDate(CRM.config.dateInputFormat, date),
                  id: $.datepicker.formatDate('yy-mm-dd', date)
                });
                $el.select2('data', data, true);
              }
            })
            .on('keyup', function() {
              $(this).val('').datepicker('show');
            });
        }
      })
      // Don't leave datepicker open when clearing selections
      .on('select2-removed', function() {
        $('input.hasDatepicker', $(this).select2('container'))
          .datepicker('hide');
      });


    // Dialog for preview repeat Configuration dates
    function previewDialog() {
      // Set default value for start date on activity forms before generating preview
      if (!$('#repetition_start_date', $form).val() && $('#activity_date_time', $form).val()) {
        $('#repetition_start_date', $form)
          .val($('#activity_date_time', $form).val())
          .next().val($('#activity_date_time', $form).next().val())
          .siblings('.hasTimeEntry').val($('#activity_date_time', $form).siblings('.hasTimeEntry').val());
      }
      var payload = $form.serialize() + '{/literal}&entity_table={$entityTable}&entity_id={$currentEntityId}{literal}';
      CRM.confirm({
        width: '50%',
        url: CRM.url("civicrm/recurringentity/preview", payload)
      }).on('crmConfirm:yes', function() {
          $form.submit();
        });
    }

    $('#_qf_Repeat_submit-top, #_qf_Repeat_submit-bottom').click(function (e) {
      if (validate()) {
        previewDialog();
      }
      e.preventDefault();
    });

    $('#_qf_Activity_upload-top, #_qf_Activity_upload-bottom').click(function (e) {
      if (CRM.utils.initialValueChanged('#recurring-entity-block')) {
        e.preventDefault();
        if (validate()) {
          previewDialog();
        }
      }
      else {
        // Avoid jquery validation on required fields if they are visible
        $('#recurring-entity-block :input').removeClass('required');
      }
    });

    // Enable/disable form buttons when not embedded in another form
    $form.on('change', function() {
      $('#_qf_Repeat_submit-top, #_qf_Repeat_submit-bottom').prop('disabled', !CRM.utils.initialValueChanged('#recurring-entity-block'));
    });

    // Pluralize frequency options
    var recurringFrequencyOptions = {/literal}{$recurringFrequencyOptions|@json_encode}{literal};
    function pluralizeUnits() {
      CRM.utils.setOptions($('[name=repetition_frequency_unit]', $form),
        $(this).val() === '1' ? recurringFrequencyOptions.single : recurringFrequencyOptions.plural);
    }
    $('[name=repetition_frequency_interval]', $form).each(pluralizeUnits).change(pluralizeUnits);

  });
})(CRM._);
</script>
{/literal}
