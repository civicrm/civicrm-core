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
namespace Civi\Api4\Generic\Traits;

/**
 * @deprecated
 * @see \Civi\Api4\Event\Subscriber\IsCurrentSubscriber
 */
trait IsCurrentTrait {

  /**
   * Param deprecated in favor of is_current filter.
   *
   * @var bool
   * @deprecated
   */
  protected $current;

  /**
   * @deprecated
   * @return bool
   */
  public function getCurrent() {
    return $this->current;
  }

  /**
   * @deprecated
   * @param bool $current
   * @return $this
   */
  public function setCurrent($current) {
    $this->current = $current;
    return $this;
  }

}
