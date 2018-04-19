<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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

namespace Civi\Core\Event;

/**
 * Class UnhandledExceptionEvent
 * @package Civi\API\Event
 */
class UnhandledExceptionEvent extends GenericHookEvent {

  /**
   * @var \Exception
   */
  public $exception;

  /**
   * @var mixed reserved for future use
   */
  public $request;

  /**
   * @param $e
   * @param $request
   */
  public function __construct($e, $request) {
    $this->request = $request;
    $this->exception = $e;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return array($this->exception, $this->request);
  }

}
