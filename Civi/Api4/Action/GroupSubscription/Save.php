<?php

namespace Civi\Api4\Action\GroupSubscription;

use Civi\Api4\Generic\Result;

/**
 * Process API saves the group subscriptions for a contact.
 *
 * @inheritDoc
 * @package Civi\Api4\Action\GroupSubscription
 */
class Save extends \Civi\Api4\Generic\BasicSaveAction {

  /**
   * Main contact to keep with merged values
   *
   * @var int
   * @required
   */
  protected $contactId;

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $groups = self::process($this->contactId, $this->records);

    $result[] = $groups;
  }

  /**
   * Function to process groups
   *
   * @param int $contactId
   * @param array $submittedGroups
   *
   * @return void
   */
  public static function process($contactId, $submittedGroups) {
    // submitted Array ( [group_2] => 1, [group_3] => 0,[group_4] => 1 )

    // TOFIX: need to fix the flow via FB
    return;

    // get the current groups for this contact
    $currentGroups = \Civi\Api4\GroupContact::get(FALSE)
      ->addSelect('group_id', 'status')
      ->addWhere('contact_id', '=', $contactId)
      ->execute();

    // track group changes
    $groupChanges = [];

    // loop through submitted groups
    foreach ($submittedGroups as $field => $value) {
      // check if it's a group field
      if (strpos($field, 'group_') === FALSE) {
        continue;
      }

      // get group id
      $groupId = str_replace('group_', '', $field);

      // check if the group is in the current groups
      $currentGroupInfo = [];
      foreach ($currentGroups as $currentGroup) {
        if ($currentGroup['group_id'] == $groupId) {
          $currentGroupInfo = $currentGroup;
          break;
        }
      }

      // check if double opt in is enabled
      $groupStatus = 'Added';
      $contactPrimaryEmail = '';
      if (!empty($submittedGroups['enable_double_optin'])) {
        $groupStatus = 'Pending';
        $contactPrimaryEmail = self::getContactPrimaryEmail($contactId);
      }

      // check the sumbitted value
      // if the value is 1, and not part of current groups, add the group
      // if the value is 1, and part of current groups, update group status to 'Added'
      if ($value == 1) {
        // add the group
        if (empty($currentGroupInfo)) {
          self::addGroup($contactId, $groupId, $groupStatus, $contactPrimaryEmail);
          $groupChanges[$groupId] = ['new' => 'Added'];
        }
        // update group status to 'Added'
        elseif ($currentGroupInfo['status'] != 'Added') {
          self::updateGroupStatus($contactId, $groupId, $groupStatus, $contactPrimaryEmail);
          $groupChanges[$groupId] = [
            'prev' => $currentGroupInfo['status'],
            'new' => 'Added',
          ];
        }
        // else do nothing
      }
      else {
        // if the value is 0, and part of current groups, update group status to 'Removed'
        if (!empty($currentGroupInfo) && $currentGroupInfo['status'] != 'Removed') {
          self::updateGroupStatus($contactId, $groupId, 'Removed');
          $groupChanges[$groupId] = [
            'prev' => $currentGroupInfo['status'],
            'new' => 'Removed',
          ];
        }
        // if the value is 0, and not part of current groups, do nothing
      }

    }

    return $groupChanges;
  }

  /**
   * Function to add contact to group
   *
   * @param int $contactId
   * @param int $groupId
   * @param string $status
   * @param string $email
   *
   * @return void
   */
  public static function addGroup($contactId, $groupId, $status, $email) {
    if ($status == 'Pending') {
      // this means double opt in is enabled
      self::triggerDoubleOptin($contactId, $groupId, $email);
    }
    else {
      // add contact to group
      \Civi\Api4\GroupContact::create(FALSE)
        ->addValue('group_id', $groupId)
        ->addValue('contact_id', $contactId)
        ->addValue('status:name', $status)
        ->execute();
    }
  }

  /**
   * Function to update contact group status
   *
   * @param int $contactId
   * @param int $groupId
   *
   * @return void
   */
  public static function updateGroupStatus($contactId, $groupId, $status = 'Removed', $email = NULL) {
    if ($status == 'Pending') {
      // this means double opt in is enabled
      self::triggerDoubleOptin($contactId, $groupId, $email);
    }
    else {
      // update group status to removed
      \Civi\Api4\GroupContact::update(FALSE)
        ->addValue('status:name', $status)
        ->addWhere('group_id', '=', $groupId)
        ->addWhere('contact_id', '=', $contactId)
        ->execute();
    }
  }

  /**
   * Function to trigger double optin process
   *
   * @param int $contactId
   * @param int $groupId
   * @param string $email
   *
   * @return void
   */
  public static function triggerDoubleOptin($contactId, $groupId, $email) {
    // call mailing event subscribe api
    civicrm_api3('MailingEventSubscribe', 'create', [
      'contact_id' => $contactId,
      'group_id' => $groupId,
      'email' => $email,
    ]);
  }

  /**
   * Function to get contact primary email
   *
   * @param int $contactId
   *
   * @return string
   */
  public static function getContactPrimaryEmail($contactId) {
    $emails = \Civi\Api4\Email::get(FALSE)
      ->addSelect('email')
      ->addWhere('contact_id', '=', $contactId)
      ->addWhere('is_primary', '=', TRUE)
      ->setLimit(1)
      ->execute();

    return $emails[0]['email'];
  }

}
