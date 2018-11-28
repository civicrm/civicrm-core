<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

namespace Civi\Api4\Action\Entity;

use Civi\Api4\Generic\Result;
use \Civi\Api4\Action\GetFields as GenericGetFields;

/**
 * Get fields for all entities
 */
class GetFields extends GenericGetFields {

  public function _run(Result $result) {
    $action = $this->getAction();
    $includeCustom = $this->getIncludeCustom();
    $entities = \Civi\Api4\Entity::get()->execute();
    foreach ($entities as $entity) {
      $entity = ((array) $entity) + ['fields' => []];
      // Prevent infinite recursion
      if ($entity['name'] != 'Entity') {
        $entity['fields'] = (array) civicrm_api4($entity['name'], 'getFields', ['action' => $action, 'includeCustom' => $includeCustom, 'select' => $this->select]);
      }
      $result[] = $entity;
    }
  }

  /**
   * @inheritDoc
   */
  public function getParamInfo($param = NULL) {
    $info = parent::getParamInfo($param);
    if (!$param) {
      // This action doesn't actually let you select fields.
      unset($info['fields']);
    }
    return $info;
  }

}
