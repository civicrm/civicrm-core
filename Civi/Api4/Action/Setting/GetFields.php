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

namespace Civi\Api4\Action\Setting;

/**
 * Get information about CiviCRM settings.
 *
 * @method int getDomainId
 * @method $this setDomainId(int $domainId)
 */
class GetFields extends \Civi\Api4\Generic\BasicGetFieldsAction {

  /**
   * Domain id of settings. Leave NULL for default domain.
   *
   * @var int
   */
  protected $domainId;

  protected function getRecords() {
    // TODO: Waiting for filter handling to get fixed in core
    // $names = $this->_itemsToGet('name');
    // $filter = $names ? ['name' => $names] : [];
    $filter = [];
    return \Civi\Core\SettingsMetadata::getMetadata($filter, $this->domainId, $this->loadOptions);
  }

  public function fields() {
    return [
      [
        'name' => 'name',
        'data_type' => 'String',
      ],
      [
        'name' => 'title',
        'data_type' => 'String',
      ],
      [
        'name' => 'description',
        'data_type' => 'String',
      ],
      [
        'name' => 'help_text',
        'data_type' => 'String',
      ],
      [
        'name' => 'default',
        'data_type' => 'String',
      ],
      [
        'name' => 'pseudoconstant',
        'data_type' => 'String',
      ],
      [
        'name' => 'options',
        'data_type' => 'Array',
      ],
      [
        'name' => 'group_name',
        'data_type' => 'String',
      ],
      [
        'name' => 'group',
        'data_type' => 'String',
      ],
      [
        'name' => 'html_type',
        'data_type' => 'String',
      ],
      [
        'name' => 'add',
        'data_type' => 'String',
      ],
      [
        'name' => 'serialize',
        'data_type' => 'Integer',
      ],
      [
        'name' => 'data_type',
        'data_type' => 'Integer',
      ],
    ];
  }

}
