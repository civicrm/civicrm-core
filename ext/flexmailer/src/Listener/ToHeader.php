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
namespace Civi\FlexMailer\Listener;

use Civi\FlexMailer\Event\ComposeBatchEvent;

class ToHeader extends BaseListener {

  /**
   * Inject the "To:" header.
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   */
  public function onCompose(ComposeBatchEvent $e) {
    if (!$this->isActive()) {
      return;
    }

    $names = $this->getContactNames($e->getTasks());
    foreach ($e->getTasks() as $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */

      $task->setMailParam('toEmail', $task->getAddress());

      if (isset($names[$task->getContactId()])) {
        $task->setMailParam('toName', $names[$task->getContactId()]);
      }
      else {
        $task->setMailParam('toName', '');
      }
    }
  }

  /**
   * Lookup contact names as a batch.
   *
   * @param array<FlexMailerTask> $tasks
   * @return array
   *   Array(int $contactId => string $displayName).
   */
  protected function getContactNames($tasks) {
    $ids = array();
    foreach ($tasks as $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */
      $ids[$task->getContactId()] = $task->getContactId();
    }

    $ids = array_filter($ids, 'is_numeric');
    if (empty($ids)) {
      return array();
    }

    $idString = implode(',', $ids);

    $query = \CRM_Core_DAO::executeQuery(
      "SELECT id, display_name FROM civicrm_contact WHERE id in ($idString)");
    $names = array();
    while ($query->fetch()) {
      $names[$query->id] = $query->display_name;
    }
    return $names;
  }

}
