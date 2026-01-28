<?php

use Civi\Api4\Contribution;
use Civi\Api4\Order;
use Civi\Api4\Payment;
use Civi\Api4\Query\SqlExpression;
use Civi\Api4\Utils\CoreUtil;

/**
 * Import parser for Api4-based imports via SearchKit
 */
class CRM_Search_Import_Parser extends CRM_Import_Parser {
  use \Civi\Api4\Generic\Traits\SavedSearchInspectorTrait;

  /**
   * @var string
   */
  protected $baseEntity;

  /**
   * @var array
   */
  protected $display;

  private ?array $importEntities = NULL;

  private ?array $fieldMappings = NULL;

  private array $joinPrefixes = [];

  private array $lineItemEntities = ['Membership', 'Participant'];

  /**
   * Get information about the provided job.
   *
   *  - name
   *  - id (generally the same as name)
   *  - label
   *
   * @return array
   */
  public static function getUserJobInfo(): array {
    return [
      'search_batch_import' => [
        'id' => 'search_batch_import',
        'name' => 'search_batch_import',
        'label' => ts('Import data from Search Kit'),
      ],
    ];
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import(array $values): void {
    $rowNumber = (int) $values['_id'];
    $mappedRow = $this->getMappedRow($values);
    if (!$mappedRow[$this->baseEntity]) {
      $this->setImportStatus($rowNumber, 'ERROR', ts('No data found for %1.', [1 => CoreUtil::getInfoItem($this->baseEntity, 'title')]));
      return;
    }
    try {
      $this->saveEntities($mappedRow);
      $idField = CoreUtil::getIdFieldName($this->baseEntity);
      $this->setImportStatus($rowNumber, 'IMPORTED', '', $mappedRow[$this->baseEntity][$idField]);
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
    }
  }

  private function saveFinancialEntities(array &$mappedRow): void {
    $contributionValues = NULL;
    $contributionKey = NULL;
    foreach ($this->getImportEntities() as $entityKey => $entity) {
      if ($entity['entity_name'] === 'Contribution' && isset($mappedRow[$entityKey])) {
        $contributionKey = $entityKey;
        $contributionValues = $this->getEntityValues($mappedRow, $entity);
        if (isset($contributionValues['contact_id'])) {
          $contributionValues['contact_id'] = $this->getMergedToContactIfDeleted($contributionValues['contact_id']);
        }
      }
    }
    if (!$contributionValues) {
      return;
    }
    $lineItems = [];
    foreach ($this->getImportEntities() as $entityKey => $entity) {
      if ($entity['is_line_item'] && isset($mappedRow[$entityKey])) {
        $lineItem = $this->getEntityValues($mappedRow, $entity);
        $lineItems[] = CRM_Utils_Array::prefixKeys($lineItem, 'entity_id.');
      }
      // Set default amount for soft credits from contribution total if not otherwise specified
      if ($entity['entity_name'] === 'ContributionSoft' && !empty($contributionValues['total_amount'])) {
        $softCredit = $this->getEntityValues($mappedRow, $entity);
        if (!empty($softCredit['contact_id']) && empty($softCredit['amount'])) {
          $mappedRow[$entity['key']]['amount'] = $contributionValues['total_amount'];
        }
      }
    }
    if (!$lineItems && isset($contributionValues['total_amount'])) {
      $lineItems[] = ['line_total_inclusive' => $contributionValues['total_amount']];
    }
    try {
      $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contributionValues['contribution_status_id'] ?? NULL);
      $contribution = Order::create()
        ->setContributionValues($contributionValues)
        ->setLineItems($lineItems)
        ->execute()->single();
      if ($contributionStatus === 'Completed') {
        // Use values from Contribution as saved, in case the hooks changed any.
        $contribution = Contribution::get()
          ->addWhere('id', '=', $contribution['id'])
          ->execute()->single();
        Payment::create()
          ->setValues([
            'contribution_id' => $contribution['id'],
            'total_amount' => $contribution['total_amount'],
            'check_number' => $contribution['check_number'] ?? NULL,
            'trxn_id' => $contribution['trxn_id'] ?? NULL,
            'trxn_date' => $contribution['receive_date'] ?? 'now',
            'payment_instrument_id' => $contribution['payment_instrument_id'],
            'fee_amount' => $contribution['fee_amount'],
            'currency' => $contribution['currency'],
          ])
          ->setNotificationForCompleteOrder(FALSE)
          ->execute();
      }
      $mappedRow[$contributionKey]['id'] = $contribution['id'];
    }
    catch (\CRM_Core_Exception $e) {
      if ($contributionKey === $this->baseEntity) {
        throw $e;
      }
    }
  }

  private function saveEntities(array &$mappedRow): void {
    foreach ($this->getImportEntities() as $entityKey => $entity) {
      if (!isset($mappedRow[$entityKey]) || $entity['is_line_item']) {
        continue;
      }
      try {
        if ($entity['entity_name'] === 'Contribution') {
          $this->saveFinancialEntities($mappedRow);
          continue;
        }
        $entityValues = $this->getEntityValues($mappedRow, $entity);
        if (isset($entityValues['contact_id'])) {
          $entityValues['contact_id'] = $this->getMergedToContactIfDeleted($entityValues['contact_id']);
        }
        $saved = civicrm_api4($entity['entity_name'], 'save', [
          'records' => [$entityValues],
        ])->single();
        $mappedRow[$entityKey] += $saved;
      }
      catch (\CRM_Core_Exception $e) {
        if ($entity['entity_name'] === $this->baseEntity) {
          throw $e;
        }
      }
    }
  }

  private function getEntityValues(array $mappedRow, array $entity): array {
    $entityValues = array_merge($mappedRow[$entity['key']] ?? [], $entity['static_values']);
    foreach ($entity['join_values'] as $field => $joinField) {
      $joinEntity = $this->extractEntityFromFieldName($joinField);
      if (isset($mappedRow[$joinEntity][$joinField])) {
        $entityValues[$field] = $mappedRow[$joinEntity][$joinField];
      }
    }
    return $entityValues;
  }

  /**
   * Get the row from the table mapped to our parameters.
   *
   * @param array $values
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getMappedRow(array $values): array {
    $fieldMappings = $this->getFieldMappings();
    $mappedEntities = [
      $this->baseEntity => [],
    ];
    foreach ($values as $key => $value) {
      if (!isset($fieldMappings[$key])) {
        // Ignore _id, _status, etc
        continue;
      }
      if ($value === '' || $value === NULL) {
        continue;
      }
      $key = $fieldMappings[$key];
      $entity = $this->extractEntityFromFieldName($key);
      $mappedEntities[$entity][$key] = $value;
    }
    return $mappedEntities;
  }

  protected function getFieldMappings(): array {
    if (!isset($this->fieldMappings)) {
      $this->fieldMappings = [];
      foreach ($this->display['settings']['columns'] as $column) {
        if (empty($column['spec'])) {
          continue;
        }
        [$columnName] = explode(':', $column['key']);
        $this->fieldMappings[$column['spec']['name']] = $columnName;
      }
    }
    return $this->fieldMappings;
  }

  public function getImportEntities(): array {
    if ($this->importEntities !== NULL) {
      return $this->importEntities;
    }
    $entities = [
      $this->baseEntity => [
        'entity_name' => $this->baseEntity,
        'key' => $this->baseEntity,
        'entity_field_prefix' => '',
        'static_values' => [],
        'join_values' => [],
        'is_line_item' => FALSE,
      ],
    ];
    $this->joinPrefixes = [];
    foreach ($this->getJoins() as $join) {
      $this->joinPrefixes[$join['alias']] = $join['alias'] . '.';
      $entities[$join['alias']] = [
        'entity_name' => $join['entity'],
        'key' => $join['alias'],
        'entity_field_prefix' => $join['alias'] . '.',
        'static_values' => [],
        'join_values' => [],
        'is_line_item' => FALSE,
      ];
    }
    $entitySortOrder = [$this->baseEntity];
    foreach ($this->getJoins() as $join) {
      $sort = 'after';
      foreach ($join['on'] ?? [] as $clause) {
        if (isset($clause[2]) && is_scalar($clause[2]) && $clause[1] === '=') {
          $field = [$clause[0], $clause[2]];
          $expr = SqlExpression::convert($field[1]);
          // Scalar expr == static value to be added to entity during import
          if (in_array($expr->getType(), ['SqlString', 'SqlNumber'], TRUE)) {
            $entity = $this->extractEntityFromFieldName($field[0]);
            $entities[$entity]['static_values'][$field[0]] = $expr->expr;
          }
          elseif ($expr->getType() === 'SqlField') {
            $prefixedField = $field;
            $entity = [
              $this->extractEntityFromFieldName($field[0]),
              $this->extractEntityFromFieldName($field[1]),
            ];
            // Now we've got 2 fields belonging to 2 entities. Figure out which way the FK goes.
            if (CoreUtil::getIdFieldName($entity[0]) === $field[0]) {
              $entity = array_reverse($entity);
              $field = array_reverse($field);
              $prefixedField = array_reverse($prefixedField);
            }
            // Now we have entity 0 as the FK and entity 1 as the PK
            $entities[$entity[0]]['join_values'][$field[0]] = $prefixedField[1];
            $sort = $entity[1] === $join['alias'] ? 'before' : 'after';
            // Mark any participant or memberships linked to contribution as financial
            if ($entities[$entity[0]]['entity_name'] === 'Contribution' && in_array($entities[$entity[1]]['entity_name'], $this->lineItemEntities)) {
              $entities[$entity[1]]['is_line_item'] = TRUE;
            }
            if ($entities[$entity[1]]['entity_name'] === 'Contribution' && in_array($entities[$entity[0]]['entity_name'], $this->lineItemEntities)) {
              $entities[$entity[0]]['is_line_item'] = TRUE;
            }
          }
        }
      }
      if ($sort === 'before') {
        array_unshift($entitySortOrder, $join['alias']);
      }
      else {
        $entitySortOrder[] = $join['alias'];
      }
    }
    // Put entities in $entitySortOrder
    $this->importEntities = [];
    foreach ($entitySortOrder as $entityName) {
      $this->importEntities[$entityName] = $entities[$entityName];
    }
    return $this->importEntities;
  }

  public function validateRow(?array $row): bool {
    // TODO
    return TRUE;
  }

  private function extractEntityFromFieldName(string &$fieldName): string {
    foreach ($this->joinPrefixes as $alias => $prefix) {
      if (str_starts_with($fieldName, $prefix)) {
        $fieldName = substr($fieldName, strlen($prefix));
        return $alias;
      }
    }
    return $this->baseEntity;
  }

  public function init() {
    $userJob = $this->getUserJob();
    if (empty($userJob['search_display_id.name'])) {
      // Exception is caught by ImportSpecProvider::modifySpec to prevent hard crash in Api4 getFields
      throw new \CRM_Core_Exception('No search display found for this job.');
    }
    $this->display = $userJob['search_display_id.name'];
    $this->savedSearch = $userJob['search_display_id.saved_search_id.name'];
    $this->loadSavedSearch();
    $this->loadSearchDisplay();
    $this->baseEntity = $this->savedSearch['api_entity'];
    $this->importEntities = NULL;
    $this->fieldMappings = NULL;
    // Will populate `$this->importEntities` & `$this->joinPrefixes`
    $this->getImportEntities();
  }

  protected function getDataSourceObject(): ?CRM_Import_DataSource {
    return new CRM_Search_Import_DataSource($this->getUserJobID());
  }

  public function getBaseEntity(): string {
    if (!isset($this->baseEntity)) {
      $this->init();
    }
    return $this->baseEntity;
  }

}
