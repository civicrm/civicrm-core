<?php

namespace Civi\Contribute\Utils;

class PriceFieldUtils {

  /**
   * @return string[] entities for which payments are enabled
   */
  public static function getEnabledEntities(): array {
    return array_keys(self::getPriceFieldSpecs());
  }

  /**
   * Map for non-pseudoconstant `extends` field on `price_set`
   *
   * TODO: make extends a pseudoconstant OR use a new field on `price_field` table
   *
   * @return array
   */
  protected static function getExtendsIdToEntityMap(): array {
    $componentToEntityMap = [
      'CiviMember' => 'Membership',
      'CiviContribute' => 'Contribution',
      'CiviEvent' => 'Participant',
    ];

    $extendsIdToEntityMap = [];

    foreach (\CRM_Price_BAO_PriceSet::getExtendsOptions() as $option) {
      $component = $option['name'];
      $entity = $componentToEntityMap[$component];

      $extendsId = (int) $option['id'];
      $extendsIdToEntityMap[$extendsId] = $entity;
    }

    return $extendsIdToEntityMap;
  }

  public static function getPriceFieldsForEntity(string $entity): array {
    return self::getPriceFieldSpecs()[$entity] ?? [];
  }

  public static function getPriceFieldSpecs(): array {
    if (!isset(\Civi::$statics[__CLASS__])) {
      \Civi::$statics[__CLASS__] = self::fetchPriceFieldSpecs();
    }
    return \Civi::$statics[__CLASS__];
  }

  protected static function fetchPriceFieldSpecs(): array {
    $priceFields = (array) \Civi\Api4\PriceField::get(FALSE)
      ->addSelect('id', 'name', 'label', 'html_type', 'is_enter_qty', 'is_display_amounts')
      // TODO: using entity extends from the parent PriceSet for now
      // if we want to flatten to only use PriceFields, we could add
      // an extends field to PriceField
      // (in this case use a nice pseudoconstant straight from the outset
      // to avoid need for getExtendsIdForEntity and allow easy extending
      // to other (custom) entities)
      // we also using price set name and label to create full names / labels for each field
      // if we flatten then we should enforce unique names on PriceField
      // and make sure labels are clear
      ->addSelect('price_set_id.extends', 'price_set_id.name', 'price_set_id.title')
      ->execute()
      ->indexBy('id');

    $fieldValues = (array) \Civi\Api4\PriceFieldValue::get(FALSE)
      ->addSelect('id', 'price_field_id', 'label', 'amount')
      ->execute();

    // Add amount to each PriceFieldValue option label
    foreach ($fieldValues as &$fieldValue) {
      if (!empty($priceFields[$fieldValue['price_field_id']]['is_display_amounts'])) {
        $fieldValue['label'] = $fieldValue['label'] . ' - ' . \Civi::format()->money($fieldValue['amount'], \CRM_Core_Config::singleton()->defaultCurrency);
      }
    }

    $extendsIdToEntityMap = self::getExtendsIdToEntityMap();

    $fieldSpecs = [];

    foreach ($priceFields as $priceField) {
      $fullName = "{$priceField['price_set_id.name']}.{$priceField['name']}";

      // concatenate set label if not duplicate
      $fullLabel = ($priceField['price_set_id.title'] === $priceField['label']) ?
        $priceField['label'] : "{$priceField['price_set_id.title']}: {$priceField['label']}";

      // Price Field configuration sets Text for "Text / Numeric Quantity"
      // but we want input_type = Number so we get clientside validation
      // that the user has entered a numeric value
      if ($priceField['html_type'] === 'Text') {
        $priceField['html_type'] = 'Number';
      }
      $fieldSpec = [
        'price_field_id' => $priceField['id'],
        'name' => $fullName,
        'label' => $fullLabel,
        'frontend_label' => $priceField['label'],
        // TODO: do all price fields correspond to an amount?
        'data_type' => 'Float',
        'input_type' => $priceField['html_type'],
        'is_enter_qty' => $priceField['is_enter_qty'],
      ];

      if ($fieldSpec['price_field_id'] === 1) {
        // price_field_id = 1 is the "magic" Default Contribution Amount,
        // the schema has options but we ignore them as user will
        // enter amount rather than option ID
      }
      else {
        $options = array_filter($fieldValues, fn($value) => ($value['price_field_id'] === $priceField['id']));

        if ($options) {
          $fieldSpec['options'] = array_column($options, 'label', 'id');
          // note: field value will be a PriceFieldValue id rather than an amount
          $fieldSpec['data_type'] = 'Integer';
          if ($fieldSpec['is_enter_qty']) {
            $fieldSpec['amount'] = reset($options)['amount'];
          }
        }
      }

      // add to sub array keyed by entity
      // NOTE this allows the same field may appear multiple times on separate entities?
      // if price_set_id.extends is multivalued... is it ever?
      foreach ($priceField['price_set_id.extends'] ?? [] as $extendsId) {
        $entity = $extendsIdToEntityMap[$extendsId];
        $fieldSpec['extends'] = $entity;

        // initialise sub-array for this entity if necessary
        $fieldSpecs[$entity] ??= [];
        $fieldSpecs[$entity][$fullName] = $fieldSpec;
      }
    }

    return $fieldSpecs;
  }

  public static function getLineItemForPriceFieldValue(string $entityType, ?int $entityId, array $field, $fieldValue): array {
    $entityTable = \Civi\Schema\EntityRepository::getEntity($entityType)['table'] ?? NULL;
    $lineItem = [
      'entity_type' => $entityType,
      'entity_table' => $entityTable,
      'entity_id' => $entityId,
      'price_field_id' => $field['price_field_id'],
      'label' => $field['label'],
      // TODO: is this true/used? what if $entityType === 'Participant' ?
      'participant_count' => 0,
      // these will need to be populated per record
      // 'field_title' => $field['label'],
      // 'description' => E::ts('%1 ID: %2', [
      //   1 => $entityType,
      //   2 => $entityId,
      // ]),
      // 'contribution_id' => 1,
      // 'qty' => 1,
      // 'unit_price' => 125,
      // 'line_total' => 125,
      // 'participant_count' => 0,
      // 'price_field_value_id' => 1,
      // 'financial_type_id' => 1,
      // 'non_deductible_amount' => 0,
      // 'tax_amount' => 0,
      // 'membership_num_terms' => NULL,
    ];

    // special handling for Default Contribution Amount
    // this has one field value but the user will enter an amount
    // rather than option id
    if ($field['price_field_id'] === 1) {
      $lineItem['qty'] = 1;
      $lineItem['unit_price'] = $fieldValue;
      // generic line item
      $lineItem['price_field_value_id'] = 1;
    }
    elseif ($field['is_enter_qty'] ?? FALSE) {
      $lineItem['qty'] = $fieldValue;
      $lineItem['unit_price'] = $field['amount'];
      $lineItem['price_field_value_id'] = array_keys($field['options'])[0];
    }
    elseif ($field['options'] ?? FALSE) {
      if (!\array_key_exists($fieldValue, $field['options'])) {
        throw new \CRM_Core_Exception("Invalid option ID {$fieldValue} for field ID {$field['price_field_id']}");
      }
      $lineItem['price_field_value_id'] = $fieldValue;
      //$lineItem['description'] = "{$field['options'][$fieldValue]} ({$lineItem['description']})";
    }
    else {
      $lineItem['qty'] = 1;
      $lineItem['unit_price'] = $fieldValue;
      // generic line item
      $lineItem['price_field_value_id'] = 1;
    }

    return $lineItem;
  }

}
