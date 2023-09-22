<?php

namespace Civi\Api4;

/**
 * OAuth Access Tokens stored in the session
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/oauth/#model-token
 *
 * @primaryKey cardinal
 * @searchable none
 * @since 5.67
 * @package Civi\Api4
 */
class OAuthSessionToken extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\BasicCreateAction
   */
  public static function create($checkPermissions = TRUE): Generic\BasicCreateAction {
    $action = new Generic\BasicCreateAction(
      static::getEntityName(),
      __FUNCTION__,
      function ($item) {
        $session = \CRM_Core_Session::singleton();
        $allTokens = $session->get('OAuthSessionTokens') ?? [];
        $cardinal = ($session->get('OAuthSessionTokenCount') ?? 0) + 1;
        $item['cardinal'] = $cardinal;
        $allTokens[$cardinal] = $item;
        $session->set('OAuthSessionTokens', $allTokens);
        $session->set('OAuthSessionTokenCount', $cardinal);
        return $item;
      });
    return $action->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicBatchAction
   */
  public static function delete($checkPermissions = TRUE): Generic\BasicBatchAction {
    $action = new Action\OAuthSessionToken\Delete(static::getEntityName(), __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE): Generic\BasicGetAction {
    $action = new Generic\BasicGetAction(static::getEntityName(), __FUNCTION__, function () {
      $session = \CRM_Core_Session::singleton();
      return $session->get('OAuthSessionTokens') ?? [];
    });
    return $action->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    $action = new Generic\BasicGetFieldsAction(static::getEntityName(), __FUNCTION__, function () {
      return [
        [
          'name' => 'client_id',
          'required' => TRUE,
          'data_type' => 'Integer',
          'fk_entity' => 'OAuthClient',
        ],
        [
          'name' => 'cardinal',
          'readonly' => TRUE,
          'data_type' => 'Integer',
          'description' => 'Order in which the token was created within the current session. Unique within the session.',
        ],
        ['name' => 'grant_type'],
        ['name' => 'tag'],
        ['name' => 'scopes'],
        ['name' => 'token_type'],
        ['name' => 'access_token'],
        ['name' => 'refresh_token'],
        ['name' => 'expires'],
        ['name' => 'storage'],
        ['name' => 'resource_owner_name'],
        ['name' => 'resource_owner'],
        ['name' => 'raw'],
      ];
    });
    return $action->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      "meta" => ["access CiviCRM"],
      "default" => ["administer CiviCRM data"],
    ];
  }

  protected static function getEntityTitle(bool $plural = FALSE): string {
    return $plural ? ts('OAuth Session Tokens') : ts('OAuth Session Token');
  }

}
