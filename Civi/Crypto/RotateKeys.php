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

namespace Civi\Crypto;

use Civi\Core\Event\GenericHookEvent;

/**
 * Class RotateKeys
 *
 * @package Civi\Crypto
 */
class RotateKeys {

  /**
   * The SMTP password is stored inside of the 'mailing_backend' setting.
   *
   * @see CRM_Utils_Hook::cryptoRotateKey()
   */
  public static function rotateSmtp(GenericHookEvent $e) {
    if ($e->tag !== 'CRED') {
      return;
    }

    $mand = \Civi::settings()->getMandatory('mailing_backend');
    if ($mand !== NULL && !empty($mand['smtpPassword'])) {
      $e->log->warning('The settings override for smtpPassword cannot be changed automatically.');
    }

    $exp = \Civi::settings()->getExplicit('mailing_backend');
    if ($exp !== NULL && !empty($exp['smtpPassword'])) {
      $cryptoToken = \Civi::service('crypto.token');
      $newValue = $cryptoToken->rekey($exp['smtpPassword'], 'CRED');
      if ($newValue !== NULL) {
        $exp['smtpPassword'] = $newValue;
        \Civi::settings()->set('mailing_backend', $exp);
        $e->log->info('Updated mailing_backend.smtpPassword');
      }
    }
  }

}
