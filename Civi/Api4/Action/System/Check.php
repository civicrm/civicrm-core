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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
