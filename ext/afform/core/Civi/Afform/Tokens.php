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
use Civi\Core\Service\AutoService;
use Civi\Crypto\Exception\CryptoException;
use Civi\Token\TokenRow;
use CRM_Afform_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Every afform with the property `is_token=true` should have a corresponding
 * set of tokens, `{afform.myFormUrl}` and `{afform.myFormLink}`.
 *
 * @see MockPublicFormTest
 * @package Civi\Afform
 * @service civi.afform.tokens
 */
class Tokens extends AutoService implements EventSubscriberInterface {

  private static $placement = 'msg_token_single';

  private static $prefix = 'form';

  private static $jwtScope = 'afform';

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
    $pat = ';https?://(\{' . preg_quote(static::$prefix, ';') . '.*Url\});';
    foreach (array_keys($e->content) as $field) {
      if (is_string($e->content[$field])) {
        $e->content[$field] = preg_replace($pat, '$1', $e->content[$field]);
      }
    }
  }

  public static function listTokens(\Civi\Token\Event\TokenRegisterEvent $e) {
    // this tokens should be available only in contact context i.e. in Message Templates (add/edit)
    $tokenForms = static::getTokenForms();
    foreach ($tokenForms as $tokenName => $afform) {
      $e->entity(static::$prefix)
        ->register("{$tokenName}Url", E::ts('%1 (URL)', [1 => $afform['title'] ?? $afform['name']]));
      $e->entity(static::$prefix)
        ->register("{$tokenName}Link", E::ts('%1 (Full Hyperlink)', [1 => $afform['title'] ?? $afform['name']]));
    }
    if (!in_array('contactId', $e->getTokenProcessor()->getContextValues('schema')[0])) {
      return;
    }

    $e->entity('afformSubmission')
      ->register('validateSubmissionUrl', E::ts('Validate Submission URL'))
      ->register('validateSubmissionLink', E::ts('Validate Submission (Full Hyperlink)'));
  }

  public static function evaluateTokens(\Civi\Token\Event\TokenValueEvent $e) {
    $messageTokens = $e->getTokenProcessor()->getMessageTokens();

    try {
      $activeAfformTokens = $messageTokens[static::$prefix] ?? [];
      if (!empty($activeAfformTokens)) {
        $tokenForms = static::getTokenForms();
        foreach ($tokenForms as $formName => $afform) {
          if (!array_intersect($activeAfformTokens, [
            "{$formName}Url",
            "{$formName}Link",
          ])) {
            continue;
          }

          if (empty($afform['server_route'])) {
            continue;
          }
          foreach ($e->getRows() as $row) {
            if (empty($row->context['contactId'])) {
              continue;
            }
            $url = self::createUrl($afform, $row->context['contactId'], self::getAfformArgsFromTokenContext($row));
            $row->format('text/plain')->tokens(static::$prefix, $afform['name'] . 'Url', $url);
            $row->format('text/html')->tokens(static::$prefix, $afform['name'] . 'Link', sprintf('<a href="%s">%s</a>', htmlentities($url), htmlentities($afform['title'] ?? $afform['name'])));
          }
        }
      }
    }
    catch (CryptoException $ex) {
      \Civi::log()->warning(__CLASS__ . ' cannot generate tokens due to a crypto exception.',
        ['exception' => $ex]);
    }
    if (empty($messageTokens['afformSubmission'])) {
      return;
    }

    // If these tokens are being used, there will only be a single "row".
    // The relevant context is on the TokenProcessor itself, not the row.
    $context = $e->getTokenProcessor()->context;
    $sid = $context['validateAfformSubmission']['submissionId'] ?? NULL;
    if (empty($sid)) {
      return;
    }

    /** @var \Civi\Token\TokenRow $row */
    $url = self::generateEmailVerificationUrl($sid);
    $link = sprintf(
      '<a href="%s">%s</a>', htmlentities($url),
      htmlentities(ts('verify your email address')));

    foreach ($e->getRows() as $row) {
      $row->format('text/plain')->tokens('afformSubmission', 'validateSubmissionUrl', $url);
      $row->format('text/html')->tokens('afformSubmission', 'validateSubmissionLink', $link);
    }
  }

  private static function generateEmailVerificationUrl(int $submissionId): string {
    // 10 minutes
    $expires = \CRM_Utils_Time::time() + (10 * 60);

    try {
      /** @var \Civi\Crypto\CryptoJwt $jwt */
      $jwt = \Civi::service('crypto.jwt');

      $token = $jwt->encode([
        'exp' => $expires,
        // Note: Scope is not the same as "authx" scope. "Authx" tokens are user-login tokens. This one is a more limited access token.
        'scope' => 'afformVerifyEmail',
        'submissionId' => $submissionId,
      ]);
    }
    catch (CryptoException $exception) {
      \Civi::log()->warning(
        'Civi\Afform\Tokens cannot generate tokens due to crypto exception.',
        ['exception' => $exception]);
    }

    return \CRM_Utils_System::getNotifyUrl('civicrm/afform/submission/verify',
      ['token' => $token], TRUE, NULL, NULL, TRUE);
  }

  /**
   * Get a list of forms that have token support enabled.
   *
   * @return array
   *   $result[$formName] = ['name' => $formName, 'title' => $formTitle, 'server_route' => $route];
   */
  public static function getTokenForms(): array {
    if (!isset(\Civi::$statics[__CLASS__]['tokenForms'])) {
      $tokenForms = (array) \Civi\Api4\Afform::get(FALSE)
        ->addWhere('placement', 'CONTAINS', static::$placement)
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
   * @param array $afformArgs
   *   Additional Args for the Afform. E.g. as case_id.
   *
   * @return string
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public static function createUrl($afform, $contactId, array $afformArgs = []): string {
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
      'scope' => static::$jwtScope,
      'afform' => $afform['name'],
      'afformArgs' => $afformArgs,
    ]);
    return $url->addQuery(['_aff' => $bearerToken]);
  }

  /**
   * Get Additional args from the row context.
   *
   * This supports args for the contact being viewed and for the case being viewed.
   *
   * @param \Civi\Token\TokenRow $row
   * @return array
   */
  private static function getAfformArgsFromTokenContext(TokenRow $row): array {
    $afformArgs = [];
    if (!empty($row->context['contactId'])) {
      $afformArgs['contact_id'] = $row->context['contactId'];
    }
    if (!empty($row->context['caseId'])) {
      $afformArgs['case_id'] = $row->context['caseId'];
    }
    return $afformArgs;
  }

}
