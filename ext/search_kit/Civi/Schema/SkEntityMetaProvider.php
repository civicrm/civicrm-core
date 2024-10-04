<?php

namespace Civi\Schema;

use CRM_Search_ExtensionUtil as E;

class SkEntityMetaProvider extends SqlEntityMetadata {

  private $displayInfo;

  public function getProperty(string $propertyName) {
    $staticProps = [
      'primary_keys' => ['_row'],
      'log' => FALSE,
      'icon' => 'fa-search-plus',
      'paths' => [],
    ];
    if (isset($staticProps[$propertyName])) {
      return $staticProps[$propertyName];
    }
    $display = $this->getDisplayInfo();
    $displayProps = [
      'name' => $display['entityName'],
      'title' => $display['label'],
      'title_plural' => $display['label'],
      'description' => $display['settings']['description'] ?? NULL,
      'table' => $display['tableName'],
    ];
    return $displayProps[$propertyName] ?? NULL;
  }

  public function getFields(): array {
    $entityDisplay = $this->getDisplayInfo();
    $fields = [
      '_row' => [
        'title' => E::ts('Case ID'),
        'sql_type' => 'int unsigned',
        'input_type' => 'Number',
        'required' => TRUE,
        'description' => E::ts('Search result row number'),
        'usage' => [],
        'primary_key' => TRUE,
        'auto_increment' => TRUE,
      ],
    ];
    foreach ($entityDisplay['settings']['columns'] as $column) {
      $field = [
        'title' => $column['label'],
        'data_type' => $column['spec']['data_type'],
        'entity_reference' => $column['spec']['entity_reference'] ?? NULL,
        'input_type' => $column['spec']['input_type'] ?? NULL,
        'serialize' => $column['spec']['serialize'] ?? NULL,
        'usage' => [],
      ];
      $fields[$column['spec']['name']] = $field;
    }
    return $fields;
  }

  private function getDisplayInfo(): array {
    // Load and cache display (substr is used to strip the `SK_` prefix)
    $this->displayInfo ??= _getSearchKitEntityDisplays(substr($this->entityName, 3))[0];
    return $this->displayInfo;
  }

}
