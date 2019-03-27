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

/**
 * Class CRM_Contact_Form_Merge.
 */
class CRM_Contact_Form_Merge extends CRM_Core_Form {
  // The id of the contact that there's a duplicate for; this one will
  // possibly inherit some of $_oid's properties and remain in the system.
  var $_cid = NULL;

  // The id of the other contact - the duplicate one that will get deleted.
  var $_oid = NULL;

  var $_contactType = NULL;

  /**
   * @var array
   */
  public $criteria = array();

  /**
   * Query limit to be retained in the urls.
   *
   * @var int
   */
  var $limit;

  /**
   * String for quickform bug handling.
   *
   * FIXME: QuickForm can't create advcheckboxes with value set to 0 or '0' :(
   * see HTML_QuickForm_advcheckbox::setValues() - but patching that doesn't
   * help, as QF doesn't put the 0-value elements in exportValues() anyway...
   * to side-step this, we use the below UUID as a (re)placeholder
   *
   * @var string
   */
  var $_qfZeroBug = 'e8cddb72-a257-11dc-b9cc-0016d3330ee9';

  public function preProcess() {
    try {

      $this->_cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
      $this->_oid = CRM_Utils_Request::retrieve('oid', 'Positive', $this, TRUE);
      $flip = CRM_Utils_Request::retrieve('flip', 'Positive', $this, FALSE);

      $this->_rgid = CRM_Utils_Request::retrieve('rgid', 'Positive', $this, FALSE);
      $this->_gid = $gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE);
      $this->_mergeId = CRM_Utils_Request::retrieve('mergeId', 'Positive', $this, FALSE);
      $this->limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this, FALSE);
      $this->criteria = CRM_Utils_Request::retrieve('criteria', 'Json', $this, FALSE, '{}');

      $urlParams = ['reset' => 1, 'rgid' => $this->_rgid, 'gid' => $this->_gid, 'limit' => $this->limit, 'criteria' => $this->criteria];

      $this->bounceIfInvalid($this->_cid, $this->_oid);

      $contacts = civicrm_api3('Contact', 'get', [
        'id' => ['IN' => [$this->_cid, $this->_oid]],
        'return' => ['contact_type', 'modified_date', 'created_date', 'contact_sub_type'],
      ])['values'];

      $this->_contactType = $contacts[$this->_cid]['contact_type'];

      $browseUrl = CRM_Utils_System::url('civicrm/contact/dedupefind', array_merge($urlParams, ['action' => 'browse']));

      if (!$this->_rgid) {
        // Unset browse URL as we have come from the search screen.
        $browseUrl = '';
        $this->_rgid = civicrm_api3('RuleGroup', 'getvalue', array(
          'contact_type' => $this->_contactType,
          'used' => 'Supervised',
          'return' => 'id',
        ));
      }
      $this->assign('browseUrl', $browseUrl);
      if ($browseUrl) {
        CRM_Core_Session::singleton()->pushUserContext($browseUrl);
      }

      $cacheKey = CRM_Dedupe_Merger::getMergeCacheKeyString($this->_rgid, $gid, json_decode($this->criteria, TRUE));

      $join = CRM_Dedupe_Merger::getJoinOnDedupeTable();
      $where = "de.id IS NULL";

      $pos = CRM_Core_BAO_PrevNextCache::getPositions($cacheKey, $this->_cid, $this->_oid, $this->_mergeId, $join, $where, $flip);

      // get user info of main contact.
      $config = CRM_Core_Config::singleton();
      CRM_Core_Config::setPermitCacheFlushMode(FALSE);

      $mainUfId = CRM_Core_BAO_UFMatch::getUFId($this->_cid);
      $mainUser = NULL;
      if ($mainUfId) {
        $mainUser = $config->userSystem->getUser($this->_cid);
        $this->assign('mainUfId', $mainUfId);
        $this->assign('mainUfName', $mainUser ? $mainUser['name'] : NULL);
      }
      $flipParams = array_merge($urlParams, ['action' => 'update', 'cid' => $this->_oid, 'oid' => $this->_cid]);
      if (!$flip) {
        $flipParams['flip'] = '1';
      }
      $flipUrl = CRM_Utils_System::url('civicrm/contact/merge',
        $flipParams
      );
      $this->assign('flip', $flipUrl);

      $this->prev = $this->next = NULL;
      foreach (array(
                 'prev',
                 'next',
               ) as $position) {
        if (!empty($pos[$position])) {
          if ($pos[$position]['id1'] && $pos[$position]['id2']) {
            $rowParams = array_merge($urlParams, [
              'action' => 'update',
              'cid' => $pos[$position]['id1'],
              'oid' => $pos[$position]['id2'],
              'mergeId' => $pos[$position]['mergeId'],
            ]);
            $this->$position = CRM_Utils_System::url('civicrm/contact/merge', $rowParams);
            $this->assign($position, $this->$position);
          }
        }
      }

      // get user info of other contact.
      $otherUfId = CRM_Core_BAO_UFMatch::getUFId($this->_oid);
      $otherUser = NULL;

      if ($otherUfId) {
        $otherUser = $config->userSystem->getUser($this->_oid);
        $this->assign('otherUfId', $otherUfId);
        $this->assign('otherUfName', $otherUser ? $otherUser['name'] : NULL);
      }

      $cmsUser = ($mainUfId && $otherUfId) ? TRUE : FALSE;
      $this->assign('user', $cmsUser);

      $rowsElementsAndInfo = CRM_Dedupe_Merger::getRowsElementsAndInfo($this->_cid, $this->_oid);
      $main = $this->_mainDetails = $rowsElementsAndInfo['main_details'];
      $other = $this->_otherDetails = $rowsElementsAndInfo['other_details'];

      $this->assign('contact_type', $main['contact_type']);
      $this->assign('main_name', $main['display_name']);
      $this->assign('other_name', $other['display_name']);
      $this->assign('main_cid', $main['contact_id']);
      $this->assign('other_cid', $other['contact_id']);
      $this->assign('rgid', $this->_rgid);
      $this->assignSummaryRowsToTemplate($contacts);

      $this->addElement('checkbox', 'toggleSelect', NULL, NULL, array('class' => 'select-rows'));

      $this->assign('mainLocBlock', json_encode($rowsElementsAndInfo['main_details']['location_blocks']));
      $this->assign('locationBlockInfo', json_encode(CRM_Dedupe_Merger::getLocationBlockInfo()));
      $this->assign('mainContactTypeIcon', CRM_Contact_BAO_Contact_Utils::getImage($contacts[$this->_cid]['contact_sub_type'] ? $contacts[$this->_cid]['contact_sub_type'] : $contacts[$this->_cid]['contact_type'],
        FALSE,
        $this->_cid
      ));
      $this->assign('otherContactTypeIcon', CRM_Contact_BAO_Contact_Utils::getImage($contacts[$this->_oid]['contact_sub_type'] ? $contacts[$this->_oid]['contact_sub_type'] : $contacts[$this->_oid]['contact_type'],
        FALSE,
        $this->_oid
      ));

      if (isset($rowsElementsAndInfo['rows']['move_contact_type'])) {
        // We don't permit merging contacts of different types so this is just clutter - putting
        // the icon next to the contact name is consistent with elsewhere and permits hover-info
        // https://lab.civicrm.org/dev/core/issues/824
        unset($rowsElementsAndInfo['rows']['move_contact_type']);
      }

      $this->assign('rows', $rowsElementsAndInfo['rows']);

      // add elements
      foreach ($rowsElementsAndInfo['elements'] as $element) {
        // We could push this down to the getRowsElementsAndInfo function but it's
        // already so overloaded - let's start moving towards doing form-things
        // on the form.
        if (substr($element[1], 0, 13) === 'move_location') {
          $element[4] = array_merge(
            (array) CRM_Utils_Array::value(4, $element, array()),
            array(
              'data-location' => substr($element[1], 14),
              'data-is_location' => TRUE,
            ));
        }
        if (substr($element[1], 0, 15) === 'location_blocks') {
          // @todo We could add some data elements here to make jquery manipulation more straight-forward
          // @todo consider enabling if it is an add & defaulting to true.
          $element[4] = array_merge((array) CRM_Utils_Array::value(4, $element, array()), array('disabled' => TRUE));
        }
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
      $this->assign('userContextURL', CRM_Core_Session::singleton()
        ->readUserContext());
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Error::statusBounce(ts($e->getMessage()));
    }
  }

  public function addRules() {
  }

  public function buildQuickForm() {
    $this->unsavedChangesWarn = FALSE;
    CRM_Utils_System::setTitle(ts('Merge %1 contacts', array(1 => $this->_contactType)));
    $buttons = array();

    $buttons[] = array(
      'type' => 'next',
      'name' => $this->next ? ts('Merge and go to Next Pair') : ts('Merge'),
      'isDefault' => TRUE,
      'icon' => $this->next ? 'fa-play-circle' : 'check',
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

    $formValues['main_details'] = $this->_mainDetails;
    $formValues['other_details'] = $this->_otherDetails;
    $migrationData = array('migration_info' => $formValues);
    CRM_Utils_Hook::merge('form', $migrationData, $this->_cid, $this->_oid);
    CRM_Dedupe_Merger::moveAllBelongings($this->_cid, $this->_oid, $migrationData['migration_info']);

    $name = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_cid, 'display_name');
    $message = '<ul><li>' . ts('%1 has been updated.', array(1 => $name)) . '</li><li>' . ts('Contact ID %1 has been deleted.', array(1 => $this->_oid)) . '</li></ul>';
    CRM_Core_Session::setStatus($message, ts('Contacts Merged'), 'success');

    $urlParams = ['reset' => 1, 'cid' => $this->_cid, 'rgid' => $this->_rgid, 'gid' => $this->_gid, 'limit' => $this->limit, 'criteria' => $this->criteria];
    $contactViewUrl = CRM_Utils_System::url('civicrm/contact/view', ['reset' => 1, 'cid' => $this->_cid]);

    if (!empty($formValues['_qf_Merge_submit'])) {
      $urlParams['action'] = "update";
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/dedupefind',
        $urlParams
      ));
    }
    if (!empty($formValues['_qf_Merge_done'])) {
      CRM_Utils_System::redirect($contactViewUrl);
    }

    if ($this->next && $this->_mergeId) {
      $cacheKey = CRM_Dedupe_Merger::getMergeCacheKeyString($this->_rgid, $this->_gid, json_decode($this->criteria, TRUE));

      $join = CRM_Dedupe_Merger::getJoinOnDedupeTable();
      $where = "de.id IS NULL";

      $pos = CRM_Core_BAO_PrevNextCache::getPositions($cacheKey, NULL, NULL, $this->_mergeId, $join, $where);

      if (!empty($pos) &&
        $pos['next']['id1'] &&
        $pos['next']['id2']
      ) {

        $urlParams['cid'] = $pos['next']['id1'];
        $urlParams['oid'] = $pos['next']['id2'];
        $urlParams['mergeId'] = $pos['next']['mergeId'];
        $urlParams['action'] = 'update';
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/merge', $urlParams));
      }
    }

    // Perhaps never reached.
    CRM_Utils_System::redirect($contactViewUrl);
  }

  /**
   * Bounce if the merge action is invalid.
   *
   * We don't allow the merge if it is nonsensical, marked as a duplicate
   * or outside the user's permission.
   *
   * @param int $cid
   *   Contact ID to retain
   * @param int $oid
   *   Contact ID to delete.
   */
  public function bounceIfInvalid($cid, $oid) {
    if ($cid == $oid) {
      CRM_Core_Error::statusBounce(ts('Cannot merge a contact with itself.'));
    }

    if (!CRM_Dedupe_BAO_Rule::validateContacts($cid, $oid)) {
      CRM_Core_Error::statusBounce(ts('The selected pair of contacts are marked as non duplicates. If these records should be merged, you can remove this exception on the <a href="%1">Dedupe Exceptions</a> page.', array(1 => CRM_Utils_System::url('civicrm/dedupe/exception', 'reset=1'))));
    }

    if (!(CRM_Contact_BAO_Contact_Permission::allow($cid, CRM_Core_Permission::EDIT) &&
      CRM_Contact_BAO_Contact_Permission::allow($oid, CRM_Core_Permission::EDIT)
    )
    ) {
      CRM_Utils_System::permissionDenied();
    }
    // ensure that oid is not the current user, if so refuse to do the merge
    if (CRM_Core_Session::singleton()->getLoggedInContactID() == $oid) {
      $message = ts('The contact record which is linked to the currently logged in user account - \'%1\' - cannot be deleted.',
        array(1 => CRM_Core_Session::singleton()->getLoggedInContactDisplayName())
      );
      CRM_Core_Error::statusBounce($message);
    }
  }

  /**
   * Assign the summary_rows variable to the tpl.
   *
   * This adds rows to the beginning of the block that will help in making merge choices.
   *
   * It can be modified by a hook by altering what is assigned. Although not technically supported this
   * is an easy tweak with no earth-shattering impacts if later changes stop if from working.
   *
   * https://lab.civicrm.org/dev/core/issues/824
   *
   * @param array $contacts
   */
  protected function assignSummaryRowsToTemplate($contacts) {
    $mostRecent = ($contacts[$this->_cid]['modified_date'] < $contacts[$this->_oid]['modified_date']) ? $this->_oid : $this->_cid;
    $this->assign('summary_rows', [
      [
        'name' => 'created_date',
        'label' => ts('Created'),
        'main_contact_value' => CRM_Utils_Date::customFormat($contacts[$this->_cid]['created_date']),
        'other_contact_value' => CRM_Utils_Date::customFormat($contacts[$this->_oid]['created_date']),
      ],
      [
        'name' => 'modified_date',
        'label' => ts('Last Modified'),
        'main_contact_value' => CRM_Utils_Date::customFormat($contacts[$this->_cid]['modified_date']) . ($mostRecent == $this->_cid ? ' (' . ts('Most Recent') . ')' : ''),
        'other_contact_value' => CRM_Utils_Date::customFormat($contacts[$this->_oid]['modified_date']) . ($mostRecent == $this->_oid ? ' (' . ts('Most Recent') . ')' : ''),
      ],
    ]);
  }

}
