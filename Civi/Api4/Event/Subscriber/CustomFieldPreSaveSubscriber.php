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

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\AbstractAction;

class CustomFieldPreSaveSubscriber extends Generic\PreSaveSubscriber {

  /**
   * @var string
   */
  public $supportedOperation = 'create';

  public function modify(&$field, AbstractAction $request) {
    if (!empty($field['option_values'])) {
      $weight = $key = 0;
      $field['option_label'] = $field['option_value'] = $field['option_status'] = $field['option_weight'] = [];
      $field['option_name'] = $field['option_color'] = $field['option_description'] = $field['option_icon'] = [];
      foreach ($field['option_values'] as $key => $value) {
        // Translate simple key/value pairs into full-blown option values
        if (!is_array($value)) {
          $value = [
            'label' => $value,
            'id' => $key,
          ];
        }
        $weight++;
        $field['option_label'][] = $value['label'] ?? $value['name'];
        $field['option_name'][] = $value['name'] ?? NULL;
        $field['option_value'][] = $value['id'];
        $field['option_status'][] = $value['is_active'] ?? 1;
        $field['option_weight'][] = $value['weight'] ?? $weight;
        $field['option_color'][] = $value['color'] ?? NULL;
        $field['option_description'][] = $value['description'] ?? NULL;
        $field['option_icon'][] = $value['icon'] ?? NULL;
      }
    }
    $field['option_type'] = !empty($field['option_values']);
  }

  public function applies(AbstractAction $request) {
    return $request->getEntityName() === 'CustomField';
  }

}
