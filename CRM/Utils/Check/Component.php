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
 */
abstract class CRM_Utils_Check_Component {

  /**
   * Should these checks be run?
   *
   * @return bool
   */
  public function isEnabled() {
    return TRUE;
  }

  /**
   * Run all checks in this class.
   *
   * @return array
   *   [CRM_Utils_Check_Message]
   */
  public function checkAll() {
    $messages = [];
    foreach (get_class_methods($this) as $method) {
      if ($method !== 'checkAll' && strpos($method, 'check') === 0) {
        $messages = array_merge($messages, $this->$method());
      }
    }
    return $messages;
  }

  /**
   * Check if file exists on given URL.
   *
   * @param string $url
   * @param float $timeout
   *
   * @return bool
   */
  public function fileExists($url, $timeout = 0.50) {
    $fileExists = FALSE;
    try {
      $guzzleClient = new GuzzleHttp\Client();
      $guzzleResponse = $guzzleClient->request('GET', $url, array(
        'timeout' => $timeout,
      ));
      $fileExists = ($guzzleResponse->getStatusCode() == 200);
    }
    catch (Exception $e) {
      // At this stage we are not checking for variants of not being able to receive it.
      // However, we might later enhance this to distinguish forbidden from a 500 error.
    }
    return $fileExists;
  }

}
