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

use Civi\ActionSchedule\Event\MailingQueryEvent;
use Civi\Token\AbstractTokenSubscriber;
use Civi\Token\TokenProcessor;
use Civi\Token\TokenRow;

/**
 * Class CRM_Contribute_Tokens
 *
 * Generate "contribution.*" tokens.
 *
 * At time of writing, we don't have any particularly special tokens -- we just
 * do some basic formatting based on the corresponding DB field.
 */
class CRM_Contribute_Tokens extends AbstractTokenSubscriber {

  /**
   * @return string
   */
  private function getEntityName(): string {
    return 'contribution';
  }

  /**
   * Get the relevant bao name.
   */
  public function getBAOName(): string {
    return CRM_Core_DAO_AllCoreTables::getFullName(ucfirst($this->getEntityName()));
  }

  /**
   * Metadata about the entity fields.
   *
   * @var array
   */
  protected $entityFieldMetadata = [];

  /**
   * Get a list of tokens whose name and title match the DB fields.
   *
   * @return array
   */
  protected function getPassthruTokens(): array {
    return [
      'contribution_page_id',
      'receive_date',
      'total_amount',
      'fee_amount',
      'net_amount',
      'trxn_id',
      'invoice_id',
      'currency',
      'contribution_cancel_date',
      'receipt_date',
      'thankyou_date',
      'tax_amount',
      'contribution_status_id',
    ];
  }

  /**
   * Get alias tokens.
   *
   * @return array
   */
  protected function getAliasTokens(): array {
    return [
      'id' => 'contribution_id',
      'payment_instrument' => 'payment_instrument_id',
      'source' => 'contribution_source',
      'type' => 'financial_type_id',
      'cancel_date' => 'contribution_cancel_date',
    ];
  }

  /**
   * Get tokens supporting the syntax we are migrating to.
   *
   * In general these are tokens that were not previously supported
   * so we can add them in the preferred way or that we have
   * undertaken some, as yet to be written, db update.
   *
   * See https://lab.civicrm.org/dev/core/-/issues/2650
   *
   * @return string[]
   */
  protected function getBasicTokens(): array {
    return ['contribution_status_id' => ts('Contribution Status ID')];
  }

  /**
   * Get pseudoTokens - it tokens that reflect the name or label of a
   * pseudoconstant.
   *
   * @return array
   * @internal - this function will likely be made protected soon.
   *
   */
  public function getPseudoTokens(): array {
    $return = [];
    foreach (array_keys($this->getBasicTokens()) as $fieldName) {
      if (!empty($this->entityFieldMetadata[$fieldName]['pseudoconstant'])) {
        $return[$fieldName . ':label'] = $this->entityFieldMetadata[$fieldName]['html']['label'];
        $return[$fieldName . ':name'] = ts('Machine name') . ': ' . $this->entityFieldMetadata[$fieldName]['html']['label'];
      }
    }
    return $return;
  }

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->entityFieldMetadata = CRM_Contribute_DAO_Contribution::fields();
    $tokens = CRM_Utils_Array::subset(
      CRM_Utils_Array::collect('title', $this->entityFieldMetadata),
      $this->getPassthruTokens()
    );
    $tokens['id'] = ts('Contribution ID');
    $tokens['payment_instrument'] = ts('Payment Instrument');
    $tokens['source'] = ts('Contribution Source');
    $tokens['type'] = ts('Financial Type');
    $tokens = array_merge($tokens, $this->getPseudoTokens(), CRM_Utils_Token::getCustomFieldTokens('Contribution'));
    parent::__construct('contribution', $tokens);
  }

  /**
   * Check if the token processor is active.
   *
   * @param \Civi\Token\TokenProcessor $processor
   *
   * @return bool
   */
  public function checkActive(TokenProcessor $processor) {
    return !empty($processor->context['actionMapping'])
      && $processor->context['actionMapping']->getEntity() === 'civicrm_contribution';
  }

  /**
   * Alter action schedule query.
   *
   * @param \Civi\ActionSchedule\Event\MailingQueryEvent $e
   */
  public function alterActionScheduleQuery(MailingQueryEvent $e): void {
    if ($e->mapping->getEntity() !== 'civicrm_contribution') {
      return;
    }

    $fields = CRM_Contribute_DAO_Contribution::fields();
    foreach ($this->getPassthruTokens() as $token) {
      $e->query->select("e." . $fields[$token]['name'] . " AS contrib_{$token}");
    }
    foreach (array_keys($this->getPseudoTokens()) as $token) {
      $split = explode(':', $token);
      $e->query->select("e." . $fields[$split[0]]['name'] . " AS contrib_{$split[0]}");
    }
    foreach ($this->getAliasTokens() as $alias => $orig) {
      $e->query->select('e.' . $fields[$orig]['name'] . " AS contrib_{$alias}");
    }
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
    $actionSearchResult = $row->context['actionSearchResult'];
    $fieldValue = $actionSearchResult->{"contrib_$field"} ?? NULL;

    $aliasTokens = $this->getAliasTokens();
    if (in_array($field, ['total_amount', 'fee_amount', 'net_amount'])) {
      return $row->format('text/plain')->tokens($entity, $field,
        \CRM_Utils_Money::format($fieldValue, $actionSearchResult->contrib_currency));
    }
    if (isset($aliasTokens[$field])) {
      $row->dbToken($entity, $field, 'CRM_Contribute_BAO_Contribution', $aliasTokens[$field], $fieldValue);
    }
    elseif ($cfID = \CRM_Core_BAO_CustomField::getKeyID($field)) {
      $row->customToken($entity, $cfID, $actionSearchResult->entity_id);
    }
    elseif (array_key_exists($field, $this->getPseudoTokens())) {
      $split = explode(':', $field);
      $row->tokens($entity, $field, $this->getPseudoValue($split[0], $split[1], $actionSearchResult->{"contrib_$split[0]"} ?? NULL));
    }
    elseif (in_array($field, array_keys($this->getBasicTokens()))) {
      $row->tokens($entity, $field, $fieldValue);
    }
    else {
      // This section is copied & pasted & can be cleaned up later.
      // most if probably already done in this functions
      $tokenEntity = $entity;
      $tokenField = $field;
      $baoName = $this->getBAOName();
      $baoField = $field;
      if ($fieldValue === NULL || $fieldValue === '') {
        return $row->tokens($tokenEntity, $tokenField, '');
      }

      $fields = $baoName::fields();
      if (!empty($fields[$baoField]['pseudoconstant'])) {
        $options = $baoName::buildOptions($baoField, 'get');
        return $row->format('text/plain')
          ->tokens($tokenEntity, $tokenField, $options[$fieldValue]);
      }

      switch ($fields[$baoField]['type']) {
        case \CRM_Utils_Type::T_DATE + \CRM_Utils_Type::T_TIME:
          return $row->format('text/plain')
            ->tokens($tokenEntity, $tokenField, \CRM_Utils_Date::customFormat($fieldValue));

        case \CRM_Utils_Type::T_MONEY:
          // Is this something you should ever use? Seems like you need more context
          // to know which currency to use.
          return $row->format('text/plain')
            ->tokens($tokenEntity, $tokenField, \CRM_Utils_Money::format($fieldValue));

        case \CRM_Utils_Type::T_STRING:
        case \CRM_Utils_Type::T_BOOLEAN:
        case \CRM_Utils_Type::T_INT:
        case \CRM_Utils_Type::T_TEXT:
          return $row->format('text/plain')
            ->tokens($tokenEntity, $tokenField, $fieldValue);

      }
      throw new \CRM_Core_Exception("Cannot format token for field '$baoField' in '$baoName'");
    }
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
