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
 * Class CRM_Case_Tokens
 *
 * Generate "case.*" tokens.
 */
class CRM_Core_DomainTokens extends AbstractTokenSubscriber {
  /**
   * @var string
   *   Token prefix
   */
  public $entity = 'domain';

  /**
   * @var array
   *   List of tokens provided by this class
   *   Array(string $fieldName => string $label).
   */
  public $tokenNames;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct($this->entity, $this->getDomainTokens());
  }

  public function getDomainTokens(): array {
    return [
      'name' => ts('Domain name'),
      'address' => ts('Domain (organization) address'),
      'phone' => ts('Domain (organization) phone'),
      'email' => ts('Domain (organization) email'),
      'id' => ts('Domain ID'),
      'description' => ts('Domain Description'),
      'now' => ts('Current time/date'),
    ];
  }

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL): void {
    if ($field === 'now') {
      $nowObj = (new \DateTime())->setTimestamp(\CRM_Utils_Time::time());
      $row->format('text/html')->tokens($entity, $field, $nowObj);
      return;
    }
    $row->format('text/html')->tokens($entity, $field, self::getDomainTokenValues()[$field]);
    $row->format('text/plain')->tokens($entity, $field, self::getDomainTokenValues(NULL, FALSE)[$field]);
  }

  /**
   * Get the tokens available for the domain.
   *
   * This function will be made protected soon...
   *
   * @param int|null $domainID
   * @param bool $html
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @internal
   *
   */
  public static function getDomainTokenValues(?int $domainID = NULL, bool $html = TRUE): array {
    if (!$domainID) {
      $domainID = CRM_Core_Config::domainID();
    }
    $cacheKey = __CLASS__ . 'domain_tokens' . $html . '_' . $domainID . '_' . CRM_Core_I18n::getLocale();
    if (!Civi::cache('metadata')->has($cacheKey)) {
      if (CRM_Core_Config::domainID() === $domainID) {
        $domain = CRM_Core_BAO_Domain::getDomain();
      }
      else {
        $domain = new CRM_Core_BAO_Domain();
        $domain->find(TRUE);
      }
      $tokens = [
        'name' => $domain->name,
        'id' => $domain->id,
        'description' => $domain->description,
      ];
      $loc = $domain->getLocationValues();
      if ($html) {
        $tokens['address'] = str_replace("\n", '<br />', ($loc['address'][1]['display'] ?? ''));
      }
      else {
        $tokens['address'] = $loc['address'][1]['display_text'] ?? '';
      }
      $phone = reset($loc['phone']);
      $email = reset($loc['email']);
      $tokens['phone'] = $phone['phone'] ?? '';
      $tokens['email'] = $email['email'] ?? '';
      Civi::cache('metadata')->set($cacheKey, $tokens);
    }
    return Civi::cache('metadata')->get($cacheKey);
  }

}
