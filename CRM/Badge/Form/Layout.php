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
 */

/**
 * This class generates form components for name badge layout.
 */
class CRM_Badge_Form_Layout extends CRM_Admin_Form {

  const FIELD_ROWCOUNT = 6;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      return parent::buildQuickForm();
    }

    $config = CRM_Core_Config::singleton();
    $resources = CRM_Core_Resources::singleton();
    $resources->addSetting(
      array(
        'kcfinderPath' => $config->userFrameworkResourceURL . 'packages' . DIRECTORY_SEPARATOR,
      )
    );
    $resources->addScriptFile('civicrm', 'templates/CRM/Badge/Form/Layout.js', 1, 'html-header');

    $this->applyFilter('__ALL__', 'trim');

    $this->add('text', 'title', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_PrintLabel', 'title'), TRUE);

    $labelStyle = CRM_Core_BAO_LabelFormat::getList(TRUE, 'name_badge');
    $this->add('select', 'label_format_name', ts('Label Format'), array('' => ts('- select -')) + $labelStyle, TRUE);

    $this->add('text', 'description', ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_PrintLabel', 'title'));

    // get the tokens
    $contactTokens = CRM_Core_SelectValues::contactTokens();
    $eventTokens = array(
      '{event.event_id}' => ts('Event ID'),
      '{event.title}' => ts('Event Title'),
      '{event.start_date}' => ts('Event Start Date'),
      '{event.end_date}' => ts('Event End Date'),
    );
    $participantTokens = CRM_Core_SelectValues::participantTokens();

    $tokens = array_merge($contactTokens, $eventTokens, $participantTokens);
    asort($tokens);

    $tokens = array_merge(array('spacer' => ts('- spacer -')) + $tokens);

    $fontSizes = CRM_Core_BAO_LabelFormat::getFontSizes();
    $fontStyles = CRM_Core_BAO_LabelFormat::getFontStyles();
    $fontNames = CRM_Core_BAO_LabelFormat::getFontNames('name_badge');
    $textAlignment = CRM_Core_BAO_LabelFormat::getTextAlignments();
    $imageAlignment = $textAlignment;
    unset($imageAlignment['C']);

    $rowCount = self::FIELD_ROWCOUNT;
    for ($i = 1; $i <= $rowCount; $i++) {
      $this->add('select', "token[$i]", ts('Token'), array('' => ts('- skip -')) + $tokens);
      $this->add('select', "font_name[$i]", ts('Font Name'), $fontNames);
      $this->add('select', "font_size[$i]", ts('Font Size'), $fontSizes);
      $this->add('select', "font_style[$i]", ts('Font Style'), $fontStyles);
      $this->add('select', "text_alignment[$i]", ts('Alignment'), $textAlignment);
    }
    $rowCount++;
    $this->assign('rowCount', $rowCount);

    $barcodeTypes = CRM_Core_SelectValues::getBarcodeTypes();
    $this->add('checkbox', 'add_barcode', ts('Barcode?'));
    $this->add('select', "barcode_type", ts('Type'), $barcodeTypes);
    $this->add('select', "barcode_alignment", ts('Alignment'), $textAlignment);

    $attributes = array('readonly' => TRUE);
    $this->add('text', 'image_1', ts('Image (top left)'),
      $attributes + CRM_Core_DAO::getAttribute('CRM_Core_DAO_PrintLabel', 'title'));
    $this->add('text', 'width_image_1', ts('Width (mm)'), array('size' => 6));
    $this->add('text', 'height_image_1', ts('Height (mm)'), array('size' => 6));

    $this->add('text', 'image_2', ts('Image (top right)'),
      $attributes + CRM_Core_DAO::getAttribute('CRM_Core_DAO_PrintLabel', 'title'));
    $this->add('text', 'width_image_2', ts('Width (mm)'), array('size' => 6));
    $this->add('text', 'height_image_2', ts('Height (mm)'), array('size' => 6));

    $this->add('checkbox', 'show_participant_image', ts('Use Participant Image?'));
    $this->add('text', 'width_participant_image', ts('Width (mm)'), array('size' => 6));
    $this->add('text', 'height_participant_image', ts('Height (mm)'), array('size' => 6));
    $this->add('select', "alignment_participant_image", ts('Image Alignment'), $imageAlignment);

    $this->add('checkbox', 'is_default', ts('Default?'));
    $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->add('checkbox', 'is_reserved', ts('Reserved?'));

    $this->addRule('width_image_1', ts('Enter valid width'), 'positiveInteger');
    $this->addRule('width_image_2', ts('Enter valid width'), 'positiveInteger');
    $this->addRule('height_image_1', ts('Enter valid height'), 'positiveInteger');
    $this->addRule('height_image_2', ts('Enter valid height'), 'positiveInteger');
    $this->addRule('height_participant_image', ts('Enter valid height'), 'positiveInteger');
    $this->addRule('width_participant_image', ts('Enter valid height'), 'positiveInteger');

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'refresh',
          'name' => ts('Save and Preview'),
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    if (isset($this->_id)) {
      $defaults = array_merge($this->_values,
        CRM_Badge_BAO_Layout::getDecodedData($this->_values['data']));
    }
    else {
      for ($i = 1; $i <= self::FIELD_ROWCOUNT; $i++) {
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
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Badge_BAO_Layout::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected badge layout has been deleted.'), ts('Record Deleted'), 'success');
      return;
    }

    $params = $data = $this->exportValues();

    unset($data['qfKey']);
    $params['data'] = json_encode($data);

    if ($this->_id) {
      $params['id'] = $this->_id;
    }

    // store the submitted values in an array
    $badgeInfo = CRM_Badge_BAO_Layout::create($params);

    if (isset($params['_qf_Layout_refresh'])) {
      $this->set('id', $badgeInfo->id);
      $params['badge_id'] = $badgeInfo->id;
      self::buildPreview($params);
    }
    else {
      CRM_Core_Session::setStatus(ts("The badge layout '%1' has been saved.",
        array(1 => $params['title'])
      ), ts('Saved'), 'success');
    }
  }

  /**
   * @param array $params
   */
  public function buildPreview(&$params) {
    // get a max participant id
    $participantID = CRM_Core_DAO::singleValueQuery('select max(id) from civicrm_participant');

    if (!$participantID) {
      CRM_Core_Session::setStatus(ts('Preview requires at least one event and one participant record.
       If you are just getting started, you can add a test participant record.'), ts('Preview Requirements'), 'alert');
      return;
    }

    $this->_single = TRUE;
    $this->_participantIds = array($participantID);
    $this->_componentClause = " civicrm_participant.id = $participantID ";

    CRM_Badge_BAO_Badge::buildBadges($params, $this);
  }

}
