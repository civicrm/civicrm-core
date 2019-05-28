<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Mailing_BAO_Recipients extends CRM_Mailing_DAO_Recipients {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

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

    $sql = "
SELECT contact_id, email_id, phone_id
FROM   civicrm_mailing_recipients
WHERE  mailing_id = %1
       $limitString
";
    $params = [1 => [$mailingID, 'Integer']];

    return CRM_Core_DAO::executeQuery($sql, $params);
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
    CRM_Core_DAO::executeQuery("DROP TEMPORARY TABLE IF EXISTS  srcMailing_$sourceMailingId");
    $sql = "
CREATE TEMPORARY TABLE srcMailing_$sourceMailingId
            (mailing_recipient_id int unsigned, id int PRIMARY KEY AUTO_INCREMENT, INDEX(mailing_recipient_id))
            ENGINE=HEAP";
    CRM_Core_DAO::executeQuery($sql);
    $sql = "
INSERT INTO srcMailing_$sourceMailingId (mailing_recipient_id)
SELECT mr.id
FROM   civicrm_mailing_recipients mr
WHERE  mr.mailing_id = $sourceMailingId
ORDER BY RAND()
$limitString
    ";
    CRM_Core_DAO::executeQuery($sql);
    $sql = "
UPDATE civicrm_mailing_recipients mr
INNER JOIN srcMailing_$sourceMailingId temp_mr ON temp_mr.mailing_recipient_id = mr.id
SET mr.mailing_id = $newMailingID
     ";
    CRM_Core_DAO::executeQuery($sql);
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
        CRM_Mailing_BAO_Recipients::updateRandomRecipients($sourceMailingId, $targetMailingId, $count);
      }
    }
  }

}
