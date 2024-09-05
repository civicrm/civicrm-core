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
 * Helper classes for parsing the xml schema files.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Schema {

  public static function toString(string $key, SimpleXMLElement $xml): ?string {
    if (isset($xml->$key)) {
      return (string) $xml->$key;
    }
    return NULL;
  }

  public static function toBool(string $key, SimpleXMLElement $xml): ?bool {
    if (isset($xml->$key)) {
      $value = strtolower((string) $xml->$key);
      return $value === 'true' || $value === '1';
    }
    return NULL;
  }

  /**
   * Get some attributes related to html type
   *
   * Extracted during refactor, still a bit messy.
   *
   * @param SimpleXMLElement $fieldXML
   * @return array
   */
  public static function getTypeAttributes(SimpleXMLElement $fieldXML) {
    $type = (string) $fieldXML->type;
    $field = [];
    switch ($type) {
      case 'varchar':
      case 'char':
        $field['length'] = (int) $fieldXML->length;
        $field['sqlType'] = "$type({$field['length']})";
        $field['crmType'] = 'CRM_Utils_Type::T_STRING';
        $field['size'] = self::getSize($fieldXML);
        break;

      case 'text':
        $field['sqlType'] = $type;
        $field['crmType'] = 'CRM_Utils_Type::T_' . strtoupper($type);
        // CRM-13497 see fixme below
        $field['rows'] = isset($fieldXML->html) ? self::toString('rows', $fieldXML->html) : NULL;
        $field['cols'] = isset($fieldXML->html) ? self::toString('cols', $fieldXML->html) : NULL;
        break;

      case 'datetime':
        $field['sqlType'] = $type;
        $field['crmType'] = 'CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME';
        break;

      case 'boolean':
        // need this case since some versions of mysql do not have boolean as a valid column type and hence it
        // is changed to tinyint. hopefully after 2 yrs this case can be removed.
        $field['sqlType'] = 'tinyint';
        $field['crmType'] = 'CRM_Utils_Type::T_' . strtoupper($type);
        break;

      case 'decimal':
        $length = $fieldXML->length ?: '20,2';
        $field['sqlType'] = 'decimal(' . $length . ')';
        $field['crmType'] = self::toString('crmType', $fieldXML) ?: 'CRM_Utils_Type::T_MONEY';
        $field['precision'] = $length;
        break;

      case 'float':
        $field['sqlType'] = 'double';
        $field['crmType'] = 'CRM_Utils_Type::T_FLOAT';
        break;

      default:
        $field['sqlType'] = $type;
        if ($type === 'int unsigned' || $type === 'tinyint') {
          $field['crmType'] = 'CRM_Utils_Type::T_INT';
        }
        else {
          $field['crmType'] = self::toString('crmType', $fieldXML) ?: 'CRM_Utils_Type::T_' . strtoupper($type);
        }
        break;
    }
    // Get value of crmType constant(s)
    $field['crmTypeValue'] = 0;
    $crmTypes = explode('+', $field['crmType']);
    foreach ($crmTypes as $crmType) {
      $field['crmTypeValue'] += constant(trim($crmType));
    }
    return $field;
  }

  /**
   * Sets the size property of a textfield.
   *
   * @param SimpleXMLElement $fieldXML
   *
   * @return string
   */
  public static function getSize(SimpleXMLElement $fieldXML): string {
    // Extract from <size> tag if supplied
    if (!empty($fieldXML->html) && !empty($fieldXML->html->size)) {
      return (string) $fieldXML->html->size;
    }
    return self::getDefaultSize(self::toString('length', $fieldXML));
  }

  public static function getDefaultSize($length) {
    // Infer from <length> tag if <size> was not explicitly set or was invalid
    // This map is slightly different from CRM_Core_Form_Renderer::$_sizeMapper
    // Because we usually want fields to render as smaller than their maxlength
    $sizes = [
      2 => 'TWO',
      4 => 'FOUR',
      6 => 'SIX',
      8 => 'EIGHT',
      16 => 'TWELVE',
      32 => 'MEDIUM',
      64 => 'BIG',
    ];
    foreach ($sizes as $size => $name) {
      if ($length <= $size) {
        return "CRM_Utils_Type::$name";
      }
    }
    return 'CRM_Utils_Type::HUGE';
  }

  public static function getCrmTypeFromSqlType(string $sqlType): int {
    [$type] = explode('(', $sqlType);
    switch ($type) {
      case 'varchar':
      case 'char':
        return CRM_Utils_Type::T_STRING;

      case 'datetime':
        return CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME;

      case 'decimal':
        return CRM_Utils_Type::T_MONEY;

      case 'double':
        return CRM_Utils_Type::T_FLOAT;

      case 'int unsigned':
      case 'tinyint':
        return CRM_Utils_Type::T_INT;

      default:
        return constant('CRM_Utils_Type::T_' . strtoupper($type));
    }
  }

  /**
   * Get the data type from a field array. Defaults to 'data_type' with fallback to
   * mapping based on the 'sql_type'.
   *
   * @param array|null $field
   *   Field array as returned from EntityMetadataInterface::getField()
   *
   * @return string|null
   */
  public static function getDataType(?array $field): ?string {
    if (isset($field['data_type'])) {
      return $field['data_type'];
    }
    if (empty($field['sql_type'])) {
      return NULL;
    }

    // If no data_type provided, look it up from the sql_type
    $dataTypeInt = self::getCrmTypeFromSqlType($field['sql_type']);
    $dataTypeName = CRM_Utils_Type::typeToString($dataTypeInt) ?: NULL;

    return $dataTypeName === 'Int' ? 'Integer' : $dataTypeName;
  }

  /**
   * Fallback used when field in schema xml is missing a title.
   *
   * TODO: Trigger a deprecation notice when this happens.
   *
   * @param string $name
   *
   * @return string
   */
  public static function composeTitle(string $name): string {
    $substitutions = [
      'is_active' => 'Enabled',
    ];
    if (isset($substitutions[$name])) {
      return $substitutions[$name];
    }
    $names = explode('_', strtolower($name));
    $allCaps = ['im', 'id'];
    foreach ($names as $i => $str) {
      if (in_array($str, $allCaps, TRUE)) {
        $names[$i] = strtoupper($str);
      }
      else {
        $names[$i] = ucfirst(trim($str));
      }
    }
    return trim(implode(' ', $names));
  }

  /**
   * Get the 'usage' property for a field.
   *
   * @param SimpleXMLElement $fieldXML
   * @return array
   */
  public static function getFieldUsage(SimpleXMLElement $fieldXML): array {
    $import = self::toBool('import', $fieldXML) ?? FALSE;
    $export = self::toBool('export', $fieldXML);
    if (!isset($fieldXML->usage)) {
      $usage = [
        'import' => $import,
        'export' => $export ?? $import,
      ];
    }
    else {
      $usage = [];
      foreach ($fieldXML->usage->children() as $usedFor => $isUsed) {
        $usage[$usedFor] = self::toBool($usedFor, $fieldXML->usage);
      }
      $import = $usage['import'] ?? $import;
    }
    // Ensure all keys are populated. Import is the historical de-facto default.
    $usage = array_merge(array_fill_keys(['import', 'export', 'duplicate_matching'], $import), $usage);
    // Usage for tokens has not historically been in the metadata so we can default to FALSE.
    // historically hard-coded lists have been used.
    $usage['token'] ??= FALSE;
    return $usage;
  }

  public static function getFieldHtml(SimpleXMLElement $fieldXML): ?array {
    $html = NULL;
    if (!empty($fieldXML->html)) {
      $html = [];
      $validOptions = [
        'type',
        'formatType',
        'label',
        'controlField',
        'min',
        'max',
        /* Fixme: CRM-13497 these could also be moved
        'rows',
        'cols',
        'size', */
      ];
      foreach ($validOptions as $htmlOption) {
        if (isset($fieldXML->html->$htmlOption) && $fieldXML->html->$htmlOption !== '') {
          $html[$htmlOption] = self::toString($htmlOption, $fieldXML->html);
        }
      }
      if (isset($fieldXML->html->filter)) {
        $html['filter'] = (array) $fieldXML->html->filter;
      }
    }
    return $html;
  }

  public static function getFieldPseudoconstant(SimpleXMLElement $fieldXML): ?array {
    $pseudoconstant = NULL;
    if (!empty($fieldXML->pseudoconstant)) {
      //ok this is a bit long-winded but it gets there & is consistent with above approach
      $pseudoconstant = [];
      $validOptions = [
        // Fields can specify EITHER optionGroupName OR table, not both
        // (since declaring optionGroupName means we are using the civicrm_option_value table)
        'optionGroupName',
        'table',
        // If table is specified, keyColumn and labelColumn are also required
        'keyColumn',
        'labelColumn',
        // Non-translated machine name for programmatic lookup. Defaults to 'name' if that column exists
        'nameColumn',
        // Column to fetch in "abbreviate" context
        'abbrColumn',
        // Supported by APIv4 suffixes
        'colorColumn',
        'iconColumn',
        // Where clause snippet (will be joined to the rest of the query with AND operator)
        'condition',
        // callback function incase of static arrays
        'callback',
        // Path to options edit form
        'optionEditPath',
        // Should options for this field be prefetched (for presenting on forms).
        // The default is TRUE, but adding FALSE helps when there could be many options
        'prefetch',
      ];
      foreach ($validOptions as $pseudoOption) {
        if (!empty($fieldXML->pseudoconstant->$pseudoOption)) {
          $pseudoconstant[$pseudoOption] = self::toString($pseudoOption, $fieldXML->pseudoconstant);
        }
      }
      if (!isset($pseudoconstant['optionEditPath']) && !empty($pseudoconstant['optionGroupName'])) {
        $pseudoconstant['optionEditPath'] = 'civicrm/admin/options/' . $pseudoconstant['optionGroupName'];
      }
      // Set suffixes if explicitly declared
      if (!empty($fieldXML->pseudoconstant->suffixes)) {
        $pseudoconstant['suffixes'] = explode(',', self::toString('suffixes', $fieldXML->pseudoconstant));
      }
      // For now, fields that have option lists that are not in the db can simply
      // declare an empty pseudoconstant tag and we'll add this placeholder.
      // That field's BAO::buildOptions fn will need to be responsible for generating the option list
      if (empty($pseudoconstant)) {
        $pseudoconstant = 'not in database';
      }
    }
    return $pseudoconstant;
  }

  public static function getFieldPermission(SimpleXMLElement $fieldXML): ?array {
    $permission = NULL;
    if (isset($fieldXML->permission)) {
      $permission = trim(self::toString('permission', $fieldXML));
      $permission = $permission ? array_filter(array_map('trim', explode(',', $permission))) : [];
      if (isset($fieldXML->permission->or)) {
        $permission[] = array_filter(array_map('trim', explode(',', $fieldXML->permission->or)));
      }
    }
    return $permission;
  }

  /**
   * In multilingual context popup, we need extra information to create appropriate widget
   *
   * @param SimpleXMLElement $fieldXML
   * @return array|string[]|null
   */
  public static function getFieldWidget(SimpleXMLElement $fieldXML): ?array {
    $widget = NULL;
    if ($fieldXML->localizable) {
      if (isset($fieldXML->html)) {
        $widget = (array) $fieldXML->html;
      }
      else {
        // default
        $widget = ['type' => 'Text'];
      }
      if (isset($fieldXML->required)) {
        $widget['required'] = self::toString('required', $fieldXML);
      }
    }
    return $widget;
  }

}
