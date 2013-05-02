<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Event_BAO_ParticipantPayment extends CRM_Event_DAO_ParticipantPayment {

  /**
   * Creates or updates a participant payment record
   *
   * @param $params array of values to initialize the record with
   * @param $ids    array with one values of id for this participantPayment record (for update)
   *
   * @return object the partcipant payment record
   * @static
   */
  static function &create(&$params, &$ids) {
    if (isset($ids['id'])) {
      CRM_Utils_Hook::pre('edit', 'ParticipantPayment', $ids['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'ParticipantPayment', NULL, $params);
    }

    $participantPayment = new CRM_Event_BAO_ParticipantPayment();
    $participantPayment->copyValues($params);
    if (isset($ids['id'])) {
      $participantPayment->id = CRM_Utils_Array::value('id', $ids);
  }
    else {
      $participantPayment->find(TRUE);
    }
    $participantPayment->save();

    if (isset($ids['id'])) {
      CRM_Utils_Hook::post('edit', 'ParticipantPayment', $ids['id'], $participantPayment);
    }
    else {
      CRM_Utils_Hook::post('create', 'ParticipantPayment', NULL, $participantPayment);
    }

    return $participantPayment;
  }

  /**
   * Delete the record that are associated with this ParticipantPayment
   * Also deletes the associated contribution for this participant
   *
   * @param  array  $params   associative array whose values match the record to be deleted
   *
   * @return boolean  true if deleted false otherwise
   * @static
   * @access public
   */
  static function deleteParticipantPayment($params) {
    $participantPayment = new CRM_Event_DAO_ParticipantPayment();

    $valid = FALSE;
    foreach ($params as $field => $value) {
      if (!empty($value)) {
        $participantPayment->$field = $value;
        $valid = TRUE;
      }
    }

    if (!$valid) {
      CRM_Core_Error::fatal();
    }

    if ($participantPayment->find(TRUE)) {
      CRM_Utils_Hook::pre('delete', 'ParticipantPayment', $participantPayment->id, $params);
      CRM_Contribute_BAO_Contribution::deleteContribution($participantPayment->contribution_id);
      $participantPayment->delete();
      CRM_Utils_Hook::post('delete', 'ParticipantPayment', $participantPayment->id, $participantPayment);
      return $participantPayment;
    }
    return FALSE;
  }
}

