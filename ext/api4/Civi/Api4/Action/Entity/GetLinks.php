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

use Civi\Api4\Generic\AbstractAction;
use \CRM_Core_DAO_AllCoreTables as AllTables;
use Civi\Api4\Generic\Result;

/**
 * Get a list of FK links between entities
 */
class GetLinks extends AbstractAction {

  public function _run(Result $result) {
    /** @var \Civi\Api4\Service\Schema\SchemaMap $schema */
    $schema = \Civi::container()->get('schema_map');
    foreach ($schema->getTables() as $table) {
      $entity = AllTables::getBriefName(AllTables::getClassForTable($table->getName()));
      // Since this is an api function, exclude tables that don't have an api
      if (class_exists('\Civi\Api4\\' . $entity)) {
        $item = [
          'entity' => $entity,
          'table' => $table->getName(),
          'links' => [],
        ];
        foreach ($table->getTableLinks() as $link) {
          $link = $link->toArray();
          $link['entity'] = AllTables::getBriefName(AllTables::getClassForTable($link['targetTable']));
          $item['links'][] = $link;
        }
        $result[] = $item;
      }
    }
    return $result;
  }

}
