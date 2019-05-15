// https://civicrm.org/licensing
jQuery(function($) {
  $('body')
    // Enable administrators to edit option lists in a dialog
    .on('click', 'a.crm-option-edit-link', CRM.popup)
    .on('crmPopupFormSuccess', 'a.crm-option-edit-link', function() {
      $(this).trigger('crmOptionsEdited');
      var optionEditPath = $(this).data('option-edit-path');
      var $selects = $('select[data-option-edit-path="' + optionEditPath + '"]');
      var $inputs = $('input[data-option-edit-path="' + optionEditPath + '"]');
      var $radios = $inputs.filter('[type=radio]');
      var $checkboxes = $inputs.filter('[type=checkbox]');

      if ($selects.length > 0) {
        rebuildOptions($selects, CRM.utils.setOptions);
      }
      else if ($radios.length > 0) {
        rebuildOptions($radios, rebuildRadioOptions);
      }
      else if ($checkboxes.length > 0) {
        rebuildOptions($checkboxes, rebuildCheckboxOptions);
      }
    });

  /**
   * Fetches options using metadata from the existing ones and calls the
   * function to rebuild them
   * @param $existing {object} The existing options, used as metadata store
   * @param rebuilder {function} Function to be called to rebuild the options
   */
  function rebuildOptions($existing, rebuilder) {
    if ($existing.data('api-entity') && $existing.data('api-field')) {
      var params = {
        sequential: 1,
        field: $existing.data('api-field')
      };
      $.extend(params, $existing.data('option-edit-context'));

      CRM.api3($existing.data('api-entity'), 'getoptions', params)
      .done(function(data) {
        rebuilder($existing, data.values);
      });
    }
  }

  /**
   * Rebuild checkbox input options, overwriting the existing options
   *
   * @param $existing {object} the existing checkbox options
   * @param newOptions {array} in format returned by api.getoptions
   */
  function rebuildCheckboxOptions($existing, newOptions) {
    var $parent = $existing.first().parent(),
      $firstExisting = $existing.first(),
      optionName = $firstExisting.attr('name'),
      optionAttributes =
        'data-option-edit-path =' + $firstExisting.data('option-edit-path') +
        ' data-api-entity = ' + $firstExisting.data('api-entity') +
        ' data-api-field = ' + $firstExisting.data('api-field');

    var prefix = optionName.substr(0, optionName.lastIndexOf("["));

    var checkedBoxes = [];
    $parent.find('input:checked').each(function() {
      checkedBoxes.push($(this).attr('id'));
    });

    // remove existing checkboxes
    $parent.find('input[type=checkbox]').remove();

    // find existing labels for the checkboxes
    var $checkboxLabels = $parent.find('label').filter(function() {
      var forAttr = $(this).attr('for') || '';

      return forAttr.indexOf(prefix) !== -1;
    });

    // find what is used to separate the elements; spaces or linebreaks
    var $elementAfterLabel = $checkboxLabels.first().next();
    var separator = $elementAfterLabel.is('br') ? '<br/>' : '&nbsp;';

    // remove existing labels
    $checkboxLabels.remove();

    // remove linebreaks in container
    $parent.find('br').remove();

    // remove separator whitespace in container
    $parent.html(function (i, html) {
      return html.replace(/&nbsp;/g, '');
    });

    var renderedOptions = '';
    // replace missing br at start of element
    if (separator === '<br/>') {
      $parent.prepend(separator);
      renderedOptions = separator;
    }

    newOptions.forEach(function(option) {
      var optionId = prefix + '_' + option.key,
        checked = '';

      if ($.inArray(optionId, checkedBoxes) !== -1) {
        checked = ' checked="checked"';
      }

      renderedOptions += '<input type="checkbox" ' +
        ' value="1"' +
        ' id="' + optionId + '"' +
        ' name="' + prefix + '[' + option.key +']' + '"' +
        checked +
        ' class="crm-form-checkbox"' +
        optionAttributes +
        '><label for="' + optionId + '">' + option.value + '</label>' +
        separator;
    });

    // remove final separator
    renderedOptions = renderedOptions.substring(0, renderedOptions.lastIndexOf(separator));

    var $editLink = $parent.find('.crm-option-edit-link');

    // try to insert before the edit link to maintain structure
    if ($editLink.length > 0) {
      $(renderedOptions).insertBefore($editLink);
    }
    else {
      $parent.append(renderedOptions);
    }
  }

  /**
   * Rebuild radio input options, overwriting the existing options
   *
   * @param $existing {object} the existing input options
   * @param newOptions {array} in format returned by api.getoptions
   */
  function rebuildRadioOptions($existing, newOptions) {
    var $parent = $existing.first().parent(),
      $firstExisting = $existing.first(),
      optionName = $firstExisting.attr('name'),
      renderedOptions = '',
      checkedValue = parseInt($parent.find('input:checked').attr('value')),
      optionAttributes =
        'data-option-edit-path =' + $firstExisting.attr('data-option-edit-path') +
        ' data-api-entity = ' + $firstExisting.attr('data-api-entity') +
        ' data-api-field = ' + $firstExisting.attr('data-api-field');

    // remove existing radio inputs and labels
    $parent.find('input, label').remove();

    newOptions.forEach(function(option) {
      var optionId = 'CIVICRM_QFID_' + option.key + '_' + optionName,
        checked = (option.key === checkedValue) ? ' checked="checked"' : '';

      renderedOptions += '<input type="radio" ' +
        ' value=' + option.key +
        ' id="' + optionId +'"' +
        ' name="' + optionName + '"' +
        checked +
        ' class="crm-form-radio"' +
        optionAttributes +
        '><label for="' + optionId + '">' + option.value + '</label> ';
    });

    $parent.prepend(renderedOptions);
  }
});
