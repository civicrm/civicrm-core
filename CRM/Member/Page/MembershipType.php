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
 * $Id$
 *
 */

/**
 * Page for displaying list of membership types
 */
class CRM_Member_Page_MembershipType extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;

  public $useLivePageJS = TRUE;

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/member/membershipType/add',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Membership Type'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Membership Type'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Membership Type'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/member/membershipType/add',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Membership Type'),
        ),
      );
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
   *
   * @return void
   */
  public function browse() {
    // get all membership types sorted by weight
    $membershipType = array();
    $dao = new CRM_Member_DAO_MembershipType();

    $dao->orderBy('weight');
    $dao->find();

    while ($dao->fetch()) {
      if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
        && !CRM_Core_Permission::check('view contributions of type ' . CRM_Contribute_PseudoConstant::financialType($dao->financial_type_id))
      ) {
        continue;
      }
      $links = self::links();
      $membershipType[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $membershipType[$dao->id]);

      $membershipType[$dao->id]['period_type'] = CRM_Utils_Array::value($dao->period_type, CRM_Core_SelectValues::periodType(), '');
      $membershipType[$dao->id]['visibility'] = CRM_Utils_Array::value($dao->visibility, CRM_Core_SelectValues::memberVisibility(), '');

      //adding column for relationship type label. CRM-4178.
      if ($dao->relationship_type_id) {
        //If membership associated with 2 or more relationship then display all relationship with comma separated
        $relTypeIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $dao->relationship_type_id);
        $relTypeNames = explode(CRM_Core_DAO::VALUE_SEPARATOR, $dao->relationship_direction);
        $membershipType[$dao->id]['relationshipTypeName'] = NULL;
        foreach ($relTypeIds as $key => $value) {
          $relationshipName = 'label_' . $relTypeNames[$key];
          if ($membershipType[$dao->id]['relationshipTypeName']) {
            $membershipType[$dao->id]['relationshipTypeName'] .= ", ";
          }
          $membershipType[$dao->id]['relationshipTypeName'] .= CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType',
            $value, $relationshipName
          );
        }
        $membershipType[$dao->id]['maxRelated'] = CRM_Utils_Array::value('max_related', $membershipType[$dao->id]);
      }
      if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus() && !CRM_Core_Permission::check('edit contributions of type ' . CRM_Contribute_PseudoConstant::financialType($dao->financial_type_id))) {
        unset($links[CRM_Core_Action::UPDATE], $links[CRM_Core_Action::ENABLE], $links[CRM_Core_Action::DISABLE]);
      }
      if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus() && !CRM_Core_Permission::check('delete contributions of type ' . CRM_Contribute_PseudoConstant::financialType($dao->financial_type_id))) {
        unset($links[CRM_Core_Action::DELETE]);
      }
      // form all action links
      $action = array_sum(array_keys($this->links()));

      // update enable/disable links depending on if it is is_reserved or is_active
      if (!isset($dao->is_reserved)) {
        if ($dao->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }
        $membershipType[$dao->id]['order'] = $membershipType[$dao->id]['weight'];
        $membershipType[$dao->id]['action'] = CRM_Core_Action::formLink($links, $action,
          array('id' => $dao->id),
          ts('more'),
          FALSE,
          'membershipType.manage.action',
          'MembershipType',
          $dao->id
        );
      }
    }

    $returnURL = CRM_Utils_System::url('civicrm/admin/member/membershipType', "reset=1&action=browse");
    CRM_Utils_Weight::addOrder($membershipType, 'CRM_Member_DAO_MembershipType',
      'id', $returnURL
    );

    CRM_Member_BAO_MembershipType::convertDayFormat($membershipType);
    $this->assign('rows', $membershipType);
  }

}
