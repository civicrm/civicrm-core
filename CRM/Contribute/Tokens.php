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
   * Get a list of tokens whose name and title match the DB fields.
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
      'status' => 'contribution_status_id',
      'type' => 'financial_type_id',
      'cancel_date' => 'contribution_cancel_date',
    ];
  }

  /**
   * Class constructor.
   */
  public function __construct() {
    $tokens = CRM_Utils_Array::subset(
      CRM_Utils_Array::collect('title', CRM_Contribute_DAO_Contribution::fields()),
      $this->getPassthruTokens()
    );
    $tokens['id'] = ts('Contribution ID');
    $tokens['payment_instrument'] = ts('Payment Instrument');
    $tokens['source'] = ts('Contribution Source');
    $tokens['status'] = ts('Contribution Status');
    $tokens['type'] = ts('Financial Type');
    $tokens = array_merge($tokens, CRM_Utils_Token::getCustomFieldTokens('Contribution'));
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
    foreach ($this->getAliasTokens() as $alias => $orig) {
      $e->query->select("e." . $fields[$orig]['name'] . " AS contrib_{$alias}");
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
    elseif (isset($aliasTokens[$field])) {
      $row->dbToken($entity, $field, 'CRM_Contribute_BAO_Contribution', $aliasTokens[$field], $fieldValue);
    }
    elseif ($cfID = \CRM_Core_BAO_CustomField::getKeyID($field)) {
      $row->customToken($entity, $cfID, $actionSearchResult->entity_id);
    }
    else {
      $row->dbToken($entity, $field, 'CRM_Contribute_BAO_Contribution', $field, $fieldValue);
    }
  }

}
