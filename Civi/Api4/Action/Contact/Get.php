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

namespace Civi\Api4\Action\Contact;

use Civi\Api4\Utils\CoreUtil;

/**
 * @inheritDoc
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {

  public function getWhere(): array {
    $where = parent::getWhere();
    foreach ($where as $id => $item) {
      if ($item[0] === 'contact_sub_type') {
        // See CRM/Contact/BAO/Query.php: includeContactSubTypes()
        // $op = str_replace('IN', 'LIKE', $op);
        // $op = str_replace('=', 'LIKE', $op);
        // $op = str_replace('!', 'NOT ', $op);
        if ($item[1] === '=') {
          $item[1] = 'LIKE';
          $item[2] = '%' . $item[2] . '%';
        }
        $where[$id] = $item;
      }
    }
    return $where;
  }

}
