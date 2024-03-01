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
    $fields = civicrm_api4($entity, 'getFields', [
      'action' => 'create',
      'values' => $filters,
      'where' => [
        ['type', '=', 'Custom'],
        ['readonly', '=', FALSE],
      ],
      'checkPermissions' => TRUE,
    ])->indexBy('custom_field_id');

    $customGroupSuffixes = [];
    foreach ($fields as $field) {
      // This default suffix indicates the contact has no existing row in the table.
      // It will be overridden below with the id of any row the contact DOES have in the table.
      $customGroupSuffixes[$field['table_name']] = '_-1';
    }
    if (!empty($filters['id'])) {
      $query = [];
      foreach (array_keys($customGroupSuffixes) as $tableName) {
        $query[] = CRM_Core_DAO::composeQuery(
          'SELECT %1 as table_name, id FROM %2 WHERE entity_id = %3',
          [
            1 => [$tableName, 'String'],
            2 => [$tableName, 'MysqlColumnNameOrAlias'],
            3 => [$filters['id'], 'Integer'],
          ]
        );
      }
      if (!empty($query)) {
        $tables = CRM_Core_DAO::executeQuery(implode(' UNION ', $query));
        while ($tables->fetch()) {
          $customGroupSuffixes[$tables->table_name] = '_' . $tables->id;
        }
      }
    }

    $formValues = [];
    foreach ($fields as $field) {
      $suffix = $customGroupSuffixes[$field['table_name']];
      $elementName = 'custom_' . $field['custom_field_id'] . $suffix;
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
      $formValues[$elementName] = is_string($this->getSubmitValue($elementName)) ? CRM_Utils_String::purifyHTML($this->getSubmitValue($elementName)) : $this->getSubmitValue($elementName);
    }
    $qf = $this->get('qfKey');
    $this->assign('qfKey', $qf);
    Civi::cache('customData')->set($qf, $formValues);
  }

}
