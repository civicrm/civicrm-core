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

use Civi\Api4\SiteToken;
use Civi\Token\AbstractTokenSubscriber;
use Civi\Token\TokenRow;

/**
 * Class CRM_Site_Tokens
 *
 * Generate "site.*" tokens.
 */
class CRM_Core_SiteTokens extends AbstractTokenSubscriber {

  /**
   * Should permissions be checked when loading tokens.
   *
   * @var bool
   */
  protected $checkPermissions = FALSE;

  /**
   * @var string
   *   Token prefix
   */
  public $entity = 'site';

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
    parent::__construct($this->entity, $this->getSiteTokens());
  }

  public function getSiteTokens(): array {
    $ret = [];
    try {
      $siteTokens = SiteToken::get($this->checkPermissions)
        ->addSelect('name', 'label')
        ->addWhere('domain_id', '=', 'current_domain')
        ->addWhere('is_active', '=', TRUE)
        ->execute();
      foreach ($siteTokens as $siteToken) {
        $ret[$siteToken['name']] = $siteToken['label'];
      }
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      return [];
    }
    return $ret;
  }

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL): void {
    $row->format('text/html')->tokens($entity, $field, self::getSiteTokenValues()[$field]);
    $row->format('text/plain')->tokens($entity, $field, self::getSiteTokenValues(NULL, FALSE)[$field]);
  }

  /**
   * Get the site tokens available for the domain.
   *
   *
   * @param int|null $domainID
   * @param bool $html
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @internal
   *
   * @todo - make this non-static & protected. Remove last deprecated fn that calls it.
   */
  private function getSiteTokenValues(?int $domainID = NULL, bool $html = TRUE): array {
    $cacheKey = __CLASS__ . 'event_tokens' . $domainID . '_' . CRM_Core_I18n::getLocale() . ($this->checkPermissions ? CRM_Core_Session::getLoggedInContactID() : '');
    if (!isset(\Civi::$statics[$cacheKey])) {
      \Civi::$statics[$cacheKey] = [];
      $siteTokens = SiteToken::get($this->checkPermissions)
        ->addSelect('name', 'body_html', 'body_text')
        ->addWhere('domain_id', '=', ($domainID ?? 'current_domain'))
        ->execute();
      foreach ($siteTokens as $siteToken) {
        $value = '';
        if ($html) {
          $value = $siteToken['body_html'];
        }
        else {
          // For text value, use body_text if we can, otherwise fall back to a
          // sanitized version of body_html.
          if (!empty($siteToken['body_text'])) {
            $value = $siteToken['body_text'];
          }
          else {
            $value = CRM_Utils_String::htmlToText($siteToken['body_text']);
          }
        }
        \Civi::$statics[$cacheKey][$siteToken['name']] = $value;
      }
    }
    return \Civi::$statics[$cacheKey];
  }

}
