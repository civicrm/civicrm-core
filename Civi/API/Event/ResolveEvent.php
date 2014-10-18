<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * Class ResolveEvent
 * @package Civi\API\Event
 */
class ResolveEvent extends Event {
  /**
   * @param $apiRequest
   */
  function __construct($apiRequest) {
    parent::__construct(NULL, $apiRequest);
  }

  /**
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   */
  public function setApiProvider($apiProvider) {
    $this->apiProvider = $apiProvider;
  }

  /**
   * @param array $apiRequest
   */
  public function setApiRequest($apiRequest) {
    $this->apiRequest = $apiRequest;
  }
}
