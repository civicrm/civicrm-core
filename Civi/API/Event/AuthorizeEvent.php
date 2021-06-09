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

namespace Civi\API\Event;

use Civi\Api4\Event\ActiveUserTrait;

/**
 * Class AuthorizeEvent
 *
 * @package Civi\API\Event
 *
 * Determine whether the API request is allowed for the current user.
 * For successful execution, at least one listener must invoke
 * $event->authorize().
 *
 * Event name: 'civi.api.authorize'
 */
class AuthorizeEvent extends Event {

  use AuthorizedTrait;
  use ActiveUserTrait;

  public function __construct($apiProvider, $apiRequest, $apiKernel, int $userID) {
    parent::__construct($apiProvider, $apiRequest, $apiKernel);
    $this->setUser($userID);
  }

}
