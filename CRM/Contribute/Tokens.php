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
class CRM_Contribute_Tokens extends CRM_Core_EntityTokens {

  /**
   * @return string
   */
  protected function getEntityName(): string {
    return 'contribution';
  }

  /**
   * @return string
   */
  protected function getEntityAlias(): string {
    return 'contrib_';
  }

  /**
   * Get the entity name for api v4 calls.
   *
   * In practice this IS just ucfirst($this->GetEntityName)
   * but declaring it seems more legible.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Contribution';
  }

  /**
   * Metadata about the entity fields.
   *
   * @var array
   */
  protected $fieldMetadata = [];

  /**
   * Get a list of tokens for the entity for which access is permitted to.
   *
   * This list is historical and we need to question whether we
   * should filter out any fields (other than those fields, like api_key
   * on the contact entity) with permissions defined.
   *
   * @return array
   */
  protected function getExposedFields(): array {
    return [
      'contribution_page_id',
      'source',
      'id',
      'receive_date',
      'total_amount',
      'fee_amount',
      'net_amount',
      'non_deductible_amount',
      'trxn_id',
      'invoice_id',
      'currency',
      'cancel_date',
      'receipt_date',
      'thankyou_date',
      'tax_amount',
      'contribution_status_id',
      'financial_type_id',
      'payment_instrument_id',
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
  public function getBasicTokens(): array {
    $return = [];
    foreach ($this->getExposedFields() as $fieldName) {
      $return[$fieldName] = $this->getFieldMetadata()[$fieldName]['title'];
    }
    return $return;
  }

  /**
   * Class constructor.
   */
  public function __construct() {
    $tokens = $this->getAllTokens();
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

    $fields = $this->getFieldMetadata();
    foreach (array_keys($this->getBasicTokens()) as $token) {
      $e->query->select('e.' . $fields[$token]['name'] . ' AS ' . $this->getEntityAlias() . $token);
    }
    foreach (array_keys($this->getPseudoTokens()) as $token) {
      $split = explode(':', $token);
      $e->query->select('e.' . $fields[$split[0]]['name'] . ' AS ' . $this->getEntityAlias() . $split[0]);
    }
  }

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
    $actionSearchResult = $row->context['actionSearchResult'];
    $aliasedField = $this->getEntityAlias() . $field;
    $fieldValue = $actionSearchResult->{$aliasedField} ?? NULL;

    if ($this->isPseudoField($field)) {
      $split = explode(':', $field);
      return $row->tokens($entity, $field, $this->getPseudoValue($split[0], $split[1], $actionSearchResult->{"contrib_$split[0]"} ?? NULL));
    }
    if ($this->isMoneyField($field)) {
      return $row->format('text/plain')->tokens($entity, $field,
        \CRM_Utils_Money::format($fieldValue, $actionSearchResult->contrib_currency));
    }
    if ($this->isDateField($field)) {
      return $row->format('text/plain')->tokens($entity, $field, \CRM_Utils_Date::customFormat($fieldValue));
    }
    if ($this->isCustomField($field)) {
      $row->customToken($entity, \CRM_Core_BAO_CustomField::getKeyID($field), $actionSearchResult->entity_id);
    }
    else {
      $row->format('text/plain')->tokens($entity, $field, (string) $fieldValue);
    }
  }

}
