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
class CRM_Mailing_Tokens extends CRM_Core_EntityTokens {

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Mailing';
  }

  /**
   * Get bespoke mailing tokens.
   *
   * @return array
   */
  protected function getBespokeTokens(): array {
    return [
      'id' => ['title' => ts('Mailing ID'), 'name' => 'id', 'type' => 'calculated', 'audience' => 'user'],
      'key' => ['title' => ts('Mailing Key'), 'name' => 'key', 'type' => 'calculated', 'audience' => 'user'],
      'name' => ['title' => ts('Mailing Name'), 'name' => 'name', 'type' => 'calculated', 'audience' => 'user'],
      'group' => ['title' => ts('Mailing Group(s)'), 'name' => 'group', 'type' => 'calculated', 'audience' => 'user'],
      'subject' => ['title' => ts('Mailing Subject'), 'name' => 'subject', 'type' => 'calculated', 'audience' => 'user'],
      'viewUrl' => ['title' => ts('Mailing URL (View)'), 'name' => 'viewUrl', 'type' => 'calculated', 'audience' => 'user'],
      'editUrl' => ['title' => ts('Mailing URL (Edit)'), 'name' => 'editUrl', 'type' => 'calculated', 'audience' => 'user'],
      'scheduleUrl' => ['title' => ts('Mailing URL (Schedule)'), 'name' => 'scheduleUrl', 'type' => 'calculated', 'audience' => 'user'],
      'html' => ['title' => ts('Mailing HTML'), 'name' => 'html', 'type' => 'calculated', 'audience' => 'user'],
      'approvalStatus' => ['title' => ts('Mailing Approval Status'), 'name' => 'approvalStatus', 'type' => 'calculated', 'audience' => 'user'],
      'approvalNote' => ['title' => ts('Mailing Approval Note'), 'name' => 'approvalNote', 'type' => 'calculated', 'audience' => 'user'],
      'approveUrl' => ['title' => ts('Mailing Approval URL'), 'name' => 'approveUrl', 'type' => 'calculated', 'audience' => 'user'],
      'creator' => ['title' => ts('Mailing Creator (Name)'), 'name' => 'creator', 'type' => 'calculated', 'audience' => 'user'],
      'creatorEmail' => ['title' => ts('Mailing Creator (Email)'), 'name' => 'creatorEmail', 'type' => 'calculated', 'audience' => 'user'],
    ];
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
   * @return array|null
   * @throws \Exception
   */
  public function prefetch(\Civi\Token\Event\TokenValueEvent $e): ?array {
    $processor = $e->getTokenProcessor();

    $mailing = isset($processor->context['mailing'])
      ? $processor->context['mailing']
      : (isset($processor->context['mailingId']) ? CRM_Mailing_BAO_Mailing::findById($processor->context['mailingId']) : NULL);

    if ($mailing && !isset($processor->context['mailingId'])) {
      $processor->context['mailingId'] = $mailing->id;
    }

    $prefetch = parent::prefetch($e) ?? [];
    $prefetch['mailing'] = $mailing;

    return $prefetch;
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    $bespokeTokens = array_keys($this->getBespokeTokens());
    if (in_array($field, $bespokeTokens, TRUE)) {
      $row->format('text/plain')->tokens($entity, $field,
        (string) $this->getMailingTokenReplacement($field, $prefetch['mailing']->id ?? NULL, $prefetch['mailing']));
    }
    else {
      parent::evaluateToken($row, $entity, $field, $prefetch);
    }
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
