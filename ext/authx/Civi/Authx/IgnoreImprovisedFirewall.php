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

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service authx.ignore_improvised_proxy
 */
class IgnoreImprovisedFirewall extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      'civi.authx.checkPolicy' => ['onCheckPolicy', -500],
    ];
  }

  public function onCheckPolicy(CheckPolicyEvent $event) {
    if (
      // The request uses the standard HTTP Authorization header...
      $event->target->flow === 'header'

      // And the request includes a password...
      && preg_match('/^(Basic|Digest) /', $event->target->cred)

      // But the system-policy doesn't allow passwords...
      && !in_array('pass', $event->policy['allowCreds'])
    ) {
      // So... this header is probably irrelevant noise...
      throw new IgnoreCredentialException('Received password via HTTP Authorization, but passwords are not enabled for HTTP Authorization.');
    }
  }

}
