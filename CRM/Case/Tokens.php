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

/**
 * Class CRM_Case_Tokens
 *
 * Generate "case.*" tokens.
 *
 */
class CRM_Case_Tokens extends \Civi\Token\AbstractTokenSubscriber {

  use CRM_Core_TokenTrait;

  /**
   * @return string
   */
  private function getEntityName(): string {
    return 'case';
  }

  /**
   * @return string
   */
  private function getEntityTableName(): string {
    return 'civicrm_case';
  }

  /**
   * @return string
   */
  private function getEntityContextSchema(): string {
    return 'caseId';
  }

  /**
   * Mapping from tokenName to api return field
   * Use lists since we might need multiple fields
   *
   * @var array
   */
  private static $fieldMapping = [
    'type' => ['case_type_id'],
    'status' => ['status_id'],
  ];

  /**
   * @inheritDoc
   */
  public function prefetch(\Civi\Token\Event\TokenValueEvent $e) {
    // Find all the entity IDs
    $entityIds
      = $e->getTokenProcessor()->getContextValues('actionSearchResult', 'entityID')
      + $e->getTokenProcessor()->getContextValues($this->getEntityContextSchema());

    if (!$entityIds) {
      return NULL;
    }

    // Get data on all activities for basic and customfield tokens
    $prefetch['case'] = civicrm_api3('Case', 'get', [
      'id' => ['IN' => $entityIds],
      'options' => ['limit' => 0],
      'return' => self::getReturnFields($this->activeTokens),
    ])['values'];

    // Store the case types if needed
    if (in_array('type', $this->activeTokens)) {
      $this->caseTypes = CRM_Case_BAO_Case::buildOptions('case_type_id');
    }

    // Store the case statuses if needed
    if (in_array('status', $this->activeTokens)) {
      $this->caseStatuses = CRM_Case_BAO_Case::buildOptions('case_status_id');
    }

    return $prefetch;
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    // Get entityID either from actionSearchResult (for scheduled reminders) if exists
    $entityID = $row->context['actionSearchResult']->entityID ?? $row->context[$this->getEntityContextSchema()];

    $case = (object) $prefetch['case'][$entityID];

    if (substr($field, -5) === '_date') {
      $row->tokens($entity, $field, \CRM_Utils_Date::customFormat($case->$field));
    }
    elseif (isset(self::$fieldMapping[$field]) and (isset($case->{self::$fieldMapping[$field]}))) {
      $row->tokens($entity, $field, $case->{self::$fieldMapping[$field]});
    }
    elseif (in_array($field, ['type'])) {
      $row->tokens($entity, $field, $this->caseTypes[$case->case_type_id]);
    }
    elseif (in_array($field, ['status'])) {
      $row->tokens($entity, $field, $this->caseStatuses[$case->status_id]);
    }
    elseif (array_key_exists($field, $this->customFieldTokens)) {
      $row->tokens($entity, $field,
        isset($case->$field)
          ? \CRM_Core_BAO_CustomField::displayValue($case->$field, $field)
          : ''
      );
    }
    elseif (isset($case->$field)) {
      $row->tokens($entity, $field, $case->$field);
    }
  }

  /**
   * Get the basic tokens provided.
   *
   * @return array token name => token label
   * @throws \CiviCRM_API3_Exception
   */
  protected function getBasicTokens() {
    if (!isset($this->basicTokens)) {
      $caseFields = civicrm_api3('Case', 'getfields')['values'];
      foreach ($caseFields as $name => $detail) {
        $this->basicTokens[$detail['name']] = $detail['title'];
      }
      $this->basicTokens['status'] = $this->basicTokens['status_id'];
      $this->basicTokens['status_id'] = ts('Case Status ID');
      $this->basicTokens['type'] = $this->basicTokens['case_type_id'];
      $this->basicTokens['case_type_id'] = ts('Case Type ID');
    }
    return $this->basicTokens;
  }

}
