<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * Class CRM_Contact_Form_Merge.
 */
class CRM_Contact_Form_Merge extends CRM_Core_Form {
  // the id of the contact that tere's a duplicate for; this one will
  // possibly inherit some of $_oid's properties and remain in the system
  var $_cid = NULL;

  // the id of the other contact - the duplicate one that will get deleted
  var $_oid = NULL;

  var $_contactType = NULL;

  /**
   * Query limit to be retained in the urls.
   *
   * @var int
   */
  var $limit;

  // FIXME: QuickForm can't create advcheckboxes with value set to 0 or '0' :(
  // see HTML_QuickForm_advcheckbox::setValues() - but patching that doesn't
  // help, as QF doesn't put the 0-value elements in exportValues() anyway...
  // to side-step this, we use the below UUID as a (re)placeholder
  var $_qfZeroBug = 'e8cddb72-a257-11dc-b9cc-0016d3330ee9';

  public function preProcess() {
    if (!CRM_Core_Permission::check('merge duplicate contacts')) {
      CRM_Core_Error::fatal(ts('You do not have access to this page'));
    }

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $oid = CRM_Utils_Request::retrieve('oid', 'Positive', $this, TRUE);
    $flip = CRM_Utils_Request::retrieve('flip', 'Positive', $this, FALSE);

    $this->_rgid = CRM_Utils_Request::retrieve('rgid', 'Positive', $this, FALSE);
    $this->_gid = $gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE);
    $this->_mergeId = CRM_Utils_Request::retrieve('mergeId', 'Positive', $this, FALSE);
    $this->limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this, FALSE);
    $urlParams = "reset=1&rgid={$this->_rgid}&gid={$this->_gid}&limit=" . $this->limit;

    // Sanity check
    if ($cid == $oid) {
      CRM_Core_Error::statusBounce(ts('Cannot merge a contact with itself.'));
    }

    if (!CRM_Dedupe_BAO_Rule::validateContacts($cid, $oid)) {
      CRM_Core_Error::statusBounce(ts('The selected pair of contacts are marked as non duplicates. If these records should be merged, you can remove this exception on the <a href="%1">Dedupe Exceptions</a> page.', array(1 => CRM_Utils_System::url('civicrm/dedupe/exception', 'reset=1'))));
    }
    $this->_contactType = civicrm_api3('Contact', 'getvalue', array('id' => $cid, 'return' => 'contact_type'));
    $isFromDedupeScreen = TRUE;
    if (!$this->_rgid) {
      $isFromDedupeScreen = FALSE;
      $this->_rgid = civicrm_api3('RuleGroup', 'getvalue', array(
        'contact_type' => $this->_contactType,
        'used' => 'Supervised',
        'return' => 'id',
      ));
    }

    $cacheKey = CRM_Dedupe_Merger::getMergeCacheKeyString($this->_rgid, $gid);

    $join = CRM_Dedupe_Merger::getJoinOnDedupeTable();
    $where = "de.id IS NULL";

    $pos = CRM_Core_BAO_PrevNextCache::getPositions($cacheKey, $cid, $oid, $this->_mergeId, $join, $where, $flip);

    // Block access if user does not have EDIT permissions for both contacts.
    if (!(CRM_Contact_BAO_Contact_Permission::allow($cid, CRM_Core_Permission::EDIT) &&
      CRM_Contact_BAO_Contact_Permission::allow($oid, CRM_Core_Permission::EDIT)
    )
    ) {
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

    $flipUrl = CRM_Utils_System::url('civicrm/contact/merge',
      "reset=1&action=update&cid={$oid}&oid={$cid}&rgid={$this->_rgid}&gid={$gid}"
    );
    if (!$flip) {
      $flipUrl .= '&flip=1';
    }
    $this->assign('flip', $flipUrl);

    $this->prev = $this->next = NULL;
    foreach (array(
               'prev',
               'next',
             ) as $position) {
      if (!empty($pos[$position])) {
        if ($pos[$position]['id1'] && $pos[$position]['id2']) {
          $urlParams .= "&cid={$pos[$position]['id1']}&oid={$pos[$position]['id2']}&mergeId={$pos[$position]['mergeId']}&action=update";
          $this->$position = CRM_Utils_System::url('civicrm/contact/merge', $urlParams);
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
    if ($isFromDedupeScreen) {
      $browseUrl = CRM_Utils_System::url('civicrm/contact/dedupefind', $urlParams . '&action=browse');
      $session->pushUserContext($browseUrl);
    }
    $this->assign('browseUrl', empty($browseUrl) ? '' : $browseUrl);

    // ensure that oid is not the current user, if so refuse to do the merge
    if ($session->get('userID') == $oid) {
      $display_name = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $oid, 'display_name');
      $message = ts('The contact record which is linked to the currently logged in user account - \'%1\' - cannot be deleted.',
        array(1 => $display_name)
      );
      CRM_Core_Error::statusBounce($message);
    }

    $rowsElementsAndInfo = CRM_Dedupe_Merger::getRowsElementsAndInfo($cid, $oid);
    $main = $this->_mainDetails = &$rowsElementsAndInfo['main_details'];
    $other = $this->_otherDetails = &$rowsElementsAndInfo['other_details'];

    if ($main['contact_id'] != $cid) {
      CRM_Core_Error::fatal(ts('The main contact record does not exist'));
    }

    if ($other['contact_id'] != $oid) {
      CRM_Core_Error::fatal(ts('The other contact record does not exist'));
    }

    $this->assign('contact_type', $main['contact_type']);
    $this->assign('main_name', $main['display_name']);
    $this->assign('other_name', $other['display_name']);
    $this->assign('main_cid', $main['contact_id']);
    $this->assign('other_cid', $other['contact_id']);
    $this->assign('rgid', $this->_rgid);

    $this->_cid = $cid;
    $this->_oid = $oid;

    $this->addElement('checkbox', 'toggleSelect', NULL, NULL, array('class' => 'select-rows'));

    $this->assign('mainLocBlock', json_encode($rowsElementsAndInfo['main_details']['location_blocks']));
    $this->assign('locationBlockInfo', json_encode(CRM_Dedupe_Merger::getLocationBlockInfo()));
    $this->assign('rows', $rowsElementsAndInfo['rows']);

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

  /**
   * This virtual function is used to set the default values of
   * various form elements
   *
   * access        public
   *
   * @return array
   *   reference to the array of default values
   */
  /**
   * @return array
   */
  public function setDefaultValues() {
    return array('deleteOther' => 1);
  }

  public function addRules() {
  }

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Merge %1 contacts', array(1 => $this->_contactType)));
    $buttons = array();

    $buttons[] = array(
      'type' => 'next',
      'name' => $this->next ? ts('Merge and go to Next Pair') : ts('Merge'),
      'isDefault' => TRUE,
      'icon' => $this->next ? 'circle-triangle-e' : 'check',
    );

    if ($this->next || $this->prev) {
      $buttons[] = array(
        'type' => 'submit',
        'name' => ts('Merge and go to Listing'),
      );
      $buttons[] = array(
        'type' => 'done',
        'name' => ts('Merge and View Result'),
        'icon' => 'fa-check-circle',
      );
    }

    $buttons[] = array(
      'type' => 'cancel',
      'name' => ts('Cancel'),
    );

    $this->addButtons($buttons);
    $this->addFormRule(array('CRM_Contact_Form_Merge', 'formRule'), $this);
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();
    $link = CRM_Utils_System::href(ts('Flip between the original and duplicate contacts.'),
      'civicrm/contact/merge',
      'reset=1&action=update&cid=' . $self->_oid . '&oid=' . $self->_cid . '&rgid=' . $self->_rgid . '&flip=1'
    );
    if (CRM_Contact_BAO_Contact::checkDomainContact($self->_oid)) {
      $errors['_qf_default'] = ts("The Default Organization contact cannot be merged into another contact record. It is associated with the CiviCRM installation for this domain and contains information used for system functions. If you want to merge these records, you can: %1", array(1 => $link));
    }
    return $errors;
  }

  public function postProcess() {
    $formValues = $this->exportValues();

    // reset all selected contact ids from session
    // when we came from search context, CRM-3526
    $session = CRM_Core_Session::singleton();
    if ($session->get('selectedSearchContactIds')) {
      $session->resetScope('selectedSearchContactIds');
    }

    $formValues['main_details'] = $this->_mainDetails;
    $formValues['other_details'] = $this->_otherDetails;
    $migrationData = array('migration_info' => $formValues);
    CRM_Utils_Hook::merge('form', $migrationData, $this->_cid, $this->_oid);
    CRM_Dedupe_Merger::moveAllBelongings($this->_cid, $this->_oid, $migrationData['migration_info']);

    $name = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_cid, 'display_name');
    $message = '<ul><li>' . ts('%1 has been updated.', array(1 => $name)) . '</li><li>' . ts('Contact ID %1 has been deleted.', array(1 => $this->_oid)) . '</li></ul>';
    CRM_Core_Session::setStatus($message, ts('Contacts Merged'), 'success');

    $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->_cid}");
    $urlParams = "reset=1&gid={$this->_gid}&rgid={$this->_rgid}&limit={$this->limit}";

    if (!empty($formValues['_qf_Merge_submit'])) {
      $urlParams .= "&action=update";
      $lisitingURL = CRM_Utils_System::url('civicrm/contact/dedupefind',
        $urlParams
      );
      CRM_Utils_System::redirect($lisitingURL);
    }
    if (!empty($formValues['_qf_Merge_done'])) {
      CRM_Utils_System::redirect($url);
    }

    if ($this->next && $this->_mergeId) {
      $cacheKey = CRM_Dedupe_Merger::getMergeCacheKeyString($this->_rgid, $this->_gid);

      $join = CRM_Dedupe_Merger::getJoinOnDedupeTable();
      $where = "de.id IS NULL";

      $pos = CRM_Core_BAO_PrevNextCache::getPositions($cacheKey, NULL, NULL, $this->_mergeId, $join, $where);

      if (!empty($pos) &&
        $pos['next']['id1'] &&
        $pos['next']['id2']
      ) {

        $urlParams .= "&cid={$pos['next']['id1']}&oid={$pos['next']['id2']}&mergeId={$pos['next']['mergeId']}&action=update";
        $url = CRM_Utils_System::url('civicrm/contact/merge', $urlParams);
      }
    }

    CRM_Utils_System::redirect($url);
  }

}
