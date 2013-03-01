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

/**
 * This class is to build the form for Deleting Set
 */
class CRM_Price_Form_DeleteSet extends CRM_Core_Form {

  /**
   * the set id
   *
   * @var int
   */
  protected $_sid;

  /**
   * The title of the set being deleted
   *
   * @var string
   */
  protected $_title;

  /**
   * set up variables to build the form
   *
   * @return void
   * @acess protected
   */ function preProcess() {
    $this->_sid = $this->get('sid');

    $this->_title = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Set',
      $this->_sid, 'title'
    );
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->assign('title', $this->_title);
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Delete Price Set'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Process the form when submitted
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    if (CRM_Price_BAO_Set::deleteSet($this->_sid)) {
      CRM_Core_Session::setStatus(ts('The Price Set \'%1\' has been deleted.',
          array(1 => $this->_title), ts('Deleted'), 'success'
        ));
    }
    else {
      CRM_Core_Session::setStatus(ts('The Price Set \'%1\' has not been deleted! You must delete all price fields in this set prior to deleting the set.',
          array(1 => $this->_title)
        ), 'Unable to Delete', 'error');
    }
  }
}

