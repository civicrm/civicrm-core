<?php

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
   * @inheritDoc
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    return in_array($this->getEntityContextSchema(), $processor->context['schema']) ||
      (!empty($processor->context['actionMapping'])
        && $processor->context['actionMapping']->getEntity() === $this->getEntityTableName());
  }

  /**
   * @inheritDoc
   */
  public function getActiveTokens(\Civi\Token\Event\TokenValueEvent $e) {
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
      else {
        $altToken = preg_replace('/_\d+_/', '_N_', $msgToken);
        if (array_key_exists($altToken, $this->tokenNames)) {
          $activeTokens[] = $msgToken;
        }
      }
    }
    return array_unique($activeTokens);
  }

  /**
   * Find the fields that we need to get to construct the tokens requested.
   * @param  array $tokens list of tokens
   * @return array         list of fields needed to generate those tokens
   */
  public function getReturnFields($tokens) {
    // Make sure we always return something
    $fields = ['id'];

    foreach (array_intersect($tokens,
      array_merge(array_keys(self::getBasicTokens()), array_keys(self::getCustomFieldTokens()))
             ) as $token) {
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
  protected function getCustomFieldTokens() {
    if (!isset($this->customFieldTokens)) {
      $this->customFieldTokens = \CRM_Utils_Token::getCustomFieldTokens(ucfirst($this->getEntityName()));
    }
    return $this->customFieldTokens;
  }

}
