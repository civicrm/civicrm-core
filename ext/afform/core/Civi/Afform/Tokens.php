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

namespace Civi\Afform;

use Civi\Core\Event\GenericHookEvent;
use Civi\Crypto\Exception\CryptoException;
use CRM_Afform_ExtensionUtil as E;

/**
 * Every afform with the property `is_token=true` should have a corresponding
 * set of tokens, `{afform.myFormUrl}` and `{afform.myFormLink}`.
 *
 * @see MockPublicFormTest
 * @package Civi\Afform
 */
class Tokens {

  /**
   * CKEditor makes it hard to set an `href` to a token, so we often get
   * this munged `'http://{token}` data.
   *
   * @see CRM_Utils_Hook::alterMailContent
   */
  public static function applyCkeditorWorkaround(GenericHookEvent $e) {
    foreach (array_keys($e->content) as $field) {
      $e->content[$field] = preg_replace(';https?://(\{afform.*Url\});', '$1', $e->content[$field]);
    }
  }

  /**
   * Expose tokens for use in UI.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::tokens()
   */
  public static function hook_civicrm_tokens(GenericHookEvent $e) {
    $tokenForms = static::getTokenForms();
    foreach ($tokenForms as $tokenName => $afform) {
      $e->tokens['afform']["afform.{$tokenName}Url"] = E::ts('%1 (URL)', [1 => $afform['title'] ?? $afform['name']]);
      $e->tokens['afform']["afform.{$tokenName}Link"] = E::ts('%1 (Full Hyperlink)', [1 => $afform['title'] ?? $afform['name']]);
    }
  }

  /**
   * Substitute any tokens of the form `{afform.myFormUrl}` or `{afform.myFormLink}` with actual values.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::tokenValues()
   */
  public static function hook_civicrm_tokenValues(GenericHookEvent $e) {
    try {
      // Depending on the caller, $tokens['afform'] might be ['fooUrl'] or ['fooUrl'=>1]. Because... why not!
      $activeAfformTokens = array_merge(array_keys($e->tokens['afform'] ?? []), array_values($e->tokens['afform'] ?? []));

      $tokenForms = static::getTokenForms();
      foreach ($tokenForms as $formName => $afform) {
        if (!array_intersect($activeAfformTokens, ["{$formName}Url", "{$formName}Link"])) {
          continue;
        }

        if (empty($afform['server_route'])) {
          continue;
        }

        if (!is_array($e->contactIDs)) {
          $url = self::createUrl($afform, $e->contactIDs);
          $e->details["afform.{$formName}Url"] = $url;
          $e->details["afform.{$formName}Link"] = sprintf('<a href="%s">%s</a>', htmlentities($url), htmlentities($afform['title'] ?? $afform['name']));
        }
        else {
          foreach ($e->contactIDs as $cid) {
            $url = self::createUrl($afform, $cid);
            $e->details[$cid]["afform.{$formName}Url"] = $url;
            $e->details[$cid]["afform.{$formName}Link"] = sprintf('<a href="%s">%s</a>', htmlentities($url), htmlentities($afform['title'] ?? $afform['name']));
          }
        }
      }
    }
    catch (CryptoException $ex) {
      \Civi::log()->warning('Civi\Afform\LegacyTokens cannot generate tokens due to crypto exception.', ['exception' => $ex]);
    }
  }

  ///**
  // * Expose tokens for use in UI.
  // *
  // * @param \Civi\Token\Event\TokenRegisterEvent $e
  // */
  //public static function onRegister(\Civi\Token\Event\TokenRegisterEvent $e) {
  //  $tokenForms = static::getTokenForms();
  //  foreach ($tokenForms as $tokenName => $afform) {
  //    $e->register([
  //      'entity' => 'afform',
  //      'field' => $tokenName . 'Url',
  //      'label' => E::ts('View Form: %1 (URL)', [1 => $afform['title'] ?? $afform['name']]),
  //    ]);
  //    $e->register([
  //      'entity' => 'afform',
  //      'field' => $tokenName . 'Link',
  //      'label' => E::ts('View Form: %1 (Full Hyperlink)', [1 => $afform['title'] ?? $afform['name']]),
  //    ]);
  //  }
  //}

  ///**
  // * Substitute any tokens of the form `{afform.myFormUrl}` or `{afform.myFormLink}` with actual values.
  // *
  // * @param \Civi\Token\Event\TokenValueEvent $e
  // */
  //public static function onEvaluate(\Civi\Token\Event\TokenValueEvent $e) {
  //  $activeTokens = $e->getTokenProcessor()->getMessageTokens();
  //  if (empty($activeTokens['afform'])) {
  //    return;
  //  }
  //
  //  $tokenForms = static::getTokenForms();
  //  foreach ($tokenForms as $formName => $afform) {
  //    if (!array_intersect($activeTokens['afform'], ["{$formName}Url", "{$formName}Link"])) {
  //      continue;
  //    }
  //
  //    if (empty($afform['server_route'])) {
  //      \Civi::log()
  //        ->warning('Civi\Afform\Tokens: Cannot generate link for {formName} -- missing server_route', [
  //          'formName' => $formName,
  //        ]);
  //      continue;
  //    }
  //
  //    foreach ($e->getRows() as $row) {
  //      /** @var \Civi\Token\TokenRow $row */
  //      try {
  //        $url = self::createUrl($afform, $row->context['contactId']);
  //        $row->format('text/plain')->tokens('afform', "{$formName}Url", $url);
  //        $row->format('text/html')->tokens('afform', "{$formName}Link",
  //          sprintf('<a href="%s">%s</a>', htmlentities($url), htmlentities($afform['title'] ?? $afform['name'])));
  //      }
  //      catch (CryptoException $e) {
  //        \Civi::log()->warning('Civi\Afform\Tokens cannot generate tokens due to crypto exception.', ['exception' => $e]);
  //      }
  //    }
  //  }
  //}

  /**
   * Get a list of forms that have token support enabled.
   *
   * @return array
   *   $result[$formName] = ['name' => $formName, 'title' => $formTitle, 'server_route' => $route];
   */
  public static function getTokenForms() {
    if (!isset(\Civi::$statics[__CLASS__]['tokenForms'])) {
      $tokenForms = (array) \Civi\Api4\Afform::get(0)
        ->addWhere('is_token', '=', TRUE)
        ->addSelect('name', 'title', 'server_route', 'is_public')
        ->execute()
        ->indexBy('name');
      \Civi::$statics[__CLASS__]['tokenForms'] = $tokenForms;
    }
    return \Civi::$statics[__CLASS__]['tokenForms'];
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

    $bearerToken = "Bearer " . $jwt->encode([
      'exp' => $expires,
      'sub' => "cid:" . $contactId,
      'scope' => 'authx',
    ]);

    $url = \CRM_Utils_System::url($afform['server_route'],
      ['_authx' => $bearerToken, '_authxSes' => 1],
      TRUE,
      NULL,
      FALSE,
      $afform['is_public'] ?? TRUE
    );
    return $url;
  }

}
