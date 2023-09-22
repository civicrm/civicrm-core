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
 * Class CRM_Mailing_BAO_MailingAB
 */
class CRM_Mailing_BAO_MailingAB extends CRM_Mailing_DAO_MailingAB implements \Civi\Core\HookInterface {

  /**
   * Construct a new mailingab object.
   *
   * @params array $params
   *   Form values.
   *
   * @param array $params
   *
   * @return CRM_Mailing_DAO_MailingAB
   */
  public static function create(&$params) {
    $transaction = new CRM_Core_Transaction();

    $mailingab = self::writeRecord($params);

    if (is_a($mailingab, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $mailingab;
    }
    $transaction->commit();
    return $mailingab;
  }

  /**
   * Delete MailingAB and all its associated records.
   *
   * @param int $id
   *   Id of the mail to delete.
   *
   * @deprecated
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    static::deleteRecord(['id' => $id]);
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    if ($event->action === 'delete') {
      foreach (['mailing_id_a', 'mailing_id_b', 'mailing_id_c'] as $part) {
        if ($event->object->$part) {
          // Don't let missing mailing parts throw exceptions
          try {
            CRM_Mailing_BAO_Mailing::deleteRecord(['id' => $event->object->$part]);
          }
          catch (Exception $e) {
          }
        }
      }
    }
  }

  /**
   * Transfer recipients from the canonical mailing A to the other mailings.
   *
   * @param CRM_Mailing_DAO_MailingAB $dao
   */
  public static function distributeRecipients(CRM_Mailing_DAO_MailingAB $dao) {
    CRM_Mailing_BAO_Mailing::getRecipients($dao->mailing_id_a);

    //calculate total number of random recipients for mail C from group_percentage selected
    $totalCount = CRM_Mailing_BAO_MailingRecipients::mailingSize($dao->mailing_id_a);
    $totalSelected = max(1, round(($totalCount * $dao->group_percentage) / 100));

    CRM_Mailing_BAO_MailingRecipients::reassign($dao->mailing_id_a, [
      $dao->mailing_id_b => (2 * $totalSelected <= $totalCount) ? $totalSelected : $totalCount - $totalSelected,
      $dao->mailing_id_c => max(0, $totalCount - $totalSelected - $totalSelected),
    ]);

  }

  /**
   * get abtest based on Mailing ID
   *
   * @param int $mailingID
   *   Mailing ID.
   *
   * @return object
   */
  public static function getABTest($mailingID) {
    $query = "SELECT * FROM `civicrm_mailing_abtest` ab
      where (ab.mailing_id_a = %1
      OR ab.mailing_id_b = %1
      OR ab.mailing_id_c = %1)
      GROUP BY ab.id";
    $params = [1 => [$mailingID, 'Integer']];
    $abTest = CRM_Core_DAO::executeQuery($query, $params);
    $abTest->fetch();
    return $abTest;
  }

}
