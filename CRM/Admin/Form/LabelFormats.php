<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
 * This class generates form components for Label Format Settings
 *
 */
class CRM_Admin_Form_LabelFormats extends CRM_Admin_Form {

  /**
   * Label Format ID
   */
  protected $_id = NULL;

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::COPY)) {
      $formatName = CRM_Core_BAO_LabelFormat::getFieldValue('CRM_Core_BAO_LabelFormat', $this->_id, 'label');
      $this->assign('formatName', $formatName);
      return;
    }

    $disabled    = array();
    $required    = TRUE;
    $is_reserved = $this->_id ? CRM_Core_BAO_LabelFormat::getFieldValue('CRM_Core_BAO_LabelFormat', $this->_id, 'is_reserved') : FALSE;
    if ($is_reserved) {
      $disabled['disabled'] = 'disabled';
      $required = FALSE;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_BAO_LabelFormat');
    $this->add('text', 'label', ts('Name'), $attributes['label'] + $disabled, $required);
    $this->add('text', 'description', ts('Description'), array('size' => CRM_Utils_Type::HUGE));
    $this->add('checkbox', 'is_default', ts('Is this Label Format the default?'));
    $this->add('select', 'paper_size', ts('Sheet Size'),
      array(
        0 => ts('- default -')) + CRM_Core_BAO_PaperSize::getList(TRUE), FALSE,
      array(
        'onChange' => "selectPaper( this.value );") + $disabled
    );
    $this->add('static', 'paper_dimensions', NULL, ts('Sheet Size (w x h)'));
    $this->add('select', 'orientation', ts('Orientation'), CRM_Core_BAO_LabelFormat::getPageOrientations(), FALSE,
      array(
        'onChange' => "updatePaperDimensions();") + $disabled
    );
    $this->add('select', 'font_name', ts('Font Name'), CRM_Core_BAO_LabelFormat::getFontNames());
    $this->add('select', 'font_size', ts('Font Size'), CRM_Core_BAO_LabelFormat::getFontSizes());
    $this->add('static', 'font_style', ts('Font Style'));
    $this->add('checkbox', 'bold', ts('Bold'));
    $this->add('checkbox', 'italic', ts('Italic'));
    $this->add('select', 'metric', ts('Unit of Measure'), CRM_Core_BAO_LabelFormat::getUnits(), FALSE,
      array('onChange' => "selectMetric( this.value );")
    );
    $this->add('text', 'width', ts('Label Width'), array('size' => 8, 'maxlength' => 8) + $disabled, $required);
    $this->add('text', 'height', ts('Label Height'), array('size' => 8, 'maxlength' => 8) + $disabled, $required);
    $this->add('text', 'NX', ts('Labels Per Row'), array('size' => 3, 'maxlength' => 3) + $disabled, $required);
    $this->add('text', 'NY', ts('Labels Per Column'), array('size' => 3, 'maxlength' => 3) + $disabled, $required);
    $this->add('text', 'tMargin', ts('Top Margin'), array('size' => 8, 'maxlength' => 8) + $disabled, $required);
    $this->add('text', 'lMargin', ts('Left Margin'), array('size' => 8, 'maxlength' => 8) + $disabled, $required);
    $this->add('text', 'SpaceX', ts('Horizontal Spacing'), array('size' => 8, 'maxlength' => 8) + $disabled, $required);
    $this->add('text', 'SpaceY', ts('Vertical Spacing'), array('size' => 8, 'maxlength' => 8) + $disabled, $required);
    $this->add('text', 'lPadding', ts('Left Padding'), array('size' => 8, 'maxlength' => 8), $required);
    $this->add('text', 'tPadding', ts('Top Padding'), array('size' => 8, 'maxlength' => 8), $required);
    $this->add('text', 'weight', ts('Weight'), CRM_Core_DAO::getAttribute('CRM_Core_BAO_LabelFormat', 'weight'), TRUE);

    $this->addRule('label', ts('Name already exists in Database.'), 'objectExists', array('CRM_Core_BAO_LabelFormat', $this->_id));
    $this->addRule('NX', ts('Must be an integer'), 'integer');
    $this->addRule('NY', ts('Must be an integer'), 'integer');
    $this->addRule('tMargin', ts('Must be numeric'), 'numeric');
    $this->addRule('lMargin', ts('Must be numeric'), 'numeric');
    $this->addRule('SpaceX', ts('Must be numeric'), 'numeric');
    $this->addRule('SpaceY', ts('Must be numeric'), 'numeric');
    $this->addRule('lPadding', ts('Must be numeric'), 'numeric');
    $this->addRule('tPadding', ts('Must be numeric'), 'numeric');
    $this->addRule('width', ts('Must be numeric'), 'numeric');
    $this->addRule('height', ts('Must be numeric'), 'numeric');
    $this->addRule('weight', ts('Weight must be integer'), 'integer');
  }

  function setDefaultValues() {
    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['weight'] = CRM_Utils_Array::value('weight', CRM_Core_BAO_LabelFormat::getDefaultValues(), 0);
    }
    else {
      $defaults = $this->_values;
      // Convert field names that are illegal PHP/SMARTY variable names
      $defaults['paper_size'] = $defaults['paper-size'];
      unset($defaults['paper-size']);
      $defaults['font_name'] = $defaults['font-name'];
      unset($defaults['font-name']);
      $defaults['font_size'] = $defaults['font-size'];
      unset($defaults['font-size']);

      $defaults['bold'] = (stripos($defaults['font-style'], 'B') !== FALSE);
      $defaults['italic'] = (stripos($defaults['font-style'], 'I') !== FALSE);
      unset($defaults['font-style']);
    }
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
      // delete Label Format
      CRM_Core_BAO_LabelFormat::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Label Format has been deleted.'), ts('Record Deleted'), 'success');
      return;
    }
    if ($this->_action & CRM_Core_Action::COPY) {
      // make a copy of the Label Format
      $labelFormat = CRM_Core_BAO_LabelFormat::getById($this->_id);
      $list        = CRM_Core_BAO_LabelFormat::getList(TRUE);
      $count       = 1;
      $prefix      = ts('Copy of ');
      while (in_array($prefix . $labelFormat['label'], $list)) {
        $prefix = ts('Copy') . ' (' . ++$count . ') ' . ts('of ');
      }
      $labelFormat['label'] = $prefix . $labelFormat['label'];
      $labelFormat['grouping'] = CRM_Core_BAO_LabelFormat::customGroupName();
      $labelFormat['is_default'] = 0;
      $labelFormat['is_reserved'] = 0;
      $bao = new CRM_Core_BAO_LabelFormat();
      $bao->saveLabelFormat($labelFormat);
      CRM_Core_Session::setStatus($labelFormat['label'] . ts(' has been created.'), ts('Saved'), 'success');
      return;
    }

    $values = $this->controller->exportValues($this->getName());
    $values['is_default'] = isset($values['is_default']);

    // Restore field names that were converted because they are illegal PHP/SMARTY variable names
    if (isset($values['paper_size'])) {
      $values['paper-size'] = $values['paper_size'];
      unset($values['paper_size']);
    }
    if (isset($values['font_name'])) {
      $values['font-name'] = $values['font_name'];
      unset($values['font_name']);
    }
    if (isset($values['font_size'])) {
      $values['font-size'] = $values['font_size'];
      unset($values['font_size']);
    }

    $style = '';
    if (isset($values['bold'])) {
      $style .= 'B';
    }
    if (isset($values['italic'])) {
      $style .= 'I';
    }
    $values['font-style'] = $style;

    $bao = new CRM_Core_BAO_LabelFormat();
    $bao->saveLabelFormat($values, $this->_id);

    $status = ts('Your new Label Format titled <strong>%1</strong> has been saved.', array(1 => $values['label']));
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $status = ts('Your Label Format titled <strong>%1</strong> has been updated.', array(1 => $values['label']));
    }
    CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
  }
}
