<?php
/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\Event\AuthorizeRecordEvent;
use Civi\Api4\UserRole;
use Civi\Core\HookInterface;

/**
 * Business access object for the User entity.
 */
class CRM_Standaloneusers_BAO_User extends CRM_Standaloneusers_DAO_User implements HookInterface {

  /**
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function self_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $action = $e->getActionName();
    $mayAdminUsers = \CRM_Core_Permission::check('cms:administer users');
    $isOwnUser = (CRM_Utils_System::getLoggedInUfID()) == ($record['id'] ?? NULL);
    if ($action === 'delete') {
      if ($isOwnUser) {
        // Prevent users from deleting their own user account
        $e->setAuthorized(FALSE);
      }
      else {
        // Enforce administer user permission requirement
        $e->setAuthorized($mayAdminUsers);
      }
    }
    elseif ($action === 'update') {
      $e->setAuthorized($isOwnUser ?: $mayAdminUsers);
    }
    elseif ($action === 'create') {
      $e->setAuthorized($mayAdminUsers);
    }
    elseif ($action === 'sendPasswordResetEmail') {
      $e->setAuthorized($mayAdminUsers);
    }
    else {
      // Is there another write action we don't know about? If so, play it safe and say No.
      $e->setAuthorized(FALSE);
    }
  }

  /**
   * Event fired before an action is taken on a User record.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if (in_array($event->action, ['create', 'edit'], TRUE)) {
      if (empty($event->params['when_updated'])) {
        // Track when_updated.
        $event->params['when_updated'] = date('YmdHis');
      }
      if (empty($event->params['uf_name'])) {
        // If no email is specified, fetch from contact
        $contactId = $event->params['contact_id'] ?? NULL;

        if ($contactId) {
          $email = \Civi\Api4\Contact::get(FALSE)
            ->addWhere('id', '=', $contactId)
            ->addSelect('email_primary.email')
            ->execute()->single()['email_primary.email'] ?? NULL;

          $event->params['uf_name'] = $email;
        }
      }
    }
  }

  /**
   * Event fired after an action is taken on a User record.
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    // Handle virtual "roles" field (defined in UserSpecProvider)
    // @see \Civi\Api4\Service\Spec\Provider\UserSpecProvider
    if (
      in_array($event->action, ['create', 'edit'], TRUE) &&
      isset($event->params['roles']) && $event->id
    ) {
      if ($event->params['roles']) {
        $newRoles = array_map(function($role_id) {
          return ['role_id' => $role_id];
        }, $event->params['roles']);
        UserRole::replace(FALSE)
          ->addWhere('user_id', '=', $event->id)
          ->setRecords($newRoles)
          ->execute();
      }
      else {
        UserRole::delete(FALSE)
          ->addWhere('user_id', '=', $event->id)
          ->execute();
      }
    }
  }

  public static function updateLastAccessed() {
    $sess = CRM_Core_Session::singleton();
    $ufID = (int) $sess->get('ufID');
    CRM_Core_DAO::executeQuery("UPDATE civicrm_uf_match SET when_last_accessed = NOW() WHERE id = $ufID");
    $sess->set('lastAccess', time());
  }

  public static function getPreferredLanguages(): array {
    return CRM_Core_I18n::uiLanguages(FALSE);
  }

  public static function getTimeZones(): array {
    $timeZones = [];
    foreach (\DateTimeZone::listIdentifiers() as $timezoneId) {
      $timeZones[$timezoneId] = $timezoneId;
    }
    return $timeZones;
  }

  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses = [];

    // ↓ The following is copied from parent::addSelectWhereClause(). Is it needed? :shrug:
    $fields = $this::getSupportedFields();
    foreach ($fields as $fieldName => $field) {
      // Clause for contact-related entities like Email, Relationship, etc.
      if (str_starts_with($fieldName, 'contact_id') && ($field['FKClassName'] ?? NULL) === 'CRM_Contact_DAO_Contact') {
        $contactClause = CRM_Utils_SQL::mergeSubquery('Contact');
        if (!empty($contactClause)) {
          $clauses[$fieldName] = $contactClause;
        }
      }
      // Clause for an entity_table/entity_id combo
      if ($fieldName === 'entity_id' && isset($fields['entity_table'])) {
        $relatedClauses = self::getDynamicFkAclClauses('entity_table', 'entity_id', $conditions['entity_table'] ?? NULL);
        if ($relatedClauses) {
          // Nested array will be joined with OR
          $clauses['entity_table'] = [$relatedClauses];
        }
      }
    }
    // ↑ end copy of parent's code.

    // Limit those without administer users permission to their own record.
    if (!CRM_Core_Permission::check('cms:administer users')) {
      // A user without administer users permission is potentially requesting record(s)
      // other than their own. Limit to their own.
      $clauses['id'] = [sprintf('= %s', (int) CRM_Utils_System::getLoggedInUfID())];
    }

    CRM_Utils_Hook::selectWhereClause($entityName ?? $this, $clauses);
    return $clauses;
  }

}
