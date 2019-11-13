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
class CRM_Mailing_BAO_MailingAB extends CRM_Mailing_DAO_MailingAB {

  /**
   * class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Construct a new mailingab object.
   *
   * @params array $params
   *   Form values.
   *
   * @param array $params
   * @param array $ids
   *
   * @return object
   *   $mailingab      The new mailingab object
   */
  public static function create(&$params, $ids = []) {
    $transaction = new CRM_Core_Transaction();

    $mailingab = self::add($params, $ids);

    if (is_a($mailingab, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $mailingab;
    }
    $transaction->commit();
    return $mailingab;
  }

  /**
   * function to add the mailings.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @param array $ids
   *   Reference array contains the id.
   *
   *
   * @return object
   */
  public static function add(&$params, $ids = []) {
    $id = CRM_Utils_Array::value('mailingab_id', $ids, CRM_Utils_Array::value('id', $params));

    if ($id) {
      CRM_Utils_Hook::pre('edit', 'MailingAB', $id, $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'MailingAB', NULL, $params);
    }

    $mailingab = new CRM_Mailing_DAO_MailingAB();
    $mailingab->id = $id;
    if (!$id) {
      $mailingab->domain_id = CRM_Utils_Array::value('domain_id', $params, CRM_Core_Config::domainID());
    }

    $mailingab->copyValues($params);

    $result = $mailingab->save();

    if ($id) {
      CRM_Utils_Hook::post('edit', 'MailingAB', $mailingab->id, $mailingab);
    }
    else {
      CRM_Utils_Hook::post('create', 'MailingAB', $mailingab->id, $mailingab);
    }

    return $result;
  }

  /**
   * Delete MailingAB and all its associated records.
   *
   * @param int $id
   *   Id of the mail to delete.
   */
  public static function del($id) {
    if (empty($id)) {
      throw new CRM_Core_Exception(ts('No id passed to MailingAB del function'));
    }
    CRM_Core_Transaction::create()->run(function () use ($id) {
      CRM_Utils_Hook::pre('delete', 'MailingAB', $id, CRM_Core_DAO::$_nullArray);

      $dao = new CRM_Mailing_DAO_MailingAB();
      $dao->id = $id;
      if ($dao->find(TRUE)) {
        $mailing_ids = [$dao->mailing_id_a, $dao->mailing_id_b, $dao->mailing_id_c];
        $dao->delete();
        foreach ($mailing_ids as $mailing_id) {
          if ($mailing_id) {
            CRM_Mailing_BAO_Mailing::del($mailing_id);
          }
        }
      }

      CRM_Core_Session::setStatus(ts('Selected mailing has been deleted.'), ts('Deleted'), 'success');

      CRM_Utils_Hook::post('delete', 'MailingAB', $id, $dao);
    });
  }

  /**
   * Transfer recipients from the canonical mailing A to the other mailings.
   *
   * @param CRM_Mailing_DAO_MailingAB $dao
   */
  public static function distributeRecipients(CRM_Mailing_DAO_MailingAB $dao) {
    CRM_Mailing_BAO_Mailing::getRecipients($dao->mailing_id_a);

    //calculate total number of random recipients for mail C from group_percentage selected
    $totalCount = CRM_Mailing_BAO_Recipients::mailingSize($dao->mailing_id_a);
    $totalSelected = max(1, round(($totalCount * $dao->group_percentage) / 100));

    CRM_Mailing_BAO_Recipients::reassign($dao->mailing_id_a, [
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
