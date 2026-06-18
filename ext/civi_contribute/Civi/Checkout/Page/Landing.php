<?php

namespace Civi\Checkout\Page;

use CRM_Contribute_ExtensionUtil as E;

/**
 * Resume checkout session, potentially after trip to the external
 * payment processor.
 *
 * This endpoint may include a callback to the payment processor to
 * check the status
 */
class Landing extends \CRM_Core_Page {

  public function run() {
    try {
      $token = \CRM_Utils_Request::retrieve('session', 'String');
      $result = \Civi\Api4\Contribution::continueCheckout(FALSE)
        ->setToken($token)
        ->execute();
    }
    catch (\CRM_Core_Exception $e) {
      $result = [
        'title' => E::ts('Error confirming payment'),
        'message' => $e->getMessage(),
        'status' => 'error',
      ];
    }

    // if redirect, redirect immediately
    if ($result['redirect'] ?? FALSE) {
      \CRM_Utils_System::redirect($result['redirect']);
      return;
    }

    // otherwise set title then pass the rest to clientside renderer
    \CRM_Utils_System::setTitle($result['title']);
    \Civi::resources()->addScriptFile(E::SHORT_NAME, 'js/landing.js');
    \Civi::resources()->addVars('checkout', [
      'landingPageInitialCheck' => $result,
    ]);
    parent::run();

  }

}
