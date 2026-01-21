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

use Civi\Api4\Email;

/**
 * This class contains functions for email handling.
 */
class CRM_Core_BAO_Email extends CRM_Core_DAO_Email implements Civi\Core\HookInterface {
  use CRM_Contact_AccessTrait;

  /**
   * @deprecated
   *
   * @param array $params
   * @return CRM_Core_BAO_Email
   * @throws CRM_Core_Exception
   */
  public static function create($params) {
    // FIXME: switch CRM_Core_BAO_Block::create to call writeRecord (once Address, IM, Phone create functions go through it)
    // then this can be uncommented:
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Event fired before modifying an Email.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if (in_array($event->action, ['create', 'edit'])) {
      CRM_Core_BAO_Block::handlePrimary($event->params, __CLASS__);

      if (!empty($event->params['email'])) {
        // lower case email field to optimize queries
        $event->params['email'] = mb_strtolower($event->params['email']);
      }

      // Since we're setting bulkmail for 1 of this contact's emails, first reset
      // all their other emails to is_bulkmail false. We shouldn't set the current
      // email to false even though we are about to reset it to avoid
      // contaminating the changelog if logging is enabled.  (only 1 email
      // address can have is_bulkmail = true)
      //
      // Note setting a the is_bulkmail to '' in $params results in $email->is_bulkmail === 'null'.
      // @see https://lab.civicrm.org/dev/core/-/issues/2254
      //
      if (!empty($event->params['is_bulkmail']) && !empty($event->params['contact_id']) && !self::isMultipleBulkMail()) {
        $sql = "
UPDATE civicrm_email
SET    is_bulkmail = 0
WHERE  contact_id = {$event->params['contact_id']}
";
        if ($event->action === 'edit') {
          $sql .= " AND id <> {$event->params['id']}";
        }
        CRM_Core_DAO::executeQuery($sql);
      }

      // handle if email is on hold
      self::holdEmail($event->params);
    }
  }

  /**
   * Event fired after modifying an Email.
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    $email = $event->object;
    if ($event->action !== 'delete' && !empty($email->is_primary) && !empty($email->contact_id)) {
      // update the UF user email if that has changed
      CRM_Core_BAO_UFMatch::updateUFName($email->contact_id);
    }
    if (in_array($event->action, ['create', 'edit'])) {
      $contactId = (int) ($email->contact_id ?? CRM_Core_DAO::getFieldValue(__CLASS__, $email->id, 'contact_id'));
      $isPrimary = ($email->is_primary ?? CRM_Core_DAO::getFieldValue(__CLASS__, $email->id, 'is_primary'));
      if ($contactId && $isPrimary) {
        $address = $email->email ?? CRM_Core_DAO::getFieldValue(__CLASS__, $email->id, 'email');
        self::updateContactName($contactId, $address);
      }
    }
  }

  /**
   * @deprecated
   *
   * @param array $params
   * @return CRM_Core_BAO_Email
   * @throws CRM_Core_Exception
   */
  public static function add($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $entityBlock
   *   Input parameters to find object.
   *
   * @return array
   */
  public static function getValues($entityBlock) {
    return CRM_Core_BAO_Block::getValues('email', $entityBlock);
  }

  /**
   * Get all the emails for a specified contact_id, with the primary email being first
   *
   * @param int $id
   *   The contact id.
   *
   * @param bool $updateBlankLocInfo
   *
   * @return array
   *   the array of email id's
   */
  public static function allEmails($id, $updateBlankLocInfo = FALSE) {
    if (!$id) {
      return NULL;
    }

    $query = "
SELECT    email,
          civicrm_location_type.name as locationType,
          civicrm_email.is_primary as is_primary,
          civicrm_email.on_hold as on_hold,
          civicrm_email.id as email_id,
          civicrm_email.location_type_id as locationTypeId
FROM      civicrm_contact
LEFT JOIN civicrm_email ON ( civicrm_email.contact_id = civicrm_contact.id )
LEFT JOIN civicrm_location_type ON ( civicrm_email.location_type_id = civicrm_location_type.id )
WHERE     civicrm_contact.id = %1
ORDER BY  civicrm_email.is_primary DESC, email_id ASC ";
    $params = [
      1 => [
        $id,
        'Integer',
      ],
    ];

    $emails = [];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = [
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'on_hold' => $dao->on_hold,
        'id' => $dao->email_id,
        'email' => $dao->email,
        'locationTypeId' => $dao->locationTypeId,
      ];

      if ($updateBlankLocInfo) {
        $emails[$count++] = $values;
      }
      else {
        $emails[$dao->email_id ?? ''] = $values;
      }
    }
    return $emails;
  }

  /**
   * Get all the emails for a specified location_block id, with the primary email being first
   *
   * @param array $entityElements
   *   The array containing entity_id and.
   *   entity_table name
   *
   * @return array
   *   the array of email id's
   */
  public static function allEntityEmails(&$entityElements) {
    if (empty($entityElements)) {
      return NULL;
    }

    $entityId = $entityElements['entity_id'];
    $entityTable = $entityElements['entity_table'];

    $sql = " SELECT email, ltype.name as locationType, e.is_primary as is_primary, e.on_hold as on_hold,e.id as email_id, e.location_type_id as locationTypeId
FROM civicrm_loc_block loc, civicrm_email e, civicrm_location_type ltype, {$entityTable} ev
WHERE ev.id = %1
AND   loc.id = ev.loc_block_id
AND   e.id IN (loc.email_id, loc.email_2_id)
AND   ltype.id = e.location_type_id
ORDER BY e.is_primary DESC, email_id ASC ";

    $params = [
      1 => [
        $entityId,
        'Integer',
      ],
    ];

    $emails = [];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $emails[$dao->email_id] = [
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'on_hold' => $dao->on_hold,
        'id' => $dao->email_id,
        'email' => $dao->email,
        'locationTypeId' => $dao->locationTypeId,
      ];
    }

    return $emails;
  }

  /**
   * Set / reset hold status for an email prior to saving
   *
   * @param array $email
   *   Email params to be saved.
   */
  public static function holdEmail(array &$email) {
    if (!empty($email['id']) && !isset($email['on_hold'])) {
      // email is being updated but no change to on_hold.
      return;
    }
    if (!isset($email['on_hold']) || $email['on_hold'] === 'null') {
      // legacy handling, deprecated.
      $email['on_hold'] = 0;
    }
    $email['on_hold'] = (int) $email['on_hold'];

    //check for update mode
    if (!empty($email['id'])) {
      $params = [1 => [$email['id'], 'Integer']];
      if ($email['on_hold']) {
        $sql = "
SELECT id
FROM   civicrm_email
WHERE  id = %1
AND    hold_date IS NULL
";
        if (CRM_Core_DAO::singleValueQuery($sql, $params)) {
          $email['hold_date'] = date('YmdHis');
          $email['reset_date'] = 'null';
        }
      }
      elseif ($email['on_hold'] === 0) {
        // we do this lookup to see if reset_date should be changed.
        $sql = "
SELECT id
FROM   civicrm_email
WHERE  id = %1
AND    hold_date IS NOT NULL
AND    reset_date IS NULL
";
        if (CRM_Core_DAO::singleValueQuery($sql, $params)) {
          //set reset date only if it is not set and if hold date is set
          $email['on_hold'] = FALSE;
          $email['hold_date'] = 'null';
          $email['reset_date'] = date('YmdHis');
        }
      }
    }
    elseif ($email['on_hold']) {
      $email['hold_date'] = date('YmdHis');
    }
  }

  /**
   * Generate an array of Domain email addresses.
   * @return array $domainEmails;
   */
  public static function domainEmails() {
    $domainEmails = [];
    $domainFrom = \Civi\Api4\SiteEmailAddress::get(FALSE)
      ->addSelect('display_name', 'email')
      ->addWhere('domain_id', '=', 'current_domain')
      ->addWhere('is_active', '=', TRUE)
      ->addOrderBy('is_default', 'DESC')
      ->addOrderBy('display_name')
      ->execute();
    foreach ($domainFrom as $address) {
      $domainEmail = CRM_Utils_Mail::formatFromAddress($address);
      $domainEmails[$domainEmail] = htmlspecialchars($domainEmail);
    }
    return $domainEmails;
  }

  /**
   * Build From Email as the combination of all the email ids of the logged in user and
   * the domain email id
   *
   * @return array
   *   an array of email ids
   */
  public static function getFromEmail() {
    // add all configured site email addresses
    $fromEmailValues = self::domainEmails();

    if (!Civi::settings()->get('allow_mail_from_logged_in_contact')) {
      return $fromEmailValues;
    }

    $contactFromEmails = [];
    // add logged in user's active email ids
    $contactID = CRM_Core_Session::getLoggedInContactID();
    if ($contactID) {
      $contactEmails = self::allEmails($contactID);
      $fromDisplayName  = CRM_Core_Session::singleton()->getLoggedInContactDisplayName();

      foreach ($contactEmails as $emailId => $emailVal) {
        $email = trim($emailVal['email']);
        if (!$email || $emailVal['on_hold']) {
          continue;
        }
        $fromEmail = "$fromDisplayName <$email>";
        $fromEmailHtml = htmlspecialchars($fromEmail) . ' ' . $emailVal['locationType'];

        if (!empty($emailVal['is_primary'])) {
          $fromEmailHtml .= ' ' . ts('(preferred)');
        }
        $contactFromEmails[$emailId] = $fromEmailHtml;
      }
    }
    return CRM_Utils_Array::crmArrayMerge($contactFromEmails, $fromEmailValues);
  }

  /**
   * @return object
   */
  public static function isMultipleBulkMail() {
    return Civi::settings()->get('civimail_multiple_bulk_emails');
  }

  /**
   * Call common delete function.
   *
   * @see \CRM_Contact_BAO_Contact::on_hook_civicrm_post
   *
   * @param int $id
   * @deprecated
   * @return bool
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return (bool) self::deleteRecord(['id' => $id]);
  }

  /**
   * Get filters for entity reference fields.
   *
   * @return array
   */
  public static function getEntityRefFilters() {
    $contactFields = CRM_Contact_BAO_Contact::getEntityRefFilters();
    foreach ($contactFields as $index => &$contactField) {
      if (!empty($contactField['entity'])) {
        // For now email_getlist can't parse state, country etc.
        unset($contactFields[$index]);
      }
      elseif ($contactField['key'] !== 'contact_id') {
        $contactField['entity'] = 'Contact';
        $contactField['key'] = 'contact_id.' . $contactField['key'];
      }
    }
    return $contactFields;
  }

  /**
   *
   *
   * @param int $contactId
   * @param string $primaryEmail
   */
  public static function updateContactName($contactId, string $primaryEmail) {
    if (is_string($primaryEmail) && $primaryEmail !== '' &&
      !CRM_Contact_BAO_Contact::hasName(['id' => $contactId])
    ) {
      CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Contact', $contactId, 'display_name', $primaryEmail);
      CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Contact', $contactId, 'sort_name', $primaryEmail);
    }
  }

  /**
   * Get default text for a message with the signature from the email sender populated.
   *
   * @param int $emailID
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function getEmailSignatureDefaults(int $emailID): array {
    // Add signature
    $defaultEmail = Email::get(FALSE)
      ->addSelect('signature_html', 'signature_text')
      ->addWhere('id', '=', $emailID)->execute()->first();
    return [
      'html_message' => empty($defaultEmail['signature_html']) ? '' : '<br/><br/>--' . $defaultEmail['signature_html'],
      'text_message' => empty($defaultEmail['signature_text']) ? '' : "\n\n--\n" . $defaultEmail['signature_text'],
    ];
  }

}
