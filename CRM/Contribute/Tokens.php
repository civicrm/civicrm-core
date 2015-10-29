<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Contribute_Tokens
 *
 * Generate "contribution.*" tokens.
 *
 * At time of writing, we don't have any particularly special tokens -- we just
 * do some basic formatting based on the corresponding DB field.
 */
class CRM_Contribute_Tokens extends \Civi\Token\AbstractTokenSubscriber {

  /**
   * Get a list of tokens whose name and title match the DB fields.
   * @return array
   */
  protected function getPassthruTokens() {
    return array(
      'contribution_page_id',
      'receive_date',
      'total_amount',
      'fee_amount',
      'net_amount',
      'trxn_id',
      'invoice_id',
      'currency',
      'cancel_date',
      'receipt_date',
      'thankyou_date',
      'tax_amount',
    );
  }

  /**
   * Get alias tokens.
   *
   * @return array
   */
  protected function getAliasTokens() {
    return array(
      'id' => 'contribution_id',
      'payment_instrument' => 'payment_instrument_id',
      'source' => 'contribution_source',
      'status' => 'contribution_status_id',
      'type' => 'financial_type_id',
    );
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
    parent::__construct('contribution', $tokens);
  }

  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    return
      !empty($processor->context['actionMapping'])
      && $processor->context['actionMapping']->getEntity() === 'civicrm_contribution';
  }

  public function alterActionScheduleQuery(\Civi\ActionSchedule\Event\MailingQueryEvent $e) {
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
   * Evaluate the content of a single token.
   *
   * @param \Civi\Token\TokenRow $row
   *   The record for which we want token values.
   * @param string $entity
   * @param string $field
   *   The name of the token field.
   * @param mixed $prefetch
   *   Any data that was returned by the prefetch().
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    $actionSearchResult = $row->context['actionSearchResult'];
    $fieldValue = isset($actionSearchResult->{"contrib_$field"}) ? $actionSearchResult->{"contrib_$field"} : NULL;

    $aliasTokens = $this->getAliasTokens();
    if (in_array($field, array('total_amount', 'fee_amount', 'net_amount'))) {
      return $row->format('text/plain')->tokens($entity, $field,
        \CRM_Utils_Money::format($fieldValue, $actionSearchResult->contrib_currency));
    }
    elseif (isset($aliasTokens[$field])) {
      $row->dbToken($entity, $field, 'CRM_Contribute_BAO_Contribution', $aliasTokens[$field], $fieldValue);
    }
    else {
      $row->dbToken($entity, $field, 'CRM_Contribute_BAO_Contribution', $field, $fieldValue);
    }
  }

}
