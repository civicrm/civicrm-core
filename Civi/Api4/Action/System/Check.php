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

namespace Civi\Api4\Action\System;

/**
 * Retrieve system notices, warnings, errors, etc.
 */
class Check extends \Civi\Api4\Generic\BasicGetAction {

  protected function getRecords() {
    $messages = [];
    foreach (\CRM_Utils_Check::checkAll() as $message) {
      $messages[] = $message->toArray();
    }
    return $messages;
  }

  public static function fields() {
    return [
      [
        'name' => 'name',
        'title' => 'Name',
        'description' => 'Unique identifier',
        'data_type' => 'String',
      ],
      [
        'name' => 'title',
        'title' => 'Title',
        'description' => 'Short title text',
        'data_type' => 'String',
      ],
      [
        'name' => 'message',
        'title' => 'Message',
        'description' => 'Long description html',
        'data_type' => 'String',
      ],
      [
        'name' => 'help',
        'title' => 'Help',
        'description' => 'Optional extra help (html string)',
        'data_type' => 'String',
      ],
      [
        'name' => 'icon',
        'description' => 'crm-i class of icon to display with message',
        'data_type' => 'String',
      ],
      [
        'name' => 'severity',
        'title' => 'Severity',
        'description' => 'Psr\Log\LogLevel string',
        'data_type' => 'String',
        'options' => array_combine(\CRM_Utils_Check::getSeverityList(), \CRM_Utils_Check::getSeverityList()),
      ],
      [
        'name' => 'severity_id',
        'title' => 'Severity ID',
        'description' => 'Integer representation of Psr\Log\LogLevel',
        'data_type' => 'Integer',
        'options' => \CRM_Utils_Check::getSeverityList(),
      ],
      [
        'name' => 'is_visible',
        'title' => 'is visible',
        'description' => '0 if message has been hidden by the user',
        'data_type' => 'Boolean',
      ],
      [
        'name' => 'hidden_until',
        'title' => 'Hidden until',
        'description' => 'When will hidden message be visible again?',
        'data_type' => 'Date',
      ],
      [
        'name' => 'actions',
        'title' => 'Actions',
        'description' => 'List of actions user can perform',
        'data_type' => 'Array',
      ],
    ];
  }

}
