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
class CRM_Contact_Page_DedupeRules extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

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
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      $deleteExtra = ts('Are you sure you want to delete this Rule?');

      // helper variable for nicer formatting
      $links = [];

      if (CRM_Core_Permission::check('merge duplicate contacts')) {
        $links[CRM_Core_Action::VIEW] = [
          'name' => ts('Use Rule'),
          'url' => 'civicrm/contact/dedupefind',
          'qs' => 'reset=1&rgid=%%id%%&action=preview',
          'title' => ts('Use DedupeRule'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        ];
      }
      if (CRM_Core_Permission::check('administer dedupe rules')) {
        $links[CRM_Core_Action::UPDATE] = [
          'name' => ts('Edit Rule'),
          'url' => 'civicrm/contact/deduperules',
          'qs' => 'action=update&id=%%id%%',
          'title' => ts('Edit DedupeRule'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ];
        $links[CRM_Core_Action::DELETE] = [
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/deduperules',
          'qs' => 'action=delete&id=%%id%%',
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
          'title' => ts('Delete DedupeRule'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ];
      }

      self::$_links = $links;
    }
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the type
   * of action and executes that action. Finally it calls the parent's run
   * method.
   */
  public function run() {
    $id = $this->getIdAndAction();

    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE);
    if ($context == 'nonDupe') {
      CRM_Core_Session::setStatus(ts('Selected contacts have been marked as not duplicates'), ts('Changes Saved'), 'success');
    }

    // assign permissions vars to template
    $this->assign('hasperm_administer_dedupe_rules', CRM_Core_Permission::check('administer dedupe rules'));
    $this->assign('hasperm_merge_duplicate_contacts', CRM_Core_Permission::check('merge duplicate contacts'));

    // which action to take?
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $this->edit($this->_action, $id);
    }
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->delete($id);
    }

    // browse the rules
    $this->browse();

    // This replaces parent run, but do parent's parent run
    return CRM_Core_Page::run();
  }

  /**
   * Browse all rule groups.
   */
  public function browse() {
    $contactTypes = array_column(CRM_Contact_BAO_ContactType::basicTypeInfo(), 'label', 'name');
    $dedupeRuleTypes = CRM_Core_SelectValues::getDedupeRuleTypes();
    $ruleGroups = array_fill_keys(array_keys($contactTypes), []);

    // Get rule groups for enabled contact types
    $dao = new CRM_Dedupe_DAO_DedupeRuleGroup();
    $dao->orderBy('used ASC, title ASC');
    $dao->whereAdd('contact_type IN ("' . implode('","', array_keys($contactTypes)) . '")');
    $dao->find();

    while ($dao->fetch()) {
      $ruleGroups[$dao->contact_type][$dao->id] = [];
      CRM_Core_DAO::storeValues($dao, $ruleGroups[$dao->contact_type][$dao->id]);

      // form all action links
      $action = array_sum(array_keys($this->links()));
      $links = $this->links();

      if ($dao->is_reserved) {
        unset($links[CRM_Core_Action::DELETE]);
      }

      $ruleGroups[$dao->contact_type][$dao->id]['action'] = CRM_Core_Action::formLink(
        $links,
        $action,
        ['id' => $dao->id],
        ts('more'),
        FALSE,
        'dedupeRule.manage.action',
        'DedupeRule',
        $dao->id
      );

      $ruleGroups[$dao->contact_type][$dao->id]['used_display'] = $dedupeRuleTypes[$ruleGroups[$dao->contact_type][$dao->id]['used']];
    }
    $this->assign('brows', $ruleGroups);
    $this->assign('contactTypes', $contactTypes);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   classname of edit form
   */
  public function editForm() {
    return 'CRM_Contact_Form_DedupeRules';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page
   */
  public function editName() {
    return 'DedupeRules';
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
    return 'civicrm/contact/deduperules';
  }

  /**
   * @param int $id
   */
  public function delete($id) {
    $ruleDao = new CRM_Dedupe_DAO_DedupeRule();
    $ruleDao->dedupe_rule_group_id = $id;
    $ruleDao->delete();

    $rgDao = new CRM_Dedupe_DAO_DedupeRuleGroup();
    $rgDao->id = $id;
    if ($rgDao->find(TRUE)) {
      $rgDao->delete();
      CRM_Core_Session::setStatus(ts("The rule '%1' has been deleted.", [1 => $rgDao->title]), ts('Rule Deleted'), 'success');
      CRM_Utils_System::redirect(CRM_Utils_System::url($this->userContext(), 'reset=1'));
    }
  }

}
