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

namespace Civi\Api4\Generic\Traits;

/**
 * This trait adds the $current param to a Get action.
 *
 * @see \Civi\Api4\Event\Subscriber\IsCurrentSubscriber
 */
trait IsCurrentTrait {

  /**
   * Convenience filter for selecting items that are enabled and are currently within their start/end dates.
   *
   * Adding current = TRUE is a shortcut for
   *   WHERE is_active = 1 AND (end_date IS NULL OR end_date >= now) AND (start_date IS NULL OR start_DATE <= now)
   *
   * Adding current = FALSE is a shortcut for
   *   WHERE is_active = 0 OR start_date > now OR end_date < now
   *
   * @var bool
   */
  protected $current;

  /**
   * @return bool
   */
  public function getCurrent() {
    return $this->current;
  }

  /**
   * @param bool $current
   * @return $this
   */
  public function setCurrent($current) {
    $this->current = $current;
    return $this;
  }

}
