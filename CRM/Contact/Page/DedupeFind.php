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
class CRM_Contact_Page_DedupeFind extends CRM_Core_Page_Basic {
  protected $_cid = NULL;
  protected $_rgid;
  protected $_mainContacts;
  protected $_gid;
  protected $action;
  /**
   * Only display selected.
   *
   * @var bool
   */
  protected $selected;

  /**
   * Get isSelected value.
   *
   * This needs to be an integer of 0 or 1 or NULL for no filter.
   *
   * @return bool|NULL
   */
  public function isSelected() {
    return ($this->selected === NULL) ? NULL : (int) $this->selected;
  }

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Dedupe_BAO_DedupeRuleGroup';
  }

  /**
   * Get action Links.
   */
  public function &links() {
  }

  /**
   * Initialize properties from input.
   */
  protected function initialize() {
    $this->selected = CRM_Utils_Request::retrieveValue('selected', 'Boolean');
  }

  /**
   * Browse all rule groups.
   */
  public function run() {
    $this->initialize();
    $gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE, 0);
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 0);
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $limit = CRM_Utils_Request::retrieve('limit', 'Integer', $this);
    $rgid = CRM_Utils_Request::retrieve('rgid', 'Positive', $this);
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);

    $criteria = CRM_Utils_Request::retrieve('criteria', 'Json', $this, FALSE, '{}');
    $this->assign('criteria', $criteria);

    $isConflictMode = ($context == 'conflicts');
    if ($cid) {
      $this->_cid = $cid;
    }
    if ($gid) {
      $this->_gid = $gid;
    }
    $this->_rgid = $rgid;

    $urlQry = [
      'reset' => 1,
      'rgid' => $rgid,
      'gid' => $gid,
      'limit' => (int) $limit,
      'criteria' => $criteria,
    ];
    $this->assign('urlQuery', CRM_Utils_System::makeQueryString($urlQry));
    $this->assign('isSelected', $this->isSelected());
    $criteria = json_decode($criteria, TRUE);
    $cacheKeyString = CRM_Dedupe_Merger::getMergeCacheKeyString($rgid, $gid, $criteria, TRUE, $limit);
    $this->assign('cacheKey', $cacheKeyString);

    if ($context == 'search') {
      $context = 'search';
      $this->assign('backURL', CRM_Core_Session::singleton()->readUserContext());
    }

    if ($action & CRM_Core_Action::RENEW) {
      // empty cache
      if ($rgid) {
        CRM_Core_BAO_PrevNextCache::deleteItem(NULL, $cacheKeyString);
      }
      $urlQry['action'] = 'update';
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/dedupefind', $urlQry));
    }
    elseif ($action & CRM_Core_Action::MAP) {
      // do a batch merge if requested
      $result = CRM_Dedupe_Merger::batchMerge($rgid, $gid, 'safe', 75, 2, $criteria, TRUE, NULL, $limit);

      $skippedCount = CRM_Utils_Request::retrieve('skipped', 'Positive', $this, FALSE, 0);
      $skippedCount = $skippedCount + count($result['skipped']);
      $mergedCount = CRM_Utils_Request::retrieve('merged', 'Positive', $this, FALSE, 0);
      $mergedCount = $mergedCount + count($result['merged']);

      if (empty($result['merged']) && empty($result['skipped'])) {
        $message = '';
        if ($mergedCount >= 1) {
          $message = ts("%1 pairs of duplicates were merged", [1 => $mergedCount]);
        }
        if ($skippedCount >= 1) {
          $message = $message ? "{$message} and " : '';
          $message .= ts("%1 pairs of duplicates were skipped due to conflict",
            [1 => $skippedCount]
          );
        }
        $message .= ts(" during the batch merge process with safe mode.");
        CRM_Core_Session::setStatus($message, ts('Merge Complete'), 'success');
        $urlQry['action'] = 'update';
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/dedupefind', $urlQry));
      }
      else {
        $urlQry['action'] = 'map';
        $urlQry['skipped'] = $skippedCount;
        $urlQry['merged'] = $mergedCount;
        CRM_Utils_System::jsRedirect(
          CRM_Utils_System::url('civicrm/contact/dedupefind', $urlQry),
          ts('Batch Merge Task in progress'),
          ts('The batch merge task is still in progress. This page will be refreshed automatically.')
        );
      }
    }

    if ($action & CRM_Core_Action::UPDATE ||
      $action & CRM_Core_Action::BROWSE
    ) {
      $this->action = CRM_Core_Action::UPDATE;

      $urlQry['snippet'] = 4;

      $this->assign('sourceUrl', CRM_Utils_System::url('civicrm/ajax/dedupefind', $urlQry, FALSE, NULL, FALSE));

      $stats = CRM_Dedupe_Merger::getMergeStats($cacheKeyString);
      if ($stats) {
        $message = CRM_Dedupe_Merger::getMergeStatsMsg($stats);
        $status = empty($stats['skipped']) ? 'success' : 'alert';
        CRM_Core_Session::setStatus($message, ts('Batch Complete'), $status, ['expires' => 0]);
        // reset so we not displaying same message again
        CRM_Dedupe_Merger::resetMergeStats($cacheKeyString);
      }

      $this->_mainContacts = CRM_Dedupe_Merger::getDuplicatePairs($rgid, $gid, !$isConflictMode, 0, $this->isSelected(), $isConflictMode, $criteria, TRUE, $limit);

      if (empty($this->_mainContacts)) {
        if ($isConflictMode) {
          // if the current screen was intended to list only selected contacts, move back to full dupe list
          $urlQry['action'] = 'update';
          unset($urlQry['snippet']);
          CRM_Utils_System::redirect(CRM_Utils_System::url(CRM_Utils_System::currentPath(), $urlQry));
        }
        $ruleGroupName = civicrm_api3('RuleGroup', 'getvalue', ['id' => $rgid, 'return' => 'title']);
        CRM_Core_Session::singleton()->setStatus(ts('No possible duplicates were found using %1 rule.', [1 => $ruleGroupName]), ts('None Found'), 'info');
        $url = CRM_Utils_System::url('civicrm/contact/deduperules', 'reset=1');
        if ($context == 'search') {
          $url = CRM_Core_Session::singleton()->readUserContext();
        }
        CRM_Utils_System::redirect($url);
      }
      else {
        $urlQry['action'] = 'update';
        if ($this->_cid) {
          $urlQry['cid'] = $this->_cid;
          CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/contact/deduperules',
            $urlQry
          ));
        }
        else {
          CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/contact/dedupefind',
            $urlQry
          ));
        }
      }

      $this->assign('action', $this->action);
      $this->browse();
    }
    else {
      $this->action = CRM_Core_Action::UPDATE;
      $this->edit($this->action);
      $this->assign('action', $this->action);
    }
    $this->assign('context', $context);

    return parent::run();
  }

  /**
   * Browse all rule groups.
   */
  public function browse() {
    $this->assign('main_contacts', $this->_mainContacts);

    if ($this->_cid) {
      $this->assign('cid', $this->_cid);
    }
    if (isset($this->_gid) || $this->_gid) {
      $this->assign('gid', $this->_gid);
    }
    $this->assign('rgid', $this->_rgid);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   classname of edit form
   */
  public function editForm() {
    return 'CRM_Contact_Form_DedupeFind';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page
   */
  public function editName() {
    return 'DedupeFind';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context
   */
  public function userContext($mode = NULL) {
    return 'civicrm/contact/dedupefind';
  }

}
