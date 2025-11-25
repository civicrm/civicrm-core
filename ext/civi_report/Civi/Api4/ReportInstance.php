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
namespace Civi\Api4;

/**
 * ReportInstance entity.
 *
 * @searchable secondary
 * @since 5.58
 * @package Civi\Api4
 */
class ReportInstance extends Generic\DAOEntity {

  use Generic\Traits\ManagedEntity;

  /**
   * Specify the permissions to access ReportInstance.
   *
   * Function exists to set the get permission on report instance get to access CiviCRM.
   *
   *  This allows the permission configured on the report to be implemented in
   *  the selectWhere hook.
   *
   *  Note this might be better as TRUE rather than access CiviCRM but the latter
   *  feels safer given we are deprecating civi-report and should err on the
   *  side of stricter security.
   *
   * @return array[]
   */
  public static function permissions(): array {
    return [
      'get' => [
        'access CiviCRM',
      ],
      // @todo - set criteria for create & update - save criteria
    ];
  }

}
