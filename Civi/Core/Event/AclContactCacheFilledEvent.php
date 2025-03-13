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

 namespace Civi\Core\Event;

 /**
  * Class AuthorizeEvent
  * @package Civi\API\Event
  */
class AclContactCacheFilledEvent extends GenericHookEvent {

  /**
   * Are we forcing building of the ACL Cache
   * @var bool
   */
  public $forceBuild = FALSE;

  /**
   * Has the ACL Cache for a contact been built in the current PHP Process
   * @var bool
   */
  public $aclCacheProcessed = FALSE;


  /**
   * Contact the ACL Cache is being built for
   * @var int
   */
  public $userID;

  /**
   * Class constructor.
   *
   * @param string $forceBuild
   * @param string $entiaclCacheProcessedty
   * @param int|null $userID
   * @param array $params
   */
  public function __construct($forceBuild, $aclCacheProcessed, $userID) {
    $this->forceBuild = $forceBuild;
    $this->aclCacheProcessed = $aclCacheProcessed;
    $this->userID = $userID;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->forceBuild, $this->aclCacheProcessed, $this->userID];
  }

}