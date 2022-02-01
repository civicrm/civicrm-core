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

namespace Civi\Api4\Action\CustomField;

/**
 * Code shared by CustomField create/update/save actions
 */
trait CustomFieldSaveTrait {

  /**
   * @inheritDoc
   */
  protected function write(array $items) {
    foreach ($items as &$field) {
      if (empty($field['id'])) {
        self::formatOptionValues($field);
      }
    }
    return parent::write($items);
  }

  /**
   * If 'option_values' have been supplied, reformat it according to the expectations of the BAO
   *
   * @param array $field
   */
  private static function formatOptionValues(array &$field): void {
    $field['option_type'] = !empty($field['option_values']);
    if (!empty($field['option_values'])) {
      $weight = 0;
      $field['option_label'] = $field['option_value'] = $field['option_status'] = $field['option_weight'] =
      $field['option_name'] = $field['option_color'] = $field['option_description'] = $field['option_icon'] = [];
      foreach ($field['option_values'] as $key => $value) {
        // Translate simple key/value pairs into full-blown option values
        if (!is_array($value)) {
          $value = [
            'label' => $value,
            'id' => $key,
          ];
        }
        $field['option_label'][] = $value['label'] ?? $value['name'];
        $field['option_name'][] = $value['name'] ?? NULL;
        $field['option_value'][] = $value['id'];
        $field['option_status'][] = $value['is_active'] ?? 1;
        $field['option_weight'][] = $value['weight'] ?? ++$weight;
        $field['option_color'][] = $value['color'] ?? NULL;
        $field['option_description'][] = $value['description'] ?? NULL;
        $field['option_icon'][] = $value['icon'] ?? NULL;
      }
    }
  }

}
