<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This trait supports adding custom data to forms.
 *
 * @internal
 */
trait CRM_Custom_Form_CustomDataTrait {

  /**
   * Add custom data fields to the form.
   *
   * This function takes responsibility for registering the appropriate fields with QuickForm.
   *
   * It does not assign variables to the smarty layer to assist with rendering or setDefaults.
   * Those additional steps are not required on the 'main form' when the CustomDataForm is
   * being rendered by ajax as that process takes care of the presentation.
   *
   * However, the fields need to be registered with QuickForm to ensure that on submit
   * they are 'accepted' by quick form. Quick form validates the contents of $_POST
   * and only fields that have been registered make it through to the values
   * available in `$form->getSubmittedValues()`.
   *
   * @internal this is not supported for use outside of core and there is no guarantee the
   * function signature or behaviour won't change. It you use if from outside core
   * be sure to use unit tests in your non-core use.
   *
   * @param string $entity
   * @param array $filters
   *   Filters is only needed for entities where CustomDataGroups may be filtered.
   *   e.g Activity custom data groups might be available for only some entity types.
   *   In that case the filters would hold the id (if any) of the entity and the
   *   activity_type_id if known.
   *
   * @throws \CRM_Core_Exception
   */
  protected function addCustomDataFieldsToForm(string $entity, array $filters = []): void {
    $fields = (array) civicrm_api4($entity, 'getFields', [
      'action' => 'create',
      'values' => $filters,
      'where' => [
        ['type', '=', 'Custom'],
        ['readonly', '=', FALSE],
      ],
      'checkPermissions' => TRUE,
    ])->indexBy('custom_field_id');
    $fieldFilters = ['style' => 'Inline'];
    if ($entity === 'Contact') {
      // Ideally this would not be contact specific but the function being
      // called here does not handle the filters as received.
      $fieldFilters += [
        'extends' => [$entity, $filters['contact_type']],
        'is_multiple' => TRUE,
      ];
      if (!empty($filters['contact_sub_type'])) {
        $fieldFilters['extends_entity_column_value'] = [NULL, $filters['contact_sub_type']];
      }

      $multipleCustomGroups = CRM_Core_BAO_CustomGroup::getAll($fieldFilters);
      foreach ($multipleCustomGroups as $multipleCustomGroup) {
        foreach ($multipleCustomGroup['fields'] as $groupField) {
          $groupField['custom_group_id.is_multiple'] = TRUE;
          $groupField['table_name'] = $multipleCustomGroup['table_name'];
          $groupField['custom_field_id'] = $groupField['id'];
          $groupField['required'] = $groupField['is_required'];
          $groupField['input_type'] = $groupField['html_type'];
          $fields[$groupField['id']] = $groupField;
        }
      }
    }

    $formValues = [];
    foreach ($fields as $field) {
      // Here we add the custom fields to the form
      // based on whether they have been 'POSTed'
      foreach ($this->getInstancesOfField($field['custom_field_id']) as $elementName) {
        $formValues[$elementName] = $this->addCustomField($elementName, $field);
      }
    }
    $qf = $this->get('qfKey');
    $this->assign('qfKey', $qf);
    // We cached the POSTed values so that they can be reloaded
    // if the form fails to submit. Note that we may be combining the
    // values with those stored by other custom field entities on the
    // form.
    $defaultValues = (array) Civi::cache('customData')->get($qf);
    Civi::cache('customData')->set($qf, $formValues + $defaultValues);
  }

  /**
   * Get the instances of the given field in $_POST to determine how many to add to the form.
   *
   * @param int $id
   *
   * @return array
   */
  private function getInstancesOfField($id): array {
    $instances = [];
    $found = [];
    foreach (array_merge($_POST, ($_FILES ?? [])) as $key => $value) {
      if (preg_match('/^custom_' . $id . '_?(-?\d+)?$/', $key)) {
        $instances[] = $key;
        $found[$id] = $key;
      }
    }
    if (!isset($found[$id])) {
      // I think the _POST check was mostly about multiple fields
      // see https://github.com/civicrm/civicrm-core/pull/29708
      // However per https://lab.civicrm.org/dev/core/-/issues/5322
      // it turns out that radio fields do not show up in the form.
      // We can handle those here - although is that enough to handle blanking on
      // multiple field radios?
      $field = CRM_Core_BAO_CustomField::getField($id);
      if ($field['html_type'] === 'Radio' || $field['html_type'] === 'Select') {
        $group = CRM_Core_BAO_CustomGroup::getGroup(['id' => $field['custom_group_id']]);
        if (!$group['is_multiple']) {
          $instances[] = 'custom_' . $id;
        }
      }
    }
    return $instances;
  }

  /**
   * Add the given field to the form.
   *
   * @param string $elementName
   * @param array $field
   *
   * @return mixed
   *
   * @throws \CRM_Core_Exception
   *
   * @internal this is not supported for use outside of core and there is no guarantee the
   *  function signature or behaviour won't change. It you use if from outside core
   *  be sure to use unit tests in your non-core use.
   */
  protected function addCustomField(string $elementName, array $field) {
    // Note that passing required = TRUE here does not seem to actually do anything. As long
    // as the form opens in pop up mode jquery validation from the ajax form ensures required fields are
    // submitted. In non-popup mode however the required is not enforced. This appears to be
    // a bug that has been around for a while.
    CRM_Core_BAO_CustomField::addQuickFormElement($this, $elementName, $field['custom_field_id'], $field['required']);
    if ($field['input_type'] === 'File') {
      $this->registerFileField([$elementName]);
    }
    // Get any values from the POST & cache them so that they can be retrieved from the
    // CustomDataByType form in it's setDefaultValues() function - otherwise it cannot reload the
    // values that were just entered if validation fails.
    return is_string($this->getSubmitValue($elementName)) ? CRM_Utils_String::purifyHTML($this->getSubmitValue($elementName)) : $this->getSubmitValue($elementName);
  }

  /**
   * Get the submitted custom fields.
   *
   * This is returned apiv3 style but in future could take
   * api version as a parameter.
   *
   * @return array
   */
  protected function getSubmittedCustomFields(): array {
    $fields = [];
    foreach ($this->getSubmittedValues() as $label => $field) {
      if (CRM_Core_BAO_CustomField::getKeyID($label)) {
        $fields[$label] = $field;
      }
    }
    return $fields;
  }

}
