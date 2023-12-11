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

namespace Civi\Api4\Service\Spec\Provider;

use CRM_Standaloneusers_ExtensionUtil as E;
use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class UserSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    // Write-only `password` field
    if (in_array($spec->getAction(), ['create', 'update', 'save'], TRUE)) {
      $password = new FieldSpec('password', 'User', 'String');
      $password->setTitle(E::ts('New password'));
      $password->setDescription(E::ts('Provide a new password for this user.'));
      $password->setInputType('Password');
      $spec->addFieldSpec($password);
    }
    // Virtual "roles" field is a facade to the FK values in `civicrm_user_role`.
    // It makes forms easier to write by acting as if user.roles were a simple field on the User record.
    $roles = new FieldSpec('roles', 'User', 'Array');
    $roles->setTitle(E::ts('Roles'));
    $roles->setDescription(E::ts('Role ids belonging to this user.'));
    $roles->setInputType('Select');
    $roles->setInputAttrs(['multiple' => TRUE]);
    $roles->setSerialize(\CRM_Core_DAO::SERIALIZE_COMMA);
    $roles->setSuffixes(['id', 'name', 'label']);
    $roles->setOptionsCallback([__CLASS__, 'getRolesOptions']);
    $roles->setColumnName('id');
    $roles->setSqlRenderer([__CLASS__, 'getRolesSql']);
    $spec->addFieldSpec($roles);
  }

  public static function getRolesOptions(): array {
    $roles = \Civi::cache('metadata')->get('user_roles');
    if (!$roles) {
      $select = \CRM_Utils_SQL_Select::from('civicrm_role')
        ->select(['id', 'name', 'label'])
        ->where('is_active = 1')
        ->where('name != "everyone"')
        ->orderBy('label')
        ->toSQL();
      $roles = \CRM_Core_DAO::executeQuery($select)->fetchAll();
      \Civi::cache('metadata')->set('user_roles', $roles);
    }
    return $roles;
  }

  public static function getRolesSql(array $field, Api4SelectQuery $query): string {
    return "(SELECT GROUP_CONCAT(ur.role_id) FROM civicrm_user_role ur WHERE ur.user_id = {$field['sql_name']})";
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'User';
  }

}
