<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Class Event
 * @package Civi\API\Event
 */
class Event extends \Symfony\Component\EventDispatcher\Event {

  /**
   * @var \Civi\API\Kernel
   */
  protected $apiKernel;

  /**
   * @var \Civi\API\Provider\ProviderInterface
   *   The API provider responsible for executing the request.
   */
  protected $apiProvider;

  /**
   * @var array
   *   The full description of the API request.
   *
   * @see \Civi\API\Request::create
   */
  protected $apiRequest;

  /**
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API responsible for executing the request.
   * @param array $apiRequest
   *   The full description of the API request.
   * @param \Civi\API\Kernel $apiKernel
   */
  public function __construct($apiProvider, $apiRequest, $apiKernel) {
    $this->apiKernel = $apiKernel;
    $this->apiProvider = $apiProvider;
    $this->apiRequest = $apiRequest;
  }

  /**
   * Get api kernel.
   *
   * @return \Civi\API\Kernel
   */
  public function getApiKernel() {
    return $this->apiKernel;
  }

  /**
   * @return \Civi\API\Provider\ProviderInterface
   */
  public function getApiProvider() {
    return $this->apiProvider;
  }

  /**
   * @return array
   */
  public function getApiRequest() {
    return $this->apiRequest;
  }

}
