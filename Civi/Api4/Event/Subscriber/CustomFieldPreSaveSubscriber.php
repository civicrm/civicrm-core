<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\AbstractAction;

class CustomFieldPreSaveSubscriber extends Generic\PreSaveSubscriber {

  public $supportedOperation = 'create';

  public function modify(&$field, AbstractAction $request) {
    if (!empty($field['option_values'])) {
      $weight = 0;
      foreach ($field['option_values'] as $key => $value) {
        // Translate simple key/value pairs into full-blown option values
        if (!is_array($value)) {
          $value = [
            'label' => $value,
            'value' => $key,
            'is_active' => 1,
            'weight' => $weight,
          ];
          $key = $weight++;
        }
        $field['option_label'][$key] = $value['label'];
        $field['option_value'][$key] = $value['value'];
        $field['option_status'][$key] = $value['is_active'];
        $field['option_weight'][$key] = $value['weight'];
      }
    }
    $field['option_type'] = !empty($field['option_values']);
  }

  public function applies(AbstractAction $request) {
    return $request->getEntityName() === 'CustomField';
  }

}
