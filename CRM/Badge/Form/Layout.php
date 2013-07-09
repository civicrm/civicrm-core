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
 * This class generates form components for name badge layout
 *
 */
class CRM_Badge_Form_Layout extends CRM_Admin_Form {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $config = CRM_Core_Config::singleton();
    $resources = CRM_Core_Resources::singleton();
    $resources->addSetting(
      array(
        'kcfinderPath' => $config->userFrameworkResourceURL .'packages' .DIRECTORY_SEPARATOR
      )
    );
    $resources->addScriptFile('civicrm', 'templates/CRM/Badge/Form/Layout.js');

    $this->applyFilter('__ALL__', 'trim');

    $this->add('text', 'title', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_PrintLabel', 'title'), true);

    $labelStyle = CRM_Core_BAO_LabelFormat::getList(TRUE, 'name_badge');
    $this->add('select', 'label_format_name', ts('Label Style'), array('' => ts('- select -')) + $labelStyle, TRUE);

    $this->add('text', 'description', ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_PrintLabel', 'title'));

    // get the tokens
    $contactTokens = CRM_Core_SelectValues::contactTokens();
    $eventTokens   = CRM_Core_SelectValues::eventTokens();
    $participantTokens = CRM_Core_SelectValues::participantTokens();

    $tokens = array_merge($contactTokens, $eventTokens, $participantTokens);
    asort($tokens);

    $fontSizes = CRM_Core_BAO_LabelFormat::getFontSizes();
    $fontNames = CRM_Core_BAO_LabelFormat::getFontNames('name_badge');
    $textAlignment = CRM_Core_BAO_LabelFormat::getTextAlignments();

    $rowCount = 4;
    for ( $i =1; $i <= $rowCount; $i++ ) {
      $this->add('select', "token[$i]", ts('Token'), array('' => ts('- none -')) + $tokens);
      $this->add('select', "font_name[$i]", ts('Font Name'), $fontNames);
      $this->add('select', "font_size[$i]", ts('Font Size'), $fontSizes);
      $this->add('select', "text_alignment[$i]", ts('Alignment'), $textAlignment);
    }
    $rowCount++;
    $this->assign('rowCount', $rowCount);

    $this->add('checkbox', 'add_barcode', ts('Barcode?'));
    unset($textAlignment['J']);
    $this->add('select', "barcode_alignment", ts('Alignment'), $textAlignment);

    $attributes = array(
      'readonly'=> true,
      'value' => ts('click here and select a file double clicking on it'),
    );
    $this->add('text', 'image_1', ts('Image 1'), $attributes + CRM_Core_DAO::getAttribute('CRM_Core_DAO_PrintLabel', 'title'));
    $this->add('text', 'image_2', ts('Image 2'), $attributes + CRM_Core_DAO::getAttribute('CRM_Core_DAO_PrintLabel', 'title'));

    $this->add('checkbox', 'is_default', ts('Default?'));
    $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->add('checkbox', 'is_reserved', ts('Reserved?'));
  }

  /**
   * This function sets the default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    if (isset($this->_id)) {
      $defaults = array_merge($this->_values,
        CRM_Badge_BAO_Layout::getDecodedData($this->_values['data']));
    }
    else {
      for ($i = 1; $i <= 4; $i++) {
        $defaults['text_alignment'][$i] = "C";
      }
    }

    if ($this->_action == CRM_Core_Action::DELETE && isset($defaults['title'])) {
      $this->assign('delName', $defaults['title']);
    }

    // its ok if there is no element called is_active
    $defaults['is_active'] = ($this->_id) ? CRM_Utils_Array::value('is_active', $defaults) : 1;

    return $defaults;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Badge_BAO_Layout::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected badge layout has been deleted.'), ts('Record Deleted'), 'success');
      return;
    }

    $params = $data = $this->controller->exportValues($this->_name);

    unset($data['qfKey']);
    $params['data'] = json_encode($data);

    if ($this->_id) {
      $params['id'] = $this->_id;
    }

    // store the submitted values in an array
    CRM_Badge_BAO_Layout::create($params);

    CRM_Core_Session::setStatus(ts("The badge layout '%1' has been saved.",
      array(1 => $params['title'])
    ), ts('Saved'), 'success');
  }
}
