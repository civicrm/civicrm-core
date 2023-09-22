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
