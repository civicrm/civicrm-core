<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

require_once 'CRM/Core/Form.php';

/**
 * This class manages the auction form
 *
 */
class CRM_Auction_Form_Item extends CRM_Core_Form {

  /**
   * the id of the item we are processing
   *
   * @var int
   * @protected
   */
  public $_id;

  /**
   * the id of the auction for this item
   *
   * @var int
   * @protected
   */
  public $_aid;

  /**
   * the id of the person donating this item
   *
   * @var int
   * @protected
   */
  public $_donorID;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */ function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');

    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_aid = CRM_Utils_Request::retrieve('aid', 'Positive', $this, TRUE);

    if (($this->_action & CRM_Core_Action::VIEW ||
        $this->_action & CRM_Core_Action::UPDATE ||
        $this->_action & CRM_Core_Action::DELETE
      ) &&
      !$this->_id
    ) {
      CRM_Core_Error::fatal("I am not sure which item you looking for.");
    }

    require_once 'CRM/Auction/BAO/Auction.php';
    $params = array('id' => $this->_aid);
    $this->_auctionValues = array();
    CRM_Auction_BAO_Auction::retrieve($params, $this->_auctionValues);

    $this->assign('auctionTitle', $this->_auctionValues['auction_title']);

    // set donor id
    $session = CRM_Core_Session::singleton();
    $this->_donorID = $this->get('donorID');

    $this->assign('donorName',
      CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $this->_donorID,
        'display_name'
      )
    );

    // also set user context
    $session->pushUserContext(CRM_Utils_System::url('civicrm/auction/item',
        "reset=1&aid={$this->_aid}"
      ));
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    require_once 'CRM/Auction/BAO/Item.php';

    $defaults = array();

    if (isset($this->_id)) {
      $params = array('id' => $this->_id);
      CRM_Auction_BAO_Item::retrieve($params, $defaults);
    }
    else {
      $defaults['is_active'] = 1;
      $defaults['auction_type_id'] = 1;
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');

    $attributes = CRM_Core_DAO::getAttribute('CRM_Auction_DAO_Item');
    $this->add('text',
      'title',
      ts('Item Label'),
      $attributes['title'],
      TRUE
    );

    $this->addWysiwyg('description',
      ts('Complete Description'),
      $attributes['description']
    );

    $auctionTypes = CRM_Core_OptionGroup::values('auction_item_type');
    $this->add('select', 'auction_item_type_id', ts('Item Type'),
      array('' => ts('- select -')) + $auctionTypes
    );

    $this->add('text', 'url', ts('Item URL'),
      array_merge($attributes['description'],
        array('onfocus' => "if (!this.value) {  this.value='http://';} else return false",
          'onblur' => "if ( this.value == 'http://') {  this.value='';} else return false",
        )
      )
    );


    $this->_checkboxes = array('is_active' => ts('Is Active?'),
      'is_group' => ts('Does this item have other items associated with it?'),
    );
    foreach ($this->_checkboxes as $name => $title) {
      $this->addElement('checkbox',
        $name,
        $title
      );
    }

    $this->_numbers = array('quantity' => ts('Number of units available'),
      'retail_value' => ts('Retail value of item'),
      'min_bid_value' => ts('Minimum bid accepted'),
      'min_bid_increment' => ts('Minimum bid increment'),
      'buy_now_value' => ts('Buy it now value'),
    );

    foreach ($this->_numbers as $name => $title) {
      $this->addElement('text',
        $name,
        $title,
        $attributes[$name]
      );
      if ($name == 'quantity') {
        $this->addRule($name,
          ts('%1 should be a postive number',
            array(1 => $title)
          ),
          'positiveInteger'
        );
      }
      else {
        $this->addRule($name,
          ts('%1 should be a valid money value',
            array(1 => $title)
          ),
          'money'
        );
      }
    }

    $maxAttachments = 1;
    require_once 'CRM/Core/BAO/File.php';
    CRM_Core_BAO_File::buildAttachment($this, 'civicrm_pcp', $this->_pageId, $maxAttachments);


    if ($this->_action & CRM_Core_Action::VIEW) {
      $buttons = array(array('type' => 'upload',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ));
      $this->freeze();
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      $this->freeze();
      $buttons = array(array('type' => 'upload',
          'name' => ts('Delete'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      );
    }
    else {
      $buttons = array(array('type' => 'upload',
          'name' => ts('Save'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ));

      $session = CRM_Core_Session::singleton();
      if ($session->get('userID')) {
        $buttons[] = array('type' => 'next',
          'name' => ts('Save and New'),
          'subName' => 'new',
        );
      }
      $buttons[] = array('type' => 'cancel',
        'name' => ts('Cancel'),
      );
    }
    $this->addButtons($buttons);

    $this->addFormRule(array('CRM_Auction_Form_Item', 'formRule'), $this);
  }

  /**
   * global form rule
   *
   * @param array $fields the input form values
   * @param array $files the uploaded files if any
   * @param $self
   *
   * @internal param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static
  function formRule($fields, $files, $self) {
    $errors = array();

    if (isset($files['attachFile_1'])) {
      list($width, $height) = getimagesize($files['attachFile_1']['tmp_name']);
      if ($width > 360 || $height > 360) {
        $errors['attachFile_1'] = "Your picture or image file can not be larger than 360 x 360 pixels in size." . " The dimensions of the image you've selected is " . $width . " x " . $height . ". Please shrink or crop the file or find another smaller image and try again.";
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::VIEW) {
      return;
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Auction_BAO_Item::del($this->_id);
      return;
    }

    $params = $this->controller->exportValues($this->_name);

    $params['id'] = $this->_id;
    $params['auction_id'] = $this->_aid;

    $params['donor_id'] = $this->_donorID;

    if ($this->_action == CRM_Core_Action::ADD) {
      $params['creator_id'] = $this->_donorID;
      $params['created_date'] = date('YmdHis');
    }

    // format checkboxes
    foreach ($this->_checkboxes as $name => $title) {
      $params[$name] = CRM_Utils_Array::value($name, $params, FALSE);
    }

    // does this auction require approval
    $params['is_approved'] = $this->_auctionValues['is_approval_needed'] ? 0 : 1;

    CRM_Auction_BAO_Item::add($params);

    if ($this->controller->getButtonName() == $this->getButtonName('next', 'new')) {
      $session = CRM_Core_Session::singleton();
      //CRM_Core_Session::setStatus(ts(' You can add another profile field.'));
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/auction/item',
          "reset=1&action=add&aid={$this->_aid}"
        ));
    }
  }
}

