<?php


/**
 * @internal
 *   This class may change radically if doing so helps with the installer or upgrader.
 */
abstract class CRM_Core_CodeGen_AbstractSqlData {

  /**
   * Determine the relative order of two option-groups.
   *
   * @param \CRM_Core_CodeGen_OptionGroup $a
   * @param \CRM_Core_CodeGen_OptionGroup $b
   *
   * @return int
   */
  public static function compare(CRM_Core_CodeGen_OptionGroup $a, CRM_Core_CodeGen_OptionGroup $b): int {
    if ($a->sortKey !== $b->sortKey) {
      return strnatcmp($a->sortKey, $b->sortKey);
    }
    else {
      return strnatcmp($a->metadata['name'], $b->metadata['name']);
    }
  }

  abstract public function toSQL(): string;

  /**
   * Default values to apply to each record.
   *
   * @var array
   */
  protected $defaults = [];

  protected $rows = [];

  protected $syncRules = [];

  /**
   * @var string
   */
  protected $sortKey;

  /**
   * Copy fields
   *
   * @param string $mode
   *   copy|fill
   * @param array $fields
   *   Array(string $srcField => string $destField).
   *   Ex: ['name' => 'label']
   * @return $this
   */
  public function syncColumns(string $mode, array $fields) {
    foreach ($fields as $from => $to) {
      $this->syncRules[] = [$mode, $from, $to];
    }
    return $this;
  }

  public function addDefaults(array $fields) {
    $this->defaults = array_merge($this->defaults, $fields);
    return $this;
  }

  /**
   * Add a bunch of values to the option-group.
   *
   * @param array $optionValues
   *   List of option-value records.
   *   Ex: [
   *     ['name' => 'foo_bar', 'label' => ts('Foo Bar')],
   *     ['name' => 'whiz_bang', 'label' => ts('Whiz Bang')],
   *   ]
   * @return $this
   */
  public function addValues(array $optionValues) {
    $this->rows = array_merge($this->rows, $optionValues);
    return $this;
  }

  /**
   * Add a bunch of values to the option-group using a tabular notation.
   *
   * @param array $header
   *   Ex: ['name', 'label']
   * @param array $optionValues
   *   A list of option-value records (aligned with the header).
   *
   *   Ex: [
   *     ['foo_bar', ts('Foo Bar')]
   *     ['whiz_bang', ts('Whiz Bang')]
   *   ]
   *
   *   Additionally, to address outliers that don't fit tabular form, you may add key-value pairs.
   *
   *   Ex: ['whiz_bang', ts('Whiz Bang'), 'component_id' => 100]
   *
   * @return $this
   */
  public function addValueTable(array $header, array $optionValues) {
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

  /**
   * @param array $row
   * @return array
   *   Updated row
   */
  protected function applySyncRules(array $row): array {
    foreach ($this->syncRules as $syncRule) {
      [$mode, $from, $to] = $syncRule;
      switch ($mode) {
        case 'copy':
          $row[$to] = $row[$from];
          break;

        case 'fill':
          if (array_key_exists($from, $row) && !array_key_exists($to, $row)) {
            $row[$to] = $row[$from];
          }
          break;

        default:
          throw new \RuntimeException("Invalid sync mod: $mode");
      }
    }
    return $row;
  }

}
