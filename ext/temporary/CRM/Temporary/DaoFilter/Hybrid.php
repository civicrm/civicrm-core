<?php

class CRM_Temporary_DaoFilter_Hybrid {

  /**
   * Change to "hybrid" schema.
   *
   * Specifically: Find any TIMESTAMP fields. Supplement them with `*_gmt` fields.
   *
   * @param string $className
   * @param array $fields
   */
  public static function filter(string $className, array &$fields): void {
    if (!preg_match(TEMPORARY_TIMESTAMP_TABLES, $className::getTableName())) {
      return;
    }

    $fieldNames = array_keys($fields);
    foreach ($fieldNames as $fieldName) {
      $field = $fields[$fieldName];
      if ($field['type'] === CRM_Utils_Type::T_TIMESTAMP) {
        $new = array_merge($field, [
          'name' => $field['name'] . '_gmt',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'where' => $field['where'] . '_gmt',
        ]);
        // print_r([$new]);
        $fields[$fieldName . '_gmt'] = $new;
      }
    }
  }

}
