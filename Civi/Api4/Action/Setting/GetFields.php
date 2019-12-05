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
