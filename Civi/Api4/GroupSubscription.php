<?php
namespace Civi\Api4;

/**
 * Group Subscriptions.
 *
 * This API is used to facilitate opt-in and opt-out of groups on forms.
 * "GroupSubscription" is not a real entity in the database; this API
 * wraps the GroupContact and MailingEventSubscribe entities for convenience.
 *
 * @searchable none
 * @since 5.77
 * @package Civi\Api4
 */
class GroupSubscription extends Generic\AbstractEntity {

  public static function get($checkPermissions = TRUE) {
    return (new Action\GroupSubscription\Get('GroupSubscription', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function create($checkPermissions = TRUE) {
    return (new Action\GroupSubscription\Create('GroupSubscription', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function save($checkPermissions = TRUE) {
    return (new Action\GroupSubscription\Save('GroupSubscription', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function update($checkPermissions = TRUE) {
    return (new Action\GroupSubscription\Update('GroupSubscription', __FUNCTION__))
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
          'label' => ts('Contact'),
        ],
      ];

      // Loop through visible groups and add checkbox fields
      $groups = Action\GroupSubscription\Save::getEnabledGroups();

      foreach ($groups as $group) {
        $fields[] = [
          'name' => $group['name'],
          'data_type' => 'Boolean',
          'title' => $group['title'],
          'required' => FALSE,
          'default_value' => FALSE,
          'input_type' => 'CheckBox',
          'options' => [
            TRUE => ts('Subscribe'),
            FALSE => ts('Unsubscribe'),
          ],
          'label' => $group['frontend_title'] ?: $group['title'],
          'description' => $group['frontend_description'] ?: $group['description'],
        ];
      }

      return $fields;
    }))->setCheckPermissions($checkPermissions);
  }

  public static function getInfo() {
    $info = parent::getInfo();
    $info['primary_key'] = ['contact_id'];
    return $info;
  }

  protected static function getEntityTitle(bool $plural = FALSE): string {
    return $plural ? ts('Group Subscriptions') : ts('Group Subscription');
  }

  public static function permissions() {
    // Permission checks are passed-through to the underlying APIs
    return [
      'default' => [],
    ];
  }

}
