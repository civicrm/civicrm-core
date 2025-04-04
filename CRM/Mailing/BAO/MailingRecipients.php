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
class CRM_Mailing_BAO_MailingRecipients extends CRM_Mailing_DAO_MailingRecipients {

  /**
   * @param int $mailingID
   *
   * @return null|string
   */
  public static function mailingSize($mailingID) {
    $sql = "
SELECT count(*) as count
FROM   civicrm_mailing_recipients
WHERE  mailing_id = %1
";
    $params = [1 => [$mailingID, 'Integer']];
    return CRM_Core_DAO::singleValueQuery($sql, $params);
  }

  /**
   * @param int $mailingID
   * @param null $offset
   * @param null $limit
   *
   * @return Object
   */
  public static function mailingQuery(
    $mailingID,
    $offset = NULL, $limit = NULL
  ) {
    $limitString = NULL;
    if ($limit && $offset !== NULL) {
      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $limit = CRM_Utils_Type::escape($limit, 'Int');

      $limitString = "LIMIT $offset, $limit";
    }

    $isSMSMode = CRM_Core_DAO::getFieldValue('CRM_Mailing_BAO_Mailing', $mailingID, 'sms_provider_id', 'id');
    $mailingObject = new CRM_Mailing_BAO_Mailing();
    $mailingObject->id = $mailingID;
    $mailingObject->fetch();
    $criteria = [
      'contact_join' => CRM_Utils_SQL_Select::fragment()->join('c', 'INNER JOIN civicrm_contact c ON (c.id = r.contact_id
       AND c.is_deleted = 0
          AND c.is_deceased = 0
          AND c.do_not_' . ($isSMSMode ? 'sms' : 'email') . ' = 0
          AND c.is_opt_out = 0
        )'),
    ];
    if (!$isSMSMode) {
      $criteria['additional_join'] = CRM_Utils_SQL_Select::fragment()->join('e', 'INNER JOIN civicrm_email e ON (r.email_id = e.id AND e.on_hold = 0)');
    }
    CRM_Utils_Hook::alterMailingRecipients($mailingObject, $criteria, 'mailingQuery');

    $sqlObject = CRM_Utils_SQL_Select::from('civicrm_mailing_recipients r')
      ->select('r.contact_id')
      ->select('r.email_id')
      ->select('r.phone_id')
      ->merge($criteria)
      ->where('r.mailing_id = #mailingID')
      ->param('#mailingID', $mailingID)
      ->orderBy('r.id ASC');
    if ($limitString) {
      $sqlObject->limit($limit, $offset);
    }
    $sql = $sqlObject->toSQL();
    return CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Moves a number of randomly-chosen recipients of one Mailing to another Mailing.
   *
   * @param int $sourceMailingId
   *   Source mailing ID
   * @param int $newMailingID
   *   Destination mailing ID
   * @param int $totalLimit
   *   Number of recipients to move
   */
  public static function updateRandomRecipients($sourceMailingId, $newMailingID, $totalLimit = NULL) {
    $limitString = NULL;
    if ($totalLimit) {
      $limitString = "LIMIT 0, $totalLimit";
    }
    $temporaryTable = CRM_Utils_SQL_TempTable::build()
      ->setCategory('sr' . $sourceMailingId)
      ->setMemory()
      ->createWithColumns("mailing_recipient_id int unsigned, id int PRIMARY KEY AUTO_INCREMENT, INDEX(mailing_recipient_id)");
    $temporaryTableName = $temporaryTable->getName();
    $sql = "
INSERT INTO {$temporaryTableName} (mailing_recipient_id)
SELECT mr.id
FROM   civicrm_mailing_recipients mr
WHERE  mr.mailing_id = $sourceMailingId
ORDER BY RAND()
$limitString
    ";
    CRM_Core_DAO::executeQuery($sql);
    $sql = "
UPDATE civicrm_mailing_recipients mr
INNER JOIN {$temporaryTableName} temp_mr ON temp_mr.mailing_recipient_id = mr.id
SET mr.mailing_id = $newMailingID
     ";
    CRM_Core_DAO::executeQuery($sql);
    $temporaryTable->drop();
  }

  /**
   * Redistribute recipients from $sourceMailingId to a series of other mailings.
   *
   * @param int $sourceMailingId
   * @param array $to
   *   (int $targetMailingId => int $count).
   */
  public static function reassign($sourceMailingId, $to) {
    foreach ($to as $targetMailingId => $count) {
      if ($count > 0) {
        CRM_Mailing_BAO_MailingRecipients::updateRandomRecipients($sourceMailingId, $targetMailingId, $count);
      }
    }
  }

}
