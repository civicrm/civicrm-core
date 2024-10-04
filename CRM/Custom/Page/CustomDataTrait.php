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
 * This trait supports adding custom data to pages.
 *
 * @internal this is not supported for use outside of core and there is no guarantee the
 * function signature or behaviour won't change. It you use if from outside core
 * be sure to use unit tests in your non-core use.
 */
trait CRM_Custom_Page_CustomDataTrait {

  /**
   * Get the custom values for the entity ready to be displayed on the form.
   *
   * @param string $entity
   * @param int $id
   *
   * @return array
   * @throws \CRM_Core_Exception
   *
   */
  protected function getCustomDataFieldsForEntityDisplay(string $entity, int $id): array {
    $values = civicrm_api4($entity, 'get', [
      'select' => [
        'custom.*',
      ],
      'where' => [
        ['id', '=', $id],
      ],
      'checkPermissions' => TRUE,
    ])->single();
    $formValues = [];
    // We have api style field names not IDs so can't use the BAO function AFAIK.
    $fields = civicrm_api4($entity, 'getfields', [
      'where' => [
        ['name', 'IN', array_keys($values)],
      ],
      'checkPermissions' => TRUE,
    ])->indexBy('name');
    foreach ($values as $name => $value) {
      if ($name === 'id') {
        continue;
      }
      // https://lab.civicrm.org/dev/core/-/issues/5393
      // Early filter on empty values.
      if ($value !== NULL && $value !== []) {
        $spec = CRM_Core_BAO_CustomField::getField($fields[$name]['custom_field_id']);
        if (!isset($formValues[$spec['custom_group_id']])) {
          $formValues[$spec['custom_group_id']] = [
            'title' => $spec['custom_group']['title'],
            'name' => $spec['custom_group']['name'],
            'collapse_display' => $spec['custom_group']['collapse_display'],
            'fields' => [],
          ];
        }
        $displayValue = CRM_Core_BAO_CustomField::displayValue($value, $spec['id'], $id);
        // https://lab.civicrm.org/dev/core/-/issues/5393
        // Final filter on empty values.
        if ($displayValue !== '') {
          $formValues[$spec['custom_group_id']]['fields'][$spec['id']][] = [
            // This `field_` prefixing feels a bit awkward but is consistent with similar pages.
            'field_value' => CRM_Core_BAO_CustomField::displayValue($value, $spec['id'], $id),
            'field_title' => $spec['label'],
            'field_input_type' => $spec['html_type'],
            'field_data_type' => $spec['data_type'],
          ];
        }
      }
    }
    return $formValues;
  }

}
