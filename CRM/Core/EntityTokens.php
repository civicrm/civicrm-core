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

use Civi\Api4\Generic\Result;
use Civi\Token\AbstractTokenSubscriber;
use Civi\Token\TokenRow;
use Civi\ActionSchedule\Event\MailingQueryEvent;
use Civi\Token\TokenProcessor;
use Civi\Token\Event\TokenValueEvent;

/**
 * Class CRM_Core_EntityTokens
 *
 * Parent class for generic entity token functionality.
 *
 * WARNING - this class is highly likely to be temporary and
 * to be consolidated with the TokenTrait and / or the
 * AbstractTokenSubscriber in future. It is being used to clarify
 * functionality but should NOT be used from outside of core tested code.
 */
class CRM_Core_EntityTokens extends AbstractTokenSubscriber {

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
    $prefetchedValues = $this->getPrefetchedValuesForRow((array) $prefetch, $row);
    $fieldValue = $this->getFieldValue($row, $field, $prefetchedValues);

    if ($this->isPseudoField($field)) {
      $split = explode(':', $field);
      return $row->tokens($entity, $field, $this->getPseudoValue($split[0], $split[1], $this->getFieldValue($row, $split[0], $prefetchedValues)));
    }
    if ($this->isMoneyField($field)) {
      return $row->format('text/plain')->tokens($entity, $field,
        \CRM_Utils_Money::format($fieldValue, $this->getFieldValue($row, 'currency', $prefetchedValues)));
    }
    if ($this->isDateField($field)) {
      return $row->format('text/plain')->tokens($entity, $field, \CRM_Utils_Date::customFormat($fieldValue));
    }
    if ($this->isCustomField($field)) {
      $row->customToken($entity, \CRM_Core_BAO_CustomField::getKeyID($field), $this->getFieldValue($row, 'id', $prefetchedValues));
    }
    else {
      $row->format('text/plain')->tokens($entity, $field, (string) $fieldValue);
    }
  }

  /**
   * @param \Civi\Token\Event\TokenValueEvent $e
   *
   * @return null|array
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function prefetch(TokenValueEvent $e): ?array {
    // Find all the entity IDs
    $entityIDs = $e->getTokenProcessor()->getContextValues($this->getEntityIDField());

    if (!empty($entityIDs)) {
      return [$this->getEntityName() => $this->getEntities($entityIDs)];
    }
    return NULL;
  }

  /**
   * Get the
   *
   * @param array $ids
   *
   * @return \Civi\Api4\Generic\Result
   * @throws \API_Exception
   */
  public function getEntities(array $ids): Result {
    return civicrm_api4($this->getApiEntityName(), 'get', [
      'checkPermissions' => FALSE,
      // Note custom fields are not yet added - I need to
      // re-do the unit tests to support custom fields first.
      'select' => $this->getReturnFields(),
      'where' => [['id', 'IN', $ids]],
    ], 'id');
  }

  /**
   * Metadata about the entity fields.
   *
   * @var array
   */
  protected $fieldMetadata = [];

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return '';
  }

  /**
   * @return string
   */
  protected function getEntityName(): string {
    return CRM_Core_DAO_AllCoreTables::convertEntityNameToLower($this->getApiEntityName());
  }

  /**
   * Get the name of the field that will provide the id for this entity.
   *
   * For example 'contributionId'
   * @return string
   */
  protected function getEntityIDField(): string {
    return $this->getEntityName() . 'Id';
  }

  /**
   * Get the entity alias to use within queries.
   *
   * The default has a double underscore which should prevent any
   * ambiguity with an existing table name.
   *
   * @return string
   */
  protected function getEntityAlias(): string {
    return $this->getApiEntityName() . '__';
  }

  /**
   * Get the name of the table this token class can extend.
   *
   * The default is based on the entity but some token classes,
   * specifically the event class, latch on to other tables - ie
   * the participant table.
   */
  public function getExtendableTableName(): string {
    return CRM_Core_DAO_AllCoreTables::getTableForEntityName($this->getApiEntityName());
  }

  /**
   * Get the relevant bao name.
   */
  public function getBAOName(): string {
    return CRM_Core_DAO_AllCoreTables::getFullName($this->getApiEntityName());
  }

  /**
   * Get an array of fields to be requested.
   *
   * @return string[]
   */
  public function getReturnFields(): array {
    return array_keys($this->getBasicTokens());
  }

  /**
   * Get all the tokens supported by this processor.
   *
   * @return array|string[]
   */
  public function getAllTokens(): array {
    return array_merge($this->getBasicTokens(), $this->getPseudoTokens(), CRM_Utils_Token::getCustomFieldTokens('Contribution'));
  }

  /**
   * Is the given field a date field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isDateField(string $fieldName): bool {
    return $this->getFieldMetadata()[$fieldName]['data_type'] === 'Timestamp';
  }

  /**
   * Is the given field a pseudo field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isPseudoField(string $fieldName): bool {
    return strpos($fieldName, ':') !== FALSE;
  }

  /**
   * Is the given field a custom field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isCustomField(string $fieldName) : bool {
    return (bool) \CRM_Core_BAO_CustomField::getKeyID($fieldName);
  }

  /**
   * Is the given field a date field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isMoneyField(string $fieldName): bool {
    return $this->getFieldMetadata()[$fieldName]['data_type'] === 'Money';
  }

  /**
   * Get the metadata for the available fields.
   *
   * @return array
   */
  protected function getFieldMetadata(): array {
    if (empty($this->fieldMetadata)) {
      try {
        // Tests fail without checkPermissions = FALSE
        $this->fieldMetadata = (array) civicrm_api4($this->getApiEntityName(), 'getfields', ['checkPermissions' => FALSE], 'name');
      }
      catch (API_Exception $e) {
        $this->fieldMetadata = [];
      }
    }
    return $this->fieldMetadata;
  }

  /**
   * Get pseudoTokens - it tokens that reflect the name or label of a pseudoconstant.
   *
   * @internal - this function will likely be made protected soon.
   *
   * @return array
   */
  public function getPseudoTokens(): array {
    $return = [];
    foreach (array_keys($this->getBasicTokens()) as $fieldName) {
      if ($this->isAddPseudoTokens($fieldName)) {
        $return[$fieldName . ':label'] = $this->fieldMetadata[$fieldName]['input_attrs']['label'];
        $return[$fieldName . ':name'] = ts('Machine name') . ': ' . $this->fieldMetadata[$fieldName]['input_attrs']['label'];
      }
    }
    return $return;
  }

  /**
   * Is this a field we should add pseudo-tokens to?
   *
   * Pseudo-tokens allow access to name and label fields - e.g
   *
   * {contribution.contribution_status_id:name} might resolve to 'Completed'
   *
   * @param string $fieldName
   */
  public function isAddPseudoTokens($fieldName): bool {
    if ($fieldName === 'currency') {
      // 'currency' is manually added to the skip list as an anomaly.
      // name & label aren't that suitable for 'currency' (symbol, which
      // possibly maps to 'abbr' would be) and we can't gather that
      // from the metadata as yet.
      return FALSE;
    }
    return (bool) $this->getFieldMetadata()[$fieldName]['options'];
  }

  /**
   * Get the value for the relevant pseudo field.
   *
   * @param string $realField e.g contribution_status_id
   * @param string $pseudoKey e.g name
   * @param int|string $fieldValue e.g 1
   *
   * @return string
   *   Eg. 'Completed' in the example above.
   *
   * @internal function will likely be protected soon.
   */
  public function getPseudoValue(string $realField, string $pseudoKey, $fieldValue): string {
    if ($pseudoKey === 'name') {
      $fieldValue = (string) CRM_Core_PseudoConstant::getName($this->getBAOName(), $realField, $fieldValue);
    }
    if ($pseudoKey === 'label') {
      $fieldValue = (string) CRM_Core_PseudoConstant::getLabel($this->getBAOName(), $realField, $fieldValue);
    }
    return (string) $fieldValue;
  }

  /**
   * @param \Civi\Token\TokenRow $row
   * @param string $field
   * @param array $prefetchedValues
   *
   * @return string|int|null
   */
  protected function getFieldValue(TokenRow $row, string $field, $prefetchedValues) {
    $actionSearchResult = $row->context['actionSearchResult'];
    $aliasedField = $this->getEntityAlias() . $field;
    return $actionSearchResult->{$aliasedField} ?? ($prefetchedValues[$field] ?? NULL);
  }

  /**
   * Class constructor.
   */
  public function __construct() {
    $tokens = $this->getAllTokens();
    parent::__construct($this->getEntityName(), $tokens);
  }

  /**
   * Check if the token processor is active.
   *
   * @param \Civi\Token\TokenProcessor $processor
   *
   * @return bool
   */
  public function checkActive(TokenProcessor $processor): bool {
    return (!empty($processor->context['actionMapping'])
        && $processor->context['actionMapping']->getEntity() === $this->getExtendableTableName())
      || $processor->getContextValues($this->getEntityIDField());
  }

  /**
   * Alter action schedule query.
   *
   * @param \Civi\ActionSchedule\Event\MailingQueryEvent $e
   */
  public function alterActionScheduleQuery(MailingQueryEvent $e): void {
    if ($e->mapping->getEntity() !== $this->getExtendableTableName()) {
      return;
    }
    foreach ($this->getReturnFields() as $token) {
      $e->query->select('e.' . $token . ' AS ' . $this->getEntityAlias() . $token);
    }
  }

  /**
   * Get any values pre-fetched for the row.
   *
   * @param array $prefetch
   * @param \Civi\Token\TokenRow $row
   *
   * @return array
   */
  protected function getPrefetchedValuesForRow(array $prefetch, TokenRow $row): array {
    $id = $this->getEntityIDFromRow($row);
    return $prefetch[$this->getEntityName()][$id] ?? [];
  }

  /**
   * @param \Civi\Token\TokenRow $row
   *
   * @return int|null
   */
  protected function getEntityIDFromRow(TokenRow $row): ?int {
    return $row->context[$this->getEntityIDField()];
  }

}
