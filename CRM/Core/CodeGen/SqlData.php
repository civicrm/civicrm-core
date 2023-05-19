<?php

/**
 * @internal
 *   This class may change radically if doing so helps with the installer or upgrader.
 */
class CRM_Core_CodeGen_SqlData extends CRM_Core_CodeGen_AbstractSqlData {

  /**
   * @var string
   */
  protected $table;

  /**
   * @var string
   *   Ex: 'INSERT INTO'
   */
  protected $verb;

  public static function create(string $table, string $verb = 'INSERT INTO'): CRM_Core_CodeGen_SqlData {
    $sqlData = new static();
    $sqlData->table = $table;
    $sqlData->verb = $verb;
    return $sqlData;
  }

  public function toArray(): array {
    $result = [];
    foreach ($this->rows as $row) {
      $result[] = array_merge($this->defaults, $this->applySyncRules($row));
    }
    return $result;
  }

  public function toSQL(): string {
    $result = '';
    $rows = $this->toArray();
    if ($rows) {
      $result .= CRM_Utils_SQL_Insert::into($this->table, $this->verb)
        ->allowLiterals()
        ->rows($rows)
        ->toSQL() . ";\n";
    }
    return $result;
  }

}
