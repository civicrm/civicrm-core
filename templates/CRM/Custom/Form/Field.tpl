{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-custom-field-form-block">
  <table class="form-layout">
    <tr class="crm-custom-field-form-block-label">
      <td class="label">{$form.label.label}
        {if $action == 2}
          {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_field' field='label' id=$id}
        {/if}
      </td>
      <td class="html-adjust">{$form.label.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-data_type">
      <td class="label">{$form.data_type.label}</td>
      <td class="html-adjust">{$form.data_type.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-html_type">
      <td class="label">{$form.html_type.label}</td>
      <td class="html-adjust">{$form.html_type.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-fk_entity">
      <td class="label">{$form.fk_entity.label} <span class="crm-marker">*</span></td>
      <td class="html-adjust">{$form.fk_entity.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-fk_entity_on_delete">
      <td class="label">{$form.fk_entity_on_delete.label} <span class="crm-marker">*</span></td>
      <td class="html-adjust">{$form.fk_entity_on_delete.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-serialize">
      <td class="label">{$form.serialize.label}</td>
      <td class="html-adjust">{$form.serialize.html}</td>
    </tr>
    {if array_key_exists('in_selector', $form)}
      <tr class='crm-custom-field-form-block-in_selector'>
        <td class='label'>{$form.in_selector.label} {help id="in_selector"}</td>
        <td class='html-adjust'>{$form.in_selector.html}</td>
      </tr>
    {/if}
    <tr class="crm-custom-field-form-block-text_length"  id="textLength">
      <td class="label">{$form.text_length.label}</td>
      <td class="html-adjust">{$form.text_length.html}</td>
    </tr>

    <tr id='showoption' {if $action eq 1 or $action eq 2}class="hiddenElement"{/if}>
      <td colspan="2">
        <table class="form-layout-compressed">
          {* Conditionally show table for setting up selection options - for field types = radio, checkbox or select *}
          {include file="CRM/Custom/Form/Optionfields.tpl"}
        </table>
      </td>
    </tr>
    <tr id='contact_reference_group'>
      <td class="label">{$form.group_id.label}</td>
      <td class="html-adjust">
        {$form.group_id.html}
        &nbsp;&nbsp;<span><a class="crm-hover-button toggle-contact-ref-mode" href="#Advance">{ts}Advanced Filter{/ts}</a></span>
        {capture assign=searchPreferences}{crmURL p="civicrm/admin/setting/search" q="reset=1"}{/capture}
        <div class="messages status no-popup"><i class="crm-i fa-exclamation-triangle" role="img" aria-hidden="true"></i> {ts 1=$searchPreferences}If you are planning on using this field in front-end profile, event registration or contribution forms, you should 'Limit List to Group' or configure an 'Advanced Filter'  (so that you do not unintentionally expose your entire set of contacts). Users must have either 'access contact reference fields' OR 'access CiviCRM' permission in order to use contact reference autocomplete fields. You can assign 'access contact reference fields' to the anonymous role if you want un-authenticated visitors to use this field. Use <a href='%1'>Search Preferences - Contact Reference Options</a> to control the fields included in the search results.{/ts}
      </td>
    </tr>
    <tr id='field_advance_filter'>
      <td class="label">{$form.filter.label}</td>
      <td class="html-adjust">
        {$form.filter.html}
        <span class="api3-filter-info"><a class="crm-hover-button toggle-contact-ref-mode" href="#Group">{ts}Filter by Group{/ts}</a></span>
        <br />
        <span class="description api3-filter-info">
          {ts}Filter contact search results for this field using Contact get API parameters. EXAMPLE: To list Students in group 3:{/ts}
          <code>action=get&group=3&contact_sub_type=Student</code>
          {docURL page="dev/api"}
        </span>
        <span class="description api4-filter-info">
          {ts}Filter search results for this field using API-style parameters{/ts}
          (<code>field=value&another_field=val1,val2</code>).<br>
          {ts}EXAMPLE (Contact entity): To list Students in "Volunteers" or "Supporters" groups:{/ts}
          <code>contact_sub_type=Student&groups:name=Volunteers,Supporters</code>
          {docURL page="dev/api"}
        </span>
      </td>
    </tr>
    <tr class="crm-custom-field-form-block-options_per_line" id="optionsPerLine">
      <td class="label">{$form.options_per_line.label}</td>
      <td class="html-adjust">{$form.options_per_line.html|crmAddClass:two}</td>
    </tr>
    <tr class="crm-custom-field-form-block-start_date_years" id="startDateRange">
      <td class="label">{$form.start_date_years.label}</td>
      <td class="html-adjust">{$form.start_date_years.html} {ts}years prior to current date.{/ts}</td>
    </tr>
    <tr class="crm-custom-field-form-block-end_date_years" id="endDateRange">
      <td class="label">{$form.end_date_years.label}</td>
      <td class="html-adjust">{$form.end_date_years.html} {ts}years after the current date.{/ts}</td>
    </tr>
    <tr class="crm-custom-field-form-block-date_format"  id="includedDatePart">
      <td class="label">{$form.date_format.label}</td>
      <td class="html-adjust">{$form.date_format.html}&nbsp;&nbsp;&nbsp;{$form.time_format.label}&nbsp;&nbsp;{$form.time_format.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-note_rows"  id="noteRows" >
      <td class="label">{$form.note_rows.label}</td>
      <td class="html-adjust">{$form.note_rows.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-note_columns" id="noteColumns" >
      <td class="label">{$form.note_columns.label}</td>
      <td class="html-adjust">{$form.note_columns.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-note_length" id="noteLength" >
      <td class="label">{$form.note_length.label} {help id="note_length"}</td>
      <td class="html-adjust">{$form.note_length.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-weight" >
      <td class="label">{$form.weight.label} {help id="weight"}</td>
      <td>{$form.weight.html|crmAddClass:two}</td>
    </tr>
    <tr class="crm-custom-field-form-block-default_value" id="hideDefault" >
      <td class="label">{$form.default_value.label} {help id="default_value"}</td>
      <td class="html-adjust">{$form.default_value.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-help_pre">
      <td class="label">{$form.help_pre.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_field' field='help_pre' id=$id}{/if}</td>
      <td class="html-adjust">{$form.help_pre.html|crmAddClass:huge}</td>
    </tr>
    <tr class="crm-custom-field-form-block-help_post">
      <td class="label">{$form.help_post.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_field' field='help_post' id=$id}{/if}</td>
      <td class="html-adjust">{$form.help_post.html|crmAddClass:huge}
        {if $action neq 4}
          <br />
          <span class="description">{ts}Explanatory text displayed on back-end forms. Pre help is displayed inline on the form (above the field). Post help is displayed in a pop-up - users click the help balloon to view help text.{/ts}</span>
        {/if}
      </td>
    </tr>
    <tr class="crm-custom-field-form-block-is_required">
      <td class="label">{$form.is_required.label} {help id="is_required"}</td>
      <td class="html-adjust">{$form.is_required.html}</td>
    </tr>
    <tr id ="searchable" class="crm-custom-field-form-block-is_searchable">
      <td class="label">{$form.is_searchable.label} {help id="is_searchable"}</td>
      <td class="html-adjust">{$form.is_searchable.html}</td>
    </tr>
    <tr id="searchByRange" class="crm-custom-field-form-block-is_search_range">
      <td class="label">{$form.is_search_range.label}</td>
      <td class="html-adjust">{$form.is_search_range.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-is_active">
      <td class="label">{$form.is_active.label}</td>
      <td class="html-adjust">{$form.is_active.html}</td>
    </tr>
    <tr class="crm-custom-field-form-block-is_view">
      <td class="label">{$form.is_view.label} {help id="is_view"}</td>
      <td class="html-adjust">{$form.is_view.html}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    const
      $form = $('form.{/literal}{$form.formClass}{literal}'),
      dataToHTML = {/literal}{$dataToHTML|json}{literal},
      originalHtmlType = '{/literal}{$originalHtmlType}{literal}',
      existingMultiValueCount = {/literal}{if empty($existingMultiValueCount)}null{else}{$existingMultiValueCount}{/if}{literal},
      originalSerialize = {/literal}{if empty($originalSerialize)}false{else}true{/if}{literal},
      htmlTypes = CRM.utils.getOptions($('#html_type', $form)),
      htmlTypesWithOptionalSerialize = {/literal}{$htmlTypesWithOptionalSerialize|json}{literal},
      htmlTypesWithMandatorySerialize = {/literal}{$htmlTypesWithMandatorySerialize|json}{literal};

    // Vars used by makeDefaultValueField()
    let oldDataType = null,
      oldSerialize = null,
      oldHasOptionGroup = null,
      oldOptionGroupId = null;

    function onChangeDataType() {
      const dataType = $('#data_type', $form).val();
      const allowedHtmlTypes = htmlTypes.filter(type =>
        dataToHTML[dataType].includes(type.key)
      );
      CRM.utils.setOptions($('#html_type', $form), allowedHtmlTypes);
      if (!$('#html_type', $form).val()) {
        $('#html_type', $form).val(dataToHTML[dataType][0]).change();
      }
      // Hide html_type if there is only one option
      $('.crm-custom-field-form-block-html_type').toggle(allowedHtmlTypes.length > 1);
      customOptionHtmlType(dataType);

      // Show/hide entityReference selector
      $('.crm-custom-field-form-block-fk_entity').toggle(dataType === 'EntityReference');
      $('.crm-custom-field-form-block-fk_entity_on_delete').toggle(dataType === 'EntityReference');
    }

    function onChangeHtmlType() {
      const htmlType = $('#html_type', $form).val();
      const dataType = $('#data_type', $form).val();

      if (htmlTypesWithMandatorySerialize.includes(htmlType)) {
        $('#serialize', $form).prop('checked', true);
      }
      else if (!htmlTypesWithOptionalSerialize.includes(htmlType)) {
        $('#serialize', $form).prop('checked', false);
      }

      if (!['CheckBox', 'Radio'].includes(htmlType)) {
        $("#options_per_line", $form).val('');
      }

      showSearchRange(dataType);
      customOptionHtmlType();
    }

    function showSearchRange(dataType) {
      const showRange = ['Date', 'Int', 'Float', 'Money'].includes(dataType);
      $("#searchByRange", $form).toggle(showRange);
    }

    function toggleContactRefFilter(e) {
      let setSelected = $(this).attr('href');
      if (!setSelected) {
        setSelected = $('#filter_selected').val();
      } else {
        $('#filter_selected').val(setSelected.slice(1));
      }
      if (setSelected == '#Advance') {
        $('#contact_reference_group, .api4-filter-info').hide();
        $('#field_advance_filter, .api3-filter-info').show();
      } else {
        $('#field_advance_filter').hide( );
        $('#contact_reference_group').show( );
      }
      e && e.preventDefault && e.preventDefault();
    }
    $('.toggle-contact-ref-mode', $form).click(toggleContactRefFilter);

    function hasOptionGroup() {
      const dataType = $("#data_type", $form).val();
      const htmlType = $("#html_type", $form).val();
      return (['String', 'Int', 'Float', 'Money'].includes(dataType)) && !['Text', 'Hidden'].includes(htmlType);
    }

    function customOptionHtmlType() {
      const dataType = $("#data_type", $form).val();
      const htmlType = $("#html_type", $form).val();
      const serialize = $("#serialize", $form).is(':checked');

      if (!htmlType) {
        return;
      }

      if (dataType === 'ContactReference') {
        toggleContactRefFilter();
      } else if (dataType === 'EntityReference') {
        $('#field_advance_filter, .api4-filter-info').show();
        $('#contact_reference_group, .api3-filter-info').hide();
      } else {
        $('#field_advance_filter, #contact_reference_group', $form).hide();
      }

      if (hasOptionGroup()) {
        $("#showoption", $form).show();
        $("#searchByRange", $form).hide();
        const reuseOptions = $('[name=option_type]:checked', $form).val() === '2';
        $("#hideDefault", $form).toggle(reuseOptions);
      }
      else if (['String', 'Int', 'Float', 'Money'].includes(dataType)) {
        $("#hideDefault, #searchable", $form).show();
      } else {
        if (dataType === 'File') {
          $("#default_value", $form).val('');
          $("#hideDefault, #searchable", $form).hide();
        } else if (dataType === 'ContactReference') {
          $("#hideDefault").hide();
        } else {
          $("#hideDefault, #searchable", $form).show();
        }
      }

      if (['String', 'Int', 'Float', 'Money'].includes(dataType) && !['Text', 'Hidden'].includes(htmlType)) {
        if (serialize) {
          $('div[id^=checkbox]', '#optionField').show();
          $('div[id^=radio]', '#optionField').hide();
        } else {
          $('div[id^=radio]', '#optionField').show();
          $('div[id^=checkbox]', '#optionField').hide();
        }
      }

      $("#optionsPerLine", $form).toggle((htmlType === "CheckBox" || htmlType === "Radio") && dataType !== 'Boolean');

      $("#startDateRange, #endDateRange, #includedDatePart", $form).toggle(dataType === 'Date');

      $("#textLength", $form).toggle(dataType === 'String' && !serialize);

      $("#noteColumns, #noteRows, #noteLength", $form).toggle(dataType === 'Memo');

      $(".crm-custom-field-form-block-serialize", $form).toggle(htmlTypesWithOptionalSerialize.includes(htmlType) && dataType !== 'EntityReference');

      makeDefaultValueField(dataType);
    }

    function makeDefaultValueField(newDataType) {
      const field = $('#default_value', $form);
      const newSerialize = $("#serialize", $form).is(':checked');
      const newHasOptionGroup = hasOptionGroup();
      const newOptionGroupId = $('[name=option_group_id]', $form).val();

      // First, check if this function needs to run by comparing old values with new values
      if (oldDataType === newDataType && oldSerialize === newSerialize && oldHasOptionGroup === newHasOptionGroup && oldOptionGroupId === newOptionGroupId) {
        return;
      }
      // Store values for next time to ensure function doesn't run more often than necessary
      // This prevents rapidly creating/destroying/creating/destroying select2 element which can cause race condition errors
      oldDataType = newDataType;
      oldSerialize = newSerialize;
      oldHasOptionGroup = newHasOptionGroup;
      oldOptionGroupId = newOptionGroupId;

      const autocompeteApiParams = {
        formName: 'qf:{/literal}{$form.formClass}{literal}',
        fieldName: 'CustomField.default_value',
      };
      const autocompleteSelectParams = {
        multiple: newSerialize,
      };
      field.crmDatepicker('destroy');
      field.crmAutocomplete('destroy');
      switch (newDataType) {
        case 'Date':
          field.crmDatepicker({date: 'yy-mm-dd', time: false});
          return;

        case 'Boolean':
          field.crmSelect2({data: [{id: '1', text: ts('Yes')}, {id: '0', text: ts('No')}], placeholder: ' '});
          return;

        case 'Country':
          field.crmAutocomplete('Country', autocompeteApiParams, autocompleteSelectParams);
          return;

        case 'StateProvince':
          field.crmAutocomplete('StateProvince', autocompeteApiParams, autocompleteSelectParams);
          return;
      }
      if (newHasOptionGroup && newOptionGroupId) {
        autocompeteApiParams.filters = {option_group_id: newOptionGroupId};
        autocompeteApiParams.key = 'value';
        field.crmAutocomplete('OptionValue', autocompeteApiParams, autocompleteSelectParams);
      }
    }

    // Watch changes
    $('#html_type, #is_searchable', $form).change(onChangeHtmlType);
    $('#data_type', $form).change(onChangeDataType);

    // Set initial form state
    onChangeDataType();
    onChangeHtmlType();

    // When changing the set of options, clear & rebuild the default value selector
    $('[name=option_type],[name=option_group_id],[name=serialize]', $form).click(() => {
      $('#default_value', $form).val('');
      customOptionHtmlType();
    });

    $form.submit(function() {
      const htmlType = $('#html_type', $form).val();
      const serialize = $("#serialize", $form).is(':checked');
      let htmlTypeLabel = (serialize && ['Select', 'Autocomplete-Select'].includes(htmlType)) ? ts('Multi-Select') : htmlTypes.find(item => item.key === htmlType).value;
      if (originalHtmlType && (originalHtmlType !== htmlType || originalSerialize !== serialize)) {
        let origHtmlTypeLabel = (originalSerialize && originalHtmlType === 'Select') ? ts('Multi-Select') : htmlTypes.find(item => item.key === originalHtmlType).value;
        if (originalSerialize && !serialize && existingMultiValueCount) {
          return confirm(ts('WARNING: Changing this multivalued field to singular will result in the loss of data!')
            + "\n" + ts('%1 existing records contain multiple values - the data in each of these fields will be truncated to a single value.', {1: existingMultiValueCount})
          )
        } else {
          return confirm(ts('Change this field from %1 to %2? Existing data will be preserved.', {1: origHtmlTypeLabel, 2: htmlTypeLabel}));
        }
      }
    });
  });
</script>
{/literal}
{* Give link to view/edit option group *}
{if $action eq 2 && !empty($hasOptionGroup)}
  <div class="action-link">
    {crmButton p="civicrm/admin/custom/group/field/option" q="reset=1&action=browse&fid=`$id`&gid=`$gid`" icon="pencil"}{ts}View / Edit Multiple Choice Options{/ts}{/crmButton}
  </div>
{/if}
