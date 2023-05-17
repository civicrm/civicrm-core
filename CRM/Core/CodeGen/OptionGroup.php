<?php
class CRM_Core_CodeGen_OptionGroup {

  /**
   * @var array
   * @internal
   */
  public $metadata = [
    'is_active' => 1,
    'is_reserved' => 1,
    'option_value_fields' => 'name,label,description',
  ];

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

  protected $rows = [];

  /**
   * @var string
   * @internal
   */
  public $historicalId;

  public static function create(string $name, ?string $historicalId = NULL): CRM_Core_CodeGen_OptionGroup {
    $og = new static();
    $og->metadata['name'] = $name;
    // $og->var = '@option_group_id_' . $name;
    $og->var = '@this_option_group_id';
    $og->historicalId = $historicalId;
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

  public function addDefaults(array $fields): CRM_Core_CodeGen_OptionGroup {
    $this->defaults = array_merge($this->defaults, $fields);
    return $this;
  }

  /**
   * Add a bunch of values to the option-group.
   *
   * @param array $header
   *   Ex: ['name', 'label']
   * @param array $optionValues
   *   A list of option-value records.
   *
   *   Generally, each record should match the header.
   *   Ex: [
   *     ['foo_bar', ts('Foo Bar')]
   *     ['whiz_bang', ts('Whiz Bang')]
   *   ]
   *
   *   Additionally, you may add extra key-value pairs
   *
   *   Ex: [
   *     ['foo_bar', ts('Foo Bar')]
   *     ['whiz_bang', ts('Whiz Bang'), 'is_active' => 0, 'component_id' => 100]
   *   ]
   *
   * @return $this
   */
  public function addValueTable(array $header, array $optionValues) : CRM_Core_CodeGen_OptionGroup {
    foreach ($optionValues as $optionValue) {
      $row = [];
      foreach ($optionValue as $key => $value) {
        if (is_numeric($key)) {
          $key = $header[$key];
        }
        $row[$key] = $value;
      }
      $this->rows[] = $row;
    }
    return $this;
  }

  public function toArray(): array {
    $position = 1;
    $result = [];
    foreach ($this->rows as $row) {
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
