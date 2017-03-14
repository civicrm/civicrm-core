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
    return array(
      Events::TOKEN_EVALUATE => 'onEvaluate',
      Events::TOKEN_RENDER => 'onRender',
    );
  }

  /**
   * Load token data.
   *
   * @param TokenValueEvent $e
   * @throws TokenException
   */
  public function onEvaluate(TokenValueEvent $e) {
    // For reasons unknown, replaceHookTokens requires a pre-computed list of
    // hook *categories* (aka entities aka namespaces). We'll cache
    // this in the TokenProcessor's context.

    $hookTokens = array();
    \CRM_Utils_Hook::tokens($hookTokens);
    $categories = array_keys($hookTokens);
    $e->getTokenProcessor()->context['hookTokenCategories'] = $categories;

    $messageTokens = $e->getTokenProcessor()->getMessageTokens();

    foreach ($e->getRows() as $row) {
      /** @var int $contactId */
      $contactId = $row->context['contactId'];
      if (empty($row->context['contact'])) {
        $params = array(
          array('contact_id', '=', $contactId, 0, 0),
        );
        list($contact, $_) = \CRM_Contact_BAO_Query::apiQuery($params);
        $contact = reset($contact); //CRM-4524
        if (!$contact || is_a($contact, 'CRM_Core_Error')) {
          // FIXME: Need to differentiate errors which kill the batch vs the individual row.
          throw new TokenException("Failed to generate token data. Invalid contact ID: " . $row->context['contactId']);
        }

        //update value of custom field token
        if (!empty($messageTokens['contact'])) {
          foreach ($messageTokens['contact'] as $token) {
            if (\CRM_Core_BAO_CustomField::getKeyID($token)) {
              $contact[$token] = civicrm_api3('Contact', 'getvalue', array(
                'return' => $token,
                'id' => $contactId,
              ));
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

      $contactArray = !is_array($contactId) ? array($contactId => $contact) : $contact;

      // Note: This is a small contract change from the past; data should be missing
      // less randomly.
      \CRM_Utils_Hook::tokenValues($contactArray,
        (array) $contactId,
        empty($row->context['mailingJob']) ? NULL : $row->context['mailingJob']->id,
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
   * @param TokenRenderEvent $e
   */
  public function onRender(TokenRenderEvent $e) {
    $isHtml = ($e->message['format'] == 'text/html');
    $useSmarty = !empty($e->context['smarty']);

    $domain = \CRM_Core_BAO_Domain::getDomain();
    $e->string = \CRM_Utils_Token::replaceDomainTokens($e->string, $domain, $isHtml, $e->message['tokens'], $useSmarty);

    if (!empty($e->context['contact'])) {
      $e->string = \CRM_Utils_Token::replaceContactTokens($e->string, $e->context['contact'], $isHtml, $e->message['tokens'], TRUE, $useSmarty);

      // FIXME: This may depend on $contact being merged with hook values.
      $e->string = \CRM_Utils_Token::replaceHookTokens($e->string, $e->context['contact'], $e->context['hookTokenCategories'], $isHtml, $useSmarty);

      \CRM_Utils_Token::replaceGreetingTokens($e->string, $e->context['contact'], $e->context['contact']['contact_id'], NULL, $useSmarty);
    }

    if ($useSmarty) {
      $smarty = \CRM_Core_Smarty::singleton();
      $e->string = $smarty->fetch("string:" . $e->string);
    }
  }

}
