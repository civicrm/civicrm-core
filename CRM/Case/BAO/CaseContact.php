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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class contains the functions for Case Contact management.
 */
class CRM_Case_BAO_CaseContact extends CRM_Case_DAO_CaseContact implements \Civi\Core\HookInterface {

  /**
   * Create case contact record.
   *
   * @param array $params
   *
   * @deprecated
   * @return CRM_Case_BAO_CaseContact
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    if ($event->action === 'create' || $event->action === 'edit') {
      $caseContact = $event->object;

      // Add case to recentl items for logged-in users
      if ($caseContact->case_id && $caseContact->contact_id && CRM_Core_Session::getLoggedInContactID()) {
        $caseType = CRM_Case_BAO_Case::getCaseType($caseContact->case_id);
        $url = CRM_Utils_System::url('civicrm/contact/view/case',
          "action=view&reset=1&id={$caseContact->case_id}&cid={$caseContact->contact_id}&context=home"
        );

        $title = CRM_Contact_BAO_Contact::displayName($caseContact->contact_id) . ' - ' . $caseType;

        $recentOther = [];
        if (CRM_Core_Permission::checkActionPermission('CiviCase', CRM_Core_Action::DELETE)) {
          $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/case',
            "action=delete&reset=1&id={$caseContact->case_id}&cid={$caseContact->contact_id}&context=home"
          );
        }

        // add the recently affected case
        CRM_Utils_Recent::add($title,
          $url,
          $caseContact->case_id,
          'Case',
          $caseContact->contact_id,
          NULL,
          $recentOther
        );
      }
    }
  }

  /**
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   * @inheritDoc
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    return [
      // Reuse case acls
      'case_id' => CRM_Utils_SQL::mergeSubquery('Case'),
      // Case acls already check for contact access so we can just mark contact_id as handled
      'contact_id' => [],
    ];
    // Don't call hook selectWhereClause, the case query already did
  }

}
