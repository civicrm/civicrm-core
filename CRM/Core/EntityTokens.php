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
   * Is the given field a date field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isDateField(string $fieldName): bool {
    return $this->getFieldMetadata()[$fieldName]['type'] === (\CRM_Utils_Type::T_DATE + \CRM_Utils_Type::T_TIME);
  }

  /**
   * Is the given field a date field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  public function isMoneyField(string $fieldName): bool {
    return $this->getFieldMetadata()[$fieldName]['type'] === (\CRM_Utils_Type::T_MONEY);
  }

  /**
   * Get the metadata for the available fields.
   *
   * @return array
   */
  protected function getFieldMetadata(): array {
    if (empty($this->fieldMetadata)) {
      $baoName = $this->getBAOName();

      $fields = (array) $baoName::fields();
      // re-index by real field name. I originally wanted to use apiv4
      // getfields - but it returns different stuff for 'type' and
      // does not return 'pseudoconstant' as a key so for now...
      foreach ($fields as $details) {
        $this->fieldMetadata[$details['name']] = $details;
      }
    }
    return $this->fieldMetadata;
  }

}
