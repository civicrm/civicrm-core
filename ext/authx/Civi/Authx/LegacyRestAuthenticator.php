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

namespace Civi\Authx;

use Civi\Core\Event\GenericHookEvent;
use GuzzleHttp\Psr7\Response;

/**
 * Historically, 'extern/rest.php' and 'civicrm/ajax/rest' were similar interfaces
 * based on the same controller, but they used different authentication styles.
 *
 * This authenticator is activated if one requests 'civicrm/ajax/rest' using the
 * authentication style of 'extern/rest.php'.
 *
 * @package Civi\Authx
 * @service authx.legacy_authenticator
 */
class LegacyRestAuthenticator extends Authenticator {

  public function on_civi_invoke_auth(GenericHookEvent $e) {
    // Accept legacy auth (?key=...&api_key=...) for 'civicrm/ajax/rest' and 'civicrm/ajax/api4/*'.
    // The use of `?key=` could clash on some endpoints. Only accept on a small list of endpoints that are compatible with it.
    if (count($e->args) > 2 && $e->args[1] === 'ajax' && in_array($e->args[2], ['rest', 'api4'])) {
      if ((!empty($_REQUEST['api_key']) || !empty($_REQUEST['key']))) {
        return $this->auth($e, ['flow' => 'legacyrest', 'cred' => 'Bearer ' . $_REQUEST['api_key'] ?? '', 'siteKey' => $_REQUEST['key'] ?? NULL]);
      }
    }
  }

  protected function reject($message = 'Authentication failed') {
    $data = ["error_message" => "FATAL: $message", "is_error" => 1];
    $r = new Response(200, ['Content-Type' => 'text/javascript'], json_encode($data));
    \CRM_Utils_System::sendResponse($r);
  }

  protected function login(AuthenticatorTarget $tgt) {
    parent::login($tgt);
    \Civi::dispatcher()->addListener('hook_civicrm_permission_check', function ($e) {
      if ($e->permission === 'access AJAX API') {
        $e->granted = TRUE;
      }
    });
  }

}
