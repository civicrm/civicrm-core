<?php
namespace Civi\Token;

use Civi\Token\Event\TokenRenderEvent;
use Civi\Token\Event\TokenValueEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TokenCompatSubscriber
 * @package Civi\Token
 *
 * This class provides a compatibility layer for using CRM_Utils_Token
 * helpers within TokenProcessor.
 *
 * THIS IS NOT A GOOD EXAMPLE TO EMULATE. The class exists to two
 * bridge two different designs. CRM_Utils_Token has some
 * undesirable elements (like iterative token substitution).
 * However, if you're refactor CRM_Utils_Token or improve the
 * bridge, then it makes sense to update this class.
 */
class TokenCompatSubscriber implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      'civi.token.eval' => [
        ['setupSmartyAliases', 1000],
        ['onEvaluate'],
      ],
      'civi.token.render' => 'onRender',
    ];
  }

  /**
   * Interpret the variable `$context['smartyTokenAlias']` (e.g. `mySmartyField' => `tkn_entity.tkn_field`).
   *
   * We need to ensure that any tokens like `{tkn_entity.tkn_field}` are hydrated, so
   * we pretend that they are in use.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   */
  public function setupSmartyAliases(TokenValueEvent $e) {
    $aliasedTokens = [];
    foreach ($e->getRows() as $row) {
      $aliasedTokens = array_unique(array_merge($aliasedTokens,
        array_values($row->context['smartyTokenAlias'] ?? [])));
    }

    $fakeMessage = implode('', array_map(function ($f) {
      return '{' . $f . '}';
    }, $aliasedTokens));

    $proc = $e->getTokenProcessor();
    $proc->addMessage('TokenCompatSubscriber.aliases', $fakeMessage, 'text/plain');
  }

  /**
   * Load token data.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   * @throws TokenException
   */
  public function onEvaluate(TokenValueEvent $e) {
    // For reasons unknown, replaceHookTokens used to require a pre-computed list of
    // hook *categories* (aka entities aka namespaces). We cache
    // this in the TokenProcessor's context but can likely remove it now.

    $e->getTokenProcessor()->context['hookTokenCategories'] = \CRM_Utils_Token::getTokenCategories();

    $messageTokens = $e->getTokenProcessor()->getMessageTokens();
    $returnProperties = $this->getReturnFields($messageTokens['contact']);

    foreach ($e->getRows() as $row) {
      if (empty($row->context['contactId'])) {
        continue;
      }

      unset($swapLocale);
      $swapLocale = empty($row->context['locale']) ? NULL : \CRM_Utils_AutoClean::swapLocale($row->context['locale']);

      /** @var int $contactId */
      $contactId = $row->context['contactId'];
      if (empty($row->context['contact'])) {
        $params = [
          ['contact_id', '=', $contactId, 0, 0],
        ];
        [$contact] = \CRM_Contact_BAO_Query::apiQuery($params, $returnProperties ?? NULL);
        //CRM-4524
        $contact = reset($contact);
        // Test cover for greeting in CRM_Core_BAO_ActionScheduleTest::testMailer
        $contact['email_greeting'] = $contact['email_greeting_display'] ?? '';
        $contact['postal_greeting'] = $contact['postal_greeting_display'] ?? '';
        $contact['addressee'] = $contact['address_display'] ?? '';
        if (!$contact || is_a($contact, 'CRM_Core_Error')) {
          // FIXME: Need to differentiate errors which kill the batch vs the individual row.
          \Civi::log()->debug('Failed to generate token data. Invalid contact ID: ' . $row->context['contactId']);
          continue;
        }

        //update value of custom field token
        if (!empty($messageTokens['contact'])) {
          foreach ($messageTokens['contact'] as $token) {
            if (\CRM_Core_BAO_CustomField::getKeyID($token)) {
              $contact[$token] = \CRM_Core_BAO_CustomField::displayValue($contact[$token], \CRM_Core_BAO_CustomField::getKeyID($token));
            }
          }
        }
      }
      else {
        $contact = $row->context['contact'];
      }

      if (!empty($row->context['tmpTokenParams'])) {
        // merge activity tokens with contact array
        // this is pretty weird.
        $contact = array_merge($contact, $row->context['tmpTokenParams']);
      }

      $contactArray = [$contactId => $contact];
      \CRM_Utils_Hook::tokenValues($contactArray,
        [$contactId],
        empty($row->context['mailingJobId']) ? NULL : $row->context['mailingJobId'],
        $messageTokens,
        $row->context['controller']
      );

      // merge the custom tokens in the $contact array
      if (!empty($contactArray[$contactId])) {
        $contact = array_merge($contact, $contactArray[$contactId]);
      }
      $row->context('contact', $contact);
    }
  }

  /**
   * Apply the various CRM_Utils_Token helpers.
   *
   * @param \Civi\Token\Event\TokenRenderEvent $e
   */
  public function onRender(TokenRenderEvent $e) {
    $isHtml = ($e->message['format'] == 'text/html');
    $useSmarty = !empty($e->context['smarty']);

    $domain = \CRM_Core_BAO_Domain::getDomain();
    $e->string = \CRM_Utils_Token::replaceDomainTokens($e->string, $domain, $isHtml, $e->message['tokens'], $useSmarty);

    if (!empty($e->context['contact'])) {
      $e->string = \CRM_Utils_Token::replaceContactTokens($e->string, $e->context['contact'], $isHtml, $e->message['tokens'], FALSE, $useSmarty);

      // FIXME: This may depend on $contact being merged with hook values.
      $e->string = \CRM_Utils_Token::replaceHookTokens($e->string, $e->context['contact'], $e->context['hookTokenCategories'], $isHtml, $useSmarty);
    }

    if ($useSmarty) {
      $smartyVars = [];
      foreach ($e->context['smartyTokenAlias'] ?? [] as $smartyName => $tokenName) {
        // Note: $e->row->tokens resolves event-based tokens (eg CRM_*_Tokens). But if the target token relies on the
        // above bits (replaceGreetingTokens=>replaceContactTokens=>replaceHookTokens) then this lookup isn't sufficient.
        $smartyVars[$smartyName] = \CRM_Utils_Array::pathGet($e->row->tokens, explode('.', $tokenName));
      }
      \CRM_Core_Smarty::singleton()->pushScope($smartyVars);
      try {
        $e->string = \CRM_Utils_String::parseOneOffStringThroughSmarty($e->string);
      }
      finally {
        \CRM_Core_Smarty::singleton()->popScope();
      }
    }
  }

  /**
   * @param $contact1
   *
   * @return array|int[]
   */
  protected function getReturnFields($contact1): array {
    $returnProperties = array_fill_keys($contact1 ?? [], 1);
    $returnProperties = array_merge(\CRM_Contact_BAO_Query::defaultReturnProperties(), $returnProperties);
    return $returnProperties;
  }

}
