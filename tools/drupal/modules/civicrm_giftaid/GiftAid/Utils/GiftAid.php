<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

require_once 'CRM/Core/DAO.php';
require_once 'CRM/Core/Error.php';
require_once 'CRM/Utils/Array.php';
require_once 'CRM/Utils/Date.php';
require_once 'GiftAid/Utils/Hook.php';
class GiftAid_Utils_GiftAid {

  /**
   * How long a positive Gift Aid declaration is valid for under HMRC rules (years).
   */
  CONST DECLARATION_LIFETIME = 3;

  /**
   * Get Gift Aid declaration record for Individual.
   *
   * @param int    $contactID - the Individual for whom we retrieve declaration
   * @param date   $date      - date for which we retrieve declaration (in ISO date format)
   *							- e.g. the date for which you would like to check if the contact has a valid
   * 								  declaration
   *
   * @return array            - declaration record as associative array,
   *                            else empty array.
   * @access public
   * @static
   */
  static
  function getDeclaration($contactID, $date = NULL, $charity = NULL) {
    static $charityColumnExists = NULL;

    if (is_null($date)) {
      $date = date('Y-m-d H:i:s');
    }

    if ($charityColumnExists === NULL) {
      $charityColumnExists = CRM_Core_DAO::checkFieldExists('civicrm_value_gift_aid_declaration', 'charity');
    }
    $charityClause = '';
    if ($charityColumnExists) {
      $charityClause = $charity ? " AND charity='{$charity}'" : " AND ( charity IS NULL OR charity = '' )";
    }

    // Get current declaration: start_date in past, end_date in future or null
    // - if > 1, pick latest end_date
    $currentDeclaration = array();
    $sql = "
        SELECT id, eligible_for_gift_aid, start_date, end_date, reason_ended, source, notes
        FROM   civicrm_value_gift_aid_declaration
        WHERE  entity_id = %1 AND start_date <= %2 AND (end_date > %2 OR end_date IS NULL) {$charityClause}
        ORDER BY end_date DESC";
    $sqlParams = array(1 => array($contactID, 'Integer'),
      2 => array(CRM_Utils_Date::isoToMysql($date), 'Timestamp'),
    );
    // allow query to be modified via hook
    GiftAid_Utils_Hook::alterDeclarationQuery($sql, $sqlParams);

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    if ($dao->fetch()) {
      $currentDeclaration['id'] = $dao->id;
      $currentDeclaration['eligible_for_gift_aid'] = $dao->eligible_for_gift_aid;
      $currentDeclaration['start_date'] = $dao->start_date;
      $currentDeclaration['end_date'] = $dao->end_date;
      $currentDeclaration['reason_ended'] = $dao->reason_ended;
      $currentDeclaration['source'] = $dao->source;
      $currentDeclaration['notes'] = $dao->notes;
    }
    //CRM_Core_Error::debug('currentDeclaration', $currentDeclaration);
    return $currentDeclaration;
  }

  static
  function isEligibleForGiftAid($contactID, $date = NULL, $contributionID = NULL) {
    $charity = NULL;
    if ($contributionID &&
      CRM_Core_DAO::checkFieldExists('civicrm_value_gift_aid_submission', 'charity')
    ) {
      $charity = CRM_Core_DAO::singleValueQuery('SELECT charity FROM civicrm_value_gift_aid_submission WHERE entity_id = %1',
        array(1 => array($contributionID, 'Integer'))
      );
    }

    $declaration = self::getDeclaration($contactID, $date, $charity);
    if (isset($declaration['eligible_for_gift_aid'])) {
      $isEligible = ($declaration['eligible_for_gift_aid'] == 1);
    }

    // hook can alter the eligibility if needed
    GiftAid_Utils_Hook::giftAidEligible($isEligible, $contactID, $date, $contributionID);

    return $isEligible;
  }

  /**
   * Create / update Gift Aid declaration records on Individual when
   * "Eligible for Gift Aid" field on Contribution is updated.
   * See http://wiki.civicrm.org/confluence/display/CRM/Gift+aid+implementation
   *
   * TODO change arguments to single $param array
   *
   * @param array  $params    - fields to store in declaration:
   *               - entity_id:  the Individual for whom we will create/update declaration
   *               - eligible_for_gift_aid: 1 for positive declaration, 0 for negative
   *               - start_date: start date of declaration (in ISO date format)
   *               - end_date:   end date of declaration (in ISO date format)
   *
   * @return array   TODO
   * @access public
   * @static
   */
  static
  function setDeclaration($params) {
    static $charityColumnExists = NULL;

    if (!CRM_Utils_Array::value('entity_id', $params)) {
      return (array(
          'is_error' => 1,
          'error_message' => 'entity_id is required',
        ));
    }
    $charity = CRM_Utils_Array::value('charity', $params);

    // Retrieve existing declarations for this user.
    $currentDeclaration = GiftAid_Utils_GiftAid::getDeclaration($params['entity_id'],
      $params['start_date'],
      $charity
    );

    $charityClause = '';
    if ($charityColumnExists === NULL) {
      $charityColumnExists = CRM_Core_DAO::checkFieldExists('civicrm_value_gift_aid_declaration', 'charity');
    }
    if ($charityColumnExists) {
      $charityClause = $charity ? " AND charity='{$charity}'" : " AND ( charity IS NULL OR charity = '' )";
    }

    // Get future declarations: start_date in future, end_date in future or null
    // - if > 1, pick earliest start_date
    $futureDeclaration = array();
    $sql = "
        SELECT id, eligible_for_gift_aid, start_date, end_date
        FROM   civicrm_value_gift_aid_declaration
        WHERE  entity_id = %1 AND start_date > %2 AND (end_date > %2 OR end_date IS NULL) {$charityClause}
        ORDER BY start_date";
    $dao = CRM_Core_DAO::executeQuery($sql, array(
        1 => array($params['entity_id'], 'Integer'),
        2 => array(CRM_Utils_Date::isoToMysql($params['start_date']), 'Timestamp'),
      ));
    if ($dao->fetch()) {
      $futureDeclaration['id'] = $dao->id;
      $futureDeclaration['eligible_for_gift_aid'] = $dao->eligible_for_gift_aid;
      $futureDeclaration['start_date'] = $dao->start_date;
      $futureDeclaration['end_date'] = $dao->end_date;
    }
    #CRM_Core_Error::debug('futureDeclaration', $futureDeclaration);

    $specifiedEndTimestamp = NULL;
    if (CRM_Utils_Array::value('end_date', $params)) {
      $specifiedEndTimestamp = strtotime(CRM_Utils_Array::value('end_date', $params));
    }

    // Calculate new_end_date for negative declaration
    // - new_end_date =
    //   if end_date specified then (specified end_date)
    //   else (start_date of first future declaration if any, else null)
    $futureTimestamp = NULL;
    if (CRM_Utils_Array::value('start_date', $futureDeclaration)) {
      $futureTimestamp = strtotime(CRM_Utils_Array::value('start_date', $futureDeclaration));
    }

    if ($specifiedEndTimestamp) {
      $endTimestamp = $specifiedEndTimestamp;
    }
    elseif ($futureTimestamp) {
      $endTimestamp = $futureTimestamp;
    }
    else {
      $endTimestamp = NULL;
    }

    if ($params['eligible_for_gift_aid'] == 1) {

      if (!$currentDeclaration) {
        // There is no current declaration so create new.
        GiftAid_Utils_GiftAid::_insertDeclaration($params, $endTimestamp);
      }
      elseif ($currentDeclaration['eligible_for_gift_aid'] == 1 && $endTimestamp) {
        //   - if current positive, extend its end_date to new_end_date.
        $updateParams = array(
          'id' => $currentDeclaration['id'],
          'end_date' => date('YmdHis', $endTimestamp),
        );
        GiftAid_Utils_GiftAid::_updateDeclaration($updateParams);
      }
      elseif ($currentDeclaration['eligible_for_gift_aid'] == 0) {
        //   - if current negative, set its end_date to now and create new ending new_end_date.
        $updateParams = array(
          'id' => $currentDeclaration['id'],
          'end_date' => CRM_Utils_Date::isoToMysql($params['start_date']),
        );
        GiftAid_Utils_GiftAid::_updateDeclaration($updateParams);
        GiftAid_Utils_GiftAid::_insertDeclaration($params, $endTimestamp);
      }
    }
    elseif ($params['eligible_for_gift_aid'] == 0) {

      if (!$currentDeclaration) {
        // There is no current declaration so create new.
        GiftAid_Utils_GiftAid::_insertDeclaration($params, $endTimestamp);
      }
      elseif ($currentDeclaration['eligible_for_gift_aid'] == 1) {
        //   - if current positive, set its end_date to now and create new ending new_end_date.
        $updateParams = array(
          'id' => $currentDeclaration['id'],
          'end_date' => CRM_Utils_Date::isoToMysql($params['start_date']),
        );
        GiftAid_Utils_GiftAid::_updateDeclaration($updateParams);
        GiftAid_Utils_GiftAid::_insertDeclaration($params, $endTimestamp);
      }
      //   - if current negative, leave as is.
    }

    return array(
      'is_error' => 0,
      // TODO 'inserted' => array(id => A, entity_id = B, ...),
      // TODO 'updated'  => array(id => A, entity_id = B, ...),
    );
  }

  /*
     * Private helper function for setDeclaration
     * - update a declaration record.
     */


  static
  function _updateDeclaration($params) {
    // Update (currently we only need to update end_date but can make generic)
    // $params['end_date'] should by in date('YmdHis') format
    $sql = "
        UPDATE civicrm_value_gift_aid_declaration
        SET    end_date = %1
        WHERE  id = %2";
    $dao = CRM_Core_DAO::executeQuery($sql, array(
        1 => array($params['end_date'], 'Timestamp'),
        2 => array($params['id'], 'Integer'),
      ));
  }

  /*
     * Private helper function for setDeclaration
     * - insert a declaration record.
     */


  static
  function _insertDeclaration($params, $endTimestamp) {
    static $charityColumnExists = NULL;
    $charityClause = '';
    if ($charityColumnExists === NULL) {
      $charityColumnExists = CRM_Core_DAO::checkFieldExists('civicrm_value_gift_aid_declaration', 'charity');
    }
    if (!CRM_Utils_Array::value('charity', $params)) {
      $charityColumnExists = FALSE;
    }

    if ($charityColumnExists) {
      $charityCol = ', charity';
      $charityVal = ', %8';
    }

    // Insert
    $sql = "
        INSERT INTO civicrm_value_gift_aid_declaration (entity_id, eligible_for_gift_aid, start_date, end_date, reason_ended, source, notes {$charityCol})
        VALUES (%1, %2, %3, %4, %5, %6, %7 {$charityVal})";
    $queryParams = array(
      1 => array($params['entity_id'], 'Integer'),
      2 => array($params['eligible_for_gift_aid'], 'Integer'),
      3 => array(CRM_Utils_Date::isoToMysql($params['start_date']), 'Timestamp'),
      4 => array(($endTimestamp ? date('YmdHis', $endTimestamp) : ''), 'Timestamp'),
      5 => array(CRM_Utils_Array::value('reason_ended', $params, ''), 'String'),
      6 => array(CRM_Utils_Array::value('source', $params, ''), 'String'),
      7 => array(CRM_Utils_Array::value('notes', $params, ''), 'String'),
    );
    if ($charityColumnExists) {
      $queryParams[8] = array(CRM_Utils_Array::value('charity', $params, ''), 'String');
    }

    $dao = CRM_Core_DAO::executeQuery($sql, $queryParams);
  }
}

