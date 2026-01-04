<?php

namespace Civi\Api4;

class OAuthProvider extends Generic\AbstractEntity {

  const TTL = 600;

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    $action = new Action\OAuthProvider\GetProviders('OAuthProvider', __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    $action = new Generic\BasicGetFieldsAction('OAuthProvider', __FUNCTION__, function () {
      return [
        [
          'name' => 'name',
        ],
        [
          'name' => 'title',
        ],
        [
          'name' => 'class',
        ],
        [
          'name' => 'options',
        ],
        [
          'name' => 'tags',
          'data_type' => 'Array',
        ],
        [
          'name' => 'contactTemplate',
          // TODO: Migrate to templates['Contact']
        ],
        [
          'name' => 'mailSettingsTemplate',
          // TODO: Migrate to templates['MailStore']
        ],
        [
          'name' => 'templates',
          'description' => 'Open-ended list of templates. Generally, these will be used after an OAuth connection is established. Details vary by tag/workflow.',
        ],
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
      "get" => ["access CiviCRM"],
      "default" => ["administer CiviCRM"],
    ];
  }

}
