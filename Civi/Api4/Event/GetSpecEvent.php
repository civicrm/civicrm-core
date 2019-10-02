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


namespace Civi\Api4\Event;

use Civi\Api4\Generic\AbstractAction;
use Symfony\Component\EventDispatcher\Event as BaseEvent;

class GetSpecEvent extends BaseEvent {
  /**
   * @var \Civi\Api4\Generic\AbstractAction
   */
  protected $request;

  /**
   * @param \Civi\Api4\Generic\AbstractAction $request
   */
  public function __construct(AbstractAction $request) {
    $this->request = $request;
  }

  /**
   * @return \Civi\Api4\Generic\AbstractAction
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * @param $request
   */
  public function setRequest(AbstractAction $request) {
    $this->request = $request;
  }

}
