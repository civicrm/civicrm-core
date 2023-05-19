<?php

/**
 * @internal
 *   This class may change radically if doing so helps with the installer or upgrader.
 */
class CRM_Core_CodeGen_OptionGroup extends CRM_Core_CodeGen_AbstractSqlData {

  /**
   * OptionGroup properties.
   *
   * @var array
   * @internal
   */
  protected $metadata = [
    'is_active' => 1,
    'is_reserved' => 1,
    'option_value_fields' => 'name,label,description',
  ];

  /**
   * Default properties for all OptionValues in this OptionGroup.
   *
   * @var array
   */
  protected $defaults = [
    'color' => NULL,
    'component_id' => NULL,
    'description' => NULL,
    'domain_id' => NULL,
    'filter' => 0,
    'grouping' => NULL,
    'icon' => NULL,
    'is_active' => 1,
    'is_default' => 0,
    'is_optgroup' => 0,
    'is_reserved' => 0,
    'visibility_id' => NULL,
  ];

  protected $var;

  public static function create(string $name, ?string $sortKey = NULL): CRM_Core_CodeGen_OptionGroup {
    $og = new static();
    $og->metadata['name'] = $name;
    // $og->var = '@option_group_id_' . $name;
    $og->var = '@this_option_group_id';
    $og->sortKey = $sortKey;
    return $og;
  }

  /**
   * @param array $fields
   *  List of OptionGroup fields/values.
   *  Ex: ['is_reserved' => 0, 'description' => 'Store the stuff']
   * @return $this
   */
  public function addMetadata(array $fields): CRM_Core_CodeGen_OptionGroup {
    $this->metadata = array_merge($this->metadata, $fields);
    return $this;
  }

  public function toArray(): array {
    $position = 1;
    $result = [];
    foreach ($this->rows as $row) {
      $row = $this->applySyncRules($row);
      $result[] = array_merge(
        ['option_group_id' => new CRM_Utils_SQL_Literal($this->var), 'value' => $position, 'weight' => $position],
        $this->defaults,
        $row
      );
      $position++;
    }
    return $result;
  }

  public function toSQL(): string {
    $result = '';
    $result .= CRM_Utils_SQL_Insert::into('civicrm_option_group')
      ->row($this->metadata)
      ->toSQL() . ";\n";

    $rows = $this->toArray();
    if ($rows) {
      $result .= CRM_Utils_SQL_Select::from('civicrm_option_group')
        ->select("{$this->var} := max(id)")
        ->where('name = @NAME', ['NAME' => $this->metadata['name']])
        ->toSQL() . ";\n";

      $result .= CRM_Utils_SQL_Insert::into('civicrm_option_value')
        ->allowLiterals()
        // ->columns(['option_group_id', 'label', 'value', 'name', 'grouping', 'filter', 'is_default', 'weight', 'description', 'is_optgroup', 'is_reserved', 'is_active', 'component_id', 'visibility_id'])
        ->rows($rows)
        ->toSQL() . ";\n";
    }
    return $result;
  }

}
