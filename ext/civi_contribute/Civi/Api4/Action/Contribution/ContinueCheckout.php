<?php

namespace Civi\Api4\Action\Contribution;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Crypto\Exception\CryptoException;
use Civi\Checkout\CheckoutSession;
use CRM_Contribute_ExtensionUtil as E;

class ContinueCheckout extends AbstractAction {

  /**
   * @var string
   */
  protected string $token;

  public function _run(Result $result): void {

    try {
      $session = CheckoutSession::restoreFromToken($this->token);
    }
    catch (CryptoException $e) {
      if (str_contains($e->getMessage(), 'Expired token')) {
        throw new \CRM_Core_Exception(E::ts('Checkout session expired'));
      }
    }

    // continue the checkout (may involve server side api calls, user redirects)
    $session->continueCheckout();

    // get updated token (in case relevant state has changed)
    $result['token'] = $session->tokenise();
    // get status key
    $result['status'] = $session->getStatus();
    // get title (for landing page)
    $result['title'] = $session->getTitle();
    // get user message
    $result['message'] = $session->getStatusMessage();
    // get onward url
    $result['redirect'] = $session->getNextUrl();
  }

}
