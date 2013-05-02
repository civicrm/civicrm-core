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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

require_once 'api/api.php';
class CRM_Contact_Form_Merge extends CRM_Core_Form {
  // the id of the contact that tere's a duplicate for; this one will
  // possibly inherit some of $_oid's properties and remain in the system
  var $_cid = NULL;

  // the id of the other contact - the duplicate one that will get deleted
  var $_oid = NULL;

  var $_contactType = NULL;

  // variable to keep all location block ids.
  protected $_locBlockIds = array();

  // FIXME: QuickForm can't create advcheckboxes with value set to 0 or '0' :(
  // see HTML_QuickForm_advcheckbox::setValues() - but patching that doesn't
  // help, as QF doesn't put the 0-value elements in exportValues() anyway...
  // to side-step this, we use the below UUID as a (re)placeholder
  var $_qfZeroBug = 'e8cddb72-a257-11dc-b9cc-0016d3330ee9'; 
  
  function preProcess() {
    if (!CRM_Core_Permission::check('merge duplicate contacts')) {
      CRM_Core_Error::fatal(ts('You do not have access to this page'));
    }

    $rows = array();
    $cid  = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $oid  = CRM_Utils_Request::retrieve('oid', 'Positive', $this, TRUE);
    $flip = CRM_Utils_Request::retrieve('flip', 'Positive', $this, FALSE);

    $this->_rgid    = $rgid = CRM_Utils_Request::retrieve('rgid', 'Positive', $this, FALSE);
    $this->_gid     = $gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE);
    $this->_mergeId = CRM_Utils_Request::retrieve('mergeId', 'Positive', $this, FALSE);

    if (!CRM_Dedupe_BAO_Rule::validateContacts($cid, $oid)) {
      CRM_Core_Error::statusBounce(ts('The selected pair of contacts are marked as non duplicates. If these records should be merged, you can remove this exception on the <a href=\'%1\'>Dedupe Exceptions</a> page.', array(1 => CRM_Utils_System::url('civicrm/dedupe/exception', 'reset=1'))));
    }

    //load cache mechanism
    $contactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'contact_type');
    $cacheKey = "merge $contactType";
    $cacheKey .= $rgid ? "_{$rgid}" : '_0';
    $cacheKey .= $gid ? "_{$gid}" : '_0';

    $join = "LEFT JOIN civicrm_dedupe_exception de ON ( pn.entity_id1 = de.contact_id1 AND 
                                                             pn.entity_id2 = de.contact_id2 )";
    $where = "de.id IS NULL";

    $pos = CRM_Core_BAO_PrevNextCache::getPositions($cacheKey, $cid, $oid, $this->_mergeId, $join, $where, $flip);

    // Block access if user does not have EDIT permissions for both contacts.
    if (!(CRM_Contact_BAO_Contact_Permission::allow($cid, CRM_Core_Permission::EDIT) &&
        CRM_Contact_BAO_Contact_Permission::allow($oid, CRM_Core_Permission::EDIT)
      )) {
      CRM_Utils_System::permissionDenied();
    }

    // get user info of main contact.
    $config = CRM_Core_Config::singleton();
    $config->doNotResetCache = 1;

    $viewUser = CRM_Core_Permission::check('access user profiles');
    $mainUfId = CRM_Core_BAO_UFMatch::getUFId($cid);
    $mainUser = NULL;
    if ($mainUfId) {
      // d6 compatible
      if ($config->userSystem->is_drupal == '1') {
        $mainUser = user_load($mainUfId);
      }
      elseif ($config->userFramework == 'Joomla') {
        $mainUser = JFactory::getUser($mainUfId);
      }

      $this->assign('mainUfId', $mainUfId);
      $this->assign('mainUfName', $mainUser ? $mainUser->name : NULL);
    }

    $flipUrl = CRM_Utils_system::url('civicrm/contact/merge',
      "reset=1&action=update&cid={$oid}&oid={$cid}&rgid={$rgid}&gid={$gid}"
    );
    if (!$flip) {
      $flipUrl .= '&flip=1';
    }
    $this->assign('flip', $flipUrl);

    $this->prev = $this->next = NULL;
    foreach (array(
      'prev', 'next') as $position) {
      if (!empty($pos[$position])) {
        if ($pos[$position]['id1'] && $pos[$position]['id2']) {
          $urlParam = "reset=1&cid={$pos[$position]['id1']}&oid={$pos[$position]['id2']}&mergeId={$pos[$position]['mergeId']}&action=update";

          if ($rgid) {
            $urlParam .= "&rgid={$rgid}";
          }
          if ($gid) {
            $urlParam .= "&gid={$gid}";
          }

          $this->$position = CRM_Utils_system::url('civicrm/contact/merge', $urlParam);
          $this->assign($position, $this->$position);
        }
      }
    }

    // get user info of other contact.
    $otherUfId = CRM_Core_BAO_UFMatch::getUFId($oid);
    $otherUser = NULL;

    if ($otherUfId) {
      // d6 compatible
      if ($config->userSystem->is_drupal == '1') {
        $otherUser = user_load($otherUfId);
      }
      elseif ($config->userFramework == 'Joomla') {
        $otherUser = JFactory::getUser($otherUfId);
      }

      $this->assign('otherUfId', $otherUfId);
      $this->assign('otherUfName', $otherUser ? $otherUser->name : NULL);
    }

    $cmsUser = ($mainUfId && $otherUfId) ? TRUE : FALSE;
    $this->assign('user', $cmsUser);

    $session = CRM_Core_Session::singleton();

    // context fixed.
    if ($rgid) {
      $urlParam = "reset=1&action=browse&rgid={$rgid}";
      if ($gid) {
        $urlParam .= "&gid={$gid}";
      }
      $session->pushUserContext(CRM_Utils_system::url('civicrm/contact/dedupefind', $urlParam));
    }

    // ensure that oid is not the current user, if so refuse to do the merge
    if ($session->get('userID') == $oid) {
      $display_name = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $oid, 'display_name');
      $message = ts('The contact record which is linked to the currently logged in user account - \'%1\' - cannot be deleted.',
        array(1 => $display_name)
      );
      CRM_Core_Error::statusBounce($message);
    }

    $rowsElementsAndInfo = CRM_Dedupe_Merger::getRowsElementsAndInfo($cid, $oid);
    $main                = &$rowsElementsAndInfo['main_details'];
    $other               = &$rowsElementsAndInfo['other_details'];

    if ($main['contact_id'] != $cid) {
      CRM_Core_Error::fatal(ts('The main contact record does not exist'));
    }

    if ($other['contact_id'] != $oid) {
      CRM_Core_Error::fatal(ts('The other contact record does not exist'));
    }

    $subtypes = CRM_Contact_BAO_ContactType::subTypePairs(NULL, TRUE, '');

    $this->assign('contact_type', $main['contact_type']);
    if (isset($main['contact_sub_type'])) {
      $this->assign('main_contact_subtype',
        CRM_Utils_Array::value('contact_sub_type', $subtypes[$main['contact_sub_type'][0]])
      );
    }
    if (isset($other['contact_sub_type'])) {
      $this->assign('other_contact_subtype',
        CRM_Utils_Array::value('contact_sub_type', $subtypes[$other['contact_sub_type'][0]])
      );
    }
    $this->assign('main_name', $main['display_name']);
    $this->assign('other_name', $other['display_name']);
    $this->assign('main_cid', $main['contact_id']);
    $this->assign('other_cid', $other['contact_id']);

    $this->_cid         = $cid;
    $this->_oid         = $oid;
    $this->_rgid        = $rgid;
    $this->_contactType = $main['contact_type'];
    $this->addElement('checkbox', 'toggleSelect', NULL, NULL, array('onclick' => "return toggleCheckboxVals('move_',this);"));

    $this->assign('mainLocBlock', json_encode($rowsElementsAndInfo['main_loc_block']));
    $this->assign('rows', $rowsElementsAndInfo['rows']);

    $this->_locBlockIds = array(
      'main' => $rowsElementsAndInfo['main_details']['loc_block_ids'],
      'other' => $rowsElementsAndInfo['other_details']['loc_block_ids']
    );

    // add elements
    foreach ($rowsElementsAndInfo['elements'] as $element) {
      $this->addElement($element[0],
        $element[1],
        array_key_exists('2', $element) ? $element[2] : NULL,
        array_key_exists('3', $element) ? $element[3] : NULL,
        array_key_exists('4', $element) ? $element[4] : NULL,
        array_key_exists('5', $element) ? $element[5] : NULL
      );
    }

    // add related table elements
    foreach ($rowsElementsAndInfo['rel_table_elements'] as $relTableElement) {
      $element = $this->addElement($relTableElement[0], $relTableElement[1]);
      $element->setChecked(TRUE);
    }

    $this->assign('rel_tables', $rowsElementsAndInfo['rel_tables']);
    $this->assign('userContextURL', $session->readUserContext());
  }

  function setDefaultValues() {
    return array('deleteOther' => 1);
  }

  function addRules() {}

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Merge %1s', array(1 => $this->_contactType)));
    $name = ts('Merge');
    if ($this->next) {
      $name = ts('Merge and Goto Next Pair');
    }

    if ($this->next || $this->prev) {
      $button = array(
        array(
          'type' => 'next',
          'name' => $name,
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'submit',
          'name' => ts('Merge and Goto Listing'),
        ),
        array(
          'type' => 'done',
          'name' => ts('Merge and View Result'),
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      );
    }
    else {
      $button = array(
        array(
          'type' => 'next',
          'name' => $name,
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      );
    }

    $this->addButtons($button);
  }

  public function postProcess() {
    $formValues = $this->exportValues();
 
    // reset all selected contact ids from session
    // when we came from search context, CRM-3526
    $session = CRM_Core_Session::singleton();
    if ($session->get('selectedSearchContactIds')) {
      $session->resetScope('selectedSearchContactIds');
    }

    $formValues['main_details'] = $formValues['other_details'] = array();
    $formValues['main_details']['contact_type'] = $this->_contactType;
    $formValues['main_details']['loc_block_ids'] = $this->_locBlockIds['main'];
    $formValues['other_details']['loc_block_ids'] = $this->_locBlockIds['other'];

    CRM_Dedupe_Merger::moveAllBelongings($this->_cid, $this->_oid, $formValues);

    CRM_Core_Session::setStatus(ts('Contact id %1 has been updated and contact id %2 has been deleted.', array(1 => $this->_cid, 2 => $this->_oid)), ts('Contacts Merged'), 'success');
    $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->_cid}");
    if (CRM_Utils_Array::value('_qf_Merge_submit', $formValues)) {
      $listParamsURL = "reset=1&action=update&rgid={$this->_rgid}";
      if ($this->_gid) {
        $listParamsURL .= "&gid={$this->_gid}";
      }
      $lisitingURL = CRM_Utils_System::url('civicrm/contact/dedupefind',
        $listParamsURL
      );
      CRM_Utils_System::redirect($lisitingURL);
    }
     if (CRM_Utils_Array::value('_qf_Merge_done', $formValues)) {
      CRM_Utils_System::redirect($url);
    }

    if ($this->next && $this->_mergeId) {
      $cacheKey = "merge {$this->_contactType}";
      $cacheKey .= $this->_rgid ? "_{$this->_rgid}" : '_0';
      $cacheKey .= $this->_gid ? "_{$this->_gid}" : '_0';

      $join = "LEFT JOIN civicrm_dedupe_exception de ON ( pn.entity_id1 = de.contact_id1 AND 
                                                                 pn.entity_id2 = de.contact_id2 )";
      $where = "de.id IS NULL";

      $pos = CRM_Core_BAO_PrevNextCache::getPositions($cacheKey, NULL, NULL, $this->_mergeId, $join, $where);

      if (!empty($pos) &&
        $pos['next']['id1'] &&
        $pos['next']['id2']
      ) {

        $urlParam = "reset=1&cid={$pos['next']['id1']}&oid={$pos['next']['id2']}&mergeId={$pos['next']['mergeId']}&action=update";
        if ($this->_rgid) {
          $urlParam .= "&rgid={$this->_rgid}";
        }
        if ($this->_gid) {
          $urlParam .= "&gid={$this->_gid}";
        }

        $url = CRM_Utils_system::url('civicrm/contact/merge', $urlParam);
      }
    }

    CRM_Utils_System::redirect($url);
  }
}

