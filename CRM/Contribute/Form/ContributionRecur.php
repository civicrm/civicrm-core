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
 | CiviCRM is distributed in the hope that it will be usefusul, but   |
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
 * Shared parent class for recurring contribution forms.
 */
class CRM_Contribute_Form_ContributionRecur extends CRM_Core_Form {

  /**
   * @var int Contribution ID
   */
  protected $_coid = NULL;

  /**
   * @var int Contribution Recur ID
   */
  protected $_crid = NULL;

  /**
   * The recurring contribution id, used when editing the recurring contribution.
   *
   * For historical reasons this duplicates _crid & since the name is more meaningful
   * we should probably deprecate $_crid.
   *
   * @var int
   */
  protected $contributionRecurID = NULL;

  /**
   * @var int Membership ID
   */
  protected $_mid = NULL;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'ContributionRecur';
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_mid = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE);
    $this->_crid = CRM_Utils_Request::retrieve('crid', 'Integer', $this, FALSE);
    $this->contributionRecurID = $this->_crid;
    $this->_coid = CRM_Utils_Request::retrieve('coid', 'Integer', $this, FALSE);
  }

}
