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

use Civi\Api4\MembershipType;

/**
 * Page for displaying list of membership types
 */
class CRM_Member_Page_MembershipType extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

  public $useLivePageJS = TRUE;

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/member/membershipType/add',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Membership Type'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Membership Type'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Membership Type'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/member/membershipType/add',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Membership Type'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   */
  public function run() {
    $this->browse();

    // parent run
    return parent::run();
  }

  /**
   * Browse all membership types.
   *
   * @throws \CRM_Core_Exception
   */
  public function browse(): void {
    // Ensure an action is assigned, even null - since this page is overloaded for other uses
    // we need to avoid e-notices.
    $this->assign('action');
    $membershipType = (array) MembershipType::get()
      ->addOrderBy('weight')
      ->setSelect([
        'id',
        'domain_id',
        'name',
        'fixed_period_start_day',
        'fixed_period_rollover_day',
        'max_related',
        'relationship_type_id',
        'relationship_direction',
        'member_of_contact_id',
        'financial_type_id',
        'minimum_fee',
        'duration_unit',
        'duration_interval',
        'period_type:label',
        'visibility:label',
        'weight',
        'auto_renew',
        'is_active',
      ])->execute()->indexBy('id');

    foreach ($membershipType as $type) {
      $links = $this->links();
      //adding column for relationship type label. CRM-4178.
      $membershipType[$type['id']]['relationshipTypeName'] = NULL;
      // Ideally the v4 template would handle the v4 names for these fields - however, that
      // requires updating edit-in-place so it is a 'todo' for now.
      $membershipType[$type['id']]['visibility'] = $type['visibility:label'];
      $membershipType[$type['id']]['period_type'] = $type['period_type:label'];
      $membershipType[$type['id']]['relationshipTypeName'] = NULL;
      if ($type['relationship_type_id']) {
        //If membership associated with 2 or more relationship then display all relationship with comma separated
        $membershipType[$type['id']]['relationshipTypeName'] = NULL;
        foreach ($type['relationship_type_id'] as $key => $value) {
          $relationshipName = 'label_' . $type['relationship_direction'][$key];
          if ($membershipType[$type['id']]['relationshipTypeName']) {
            $membershipType[$type['id']]['relationshipTypeName'] .= ', ';
          }
          $membershipType[$type['id']]['relationshipTypeName'] .= CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType',
            $value, $relationshipName
          );
        }
      }
      // form all action links
      $action = array_sum(array_keys($this->links()));

      // update enable/disable links depending on if it is is_reserved or is_active
      if ($type['is_active']) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $membershipType[$type['id']]['action'] = CRM_Core_Action::formLink($links, $action,
        ['id' => $type['id']],
        ts('more'),
        FALSE,
        'membershipType.manage.action',
        'MembershipType',
        $type['id']
      );
    }

    $returnURL = CRM_Utils_System::url('civicrm/admin/member/membershipType', 'reset=1&action=browse');
    CRM_Utils_Weight::addOrder($membershipType, 'CRM_Member_DAO_MembershipType',
      'id', $returnURL
    );

    CRM_Member_BAO_MembershipType::convertDayFormat($membershipType);
    $this->assign('rows', $membershipType);
  }

}
