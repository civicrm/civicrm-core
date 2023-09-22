<?php

namespace Civi\Api4;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * OAuth Access Tokens.
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/oauth/#model-token
 *
 * @primaryKey cardinal
 * @searchable none
 * @since 5.67
 * @package Civi\Api4
 */
class OAuthSessionToken extends Generic\AbstractEntity {

  const ENTITY = 'OAuthSessionToken';

  public static function create($checkPermissions = TRUE): Generic\BasicCreateAction {
    $action = new Generic\BasicCreateAction(
      self::ENTITY,
      __FUNCTION__,
      function ($item, $createAction) {
        $session = \CRM_Core_Session::singleton();
        $all = $session->get('OAuthSessionTokens') ?? [];
        $all[] = &$item;
        $item['cardinal'] = array_key_last($all);
        $session->set('OAuthSessionTokens', $all);
        return $item;
      });
    return $action->setCheckPermissions($checkPermissions);
  }

  public static function deleteAll($checkPermissions = TRUE): AbstractAction {
    return (new class(self::ENTITY, __FUNCTION__) extends AbstractAction {

      public function _run(Result $result) {
        $result->exchangeArray(OAuthSessionToken::get());
        \CRM_Core_Session::singleton()->set('OAuthSessionTokens');
      }

    })->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE): Generic\BasicGetAction {
    $action = new Generic\BasicGetAction(self::ENTITY, __FUNCTION__, function () {
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
    $action = new Generic\BasicGetFieldsAction(self::ENTITY, __FUNCTION__, function () {
      return [
        [
          'name' => 'client_id',
          'required' => TRUE,
        ],
        ['name' => 'cardinal'],
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

  protected static function getEntityTitle($plural = FALSE) {
    return $plural ? ts('OAuth Session Tokens') : ts('OAuth Session Token');
  }

}
