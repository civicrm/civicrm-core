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

use Civi\Api4\Generic\Result;

/**
 * Set the value of one or more CiviCRM settings.
 *
 * @method array getValues
 * @method $this setValues(array $value)
 * @method $this addValue(string $name, mixed $value)
 */
class Set extends AbstractSettingAction {

  /**
   * Setting names/values to set.
   *
   * @var mixed
   * @required
   */
  protected $values = [];

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @param \Civi\Core\SettingsBag $settingsBag
   * @param array $meta
   * @param int $domain
   * @throws \Exception
   */
  protected function processSettings(Result $result, $settingsBag, $meta, $domain) {
    foreach ($this->values as $name => $value) {
      if (isset($value) && !empty($meta[$name]['serialize'])) {
        $value = \CRM_Core_DAO::serializeField($value, $meta[$name]['serialize']);
      }
      $settingsBag->set($name, $value);
      $result[] = [
        'name' => $name,
        'value' => $this->values[$name],
        'domain_id' => $domain,
      ];
    }
  }

}
