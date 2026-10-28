<?php
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_BAO_SearchParamSet extends CRM_Afform_DAO_SearchParamSet implements \Civi\Core\HookInterface {
  use CRM_Core_OwnershipAccessTrait;

  protected static function getOwnerContactIdField(): string {
    return 'created_by';
  }

  protected static function checkOwnerAdminPermission(?int $userID = NULL): bool {
    return CRM_Core_Permission::check([['administer afform', 'administer CiviCRM data']], $userID);
  }

}
