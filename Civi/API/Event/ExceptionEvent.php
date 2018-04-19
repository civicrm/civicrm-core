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

namespace Civi\API\Event;

/**
 * Class ExceptionEvent
 * @package Civi\API\Event
 */
class ExceptionEvent extends Event {

  /**
   * @var \Exception
   */
  private $exception;

  /**
   * @param \Exception $exception
   *   The exception which arose while processing the API request.
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API provider responsible for executing the request.
   * @param array $apiRequest
   *   The full description of the API request.
   * @param \Civi\API\Kernel $apiKernel
   *   The kernel which fired the event.
   */
  public function __construct($exception, $apiProvider, $apiRequest, $apiKernel) {
    $this->exception = $exception;
    parent::__construct($apiProvider, $apiRequest, $apiKernel);
  }

  /**
   * @return \Exception
   */
  public function getException() {
    return $this->exception;
  }

}
