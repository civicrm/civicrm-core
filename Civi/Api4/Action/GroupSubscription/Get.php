<?php

namespace Civi\Api4\Action\GroupSubscription;

use Civi\Api4\Generic\Result;

/**
 * Get API gets the group subscriptions for a contact.
 *
 * @inheritDoc
 * @package Civi\Api4\Action\GroupSubscription
 */
class Get extends \Civi\Api4\Generic\BasicGetAction {

  public function _run(Result $result) {
    // get contact id
    $contactId = $this->_itemsToGet('contact_id')[0];

    // get all public groups
    $publicGroups = \Civi\Api4\Group::get(FALSE)
      ->addSelect('id', 'title')
      ->addWhere('visibility', '=', 'Public Pages')
      ->addWhere('is_active', '=', 1)
      ->execute();

    // get all contact groups
    $currentContactGroups = \Civi\Api4\GroupContact::get(FALSE)
      ->addSelect('group_id', 'status')
      ->addWhere('contact_id', '=', $contactId)
      ->execute();

    // build data for rendering
    $groups = [];
    foreach ($publicGroups as $publicGroup) {
      $contactIsPartOfGroup = FALSE;
      foreach ($currentContactGroups as $contactGroup) {
        if ($contactGroup['group_id'] == $publicGroup['id'] && $contactGroup['status'] == 'Added') {
          $contactIsPartOfGroup = TRUE;
          break;
        }
      }

      $groups['group_' . $publicGroup['id']] = $contactIsPartOfGroup;
    }

    $result[] = [
      'id' => $contactId,
      'contact_id' => $contactId,
    ] + $groups;

  }

}
