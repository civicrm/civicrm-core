<?php
namespace Civi\Token;

use Civi\Token\Event\TokenRenderEvent;
use Civi\Token\Event\TokenValueEvent;
use Money\Money;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TokenCompatSubscriber
 * @package Civi\Token
 *
 * This class handles the smarty processing of tokens.
 */
class TokenCompatSubscriber implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.token.eval' => [
        ['setupSmartyAliases', 1000],
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
   * Apply the various CRM_Utils_Token helpers.
   *
   * @param \Civi\Token\Event\TokenRenderEvent $e
   */
  public function onRender(TokenRenderEvent $e): void {
    $useSmarty = !empty($e->context['smarty']);
    $e->string = $e->getTokenProcessor()->visitTokens($e->string, function() {
      // For historical consistency, we filter out unrecognized tokens.
      return '';
    });

    // This removes the pattern used in greetings of having bits of text that
    // depend on the tokens around them - ie '{first_name}{ }{last_name}
    // has an extra construct '{ }' which will resolve as a space if the
    // tokens on either side are resolved to 'something'
    $e->string = preg_replace('/\\\\|\{(\s*)?\}/', ' ', $e->string);

    if ($useSmarty) {
      $smartyVars = [];
      foreach ($e->context['smartyTokenAlias'] ?? [] as $smartyName => $tokenName) {
        $smartyVars[$smartyName] = \CRM_Utils_Array::pathGet($e->row->tokens, explode('.', $tokenName), $e->context['locale'] ?? NULL);
        if ($smartyVars[$smartyName] instanceof \Brick\Money\Money) {
          $smartyVars[$smartyName] = \Civi::format()->money($smartyVars[$smartyName]->getAmount(), $smartyVars[$smartyName]->getCurrency());
        }
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

}
