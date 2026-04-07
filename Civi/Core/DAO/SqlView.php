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

namespace Civi\Core\DAO;

/**
 * DAO stub for Api4 SqlView.
 */
class SqlView extends \CRM_Core_DAO {

  public static function tableHasBeenAdded(): bool {
    // Sql views don't have a real table; the view is created by `Civi\Api4\Generic\SqlView::_on_civi_api4_entityTypes`
    return TRUE;
  }

}
