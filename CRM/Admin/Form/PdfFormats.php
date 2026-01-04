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

/**
 * This class generates form components for PDF Page Format Settings.
 */
class CRM_Admin_Form_PdfFormats extends CRM_Admin_Form {

  /**
   * PDF Page Format ID.
   * @var int
   */
  public $_id = NULL;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      $formatName = CRM_Core_BAO_PdfFormat::getFieldValue('CRM_Core_BAO_PdfFormat', $this->_id, 'name');
      $this->assign('formatName', $formatName);
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_BAO_PdfFormat');
    $this->add('text', 'name', ts('Name'), $attributes['name'], TRUE);
    $this->add('text', 'description', ts('Description'), ['size' => CRM_Utils_Type::HUGE]);
    $this->add('checkbox', 'is_default', ts('Is this PDF Page Format the default?'));

    $this->add('select', 'paper_size', ts('Paper Size'),
      [
        0 => ts('- default -'),
      ] + CRM_Core_BAO_PaperSize::getList(TRUE), FALSE,
      ['onChange' => "selectPaper( this.value );"]
    );

    $this->add('static', 'paper_dimensions', ts('Width x Height'));
    $this->add('select', 'orientation', ts('Orientation'), CRM_Core_BAO_PdfFormat::getPageOrientations(), FALSE,
      ['onChange' => "updatePaperDimensions();"]
    );
    $this->add('select', 'metric', ts('Unit of Measure'), CRM_Core_BAO_PdfFormat::getUnits(), FALSE,
      ['onChange' => "selectMetric( this.value );"]
    );
    $this->add('text', 'margin_left', ts('Left Margin'), ['size' => 8, 'maxlength' => 8], TRUE);
    $this->add('text', 'margin_right', ts('Right Margin'), ['size' => 8, 'maxlength' => 8], TRUE);
    $this->add('text', 'margin_top', ts('Top Margin'), ['size' => 8, 'maxlength' => 8], TRUE);
    $this->add('text', 'margin_bottom', ts('Bottom Margin'), ['size' => 8, 'maxlength' => 8], TRUE);
    $this->add('number', 'weight', ts('Order'), CRM_Core_DAO::getAttribute('CRM_Core_BAO_PdfFormat', 'weight'), TRUE);

    $this->addRule('name', ts('Name already exists in Database.'), 'objectExists', [
      'CRM_Core_BAO_PdfFormat',
      $this->_id,
    ]);
    $this->addRule('margin_left', ts('Margin must be numeric'), 'numeric');
    $this->addRule('margin_right', ts('Margin must be numeric'), 'numeric');
    $this->addRule('margin_top', ts('Margin must be numeric'), 'numeric');
    $this->addRule('margin_bottom', ts('Margin must be numeric'), 'numeric');
    $this->addRule('weight', ts('Weight must be integer'), 'integer');
  }

  /**
   * @return int
   */
  public function setDefaultValues() {
    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['weight'] = CRM_Core_BAO_PdfFormat::getDefaultValues()['weight'] ?? 0;
    }
    else {
      $defaults = $this->_values;
    }
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      // delete PDF Page Format
      CRM_Core_BAO_PdfFormat::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected PDF Page Format has been deleted.'), ts('Record Deleted'), 'success');
      return;
    }

    $values = $this->controller->exportValues($this->getName());
    $values['is_default'] = isset($values['is_default']);
    $bao = new CRM_Core_BAO_PdfFormat();
    $bao->savePdfFormat($values, $this->_id);

    $status = ts('Your new PDF Page Format titled <strong>%1</strong> has been saved.', [1 => $values['name']]);
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $status = ts('Your PDF Page Format titled <strong>%1</strong> has been updated.', [1 => $values['name']]);
    }
    CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
  }

}
