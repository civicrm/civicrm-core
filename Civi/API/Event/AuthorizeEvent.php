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

/**
 * Class AuthorizeEvent
 * @package Civi\API\Event
 */
class AuthorizeEvent extends Event {
  /**
   * @var bool
   */
  private $authorized = FALSE;

  /**
   * Mark the request as authorized.
   */
  public function authorize() {
    $this->authorized = TRUE;
  }

  /**
   * @return bool
   *   TRUE if the request has been authorized.
   */
  public function isAuthorized() {
    return $this->authorized;
  }

}
