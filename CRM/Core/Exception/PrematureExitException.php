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
 * Exception thrown during tests where live code would exit.
 *
 * This is when the code would exit in live mode.
 *
 * @param string $message
 *   The human friendly error message.
 * @param string $error_code
 *   A computer friendly error code. By convention, no space (but underscore allowed).
 *   ex: mandatory_missing, duplicate, invalid_format
 * @param array $data
 *   Extra params to return. eg an extra array of ids. It is not mandatory, but can help the computer using the api.
 * Keep in mind the api consumer isn't to be trusted. eg. the database password is NOT a good extra data.
 */
class CRM_Core_Exception_PrematureExitException extends RuntimeException {

  /**
   * Construct the exception. Note: The message is NOT binary safe.
   *
   * @link https://php.net/manual/en/exception.construct.php
   *
   * @param string $message [optional] The Exception message to throw.
   * @param array $errorData
   * @param int $error_code
   * @param throwable $previous [optional] The previous throwable used for the exception chaining.
   */
  public function __construct($message = "", $errorData = [], $error_code = 0, throwable $previous = NULL) {
    parent::__construct($message, $error_code, $previous);
    $this->errorData = $errorData + ['error_code' => $error_code];
  }

}
