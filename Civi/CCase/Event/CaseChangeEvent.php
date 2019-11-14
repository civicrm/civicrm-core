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

namespace Civi\CCase\Event;

use Civi\Core\Event\GenericHookEvent;

/**
 * Class CaseChangeEvent
 * @package Civi\API\Event
 */
class CaseChangeEvent extends GenericHookEvent {
  /**
   * @var \Civi\CCase\Analyzer
   */
  public $analyzer;

  /**
   * @param $analyzer
   */
  public function __construct($analyzer) {
    $this->analyzer = $analyzer;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->analyzer];
  }

}
