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

use Civi\Token\AbstractTokenSubscriber;
use Civi\Token\TokenRow;
use Civi\ActionSchedule\Event\MailingQueryEvent;
use Civi\Token\TokenProcessor;

/**
 * Generic base-class which loads tokens via APIv4.
 *
 * To write a subclass:
 *
 * - (MUST) Implement getApiEntityName()
 * - (MAY) Override getEntityName() - Customize the `{entity_name.*}` entity.
 * - (MAY) Override getApiTokens() - Add/remove tokens. (Default based on `getFields()`.)
 * - (MAY) Override getAliasTokens() - Make interoperable tokens.
 * - (MAY) Override evaluateToken() - Evaluate the content of a token.
 *
 * Parent class for generic entity token functionality.
 *
 * WARNING - this class is highly likely to be temporary and
 * to be consolidated with the TokenTrait and / or the
 * AbstractTokenSubscriber in future. It is being used to clarify
 * functionality but should NOT be used from outside of core tested code.
 */
abstract class CRM_Core_EntityTokens extends AbstractTokenSubscriber {

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
    $aliases = $this->getAliasTokens();
    if (isset($aliases[$field])) {
      $this->evaluateToken($row, $entity, $aliases[$field], $prefetch);
      return $row->copyToken("$entity." . $aliases[$field], "$entity.$field");
    }

    $values = $prefetch[$row->context[$this->getEntityIDField()]];

    if ($this->isApiFieldType($field, 'Timestamp')) {
      return $row->format('text/plain')->tokens($entity, $field, \CRM_Utils_Date::customFormat($values[$field]));
    }
    if ($this->isCustomField($field)) {
      $row->customToken($entity, \CRM_Core_BAO_CustomField::getKeyID($field), $row->context[$this->getEntityIDField()]);
    }
    else {
      $row->format('text/plain')->tokens($entity, $field, (string) ($values[$field]));
    }
  }

  /**
   * Metadata about the entity fields.
   *
   * @var array
   */
  protected $fieldMetadata = [];

  /**
   * @var string
   *   Ex: 'contribution', 'contact'
   */
  private $entityName;

  /**
   * Get the entity name, as it appears in the token.
   *
   * @return string
   *   Ex: 'contribution' for token '{contribution.total_amount}'.
   */
  protected function getEntityName(): string {
    if ($this->entityName === NULL) {
      $this->entityName = CRM_Core_DAO_AllCoreTables::convertEntityNameToLower($this->getApiEntityName());
    }
    return $this->entityName;
  }

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   *   Ex: 'Contribution' for token '{contribution.total_amount}'.
   */
  abstract protected function getApiEntityName(): string;

  /**
   * Get a list of simple, passthrough tokens which are loaded via APIv4.
   *
   * @return string[]
   *   Ex: ['foo' => 'Foo', 'bar_id' => 'Bar ID#']
   */
  protected function getApiTokens(): array {
    $return = [];
    foreach ($this->getFieldMetadata() as $fieldName => $field) {
      $return[$fieldName] = $field['title'] ?? $fieldName;
    }
    return $return;
  }

  /**
   * Get a list of aliased tokens.
   *
   * @return array
   *   Ex: ['my_alias_field' => 'original_field']
   */
  public function getAliasTokens(): array {
    return [];
  }

  public function prefetch(\Civi\Token\Event\TokenValueEvent $e): ?array {
    $entityIDs = $e->getTokenProcessor()->getContextValues($this->getEntityIDField());
    if (empty($entityIDs)) {
      return [];
    }
    $select = $this->getPrefetchFields($e);
    $result = (array) civicrm_api4($this->getApiEntityName(), 'get', [
      'checkPermissions' => FALSE,
      // Note custom fields are not yet added - I need to
      // re-do the unit tests to support custom fields first.
      'select' => $select,
      'where' => [['id', 'IN', $entityIDs]],
    ], 'id');
    return $result;
  }

  /**
   * Determine which fields should be prefetched.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   * @return array
   *   List of API fields to prefetch.
   */
  public function getPrefetchFields(\Civi\Token\Event\TokenValueEvent $e): array {
    $activeTokens = $this->getActiveTokens($e);
    foreach ($this->getAliasTokens() as $aliasToken => $aliasTarget) {
      if (in_array($aliasToken, $activeTokens)) {
        $activeTokens[] = $aliasTarget;
      }
    }
    return array_intersect(array_keys($this->getApiTokens()), $activeTokens);
  }

  /**
   * Get all the tokens supported by this processor.
   *
   * @return array|string[]
   */
  public function getAllTokens(): array {
    $return = $this->getApiTokens();
    foreach ($this->getAliasTokens() as $aliasToken => $aliasTarget) {
      $return[$aliasToken] = ts('%1 (Alias)', [1 => $return[$aliasTarget]]);
    }
    return array_merge($return, CRM_Utils_Token::getCustomFieldTokens($this->getApiEntityName()));
  }

  /**
   * Does the field have the given type?
   *
   * @param string $fieldName
   * @param string $expectType
   *   Ex: 'Number', 'Money', 'Timestamp'
   * @return bool
   */
  protected function isApiFieldType(string $fieldName, string $expectType): bool {
    return ($this->getFieldMetadata($fieldName)['data_type'] ?? NULL) === $expectType;
  }

  /**
   * Is the given field a custom field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  protected function isCustomField(string $fieldName) : bool {
    return (bool) \CRM_Core_BAO_CustomField::getKeyID($fieldName);
  }

  /**
   * Get the metadata for the available fields.
   *
   * @param string|null $field
   * @return array|null
   */
  protected function getFieldMetadata(?string $field = NULL): ?array {
    if (empty($this->fieldMetadata)) {
      try {
        // Tests fail without checkPermissions = FALSE
        $this->fieldMetadata = (array) civicrm_api4($this->getApiEntityName(), 'getfields', ['checkPermissions' => FALSE], 'name');
      }
      catch (API_Exception $e) {
        $this->fieldMetadata = [];
      }
    }
    if ($field) {
      return $this->fieldMetadata[$field] ?? NULL;
    }
    else {
      return $this->fieldMetadata;
    }
  }

  /**
   * Get pseudoTokens - it tokens that reflect the name or label of a pseudoconstant.
   *
   * @internal - this function is a bridge for legacy CRM_Utils_Token callers. It should be removed.
   * @deprecated
   * @return array
   */
  public function getPseudoTokens(): array {
    // Simpler, but doesn't currently pass: $labels = $this->tokenNames;
    // FIXME: change getApiTokens to $this->tokenNames
    $r = [];
    foreach ($this->getApiTokens() as $key => $label) {
      if (strpos($key, ':') !== FALSE || strpos($key, '.') !== FALSE) {
        $r[$key] = $label;
      }
    }
    return $r;
  }

  /**
   * Get the values for all exported pseudo-fields.
   *
   * @param int $id
   * @return array
   * @throws \API_Exception
   * @internal - this function is a bridge for legacy CRM_Utils_Token callers. It should be removed.
   * @deprecated
   */
  public function getPseudoValues(int $id): array {
    $pseudoFields = array_keys($this->getPseudoTokens());
    $api4 = civicrm_api4('Contribution', 'get', [
      'checkPermissions' => FALSE,
      'select' => $pseudoFields,
      'where' => [['id', '=', $id]],
    ]);
    $result = CRM_Utils_Array::subset($api4->single(), $pseudoFields);
    foreach ($this->getAliasTokens() as $aliasToken => $aliasTarget) {
      if (isset($result[$aliasTarget])) {
        $result[$aliasToken] = $result[$aliasTarget];
      }
    }
    return $result;
  }

  /**
   * Class constructor.
   */
  public function __construct() {
    $tokens = $this->getAllTokens();
    parent::__construct($this->getEntityName(), $tokens);
  }

  public function getEntityIDField() {
    return $this->getEntityName() . 'Id';
  }

  /**
   * Check if the token processor is active.
   *
   * @param \Civi\Token\TokenProcessor $processor
   *
   * @return bool
   */
  public function checkActive(TokenProcessor $processor) {
    return in_array($this->getEntityIDField(), $processor->context['schema']);
  }

  /**
   * Alter action schedule query.
   *
   * If there is an action-schedule that deals with our entity, then make sure the
   * entity ID is passed through in `$tokenRow->context['myEntityid']`.
   *
   * @param \Civi\ActionSchedule\Event\MailingQueryEvent $e
   */
  public function alterActionScheduleQuery(MailingQueryEvent $e): void {
    if ($e->mapping->getEntity() === CRM_Core_DAO_AllCoreTables::getTableForEntityName($this->getApiEntityName())) {
      $e->query->select('e.id' . ' AS tokenContext_' . $this->getEntityIDField());
    }
  }

}
