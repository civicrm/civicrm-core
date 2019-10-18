<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
