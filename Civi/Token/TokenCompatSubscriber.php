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
    $e->string = $e->getTokenProcessor()->visitTokens($e->string, function($token = NULL, $entity = NULL, $field = NULL, $filterParams = NULL) {
      if ($filterParams && $filterParams[0] === 'boolean') {
        // This token was missed during primary rendering, and it's supposed to be coerced to boolean.
        // Treat an unknown token as false-y.
        return 0;
      }
      // For historical consistency, we filter out unrecognized tokens.
      return '';
    });

    // This removes the pattern used in greetings of having bits of text that
    // depend on the tokens around them - ie '{first_name}{ }{last_name}
    // has an extra construct '{ }' which will resolve what is inside the {} if the
    // tokens on either side are resolved to 'something' (ie there is some sort of
    // non whitespace character after the string.
    // Accepted variants of { } are {|} {,} {`} {*} {-} {(} {)}
    // In each case any amount of preceding or trailing whitespace is acceptable.
    // The accepted variants list contains known or suspected real world usages.
    // Regex is to capture  { followed by 0 or more white spaces followed by
    // a white space or one of , ` ~  ( ) - * |
    // followed by 0 or more white spaces
    // followed by }
    // the captured string is followed by 1 or more non-white spaces.
    // If it is repeated it will be replaced by the first input -
    // ie { }{ } will be replaced by the content of the latter token.
    // except that {(}, {)} and {`} are treated differently: repeated tokens are both removed
    // eg {contact.first_name}{ }{ (}{contact.nick_name}{) }{contact.last_name} becomes "First Last", not "First )Last"
    // Check testGenerateDisplayNameCustomFormats for test cover.
    // and testMailingLabel
    // This first regex targets anything like {, }{ } - where the presence of the space
    // one tells us we could be in a situation like
    // {contact.address_primary.city}{, }{contact.address_primary.state_province_id:label}{ }{contact.address_primary.postal_code}
    // Where state_province is not present. No perfect solution here but we
    // do want to keep the comma in this case.

    // Pattern to match all curlies
    $any_curly = '\{(?:\s+|\s*[,~()`\-*|]*\s*)\}';

    // Pattern to match curlies occuring in pairs - ie {(} {)} {`}
    $paired_curly = '\{(?:\s*[`()]\s*)\}';

    // Pattern to match other curlies
    $unpaired_curly = '\{(?:\s+|\s*[,~\-*|]*\s*)\}';

    // Captures the inside of a curly
    $curly_inner = '\{(\s+|\s*[,~()`\-*|]*\s*)\}';

    $regexes = [];

    // Special oddball for addresses: {, }{ } -> {, }
    $regexes[] = ["/(\{,\s+\})\s*\{\s+\}/", '$1'];

    // Remove two adjacent paired curlies
    $regexes[] = ["/$paired_curly\s*$paired_curly/", ''];

    // Replace multiple adjacent curlies with the last
    $regexes[] = ["/(?:$any_curly\s*)+($any_curly)/", '$1'];

    // Remove leading unpaired curly
    $regexes[] = ["/^\s*$unpaired_curly/", ''];

    // Remove trailing unpaired curly
    $regexes[] = ["/$unpaired_curly\s*$/", ''];

    // Finally replace curlies with the inner content
    $regexes[] = ["/$curly_inner/", '$1'];

    $e->string = preg_replace(array_column($regexes, 0), array_column($regexes, 1), $e->string);

    if ($useSmarty) {
      $smartyVars = [];
      foreach ($e->context['smartyTokenAlias'] ?? [] as $smartyName => $tokenName) {
        $tokenParts = explode('|', $tokenName);
        $modifier = $tokenParts[1] ?? '';
        $smartyVars[$smartyName] = \CRM_Utils_Array::pathGet($e->row->tokens, explode('.', $tokenParts[0]), '');
        if ($smartyVars[$smartyName] instanceof \Brick\Money\Money) {
          // TODO: We should reuse the filters from TokenProcessor::filterTokenValue()
          if ($modifier === 'crmMoney') {
            $smartyVars[$smartyName] = \Civi::format()
              ->money($smartyVars[$smartyName]->getAmount(), $smartyVars[$smartyName]->getCurrency());
          }
          else {
            $smartyVars[$smartyName] = $smartyVars[$smartyName]->getAmount();
          }
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
