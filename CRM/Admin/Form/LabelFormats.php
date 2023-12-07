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
 * This class generates form components for Label Format Settings.
 */
class CRM_Admin_Form_LabelFormats extends CRM_Admin_Form {

  /**
   * Label Format ID.
   * @var int
   */
  public $_id = NULL;

  /**
   * Group name, label format or name badge
   * @var string
   */
  protected $_group = NULL;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  public function preProcess() {
    $this->_id = $this->get('id');
    $this->_group = CRM_Utils_Request::retrieve('group', 'String', $this, FALSE, 'label_format');
    $this->_values = [];
    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      CRM_Core_BAO_LabelFormat::retrieve($params, $this->_values, $this->_group);
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::COPY)) {
      $formatName = CRM_Core_BAO_LabelFormat::getFieldValue('CRM_Core_BAO_LabelFormat', $this->_id, 'label');
      $this->assign('formatName', $formatName);
      return;
    }

    $disabled = [];
    $required = TRUE;
    $is_reserved = $this->_id ? CRM_Core_BAO_LabelFormat::getFieldValue('CRM_Core_BAO_LabelFormat', $this->_id, 'is_reserved') : FALSE;
    if ($is_reserved) {
      $disabled['disabled'] = 'disabled';
      $required = FALSE;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_BAO_LabelFormat');
    $this->add('text', 'label', ts('Name'), $attributes['label'] + $disabled, $required);
    $this->add('text', 'description', ts('Description'), ['size' => CRM_Utils_Type::HUGE]);
    $this->add('checkbox', 'is_default', ts('Is this Label Format the default?'));

    // currently we support only mailing label creation, hence comment below code
    /*
    $options = array(
    'label_format' => ts('Mailing Label'),
    'name_badge'   => ts('Name Badge'),
    );

    $labelType = $this->addRadio('label_type', ts('Used For'), $options, null, '&nbsp;&nbsp;');

    if ($this->_action != CRM_Core_Action::ADD) {
    $labelType->freeze();
    }
     */

    $this->add('select', 'paper_size', ts('Sheet Size'),
      [
        0 => ts('- default -'),
      ] + CRM_Core_BAO_PaperSize::getList(TRUE), FALSE,
      [
        'onChange' => "selectPaper( this.value );",
      ] + $disabled
    );
    $this->add('static', 'paper_dimensions', NULL, ts('Sheet Size (w x h)'));
    $this->add('select', 'orientation', ts('Orientation'), CRM_Core_BAO_LabelFormat::getPageOrientations(), FALSE,
      [
        'onChange' => "updatePaperDimensions();",
      ] + $disabled
    );
    $this->add('select', 'font_name', ts('Font Name'), CRM_Core_BAO_LabelFormat::getFontNames($this->_group));
    $this->add('select', 'font_size', ts('Font Size'), CRM_Core_BAO_LabelFormat::getFontSizes());
    $this->add('static', 'font_style', ts('Font Style'));
    $this->add('checkbox', 'bold', ts('Bold'));
    $this->add('checkbox', 'italic', ts('Italic'));
    $this->add('select', 'metric', ts('Unit of Measure'), CRM_Core_BAO_LabelFormat::getUnits(), FALSE,
      ['onChange' => "selectMetric( this.value );"]
    );
    $this->add('text', 'width', ts('Label Width'), ['size' => 8, 'maxlength' => 8] + $disabled, $required);
    $this->add('text', 'height', ts('Label Height'), ['size' => 8, 'maxlength' => 8] + $disabled, $required);
    $this->add('text', 'NX', ts('Labels Per Row'), ['size' => 3, 'maxlength' => 3] + $disabled, $required);
    $this->add('text', 'NY', ts('Labels Per Column'), ['size' => 3, 'maxlength' => 3] + $disabled, $required);
    $this->add('text', 'tMargin', ts('Top Margin'), ['size' => 8, 'maxlength' => 8] + $disabled, $required);
    $this->add('text', 'lMargin', ts('Left Margin'), ['size' => 8, 'maxlength' => 8] + $disabled, $required);
    $this->add('text', 'SpaceX', ts('Horizontal Spacing'), ['size' => 8, 'maxlength' => 8] + $disabled, $required);
    $this->add('text', 'SpaceY', ts('Vertical Spacing'), ['size' => 8, 'maxlength' => 8] + $disabled, $required);
    $this->add('text', 'lPadding', ts('Left Padding'), ['size' => 8, 'maxlength' => 8], $required);
    $this->add('text', 'tPadding', ts('Top Padding'), ['size' => 8, 'maxlength' => 8], $required);
    $this->add('number', 'weight', ts('Order'), CRM_Core_DAO::getAttribute('CRM_Core_BAO_LabelFormat', 'weight'), TRUE);

    $this->addRule('label', ts('Name already exists in Database.'), 'objectExists', [
      'CRM_Core_BAO_LabelFormat',
      $this->_id,
    ]);
    $this->addRule('NX', ts('Please enter a valid integer.'), 'integer');
    $this->addRule('NY', ts('Please enter a valid integer.'), 'integer');
    $this->addRule('tMargin', ts('Please enter a valid number.'), 'numeric');
    $this->addRule('lMargin', ts('Please enter a valid number.'), 'numeric');
    $this->addRule('SpaceX', ts('Please enter a valid number.'), 'numeric');
    $this->addRule('SpaceY', ts('Please enter a valid number.'), 'numeric');
    $this->addRule('lPadding', ts('Please enter a valid number.'), 'numeric');
    $this->addRule('tPadding', ts('Please enter a valid number.'), 'numeric');
    $this->addRule('width', ts('Please enter a valid number.'), 'numeric');
    $this->addRule('height', ts('Please enter a valid number.'), 'numeric');
    $this->addRule('weight', ts('Please enter a valid integer.'), 'integer');
  }

  /**
   * @return int
   */
  public function setDefaultValues() {
    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['weight'] = CRM_Utils_Array::value('weight', CRM_Core_BAO_LabelFormat::getDefaultValues($this->_group), 0);
      $defaults['font_name'] = CRM_Utils_Array::value('font-name', CRM_Core_BAO_LabelFormat::getDefaultValues($this->_group), '');
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

    $defaults['label_type'] = $this->_group;
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      // delete Label Format
      CRM_Core_BAO_LabelFormat::del($this->_id, $this->_group);
      CRM_Core_Session::setStatus(ts('Selected Label Format has been deleted.'), ts('Record Deleted'), 'success');
      return;
    }
    if ($this->_action & CRM_Core_Action::COPY) {
      // make a copy of the Label Format
      $labelFormat = CRM_Core_BAO_LabelFormat::getById($this->_id, $this->_group);
      $newlabel = ts('Copy of %1', [1 => $labelFormat['label']]);

      $list = CRM_Core_BAO_LabelFormat::getList(TRUE, $this->_group);
      $count = 1;

      while (in_array($newlabel, $list)) {
        $count++;
        $newlabel = ts('Copy %1 of %2', [1 => $count, 2 => $labelFormat['label']]);
      }

      $labelFormat['label'] = $newlabel;
      $labelFormat['grouping'] = CRM_Core_BAO_LabelFormat::customGroupName();
      $labelFormat['is_default'] = 0;
      $labelFormat['is_reserved'] = 0;

      $bao = new CRM_Core_BAO_LabelFormat();
      $bao->saveLabelFormat($labelFormat, NULL, $this->_group);
      CRM_Core_Session::setStatus(ts('%1 has been created.', [1 => $labelFormat['label']]), ts('Saved'), 'success');
      return;
    }

    $values = $this->controller->exportValues($this->getName());

    // since we currently support only mailing label format
    $values['label_type'] = 'label_format';

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
    $bao->saveLabelFormat($values, $this->_id, $values['label_type']);

    $status = ts('Your new Label Format titled <strong>%1</strong> has been saved.', [1 => $values['label']]);
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $status = ts('Your Label Format titled <strong>%1</strong> has been updated.', [1 => $values['label']]);
    }
    CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
  }

}
