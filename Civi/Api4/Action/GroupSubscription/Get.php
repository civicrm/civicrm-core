<?php

namespace Civi\Api4\Action\GroupSubscription;

/**
 * Get API gets the group subscriptions for a contact.
 *
 * @inheritDoc
 * @package Civi\Api4\Action\GroupSubscription
 */
class Get extends \Civi\Api4\Generic\BasicGetAction {

  protected function getRecords(): array {
    $result = [];
    // get contact id
    $contactIds = $this->_itemsToGet('contact_id');

    // get all contact groups
    $groupContactApi = \Civi\Api4\GroupContact::get($this->getCheckPermissions())
      ->addWhere('group_id.is_active', '=', TRUE)
      ->addWhere('group_id.is_hidden', '=', FALSE)
      ->addWhere('status', '=', 'Added')
      ->addSelect('group_id.name', 'contact_id');
    if ($contactIds) {
      $groupContactApi->addWhere('contact_id', 'IN', $contactIds);
      foreach ($contactIds as $contactId) {
        $result[$contactId]['contact_id'] = $contactId;
      }
    }
    else {
      $groupContactApi->addOrderBy('contact_id');
    }
    $currentContactGroups = $groupContactApi->execute();

    foreach ($currentContactGroups as $contactGroup) {
      $result[$contactGroup['contact_id']]['contact_id'] = $contactGroup['contact_id'];
      $result[$contactGroup['contact_id']][$contactGroup['group_id.name']] = TRUE;
    }
    return $result;
  }

}
