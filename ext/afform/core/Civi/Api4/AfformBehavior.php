<?php
namespace Civi\Api4;

/**
 * Form Builder Behaviors.
 *
 * Behaviors provide special functionality for different types of entities.
 * Provided by the Afform: Core Runtime extension.
 *
 * @searchable secondary
 * @since 5.56
 * @package Civi\Api4
 */
class AfformBehavior extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\AfformBehavior\Get
   */
  public static function get(bool $checkPermissions = TRUE) {
    return (new Action\AfformBehavior\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'get' => ['manage own afform'],
    ];
  }

  /**
   * @inheritDoc
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [
        [
          'name' => 'key',
          'data_type' => 'String',
          'description' => 'Unique identifier in dashed-format, name of entity attribute for selected mode',
        ],
        [
          'name' => 'attributes',
          'data_type' => 'Array',
          'description' => 'Array of attributes added to the entity by this behavior, keyed by attribute name',
        ],
        [
          'name' => 'title',
          'data_type' => 'String',
          'description' => 'Localized title displayed on admin screen',
        ],
        [
          'name' => 'description',
          'data_type' => 'String',
          'description' => 'Optional localized description displayed on admin screen',
        ],
        [
          'name' => 'template',
          'data_type' => 'String',
          'description' => 'Optional template for configuring the behavior in the AfformGuiEditor',
        ],
        [
          'name' => 'entities',
          'data_type' => 'Array',
          'description' => 'Afform entities this behavior supports',
        ],
        [
          'name' => 'modes',
          'data_type' => 'Array',
          'description' => 'Nested array of supported behavior modes, keyed by entity name',
        ],
        [
          'name' => 'default_mode',
          'data_type' => 'String',
          'description' => 'If set then mode will not be de-selectable',
        ],
      ];
    }))->setCheckPermissions(TRUE);
  }

}
