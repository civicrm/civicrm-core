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
 * CaseContact BridgeEntity.
 *
 * This connects a client to a case.
 *
 * @searchable bridge
 * @see \Civi\Api4\Case
 * @since 5.37
 * @package Civi\Api4
 */
class CaseContact extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

  protected static function getEntityTitle(bool $plural = FALSE): string {
    return $plural ? ts('Case Clients') : ts('Case Client');
  }

  /**
   * @return array
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $info['bridge_title'] = ts('Clients');
    $info['bridge'] = [
      'case_id' => [
        'to' => 'contact_id',
        'description' => ts('Cases with this contact as a client'),
      ],
      'contact_id' => [
        'label' => ts('Clients'),
        'to' => 'case_id',
        'description' => ts('Clients for this case'),
      ],
    ];
    return $info;
  }

}
