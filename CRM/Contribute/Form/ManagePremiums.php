<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class generates form components for Premiums.
 */
class CRM_Contribute_Form_ManagePremiums extends CRM_Contribute_Form {

  /**
   * Pre process the form.
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    if ($this->_id) {
      $params = array('id' => $this->_id);
      CRM_Contribute_BAO_Product::retrieve($params, $tempDefaults);
      if (isset($tempDefaults['image']) && isset($tempDefaults['thumbnail'])) {
        $defaults['imageUrl'] = $tempDefaults['image'];
        $defaults['thumbnailUrl'] = $tempDefaults['thumbnail'];
        $defaults['imageOption'] = 'thumbnail';
        // assign thumbnailUrl to template so we can display current image in update mode
        $this->assign('thumbnailUrl', $defaults['thumbnailUrl']);
      }
      else {
        $defaults['imageOption'] = 'noImage';
      }
      if (isset($tempDefaults['thumbnail']) && isset($tempDefaults['image'])) {
        $this->assign('thumbURL', $tempDefaults['thumbnail']);
        $this->assign('imageURL', $tempDefaults['image']);
      }
      if (isset($tempDefaults['period_type'])) {
        $this->assign('showSubscriptions', TRUE);
      }
    }

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->setPageTitle(ts('Premium Product'));

    if ($this->_action & CRM_Core_Action::PREVIEW) {
      CRM_Contribute_BAO_Premium::buildPremiumPreviewBlock($this, $this->_id);
      return;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'name', ts('Name'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'name'), TRUE);
    $this->addRule('name', ts('A product with this name already exists. Please select another name.'), 'objectExists', array(
        'CRM_Contribute_DAO_Product',
        $this->_id,
      ));
    $this->add('text', 'sku', ts('SKU'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'sku'));

    $this->add('textarea', 'description', ts('Description'), 'rows=3, cols=60');

    $image['image'] = $this->createElement('radio', NULL, NULL, ts('Upload from my computer'), 'image', 'onclick="add_upload_file_block(\'image\');');
    $image['thumbnail'] = $this->createElement('radio', NULL, NULL, ts('Display image and thumbnail from these locations on the web:'), 'thumbnail', 'onclick="add_upload_file_block(\'thumbnail\');');
    $image['default_image'] = $this->createElement('radio', NULL, NULL, ts('Use default image'), 'default_image', 'onclick="add_upload_file_block(\'default\');');
    $image['noImage'] = $this->createElement('radio', NULL, NULL, ts('Do not display an image'), 'noImage', 'onclick="add_upload_file_block(\'noImage\');');

    $this->addGroup($image, 'imageOption', ts('Premium Image'));
    $this->addRule('imageOption', ts('Please select an option for the premium image.'), 'required');

    $this->addElement('text', 'imageUrl', ts('Image URL'));
    $this->addElement('text', 'thumbnailUrl', ts('Thumbnail URL'));

    $this->add('file', 'uploadFile', ts('Image File Name'), 'onChange="select_option();"');

    $this->add('text', 'price', ts('Market Value'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'price'), TRUE);
    $this->addRule('price', ts('Please enter the Market Value for this product.'), 'money');

    $this->add('text', 'cost', ts('Actual Cost of Product'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'cost'));
    $this->addRule('price', ts('Please enter the Actual Cost of Product.'), 'money');

    $this->add('text', 'min_contribution', ts('Minimum Contribution Amount'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'min_contribution'), TRUE);
    $this->addRule('min_contribution', ts('Please enter a monetary value for the Minimum Contribution Amount.'), 'money');

    $this->add('textarea', 'options', ts('Options'), 'rows=3, cols=60');

    $this->add('select', 'period_type', ts('Period Type'), array(
        '' => '- select -',
        'rolling' => 'Rolling',
        'fixed' => 'Fixed',
      ));

    $this->add('text', 'fixed_period_start_day', ts('Fixed Period Start Day'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'fixed_period_start_day'));

    $this->add('Select', 'duration_unit', ts('Duration Unit'), array('' => '- select period -') + CRM_Core_SelectValues::getPremiumUnits());

    $this->add('text', 'duration_interval', ts('Duration'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'duration_interval'));

    $this->add('Select', 'frequency_unit', ts('Frequency Unit'), array('' => '- select period -') + CRM_Core_SelectValues::getPremiumUnits());

    $this->add('text', 'frequency_interval', ts('Frequency'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'frequency_interval'));

    //Financial Type CRM-11106
    $financialType = CRM_Contribute_PseudoConstant::financialType();
    $premiumFinancialType = array();
    CRM_Core_PseudoConstant::populate(
      $premiumFinancialType,
      'CRM_Financial_DAO_EntityFinancialAccount',
      $all = TRUE,
      $retrieve = 'entity_id',
      $filter = NULL,
      'account_relationship = 8'
    );

    $costFinancialType = array();
    CRM_Core_PseudoConstant::populate(
      $costFinancialType,
      'CRM_Financial_DAO_EntityFinancialAccount',
      $all = TRUE,
      $retrieve = 'entity_id',
      $filter = NULL,
      'account_relationship = 7'
    );
    $productFinancialType = array_intersect($costFinancialType, $premiumFinancialType);
    foreach ($financialType as $key => $financialTypeName) {
      if (!in_array($key, $productFinancialType)) {
        unset($financialType[$key]);
      }
    }
    if (count($financialType)) {
      $this->assign('financialType', $financialType);
    }
    $this->add(
      'select',
      'financial_type_id',
      ts('Financial Type'),
      array('' => ts('- select -')) + $financialType
    );

    $this->add('checkbox', 'is_active', ts('Enabled?'));

    $this->addFormRule(array('CRM_Contribute_Form_ManagePremiums', 'formRule'));

    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
    $this->assign('productId', $this->_id);
  }

  /**
   * Function for validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   * @param $files
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($params, $files) {

    // If choosing to upload an image, then an image must be provided
    if (CRM_Utils_Array::value('imageOption', $params) == 'image'
      && empty($files['uploadFile']['name'])
    ) {
      $errors['uploadFile'] = ts('A file must be selected');
    }

    // If choosing to use image URLs, then both URLs must be present
    if (CRM_Utils_Array::value('imageOption', $params) == 'thumbnail') {
      if (!$params['imageUrl']) {
        $errors['imageUrl'] = ts('Image URL is Required');
      }
      if (!$params['thumbnailUrl']) {
        $errors['thumbnailUrl'] = ts('Thumbnail URL is Required');
      }
    }

    // CRM-13231 financial type required if product has cost
    if (!empty($params['cost']) && empty($params['financial_type_id'])) {
      $errors['financial_type_id'] = ts('Financial Type is required for product having cost.');
    }

    if (!$params['period_type']) {
      if ($params['fixed_period_start_day'] || $params['duration_unit'] || $params['duration_interval'] ||
        $params['frequency_unit'] || $params['frequency_interval']
      ) {
        $errors['period_type'] = ts('Please select the Period Type for this subscription or service.');
      }
    }

    if ($params['period_type'] == 'fixed' && !$params['fixed_period_start_day']) {
      $errors['fixed_period_start_day'] = ts('Please enter a Fixed Period Start Day for this subscription or service.');
    }

    if ($params['duration_unit'] && !$params['duration_interval']) {
      $errors['duration_interval'] = ts('Please enter the Duration Interval for this subscription or service.');
    }

    if ($params['duration_interval'] && !$params['duration_unit']) {
      $errors['duration_unit'] = ts('Please enter the Duration Unit for this subscription or service.');
    }

    if ($params['frequency_interval'] && !$params['frequency_unit']) {
      $errors['frequency_unit'] = ts('Please enter the Frequency Unit for this subscription or service.');
    }

    if ($params['frequency_unit'] && !$params['frequency_interval']) {
      $errors['frequency_interval'] = ts('Please enter the Frequency Interval for this subscription or service.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // If previewing, don't do any post-processing
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return;
    }

    // If deleting, then only delete and skip the rest of the post-processing
    if ($this->_action & CRM_Core_Action::DELETE) {
      try {
        CRM_Contribute_BAO_Product::del($this->_id);
      }
      catch (CRM_Core_Exception $e) {
        $message = ts("This Premium is linked to an <a href='%1'>Online Contribution page</a>. Please remove it before deleting this Premium.", array(1 => CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1')));
        CRM_Core_Session::setStatus($message, ts('Cannot delete Premium'), 'error');
        CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin/contribute/managePremiums', 'reset=1&action=browse'));
        return;
      }
      CRM_Core_Session::setStatus(
        ts('Selected Premium Product type has been deleted.'),
        ts('Deleted'), 'info');
      return;
    }

    $params = $this->controller->exportValues($this->_name);

    // Clean the the money fields
    $moneyFields = array('cost', 'price', 'min_contribution');
    foreach ($moneyFields as $field) {
      $params[$field] = CRM_Utils_Rule::cleanMoney($params[$field]);
    }

    // If we're updating, we need to pass in the premium product Id
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    $this->_processImages($params);

    // Save the premium product to database
    $premium = CRM_Contribute_BAO_Product::create($params);

    CRM_Core_Session::setStatus(
      ts("The Premium '%1' has been saved.", array(1 => $premium->name)),
      ts('Saved'), 'success');
  }

  /**
   * Look at $params to find form info about images. Manipulate images if
   * necessary. Then alter $params to point to the newly manipulated images.
   *
   * @param array $params
   */
  protected function _processImages(&$params) {
    $defaults = array(
      'imageOption' => 'noImage',
      'uploadFile' => array('name' => ''),
      'image' => '',
      'thumbnail' => '',
      'imageUrl' => '',
      'thumbnailUrl' => '',
    );
    $params = array_merge($defaults, $params);

    // User is uploading an image
    if ($params['imageOption'] == 'image') {
      $imageFile = $params['uploadFile']['name'];
      try {
        $params['image'] = CRM_Utils_File::resizeImage($imageFile, 200, 200, "_full");
        $params['thumbnail'] = CRM_Utils_File::resizeImage($imageFile, 50, 50, "_thumb");
      }
      catch (CRM_Core_Exception $e) {
        $params['image'] = self::_defaultImage();
        $params['thumbnail'] = self::_defaultThumbnail();
        $msg = ts('The product has been configured to use a default image.');
        CRM_Core_Session::setStatus($e->getMessage() . " $msg", ts('Notice'), 'alert');
      }
    }

    // User is specifying existing URLs for the images
    elseif ($params['imageOption'] == 'thumbnail') {
      $params['image'] = $params['imageUrl'];
      $params['thumbnail'] = $params['thumbnailUrl'];
    }

    // User wants a default image
    elseif ($params['imageOption'] == 'default_image') {
      $params['image'] = self::_defaultImage();
      $params['thumbnail'] = self::_defaultThumbnail();
    }
  }

  /**
   * Returns the path to the default premium image
   * @return string
   */
  protected static function _defaultImage() {
    $config = CRM_Core_Config::singleton();
    return $config->resourceBase . 'i/contribute/default_premium.jpg';
  }

  /**
   * Returns the path to the default premium thumbnail
   * @return string
   */
  protected static function _defaultThumbnail() {
    $config = CRM_Core_Config::singleton();
    return $config->resourceBase . 'i/contribute/default_premium_thumb.jpg';
  }

}
