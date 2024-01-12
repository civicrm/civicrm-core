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
namespace Civi\Api4\Event;

use Civi\API\Event\AuthorizedTrait;
use Civi\API\Event\RequestTrait;
use Civi\Core\Event\GenericHookEvent;

/**
 * Determine if the a user has WRITE access to a given record.
 * This event does not impact READ access for `get` actions.
 *
 * Event name: 'civi.api4.authorizeRecord'
 */
class AuthorizeRecordEvent extends GenericHookEvent {

  use RequestTrait;
  use AuthorizedTrait;
  use ActiveUserTrait;

  /**
   * All (known/loaded) values of individual record being accessed.
   * The record should provide an 'id' but may otherwise be incomplete; guard accordingly.
   *
   * @var array
   */
  private $record;

  /**
   * CheckAccessEvent constructor.
   *
   * @param \Civi\Api4\Generic\AbstractAction $apiRequest
   * @param array $record
   *   All (known/loaded) values of individual record being accessed.
   *   The record should provide an 'id' but may otherwise be incomplete; guard accordingly.
   * @param int $userID
   *   Contact ID of the active/target user (whose access we must check).
   *   0 for anonymous.
   */
  public function __construct($apiRequest, array $record, int $userID) {
    $this->setApiRequest($apiRequest);
    $this->record = $record;
    $this->setUser($userID);
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->getApiRequest(), $this->record, &$this->authorized];
  }

  /**
   * @return array
   */
  public function getRecord(): array {
    return $this->record;
  }

}
