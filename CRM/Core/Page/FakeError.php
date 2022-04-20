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

/**
 * The "civicrm/dev/fake-error" page is a mock to facilitate E2E testing of the error-reporting mechanism.
 * Use this page to provoke common/representative errors.
 *
 * Of course, we don't want to permit arbitrary users to provoke arbitrary errors -- that could
 * lead to noisy/confusing logs.
 *
 * This has two main modes:
 *
 * - If you give no parameters (or unsigned parameters), it simply says "Hello world".
 * - If you give an authentic JWT with the claim `civi.fake-error`, then it will report
 *   one of the pre-canned error messages.
 */
class CRM_Core_Page_FakeError extends CRM_Core_Page {

  public function run() {
    try {
      /** @var \Civi\Crypto\CryptoJwt $jwt */
      $jwt = Civi::service('crypto.jwt');
      $claims = $jwt->decode(CRM_Utils_Request::retrieve('token', 'String'));
    }
    catch (\Exception $e) {
      $claims = [];
    }

    if (empty($claims['civi.fake-error'])) {
      echo 'Hello world';
      return;
    }

    switch ($claims['civi.fake-error']) {
      case 'exception':
        throw new \CRM_Core_Exception("This is a fake problem (exception).");

      case 'fatal':
        CRM_Core_Error::fatal('This is a fake problem (fatal).');
        break;

      case 'permission':
        CRM_Utils_System::permissionDenied();
        break;

      default:
        return 'Unrecognized error type.';
    }
  }

}
