<?php
namespace Civi\Api4;

/**
 * GroupSubscription entity.
 *
 * @package Civi\Api4
 */
class GroupSubscription extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\GroupSubscription\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\GroupSubscription\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\GroupSubscription\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\GroupSubscription\Save(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function ($getFieldsAction) {
      $fields = [
        [
          'name' => 'contact_id',
          'data_type' => 'Integer',
          'title' => ts('Contact ID'),
          'required' => TRUE,
          'fk_entity' => 'Contact',
          'fk_column' => 'id',
          'input_type' => 'EntityRef',
          'label' => 'Contact',
        ],
      ];

      // loop through pubic group and build the checkboxes
      // get all public groups
      $publicGroups = \Civi\Api4\Group::get(FALSE)
        ->addWhere('visibility', '=', 'Public Pages')
        ->execute();

      foreach ($publicGroups as $group) {

        $title = $group['frontend_title'] ?? $group['title'];
        $description = $group['frontend_description'] ?? $group['description'];
        $label = $title;
        if ($description) {
          $label .= " - {$description}";
        }

        $fields[] = [
          'name' => "group_" . $group['id'],
          'data_type' => 'Boolean',
          'title' => $group['title'],
          'required' => FALSE,
          "input_type" => "CheckBox",
          'label' => $label,
        ];
      }

      return $fields;
    }))->setCheckPermissions($checkPermissions);
  }

}
