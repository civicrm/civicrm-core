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
class CRM_Contact_Page_DedupeFind extends CRM_Core_Page_Basic {
  protected $_cid = NULL;
  protected $_rgid;
  protected $_mainContacts;
  protected $_gid;
  protected $action;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Dedupe_BAO_RuleGroup';
  }

  /**
   * Get action Links.
   */
  public function &links() {
  }

  /**
   * Browse all rule groups.
   */
  public function run() {
    $gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE, 0);
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 0);
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $limit = CRM_Utils_Request::retrieve('limit', 'Integer', $this);
    $rgid = CRM_Utils_Request::retrieve('rgid', 'Positive');
    $urlQry = "reset=1&rgid={$rgid}&gid={$gid}&limit={$limit}";
    $this->assign('urlQuery', $urlQry);

    $session = CRM_Core_Session::singleton();
    $contactIds = $session->get('selectedSearchContactIds');
    if ($context == 'search' || !empty($contactIds)) {
      $context = 'search';
      $this->assign('backURL', $session->readUserContext());
    }

    if ($action & CRM_Core_Action::RENEW) {
      // empty cache

      if ($rgid) {
        CRM_Core_BAO_PrevNextCache::deleteItem(NULL, CRM_Dedupe_Merger::getMergeCacheKeyString($rgid, $gid));
      }
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/dedupefind', $urlQry . "&action=update"));
    }
    elseif ($action & CRM_Core_Action::MAP) {
      // do a batch merge if requested
      $result = CRM_Dedupe_Merger::batchMerge($rgid, $gid, 'safe', TRUE, 75);

      $skippedCount = CRM_Utils_Request::retrieve('skipped', 'Positive', $this, FALSE, 0);
      $skippedCount = $skippedCount + count($result['skipped']);
      $mergedCount = CRM_Utils_Request::retrieve('merged', 'Positive', $this, FALSE, 0);
      $mergedCount = $mergedCount + count($result['merged']);

      if (empty($result['merged']) && empty($result['skipped'])) {
        $message = '';
        if ($mergedCount >= 1) {
          $message = ts("%1 pairs of duplicates were merged", array(1 => $mergedCount));
        }
        if ($skippedCount >= 1) {
          $message = $message ? "{$message} and " : '';
          $message .= ts("%1 pairs of duplicates were skipped due to conflict",
            array(1 => $skippedCount)
          );
        }
        $message .= ts(" during the batch merge process with safe mode.");
        CRM_Core_Session::setStatus($message, ts('Merge Complete'), 'success');
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/dedupefind', $urlQry . "&action=update"));
      }
      else {
        $urlQry .= "&action=map&skipped={$skippedCount}&merged={$mergedCount}";
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
      $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);
      $this->action = CRM_Core_Action::UPDATE;

      $urlQry .= '&snippet=4';
      if ($context == 'conflicts') {
        $urlQry .= "&selected=1";
      }

      $this->assign('sourceUrl', CRM_Utils_System::url('civicrm/ajax/dedupefind', $urlQry, FALSE, NULL, FALSE));

      //reload from cache table
      $cacheKeyString = CRM_Dedupe_Merger::getMergeCacheKeyString($rgid, $gid);

      $stats = CRM_Dedupe_Merger::getMergeStatsMsg($cacheKeyString);
      if ($stats) {
        CRM_Core_Session::setStatus($stats);
        // reset so we not displaying same message again
        CRM_Dedupe_Merger::resetMergeStats($cacheKeyString);
      }
      $join = CRM_Dedupe_Merger::getJoinOnDedupeTable();
      $where = "de.id IS NULL";
      if ($context == 'conflicts') {
        $where .= " AND pn.is_selected = 1";
      }
      $this->_mainContacts = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, $join, $where);
      if (empty($this->_mainContacts)) {
        if ($context == 'conflicts') {
          // if the current screen was intended to list only selected contacts, move back to full dupe list
          CRM_Utils_System::redirect(CRM_Utils_System::url(CRM_Utils_System::currentPath(), $urlQry . '&action=update'));
        }
        if ($gid) {
          $foundDupes = $this->get("dedupe_dupes_$gid");
          if (!$foundDupes) {
            $foundDupes = CRM_Dedupe_Finder::dupesInGroup($rgid, $gid, $limit);
          }
          $this->set("dedupe_dupes_$gid", $foundDupes);
        }
        elseif (!empty($contactIds)) {
          $foundDupes = $this->get("search_dedupe_dupes_$gid");
          if (!$foundDupes) {
            $foundDupes = CRM_Dedupe_Finder::dupes($rgid, $contactIds);
          }
          $this->set("search_dedupe_dupes_$gid", $foundDupes);
        }
        else {
          $foundDupes = $this->get('dedupe_dupes');
          if (!$foundDupes) {
            $foundDupes = CRM_Dedupe_Finder::dupes($rgid, array(), TRUE, $limit);
          }
          $this->set('dedupe_dupes', $foundDupes);
        }
        if (!$foundDupes) {
          $ruleGroup = new CRM_Dedupe_BAO_RuleGroup();
          $ruleGroup->id = $rgid;
          $ruleGroup->find(TRUE);

          $session = CRM_Core_Session::singleton();
          $session->setStatus(ts('No possible duplicates were found using %1 rule.', array(1 => $ruleGroup->name)), ts('None Found'), 'info');
          $url = CRM_Utils_System::url('civicrm/contact/deduperules', 'reset=1');
          if ($context == 'search') {
            $url = $session->readUserContext();
          }
          CRM_Utils_System::redirect($url);
        }
        else {
          $mainContacts = CRM_Dedupe_Finder::parseAndStoreDupePairs($foundDupes, $cacheKeyString);

          if ($cid) {
            $this->_cid = $cid;
          }
          if ($gid) {
            $this->_gid = $gid;
          }
          $this->_rgid = $rgid;
          $this->_mainContacts = $mainContacts;

          $session = CRM_Core_Session::singleton();
          if ($this->_cid) {
            $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/deduperules',
              $urlQry . "&action=update&cid={$this->_cid}"
            ));
          }
          else {
            $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/dedupefind',
              $urlQry . "&action=update"
            ));
          }
        }
      }
      else {
        if ($cid) {
          $this->_cid = $cid;
        }
        if ($gid) {
          $this->_gid = $gid;
        }
        $this->_rgid = $rgid;
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

    // parent run
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
