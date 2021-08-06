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
   * This is required for the parent - it will be filled out.
   *
   * @inheritDoc
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
  }

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return '';
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
   * Get the relevant bao name.
   */
  public function getBAOName(): string {
    return CRM_Core_DAO_AllCoreTables::getFullName($this->getApiEntityName());
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

}
