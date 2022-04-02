<?php

class CRM_Temporary_DaoFilter_Gmt {

  /**
   * Change to "gmt" schema.
   *
   * Specifically: Find any TIMESTAMP fields. Remove them. Add `*_gmt` fields.
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
        $fields[$fieldName . '_gmt'] = $new;
        unset($fields[$fieldName]);
      }
    }

  }

}
