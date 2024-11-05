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

namespace Civi\AfformLoginToken;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use CRM_Afform_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Every afform with the property `is_token=true` should have a corresponding
 * set of tokens, `{afform.myFormLoginUrl}` and `{afform.myFormLoginLink}`.
 *
 * @service civi.afformLoginTokens.tokens
 */
class Tokens extends AutoService implements EventSubscriberInterface {

  private static $placement = 'msg_token';

  /**
   * Token-picker recommends the notation `{login.myFormUrl}`
   * @var string
   */
  private static $prefix = 'login';

  /**
   * For compatibility with message-templates generated before 5.79, we
   * also support notation `{afform.myFormUrl}`.
   *
   * @var string
   */
  private static $oldPrefix = 'afform';

  public static function getSubscribedEvents(): array {
    if (!\CRM_Extension_System::singleton()->getMapper()->isActiveModule('authx')) {
      return [];
    }

    return [
      'hook_civicrm_alterMailContent' => 'applyCkeditorWorkaround',
      'civi.token.list' => 'listTokens',
      'civi.token.eval' => 'evaluateTokens',
    ];
  }

  /**
   * CKEditor makes it hard to set an `href` to a token, so we often get
   * this munged `'http://{token}` data.
   *
   * @see CRM_Utils_Hook::alterMailContent
   */
  public static function applyCkeditorWorkaround(GenericHookEvent $e) {
    $prefixes = '(' . preg_quote(static::$prefix, ';') . '|' . preg_quote(static::$oldPrefix, ';') . ')';
    $pat = ';https?://(\{' . $prefixes . '\..*Url\});';
    foreach (array_keys($e->content) as $field) {
      if (is_string($e->content[$field])) {
        $e->content[$field] = preg_replace($pat, '$1', $e->content[$field]);
      }
    }
  }

  public function listTokens(\Civi\Token\Event\TokenRegisterEvent $e): void {
    if (in_array('contactId', $e->getTokenProcessor()->getContextValues('schema')[0])) {
      $tokenForms = static::getTokenForms();
      foreach ($tokenForms as $formName => $afform) {
        $e->entity(static::$prefix)
          ->register("{$formName}Url", E::ts('%1 (URL, Login)', [1 => $afform['title'] ?? $afform['name']]))
          ->register("{$formName}Link", E::ts('%1 (Hyperlink, Login)', [1 => $afform['title'] ?? $afform['name']]));
      }
    }
  }

  /**
   * Substitute any tokens of the form `{login.myFormUrl}` or `{login.myFormLink}` with actual values.
   */
  public function evaluateTokens(\Civi\Token\Event\TokenValueEvent $e): void {
    $activeTokens = $e->getTokenProcessor()->getMessageTokens();
    if (empty($activeTokens[static::$prefix]) && empty($activeTokens[static::$oldPrefix])) {
      return;
    }

    $tokenForms = static::getTokenForms();
    foreach ([static::$prefix, static::$oldPrefix] as $prefix) {
      foreach ($tokenForms as $formName => $afform) {
        foreach ($e->getRows() as $row) {
          $url = self::createUrl($afform, $row->context['contactId']);
          $row->format('text/plain')->tokens($prefix, "{$formName}Url", $url);
          $row->format('text/html')->tokens($prefix, "{$formName}Link", sprintf('<a href="%s">%s</a>', htmlentities($url), htmlentities($afform['title'] ?? $afform['name'])));
        }
      }
    }
  }

  /**
   * Get a list of forms that have token support enabled.
   *
   * @return array
   *   $result[$formName] = ['name' => $formName, 'title' => $formTitle, 'server_route' => $route];
   */
  public static function getTokenForms() {
    $cache = &\Civi::$statics[__CLASS__]['tokenForms'];
    if (!isset($cache)) {
      $tokenForms = (array) \Civi\Api4\Afform::get(FALSE)
        ->addWhere('placement', 'CONTAINS', static::$placement)
        ->addWhere('server_route', 'IS NOT EMPTY')
        ->addSelect('name', 'title', 'server_route', 'is_public')
        ->execute()
        ->indexBy('name');
      $cache = $tokenForms;
    }
    return $cache;
  }

  /**
   * Generate an authenticated URL for viewing this form.
   *
   * @param array $afform
   * @param int $contactId
   *
   * @return string
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public static function createUrl($afform, $contactId): string {
    $expires = \CRM_Utils_Time::time() +
      (\Civi::settings()->get('checksum_timeout') * 24 * 60 * 60);

    /** @var \Civi\Crypto\CryptoJwt $jwt */
    $jwt = \Civi::service('crypto.jwt');

    $url = \Civi::url()
      ->setScheme($afform['is_public'] ? 'frontend' : 'backend')
      ->setPath($afform['server_route'])
      ->setPreferFormat('absolute');

    $bearerToken = "Bearer " . $jwt->encode([
      'exp' => $expires,
      'sub' => "cid:" . $contactId,
      'scope' => 'authx',
    ]);
    return $url->addQuery(['_authx' => $bearerToken, '_authxSes' => 1]);
  }

}
