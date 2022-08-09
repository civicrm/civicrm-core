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
 * Records "packaged" by extensions and managed by CiviCRM.
 *
 * Extensions can package records in a declarative fashion, typically in `.mgd.php` files.
 *
 * @searchable secondary
 * @see https://civicrm.org/blog/totten/api-and-art-installation
 * @since 5.42
 * @package Civi\Api4
 */
class Managed extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Managed\Reconcile
   */
  public static function reconcile($checkPermissions = TRUE) {
    return (new Action\Managed\Reconcile(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
