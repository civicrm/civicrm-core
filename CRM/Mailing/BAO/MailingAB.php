<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Class CRM_Mailing_BAO_MailingAB
 */
class CRM_Mailing_BAO_MailingAB extends CRM_Mailing_DAO_MailingAB {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Construct a new mailingab object
   *
   * @params array $params        Form values
   *
   * @param $params
   * @param array $ids
   *
   * @return object $mailingab      The new mailingab object
   * @access public
   * @static
   */
  public static function create(&$params, $ids = array()) {
    $transaction = new CRM_Core_Transaction();

    $mailingab = self::add($params, $ids);

    if (is_a($mailingab, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $mailingab;
    }
    $transaction->commit();
  }

  /**
   * function to add the mailings
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids reference array contains the id
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params, $ids = array()) {
    $id = CRM_Utils_Array::value('mailingab_id', $ids, CRM_Utils_Array::value('id', $params));

    if ($id) {
      CRM_Utils_Hook::pre('edit', 'MailingAB', $id, $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'MailingAB', NULL, $params);
    }

    $mailingab = new CRM_Mailing_DAO_MailingAB();
    $mailingab->id = $id;
    $mailingab->domain_id = CRM_Utils_Array::value('domain_id', $params, CRM_Core_Config::domainID());

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
   * Delete MailingAB and all its associated records
   *
   * @param  int $id id of the mail to delete
   *
   * @return void
   * @access public
   * @static
   */
  public static function del($id) {
    if (empty($id)) {
      CRM_Core_Error::fatal();
    }

    CRM_Utils_Hook::pre('delete', 'MailingAB', $id, CRM_Core_DAO::$_nullArray);

    $dao = new CRM_Mailing_DAO_MailingAB();
    $dao->id = $id;
    $dao->delete();

    CRM_Core_Session::setStatus(ts('Selected mailing has been deleted.'), ts('Deleted'), 'success');

    CRM_Utils_Hook::post('delete', 'MailingAB', $id, $dao);
  }

}
