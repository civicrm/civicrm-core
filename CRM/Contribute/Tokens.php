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
 * Class CRM_Contribute_Tokens
 *
 * Generate "contribution.*" tokens.
 *
 * At time of writing, we don't have any particularly special tokens -- we just
 * do some basic formatting based on the corresponding DB field.
 */
class CRM_Contribute_Tokens extends \Civi\Token\AbstractTokenSubscriber {

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
   * Mapping from tokenName to api return field
   * Use lists since we might need multiple fields
   *
   * @var array
   */
  private static $fieldMapping = [
    'contribution_id' => ['id'],
    'contribution_cancel_date' => ['cancel_date'],
  ];

  /**
   * Get a list of tokens whose name and title match the DB fields.
   * @return array
   */
  protected function getPassthruTokens() {
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
  protected function getAliasTokens() {
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
   * Alter action schedule query.
   *
   * @param \Civi\ActionSchedule\Event\MailingQueryEvent $e
   */
  public function alterActionScheduleQuery(\Civi\ActionSchedule\Event\MailingQueryEvent $e) {
    if ($e->mapping->getEntity() !== $this->getEntityTableName()) {
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
  public function prefetch(\Civi\Token\Event\TokenValueEvent $e) {
    // Find all the entity IDs
    $entityIds
      = $e->getTokenProcessor()->getContextValues('actionSearchResult', 'entityID')
      + $e->getTokenProcessor()->getContextValues($this->getEntityContextSchema());

    if (!$entityIds) {
      return NULL;
    }

    // Get data on all activities for basic and customfield tokens
    $entities = civicrm_api3('Contribution', 'get', [
      'id' => ['IN' => $entityIds],
      'options' => ['limit' => 0],
      'return' => self::getReturnFields($this->activeTokens),
    ]);
    $prefetch['contribution'] = $entities['values'];

    return $prefetch;
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    // maps token name to api field
    $mapping = [
      'contribution_id' => 'id',
    ];

    // Get EntityID either from actionSearchResult (for scheduled reminders) if exists
    $entityId = $row->context['actionSearchResult']->entityID ?? $row->context[$this->getEntityContextSchema()];

    $entityObject = (object) $prefetch['contribution'][$entityId];
    $fieldValue = $entityObject->$field;

    $aliasTokens = $this->getAliasTokens();
    if (in_array($field, ['total_amount', 'fee_amount', 'net_amount'])) {
      return $row->format('text/plain')->tokens($entity, $field,
        \CRM_Utils_Money::format($fieldValue, $entityObject->currency));
    }
    elseif (isset($mapping[$field]) && (isset($entityObject->{$mapping[$field]}))) {
      $row->tokens($entity, $field, $entityObject->{$mapping[$field]});
    }
    elseif ($field === 'contribution_status_id') {
      $row->tokens($entity, $field, $entityObject->contribution_status);
    }
    elseif (isset($aliasTokens[$field])) {
      $row->dbToken($entity, $field, 'CRM_Contribute_BAO_Contribution', $aliasTokens[$field], $fieldValue);
    }
    elseif ($cfID = \CRM_Core_BAO_CustomField::getKeyID($field)) {
      $row->customToken($entity, $cfID, $entityId);
    }
    else {
      $row->dbToken($entity, $field, 'CRM_Contribute_BAO_Contribution', $field, $fieldValue);
    }
  }

  /**
   * Get the basic tokens provided.
   *
   * @return array token name => token label
   */
  protected function getBasicTokens() {
    if (!isset($this->basicTokens)) {
      $tokens = CRM_Utils_Array::subset(
        CRM_Utils_Array::collect('title', CRM_Contribute_DAO_Contribution::fields()),
        $this->getPassthruTokens()
      );
      $tokens['contribution_id'] = ts('Contribution ID');
      $tokens['payment_instrument_id'] = ts('Payment Instrument');
      $tokens['contribution_source'] = ts('Contribution Source');
      $tokens['contribution_status_id'] = ts('Contribution Status');
      $tokens['financial_type_id'] = ts('Financial Type');
      $this->basicTokens = $tokens;
    }

    return $this->basicTokens;
  }

}
