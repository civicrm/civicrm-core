{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This template is used for adding/scheduling reminders.  *}
{capture assign='tokenTitle'}{ts}Tokens{/ts}{/capture}
<div class="crm-block crm-form-block crm-scheduleReminder-form-block">
    {if $action eq 8}
      <div class="messages status no-popup">
          {icon icon="fa-info-circle"}{/icon}
          {ts 1=$reminderName}WARNING: You are about to delete the Reminder titled <strong>%1</strong>.{/ts} {ts}Do you want to continue?{/ts}
      </div>
    {else}
      <table class="form-layout-compressed">
        <tr class="crm-scheduleReminder-form-block-title">
          <td class="label">{$form.title.label}</td>
          <td>{$form.title.html}</td>
        </tr>
        <tr {if $mappingId}style="display:none"{/if}>
          <td class="label">{$form.mapping_id.label}</td>
          <td>{$form.mapping_id.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.entity_value.label}</td>
          <td>{$form.entity_value.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.entity_status.label}</td>
          <td>{$form.entity_status.html}</td>
        </tr>

        <tr class="crm-scheduleReminder-form-block-when">
          <td class="label">{$form.absolute_or_relative_date.label}</td>
          <td>
            {$form.absolute_or_relative_date.html}
            {help id="absolute_or_relative_date"}
            {$form.absolute_date.html}
          </td>
        </tr>

        <tr class="crm-scheduleReminder-form-block-description">
          <td class="label"></td>
          <td>
              {$form.start_action_offset.html}
              {$form.start_action_unit.html}
              {$form.start_action_condition.html}
              {$form.start_action_date.html}
          </td>
        </tr>
        <tr class="crm-scheduleReminder-effective_start_date">
          <td class="label">{$form.effective_start_date.label}</td>
          <td>
              {$form.effective_start_date.html}
              {$form.effective_end_date.label}
              {$form.effective_end_date.html}
              <div class="description">{ts}Earliest and latest trigger dates to include.{/ts}</div>
          </td>
        <tr id="relativeDateRepeat" class="crm-scheduleReminder-form-block-is_repeat"><td class="label">{$form.is_repeat.label}</td>
          <td>{$form.is_repeat.html}</td>
        </tr>
        <tr class="crm-scheduleReminder-form-block-repetition_frequency_interval">
          <td class="label">{$form.repetition_frequency_interval.label} <span class="crm-marker">*</span></td>
          <td>{$form.repetition_frequency_interval.html} {$form.repetition_frequency_unit.html}</td>
        </tr>
        <tr class="crm-scheduleReminder-form-block-repetition_frequency_interval">
          <td class="label">{$form.end_frequency_interval.label} <span class="crm-marker">*</span></td>
          <td>{$form.end_frequency_interval.html} {$form.end_frequency_unit.html} {$form.end_action.html} {$form.end_date.html}</td>
        </tr>
        <tr id="recordActivity" class="crm-scheduleReminder-form-block-record_activity"><td class="label">{$form.record_activity.label}</td>
          <td>{$form.record_activity.html}</td>
        </tr>
        <tr class="crm-scheduleReminder-form-block-recipient">
          <td id="recipientLabel" class="label">{$form.recipient.label}</td>
          <td>
            <span>
              {$form.limit_to.html}&nbsp;{help id="limit_to" class="limit_to" title=$form.recipient.textLabel}
            </span>
            <span>
              {$form.recipient.html}
            </span>
          </td>
        </tr>
        <tr class="crm-scheduleReminder-form-block-recipientListing recipient">
          <td class="label">{$form.recipient_listing.label}</td><td>{$form.recipient_listing.html}</td>
        </tr>
        <tr class="crm-scheduleReminder-form-block-recipient_manual recipient">
          <td class="label">{$form.recipient_manual.label} <span class="crm-marker">*</span></td>
          <td>{$form.recipient_manual.html}</td>
        </tr>

        <tr class="crm-scheduleReminder-form-block-recipient_group_id recipient">
          <td class="label">{$form.group_id.label} <span class="crm-marker">*</span></td>
          <td>{$form.group_id.html}</td>
        </tr>
        {if $sms}
          <tr id="msgMode" class="crm-scheduleReminder-form-block-mode">
            <td class="label">{$form.mode.label}</td>
            <td>{$form.mode.html}</td>
          </tr>
        {/if}
        {if $multilingual}
          <tr class="crm-scheduleReminder-form-block-filter-contact-language">
            <td class="label">{$form.filter_contact_language.label}</td>
            <td>{$form.filter_contact_language.html} {help id="filter_contact_language"}</td>
          </tr>
          <tr class="crm-scheduleReminder-form-block-communication-language">
            <td class="label">{$form.communication_language.label}</td>
            <td>{$form.communication_language.html} {help id="communication_language"}</td>
          </tr>
        {/if}
        <tr class="crm-scheduleReminder-form-block-active">
          <td class="label">{$form.is_active.label}</td>
          <td>{$form.is_active.html}</td>
        </tr>
      </table>
      <details id="email-section" open>
        <summary>{ts}Email{/ts}</summary>
        <div class="crm-accordion-body">
          <table id="email-field-table" class="form-layout-compressed">
            <tr>
              <td class="label">{$form.from_name.label}</td>
              <td>
                  {$form.from_name.html}
                  {$form.from_email.label}
                  {$form.from_email.html}
                  {help id="from_name"}
              </td>
            </tr>
            <tr class="crm-scheduleReminder-form-block-template">
              <td class="label">{$form.template.label}</td>
              <td>{$form.template.html}</td>
            </tr>
            <tr class="crm-scheduleReminder-form-block-subject">
              <td class="label">{$form.subject.label}</td>
              <td>
                  {$form.subject.html|crmAddClass:huge}
                <input class="crm-token-selector big" data-field="subject" />
                  {help id="id-token-subject" file="CRM/Contact/Form/Task/Email.hlp" title=$tokenTitle}
              </td>
            </tr>
          </table>
            {include file="CRM/Contact/Form/Task/EmailCommon.tpl" upload=1 noAttach=1}
        </div>
      </details>
    {if $sms}
      <details id="sms-section" open>
        <summary>{ts}SMS{/ts}</summary>
        <div class="crm-accordion-body">
          <table id="sms-field-table" class="form-layout-compressed">
            <tr class="crm-scheduleReminder-form-block-sms_provider_id">
              <td class="label">{$form.sms_provider_id.label} <span class="crm-marker">*</span></td>
              <td>{$form.sms_provider_id.html}</td>
            </tr>
            <tr class="crm-scheduleReminder-form-block-sms-template">
              <td class="label">{$form.SMStemplate.label}</td>
              <td>{$form.SMStemplate.html}</td>
            </tr>
          </table>
          {include file="CRM/Contact/Form/Task/SMSCommon.tpl" upload=1 noAttach=1}
        <div>
      </details>
    {/if}

    {literal}
      <script type='text/javascript'>
        (function($, _) {
          $(function($) {
            const $form = $('form.{/literal}{$form.formClass}{literal}'),
              controlFields = {/literal}{$controlFields|@json_encode nofilter}{literal},
              recurringFrequencyOptions = {/literal}{$recurringFrequencyOptions|@json_encode nofilter}{literal};

            // Reload metadata when a controlField is changed
            $form.on('change', 'input', function() {
              if (controlFields.includes(this.name)) {
                const values = {}
                controlFields.forEach(function(fieldName) {
                  const $input = $('[name=' + fieldName + ']', $form);
                  values[fieldName] = $input.data('select2') ? $input.select2('val') : $input.val();
                });
                // Get directly dependent fields
                let $dependentFields = $('[controlField=' + this.name + ']', $form);
                // Get sub-dependencies (fortunately the dependency depth doesn't go deeper)
                $('[controlField=' + this.name + ']').each(function() {
                  $dependentFields = $dependentFields.add($('[controlField=' + this.name + ']', $form).not($dependentFields));
                })
                $dependentFields.addClass('loading').prop('disabled', true).val('');
                toggleRecipientManualGroup();
                const dependentFieldNames = $dependentFields.map((i, element) => $(element).attr('name')).get();
                CRM.api4('ActionSchedule', 'getFields', {
                  select: ['name', 'label', 'options', 'input_attrs', 'required'],
                  action: 'create',
                  loadOptions: ['id', 'label'],
                  values: values,
                  where: [['name', 'IN', dependentFieldNames]]
                }).then(function(fieldSpecs) {
                  fieldSpecs.forEach(function(fieldSpec) {
                    const $field = $('input[name=' + fieldSpec.name + ']', $form),
                      $label = $('label[for=' + fieldSpec.name + ']', $form);
                    $label.text(fieldSpec.label);
                    if (fieldSpec.required) {
                      $label.append(' <span class="crm-marker">*</span>');
                    }
                    // 'required' css class gets picked up by jQuery validate (but only in popup mode)
                    // In full-page mode there is no clientside validation & this doesn't have any effect.
                    // TODO: Would be nice for those things to be more consistent & also to use real html validation not jQuery.
                    $field.toggleClass('required', fieldSpec.required);
                    $field.removeClass('loading');
                    // Show field and update option list if applicable
                    if (fieldSpec.options && fieldSpec.options.length) {
                      fieldSpec.options.forEach(function(option) {
                        option.text = option.label;
                        delete(option.label);
                        option.id = '' + option.id;
                      });
                      // Only one option. Select it.
                      if (fieldSpec.options.length === 1) {
                        $field.val(fieldSpec.options[0].id);
                      }
                      $field.prop('disabled', false).closest('tr').show();
                      $field.crmSelect2('destroy');
                      $field.crmSelect2({
                        multiple: !!fieldSpec.input_attrs.multiple,
                        data: fieldSpec.options
                      });
                    } else {
                      // No options - hide field
                      $field.closest('tr').hide();
                    }
                  });
                  toggleLimitTo();
                  toggleAbsoluteRelativeDate();
                  toggleRepeatSection();
                  toggleRecipient();
                });
              }
            });

            // Hide dependent fields with no options
            $('input[controlField]', $form).each(function() {
              if (!getSelect2Options($(this)).length) {
                $(this).closest('tr').hide();
              }
            });

            // Pluralize frequency options
            function pluralizeUnits() {
              CRM.utils.setOptions($('[controlField=' + $(this).attr('name') + ']', $form),
                $(this).val() === '1' ? recurringFrequencyOptions.single : recurringFrequencyOptions.plural);
            }
            $('[name=start_action_offset],[name=repetition_frequency_interval],[name=end_frequency_interval]', $form).each(pluralizeUnits).change(pluralizeUnits);

            // If limit_to field has only one option, select it and hide it
            function toggleLimitTo() {
              const $limitTo = $('[name=limit_to]', $form),
                limitToOptions = getSelect2Options($limitTo);
              if (limitToOptions.length < 2) {
                $limitTo.val(limitToOptions[0].id).closest('span').hide();
              } else {
                $limitTo.closest('span').show();
              }
            }

            function toggleRecipientManualGroup() {
              toggleElementBySelection('recipient', {manual: 'recipient_manual', group: 'group_id'});
            }

            function toggleAbsoluteRelativeDate() {
              toggleElementBySelection('absolute_or_relative_date', {absolute: 'absolute_date', relative: 'start_action_offset'});
              $('.crm-scheduleReminder-effective_start_date, .crm-scheduleReminder-effective_end_date', $form).toggle(($('[name=absolute_or_relative_date]', $form).val() === 'relative'));
            }

            function toggleRepeatSection() {
              toggleElementBySelection('is_repeat', {'true': 'repetition_frequency_interval,end_frequency_interval'});
            }

            function toggleRecipient() {
              if ($('[name=limit_to]', $form).val()) {
                $('[name=recipient]', $form).closest('span').show();
              } else {
                $('[name=recipient]', $form).val('').closest('span').hide();
              }
            }

            function toggleEmailOrSms() {
              const mode = $('[name=mode]', $form).val(),
                showSMS = (mode === 'SMS' || mode === 'User_Preference');
              $('#email-section', $form).toggle(mode !== 'SMS');
              $('#sms-section', $form).toggle(showSMS);
              if (showSMS) {
                showSaveUpdateChkBox('SMS');
              }
            }

            // Given an input and a set of {optionVal: 'field1,field2'} pairs, show the field(s) that correspond
            // to the selected option, while hiding and clearing the others.
            function toggleElementBySelection(controlFieldName, options) {
              const $controlField = $('[name=' + controlFieldName + ']', $form),
                selectedOption = $controlField.is(':checkbox') ? $controlField.is(':checked').toString() : $controlField.val();
              Object.keys(options).forEach(targetValue => {
                const targetFieldNames = options[targetValue].split(',');
                targetFieldNames.forEach(function(fieldName) {
                  const $field = $('[name=' + fieldName + ']', $form);
                  $field.closest('span,tr').toggle(selectedOption === targetValue);
                  if (selectedOption !== targetValue && $field.val()) {
                    $field.val('').change();
                  }
                });
              });
            }

            function getSelect2Options($input) {
              const data = $input.data();
              // Use raw data.selectParams if select2 widget hasn't been initialized yet
              return data.select2 ? data.select2.opts.data : data.selectParams.data;
            }

            $('[name=absolute_or_relative_date]', $form)
              .change(toggleAbsoluteRelativeDate)
              .change(function() {
                if ($(this).val() === 'absolute') {
                  $('[name=absolute_date]', $form).next().datepicker('show');
                }
              });

            $('[name=is_repeat]', $form).click(toggleRepeatSection);
            $('[name=mode]', $form).change(toggleEmailOrSms);
            $('[name=limit_to]', $form).change(toggleRecipient);

            // Wait for widgets (select2, datepicker) to be initialized
            window.setTimeout(function() {
              toggleLimitTo();
              toggleRecipientManualGroup();
              toggleAbsoluteRelativeDate();
              toggleRepeatSection();
              toggleEmailOrSms();
              toggleRecipient();
            });
          });
        })(CRM.$, CRM._);
      </script>
    {/literal}

    {/if}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
