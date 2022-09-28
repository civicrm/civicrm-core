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

namespace Civi\Api4\Action\System;

/**
 * Retrieve system notices, warnings, errors, etc.
 * @method bool getIncludeDisabled()
 */
class Check extends \Civi\Api4\Generic\BasicGetAction {

  /**
   * Run checks that have been explicitly disabled (default false)
   * @var bool
   */
  protected $includeDisabled = FALSE;

  /**
   * @param bool $includeDisabled
   * @return Check
   */
  public function setIncludeDisabled(bool $includeDisabled): Check {
    $this->includeDisabled = $includeDisabled;
    return $this;
  }

  protected function getRecords() {
    $messages = $names = [];

    // Filtering by name relies on the component check rather than the api arrayQuery
    // @see \CRM_Utils_Check_Component::isCheckable
    foreach ($this->where as $i => $clause) {
      if ($clause[0] == 'name' && !empty($clause[2]) && in_array($clause[1], ['=', 'IN'], TRUE)) {
        $names = (array) $clause[2];
        unset($this->where[$i]);
        break;
      }
    }

    foreach (\CRM_Utils_Check::checkStatus($names, $this->includeDisabled) as $message) {
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
        'name' => 'severity_id',
        'title' => 'Severity ID',
        'description' => 'Integer representation of Psr\Log\LogLevel',
        'data_type' => 'Integer',
        'options' => \CRM_Utils_Check::getSeverityOptions(),
      ],
      [
        'name' => 'is_visible',
        'title' => 'is visible',
        'description' => 'FALSE if message has been hidden by the user',
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
