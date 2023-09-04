<?php

class CRM_Core_CodeGen_BounceType extends CRM_Core_CodeGen_AbstractSqlData {

  /**
   * OptionGroup properties.
   *
   * @var array
   * @internal
   */
  protected $metadata = [];

  protected $var;

  public static function create(string $name): CRM_Core_CodeGen_BounceType {
    $og = new static();
    $og->metadata['name'] = $name;
    $og->var = '@bounceTypeID';
    return $og;
  }

  /**
   * @param array $fields
   *  List of BounceType fields/values.
   *  Ex: ['is_reserved' => 0, 'description' => 'Store the stuff']
   * @return $this
   */
  public function addMetadata(array $fields): CRM_Core_CodeGen_BounceType {
    $this->metadata = array_merge($this->metadata, $fields);
    return $this;
  }

  public function toArray(): array {
    $result = [];
    foreach ($this->rows as $row) {
      $row = $this->applySyncRules($row);
      $result[] = array_merge(['bounce_type_id' => new CRM_Utils_SQL_Literal($this->var)], $this->defaults, $row);
    }
    return $result;
  }

  public function toSQL(): string {
    $result = '';
    $result .= CRM_Utils_SQL_Insert::into('civicrm_mailing_bounce_type')
      ->row($this->metadata)
      ->toSQL() . ";\n";

    $rows = $this->toArray();
    if ($rows) {
      $result .= CRM_Utils_SQL_Select::from('civicrm_mailing_bounce_type')
        ->select("{$this->var} := max(id)")
        ->where('name = @NAME', ['NAME' => $this->metadata['name']])
        ->toSQL() . ";\n";

      $result .= CRM_Utils_SQL_Insert::into('civicrm_mailing_bounce_pattern')
        ->allowLiterals()
        ->rows($rows)
        ->toSQL() . ";\n";
    }
    return $result;
  }

}
