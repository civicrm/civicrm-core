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
use Civi\Token\Event\TokenValueEvent;
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

  use CRM_Core_TokenTrait;

  /**
   * @return string
   */
  private function getEntityName(): string {
    return 'contribution';
  }

  /**
   * @return string
   */
  private function getEntityTableName(): string {
    return 'civicrm_contribution';
  }

  /**
   * @return string
   */
  private function getEntityContextSchema(): string {
    return 'contributionId';
  }

  /**
   * @var array
   */
  protected $paymentInstruments = [];

  /**
   * @var array
   */
  protected $financialTypes = [];

  /**
   * @var array
   */
  protected $contributionStatuses = [];

  /**
   * Get the basic tokens provided.
   *
   * @return array token name => token label
   */
  protected function getBasicTokens() {
    if (!isset($this->basicTokens)) {
      $this->basicTokens = CRM_Utils_Array::collect('title', CRM_Contribute_DAO_Contribution::fields());
      $this->basicTokens['payment_instrument'] = ts('Payment Instrument');
      $this->basicTokens['status'] = ts('Contribution Status');
      $this->basicTokens['financial_type'] = ts('Financial Type');
    }
    return $this->basicTokens;
  }

  /**
   * Returns a mapping of alias tokens to actual token.
   * For example to support {activity.activity_id} in addition to {activity.id} we return ['activity_id => 'id]
   *
   * @return array
   */
  protected function getAliasTokens(): array {
    return [
      'id' => 'contribution_id',
      'source' => 'contribution_source',
      'type' => 'financial_type',
      'cancel_date' => 'contribution_cancel_date',
    ];
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

    $passThroughTokens = [
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
    ];
    $fields = CRM_Contribute_DAO_Contribution::fields();
    foreach ($passThroughTokens as $token) {
      $e->query->select("e." . $fields[$token]['name'] . " AS contrib_{$token}");
    }
    $aliasTokens = [
      'id' => 'contribution_id',
      'payment_instrument' => 'payment_instrument_id',
      'source' => 'contribution_source',
      'status' => 'contribution_status_id',
      'type' => 'financial_type_id',
      'cancel_date' => 'contribution_cancel_date',
    ];
    foreach ($aliasTokens as $alias => $orig) {
      $e->query->select("e." . $fields[$orig]['name'] . " AS contrib_{$alias}");
    }
  }

  /**
   * @inheritDoc
   */
  public function prefetch(TokenValueEvent $e) {
    // Find all the entity IDs
    $entityIds
      = $e->getTokenProcessor()->getContextValues('actionSearchResult', 'entityID')
      + $e->getTokenProcessor()->getContextValues($this->getEntityContextSchema());

    if (!$entityIds) {
      return NULL;
    }

    // Get data on all entities for basic and customfield tokens
    $prefetch['contribution'] = \Civi\Api4\Contribution::get(FALSE)
      ->addSelect('*', 'custom.*')
      ->addWhere('id', 'IN', $entityIds)
      ->execute()
      ->indexBy('id');

    // Store the activity types if needed
    if (in_array('payment_instrument', $this->activeTokens)) {
      $this->paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    }

    // Store the activity statuses if needed
    if (in_array('status', $this->activeTokens)) {
      $this->contributionStatuses = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'get', []);
    }

    // Store the financial_types if needed
    if (in_array('financial_type', $this->activeTokens)) {
      $this->financialTypes = CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'get', []);;
    }

    return $prefetch;
  }

  /**
   * Evaluate the content of a single token.
   *
   * @param \Civi\Token\TokenRow $row
   *   The record for which we want token values.
   * @param string $entity
   *   The name of the token entity.
   * @param string $field
   *   The name of the token field.
   * @param mixed $prefetch
   *   Any data that was returned by the prefetch().
   *
   * @throws \CRM_Core_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
    // Get EntityID either from actionSearchResult (for scheduled reminders) if exists
    $entityId = $row->context[$this->getEntityContextSchema()];

    $contribution = $prefetch['contribution'][$entityId];

    $aliasTokens = $this->getAliasTokens();
    if (in_array($field, ['total_amount', 'fee_amount', 'net_amount'])) {
      $row->tokens($entity, $field, \CRM_Utils_Money::format($contribution[$field], $contribution['currency']));
    }
    elseif (in_array($field, ['financial_type', 'type'])) {
      $row->tokens($entity, $field, $this->financialTypes[$contribution['financial_type_id']]);
    }
    elseif (in_array($field, ['payment_instrument'])) {
      $row->tokens($entity, $field, $this->paymentInstruments[$contribution['payment_instrument_id']]);
    }
    elseif (in_array($field, ['status'])) {
      $row->tokens($entity, $field, $this->contributionStatuses[$contribution['contribution_status_id']]);
    }
    elseif (isset($aliasTokens[$field])) {
      $row->dbToken($entity, $field, 'CRM_Contribute_BAO_Contribution', $aliasTokens[$field], $contribution[$field]);
    }
    elseif ($cfID = \CRM_Core_BAO_CustomField::getKeyID($field)) {
      $row->customToken($entity, $cfID, $entityId);
    }
    else {
      $row->dbToken($entity, $field, 'CRM_Contribute_BAO_Contribution', $field, $contribution[$field]);
    }
  }

}
