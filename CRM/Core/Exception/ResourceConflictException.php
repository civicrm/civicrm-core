<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2020                                |
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
 * Exception thrown when contention over a resource causes process to abort.
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
class CRM_Core_Exception_ResourceConflictException extends \CRM_Core_Exception {

}
