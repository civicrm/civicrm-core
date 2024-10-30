<?php

use Civi\Token\Event\TokenValueEvent;
use Civi\Token\TokenProcessor;

trait CRM_Core_TokenTrait {

  private $basicTokens;
  private $customFieldTokens;

  /**
   * CRM_Entity_Tokens constructor.
   */
  public function __construct() {
    parent::__construct($this->getEntityName(), array_merge(
      $this->getBasicTokens(),
      $this->getCustomFieldTokens()
    ));
  }

  /**
   * Check if the token processor is active.
   *
   * @param \Civi\Token\TokenProcessor $processor
   *
   * @return bool
   */
  public function checkActive(TokenProcessor $processor) {
    return in_array($this->getEntityContextSchema(), $processor->context['schema']) ||
      (!empty($processor->context['actionMapping'])
        && $processor->context['actionMapping']->getEntityTable() === $this->getEntityTableName());
  }

  /**
   * @inheritDoc
   */
  public function getActiveTokens(TokenValueEvent $e) {
    $messageTokens = $e->getTokenProcessor()->getMessageTokens();
    if (!isset($messageTokens[$this->entity])) {
      return NULL;
    }

    $activeTokens = [];
    // if message token contains '_\d+_', then treat as '_N_'
    foreach ($messageTokens[$this->entity] as $msgToken) {
      if (array_key_exists($msgToken, $this->tokenNames)) {
        $activeTokens[] = $msgToken;
      }
    }
    return array_unique($activeTokens);
  }

  /**
   * Find the fields that we need to get to construct the tokens requested.
   *
   * @return array         list of fields needed to generate those tokens
   */
  public function getReturnFields(): array {
    // Make sure we always return something
    $fields = ['id'];

    $tokensInUse =
      array_merge(array_keys(self::getBasicTokens()), array_keys(self::getCustomFieldTokens()));
    foreach ($tokensInUse as $token) {
      if (isset(self::$fieldMapping[$token])) {
        $fields = array_merge($fields, self::$fieldMapping[$token]);
      }
      else {
        $fields[] = $token;
      }
    }
    return array_unique($fields);
  }

  /**
   * Get the tokens for custom fields
   * @return array token name => token label
   */
  protected function getCustomFieldTokens(): array {
    if (!isset($this->customFieldTokens)) {
      $this->customFieldTokens = [];
      foreach (CRM_Core_BAO_CustomField::getFields(ucfirst($this->getEntityName())) as $id => $info) {
        $this->customFieldTokens['custom_' . $id] = $info['label'] . ' :: ' . $info['groupTitle'];
      }
    }
    return $this->customFieldTokens;
  }

}
