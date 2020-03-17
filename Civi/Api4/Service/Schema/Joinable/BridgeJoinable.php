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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


namespace Civi\Api4\Service\Schema\Joinable;

class BridgeJoinable extends Joinable {
  /**
   * @var Joinable
   */
  protected $middleLink;

  public function __construct($targetTable, $targetColumn, $alias, Joinable $middleLink) {
    parent::__construct($targetTable, $targetColumn, $alias);
    $this->middleLink = $middleLink;
  }

  /**
   * @return Joinable
   */
  public function getMiddleLink() {
    return $this->middleLink;
  }

}
