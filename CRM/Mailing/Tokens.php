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

use Civi\Api4\MailingGroup;

/**
 * Class CRM_Mailing_Tokens
 *
 * Generate "mailing.*" tokens.
 *
 * To activate these tokens, the TokenProcessor context must specify either
 * "mailingId" (int) or "mailing" (CRM_Mailing_BAO_Mailing).
 */
class CRM_Mailing_Tokens extends \Civi\Token\AbstractTokenSubscriber {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('mailing', [
      'id' => ts('Mailing ID'),
      'key' => ts('Mailing Key'),
      'name' => ts('Mailing Name'),
      'group' => ts('Mailing Group(s)'),
      'subject' => ts('Mailing Subject'),
      'viewUrl' => ts('Mailing URL (View)'),
      'editUrl' => ts('Mailing URL (Edit)'),
      'scheduleUrl' => ts('Mailing URL (Schedule)'),
      'html' => ts('Mailing HTML'),
      'approvalStatus' => ts('Mailing Approval Status'),
      'approvalNote' => ts('Mailing Approval Note'),
      'approveUrl' => ts('Mailing Approval URL'),
      'creator' => ts('Mailing Creator (Name)'),
      'creatorEmail' => ts('Mailing Creator (Email)'),
    ]);
  }

  /**
   * @inheritDoc
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    return !empty($processor->context['mailingId']) || !empty($processor->context['mailing'])
      || in_array('mailingId', $processor->context['schema']) || in_array('mailing', $processor->context['schema']);
  }

  /**
   * Prefetch tokens.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   *
   * @return array
   * @throws \Exception
   */
  public function prefetch(\Civi\Token\Event\TokenValueEvent $e) {
    $processor = $e->getTokenProcessor();
    $mailing = isset($processor->context['mailing'])
      ? $processor->context['mailing']
      : CRM_Mailing_BAO_Mailing::findById($processor->context['mailingId']);

    return [
      'mailing' => $mailing,
    ];
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    $row->format('text/plain')->tokens($entity, $field,
      (string) $this->getMailingTokenReplacement($field, $prefetch['mailing']->id ?? NULL, $prefetch['mailing']));
  }

  /**
   * @param string $token
   * @param int|null $id
   * @param \CRM_Mailing_BAO_Mailing $mailing
   *
   * @return string
   */
  private function getMailingTokenReplacement($token, ?int $id, $mailing) {
    switch ($token) {
      // CRM-7663

      case 'id':
        $value = $id ?: 'undefined';
        break;

      // Key is the ID, or the hash when the hash URLs setting is enabled
      case 'key':
        $value = $id;
        if ($hash = CRM_Mailing_BAO_Mailing::getMailingHash($value)) {
          $value = $hash;
        }
        break;

      case 'name':
        $value = $mailing ? $mailing->name : 'Mailing Name';
        break;

      case 'group':
        $groups = $id ? ($this->getGroupNames($id) ?? []) : ['Mailing Groups'];
        $value = implode(', ', $groups);
        break;

      case 'subject':
        $value = $mailing->subject;
        break;

      case 'viewUrl':
        $mailingKey = $id;
        if ($hash = CRM_Mailing_BAO_Mailing::getMailingHash($mailingKey)) {
          $mailingKey = $hash;
        }
        $value = CRM_Utils_System::url('civicrm/mailing/view',
          "reset=1&id={$mailingKey}",
          TRUE, NULL, FALSE, TRUE
        );
        break;

      case 'editUrl':
      case 'scheduleUrl':
        // Note: editUrl and scheduleUrl used to be different, but now there's
        // one screen which can adapt based on permissions (in workflow mode).
        $value = CRM_Utils_System::url('civicrm/mailing/send',
          "reset=1&mid={$id}&continue=true",
          TRUE, NULL, FALSE, TRUE
        );
        break;

      case 'html':
        $page = new CRM_Mailing_Page_View();
        $value = $page->run($id, NULL, FALSE, TRUE);
        break;

      case 'approvalStatus':
        $value = CRM_Core_PseudoConstant::getLabel('CRM_Mailing_DAO_Mailing', 'approval_status_id', $mailing->approval_status_id);
        break;

      case 'approvalNote':
        $value = $mailing->approval_note;
        break;

      case 'approveUrl':
        $value = CRM_Utils_System::url('civicrm/mailing/approve',
          "reset=1&mid={$id}",
          TRUE, NULL, FALSE, TRUE
        );
        break;

      case 'creator':
        $value = CRM_Contact_BAO_Contact::displayName($mailing->created_id);
        break;

      case 'creatorEmail':
        $value = CRM_Contact_BAO_Contact::getPrimaryEmail($mailing->created_id);
        break;

      default:
        $value = "{mailing.$token}";
        break;
    }

    return $value;
  }

  /**
   * Return a list of group names for this mailing.  Does not work with
   * prior-mailing targets.
   *
   * @param int $id
   *
   * @return array
   *   Names of groups receiving this mailing
   * @throws \CRM_Core_Exception
   */
  private function getGroupNames(int $id): array {
    // This bypasses permissions to maintain compatibility with the SQL it replaced.
    $mailingGroups = MailingGroup::get(FALSE)
      ->addSelect('group.frontend_title')
      ->addJoin('Group AS group', 'LEFT', ['entity_id', '=', 'group.id'])
      ->addWhere('mailing_id', '=', $id)
      ->addWhere('entity_table', '=', 'civicrm_group')
      ->addWhere('group_type', '=', 'Include')
      ->execute();

    $groupNames = [];

    foreach ($mailingGroups as $group) {
      $name = $group['group.frontend_title'];
      if ($name) {
        $groupNames[] = $name;
      }
    }

    return $groupNames;
  }

}
