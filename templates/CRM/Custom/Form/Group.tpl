{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* add/update/view custom data group *}
<div class="help">{ts}Use Custom Field Sets to add logically related fields for a specific type of CiviCRM record (e.g. contact records, contribution records, etc.).{/ts} {help id="id-group_intro"}</div>
<div class="crm-block crm-form-block">
    <table class="form-layout">
    <tr>
        <td class="label">{$form.title.label} {help id="id-title"}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_group' field='title' id=$gid}{/if}</td>
        <td class="html-adjust">{$form.title.html}</td>
    </tr>
    <tr>
        <td class="label">{$form.extends.label} {help id="id-extends"}</td>
        <td>
            {$form.extends.html}
            <span {if $emptyEntityColumnId}style="display:none"{/if} class="field-extends_entity_column_id">{$form.extends_entity_column_id.html}</span>
            <span {if $emptyEntityColumnValue}style="display:none"{/if} class="field-extends_entity_column_value">{$form.extends_entity_column_value.html}</span>
        </td>
    </tr>
    <tr>
        <td class="label">{$form.weight.label} {help id="id-weight"}</td>
        <td>{$form.weight.html}</td>
    </tr>
    <tr style="display:none" class="field-is_multiple">
        <td class="right">{help id="id-is_multiple"}</td>
        <td class="html-adjust">{$form.is_multiple.html}&nbsp;{$form.is_multiple.label}</td>
    </tr>
    <tr style="display:none" class="field-max_multiple">
        <td class="label">{$form.max_multiple.label} {help id="id-max_multiple"}</td>
        <td>{$form.max_multiple.html}</td>
    </tr>
    <tr style="display:none" class="field-style">
        <td class="label">{$form.style.label} {help id="id-display_style"}</td>
        <td>{$form.style.html}</td>
    </tr>
    <tr style="display:none" class="field-icon">
        <td class="label">{$form.icon.label}</td>
        <td>{$form.icon.html}</td>
    </tr>
    <tr class="html-adjust field-collapse_display">
        <td class="right">{help id="id-collapse"}</td>
        <td>{$form.collapse_display.html} {$form.collapse_display.label}</td>
    </tr>
    <tr>
        <td class="right">{help id="id-collapse-adv"}</td>
        <td>{$form.collapse_adv_display.html} {$form.collapse_adv_display.label}</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>{$form.is_active.html} {$form.is_active.label}</td>
    </tr>
    <tr>
        <td class="right">{help id="id-is-public"}</td>
        <td>{$form.is_public.html} {$form.is_public.label}</td>
    </tr>
    <tr class="html-adjust">
        <td class="label">{$form.help_pre.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_group' field='help_pre' id=$gid}{/if} {help id="id-help_pre"}</td>
        <td>{$form.help_pre.html}</td>
    </tr>
    <tr class="html-adjust">
        <td class="label">{$form.help_post.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_custom_group' field='help_post' id=$gid}{/if} {help id="id-help_post"}</td>
        <td>{$form.help_post.html}</td>
    </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{if $action eq 2 or $action eq 4} {* Update or View*}
    <p></p>
    <div class="action-link">
      {crmButton p='civicrm/admin/custom/group/field' q="action=browse&reset=1&gid=$gid" icon="th-list"}{ts}Custom Fields for this Set{/ts}{/crmButton}
    </div>
{/if}

{literal}
<script type="text/javascript">
CRM.$(function($) {
  const {/literal}
    $form = $('form.{$form.formClass}'),
    entityColumnIdOptions = {$entityColumnIdOptions|@json_encode},
    allowMultiple = {$allowMultiple|@json_encode},
    defaultSubtypes = {$defaultSubtypes|@json_encode};
  {literal}
  let tabWithTableOption;

  // Add change/init callbacks for specific fields (each() fires the callback on page load)
  $('input[name=extends], input[name=extends_entity_column_id]', $form).change(onChangeEntityId);
  $('input[name=extends]', $form).change(handleExtends).each(handleExtends);
  $('input#is_multiple', $form).change(handleMultiple).change(onChangeMultiple).each(handleMultiple);
  $('select[name=style]', $form).change(handleStyle).each(handleStyle);

  // When changing primary `extends` or secondary `entityColumnIdOptions`
  function onChangeEntityId() {
    let values = {
      extends: $('[name=extends]', $form).val(),
    };
    let columnIdOptions = values.extends && entityColumnIdOptions[values.extends];
    // When changing the `extends` field
    if ($(this).is('input[name=extends]')) {
      $('[name=extends_entity_column_id]', $form).val('');
      if (columnIdOptions) {
        $('.field-extends_entity_column_id', $form).show();
        // Only render if type=text (if field is frozen then type=hidden)
        $('[type=text][name=extends_entity_column_id]', $form).crmSelect2({
          data: columnIdOptions
        });
      } else {
        $('.field-extends_entity_column_id', $form).hide();
      }
    }
    // When changing `entityColumnIdOptions`
    else {
      values.extends_entity_column_id = $('[name=extends_entity_column_id]', $form).val();
    }
    if (
      values.extends &&
      (values.extends_entity_column_id || !columnIdOptions) &&
      // Only render if type=text (if field is frozen then type=hidden)
      $('[type=text][name=extends_entity_column_value]', $form).length
    ) {
      $('[name=extends_entity_column_value]', $form).val('').addClass('loading').prop('disabled', true);
      $('.field-extends_entity_column_value', $form).show();
      CRM.api4('CustomGroup', 'getFields', {
        where: [['name', '=', 'extends_entity_column_value']],
        action: 'create',
        loadOptions: ['id', 'label'],
        values: values,
      }, 0).then((field) => {
        let valueOptions = field.options || [];
        if (valueOptions.length) {
          valueOptions.forEach(function(option) {
            option.text = option.label;
            option.id = '' + option.id;
          });
          $('[name=extends_entity_column_value]', $form).removeClass('loading').prop('disabled', false).crmSelect2({
            data: valueOptions
          });
        } else {
          $('.field-extends_entity_column_value', $form).hide();
        }
      });
    } else {
      $('.field-extends_entity_column_value', $form).hide();
    }
  }

  // When changing or initializing the primary `extends` field
  function handleExtends() {
    let multiAllowed = $(this).val() && allowMultiple[$(this).val()];

    if (multiAllowed) {
      $('tr.field-style, tr.field-is_multiple', $form).show();
    }
    else {
      $('input#is_multiple', $form).prop('checked', false).change();
      $('tr.field-style, tr.field-is_multiple, tr.field-max_multiple', $form).hide();
    }
  }

  // When changing the `is_multiple` field
  function onChangeMultiple() {
    if ($(this).is(':checked')) {
      $('select[name=style]', $form).val('Tab with table').change();
    }
  }

  // When changing or initializing the `is_multiple` field
  // Check if this set supports multiple records and adjust other options accordingly
  function handleMultiple() {
    if ($(this).is(':checked') || ($(this).attr('type') === 'hidden' && $(this).val() === '1')) {
      $('tr.field-max_multiple', $form).show();
      if (tabWithTableOption) {
        $('select[name=style]', $form).append(tabWithTableOption);
      }
      $('tr.field-icon', $form).toggle($('select[name=style]', $form).val() !== 'Inline');
    }
    else {
      $('tr.field-max_multiple, tr.field-icon', $form).hide();
      if ($('select[name=style]', $form).val() === 'Tab with table') {
        $('select[name=style]', $form).val('Inline');
      }
      if (!tabWithTableOption) {
        tabWithTableOption = $("select[name=style] option[value='Tab with table']", $form).detach();
      }
      $("select[name=style] option[value='Tab with table']", $form).remove();
    }
  }

  // When changing or initializing the `style` field
  function handleStyle() {
    const styleVal = $(this).val();
    $('tr.field-icon', $form).toggle(styleVal !== 'Inline');
    $('tr.field-collapse_display', $form).toggle(styleVal !== 'Tab with table');
    if (styleVal === 'Tab with table') {
      $('input#collapse_display', $form).prop('checked', false);
    }
  }

  // When saving the form after removing sub-types
  $('.crm-warnDataLoss', $form).on('click', function() {
    var submittedSubtypes = $('[name=extends_entity_column_value]', $form).val().split(',');

    var warning = false;
    $.each(defaultSubtypes, function(index, subtype) {
      if ($.inArray(subtype, submittedSubtypes) < 0) {
        warning = true;
      }
    });

    if (warning) {
      return confirm({/literal}'{ts escape='js'}Warning: You have chosen to remove one or more subtypes. This will cause any custom data records associated with those subtypes to be removed as long as the contact does not have a contact subtype still selected.{/ts}'{literal});
    }
    return true;
  });
});
</script>
{/literal}
