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

use Civi\Api4\Product;

/**
 * This class generates form components for Premiums.
 */
class CRM_Contribute_Form_ManagePremiums extends CRM_Contribute_Form {
  use CRM_Custom_Form_CustomDataTrait;

  /**
   * Classes extending CRM_Core_Form should implement this method.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'Product';
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    if ($this->_id) {
      $tempDefaults = Product::get()->addSelect('*', 'custom.*')->addWhere('id', '=', $this->_id)->execute()->first();
      if (isset($tempDefaults['image']) && isset($tempDefaults['thumbnail'])) {
        $defaults['imageUrl'] = $tempDefaults['image'];
        $defaults['imageOption'] = 'thumbnail';
      }
      else {
        $defaults['imageOption'] = 'noImage';
      }
      if (isset($tempDefaults['thumbnail']) && isset($tempDefaults['image'])) {
        $this->assign('imageURL', $tempDefaults['image']);
      }
      if (isset($tempDefaults['period_type'])) {
        $this->assign('showSubscriptions', TRUE);
      }

      // Convert api3 field names to custom_xx format
      foreach ($tempDefaults as $name => $value) {
        $short = CRM_Core_BAO_CustomField::getShortNameFromLongName($name);
        if ($short) {
          $tempDefaults[$short . '_' . $this->_id] = $value;
          unset($tempDefaults[$name]);
        }
      }
    }

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    parent::preProcess();

    // when custom data is included in this page
    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('Product', array_filter([
        'id' => $this->_id,
      ]));
    }
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
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
    $this->addRule('name', ts('A product with this name already exists. Please select another name.'), 'objectExists', [
      'CRM_Contribute_DAO_Product',
      $this->_id,
    ]);
    $this->add('text', 'sku', ts('SKU'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'sku'));

    $this->add('textarea', 'description', ts('Description'), ['cols' => 60, 'rows' => 3]);
    $imageJS = [];
    $image['image'] = ts('Upload from my computer');
    $imageJS['image'] = ['onclick' => 'add_upload_file_block(\'image\');', 'class' => 'required'];
    $image['thumbnail'] = ts('Display image and thumbnail from these locations on the web:');
    $imageJS['thumbnail'] = ['onclick' => 'add_upload_file_block(\'thumbnail\');', 'class' => 'required'];
    $image['default_image'] = ts('Use default image');
    $imageJS['default_image'] = ['onclick' => 'add_upload_file_block(\'default\');', 'class' => 'required'];
    $image['noImage'] = ts('Do not display an image');
    $imageJS['noImage'] = ['onclick' => 'add_upload_file_block(\'noImage\');', 'class' => 'required'];

    $this->addRadio('imageOption', ts('Image'), $image, [], NULL, FALSE, $imageJS);
    $this->addRule('imageOption', ts('Please select an option for the premium image.'), 'required');

    $this->addElement('text', 'imageUrl', ts('Image URL'));
    $this->add('file', 'uploadFile', ts('Image File Name'), ['onChange' => 'CRM.$("input[name=imageOption][value=image]").prop("checked", true);']);
    $this->add('text', 'price', ts('Market Value'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'price'), TRUE);
    $this->addRule('price', ts('Please enter the Market Value for this product.'), 'money');

    $this->add('text', 'cost', ts('Actual Cost of Product'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'cost'));
    $this->addRule('price', ts('Please enter the Actual Cost of Product.'), 'money');

    $this->add('text', 'min_contribution', ts('Minimum Contribution Amount'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'min_contribution'), TRUE);
    $this->addRule('min_contribution', ts('Please enter a monetary value for the Minimum Contribution Amount.'), 'money');

    $this->add('textarea', 'options', ts('Options'), ['cols' => 60, 'rows' => 3]);

    $this->add('select', 'period_type', ts('Period Type'), CRM_Core_SelectValues::periodType(), FALSE, ['placeholder' => TRUE]);

    $this->add('text', 'fixed_period_start_day', ts('Fixed Period Start Day'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'fixed_period_start_day'));

    $this->addField('duration_unit', ['placeholder' => ts('- select period -')], FALSE);
    $this->add('text', 'duration_interval', ts('Duration'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'duration_interval'));
    $this->addField('frequency_unit', ['placeholder' => ts('- select period -')], FALSE);
    $this->add('text', 'frequency_interval', ts('Frequency'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'frequency_interval'));

    //Financial Type CRM-11106
    $financialType = CRM_Contribute_PseudoConstant::financialType();
    $premiumFinancialType = [];
    CRM_Core_PseudoConstant::populate(
      $premiumFinancialType,
      'CRM_Financial_DAO_EntityFinancialAccount',
      $all = TRUE,
      $retrieve = 'entity_id',
      $filter = NULL,
      'account_relationship = 8'
    );

    $costFinancialType = [];
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
      $financialType,
      FALSE,
      ['placeholder' => TRUE]
    );

    $this->add('checkbox', 'is_active', ts('Enabled?'));

    $this->addFormRule(['CRM_Contribute_Form_ManagePremiums', 'formRule']);

    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
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
    if (($params['imageOption'] ?? NULL) == 'image'
      && empty($files['uploadFile']['name'])
    ) {
      $errors['uploadFile'] = ts('A file must be selected');
    }

    // If choosing to use image URLs, then both URLs must be present
    if (($params['imageOption'] ?? NULL) == 'thumbnail') {
      if (!$params['imageUrl']) {
        $errors['imageUrl'] = ts('Image URL is Required');
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
        CRM_Contribute_BAO_Product::deleteRecord(['id' => $this->_id]);
      }
      catch (CRM_Core_Exception $e) {
        $message = ts("This Premium is linked to an <a href='%1'>Online Contribution page</a>. Please remove it before deleting this Premium.", [1 => CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1')]);
        CRM_Core_Session::setStatus($message, ts('Cannot delete Premium'), 'error');
        CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin/contribute/managePremiums', 'reset=1'));
        return;
      }
      CRM_Core_Session::setStatus(
        ts('Selected Premium Product type has been deleted.'),
        ts('Deleted'), 'info');
      return;
    }

    $params = $this->controller->exportValues($this->_name);

    // Clean the the money fields
    $moneyFields = ['cost', 'price', 'min_contribution'];
    foreach ($moneyFields as $field) {
      $params[$field] = CRM_Utils_Rule::cleanMoney($params[$field]);
    }

    // If we're updating, we need to pass in the premium product Id
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    $this->_processImages($params);

    $params += $this->getSubmittedCustomFieldsForApi4();

    if (is_string($params['options'])) {
      // In setDefaultValues(), we loaded the serialized `options` string to present
      // it as one editable string. Now we pass to APIv4 save() -- but it doesn't want
      // the serialized string. It wants the array...
      $params['options'] = CRM_Utils_CommaKV::unserialize($params['options']);
    }

    // Save the premium product to database
    $premium = Product::save()->addRecord($params)->execute()->first();

    CRM_Core_Session::setStatus(
      ts("The Premium '%1' has been saved.", [1 => $premium['name']]),
      ts('Saved'), 'success');
  }

  /**
   * Look at $params to find form info about images. Manipulate images if
   * necessary. Then alter $params to point to the newly manipulated images.
   *
   * @param array $params
   */
  protected function _processImages(&$params) {
    $defaults = [
      'imageOption' => 'noImage',
      'uploadFile' => ['name' => ''],
      'image' => '',
      'thumbnail' => '',
      'imageUrl' => '',
    ];
    $params = array_merge($defaults, $params);

    // User is uploading an image
    if ($params['imageOption'] == 'image') {
      $imageFile = $params['uploadFile']['name'];
      try {
        $params['image'] = CRM_Utils_File::resizeImage($imageFile, 200, 200, "_full");
      }
      catch (CRM_Core_Exception $e) {
        $params['image'] = self::_defaultImage();
        $msg = ts('The product has been configured to use a default image.');
        CRM_Core_Session::setStatus($e->getMessage() . " $msg", ts('Notice'), 'alert');
      }
    }

    // User is specifying existing URLs for the images
    elseif ($params['imageOption'] == 'thumbnail') {
      $params['image'] = $params['imageUrl'];
    }

    // User wants a default image
    elseif ($params['imageOption'] == 'default_image') {
      $params['image'] = self::_defaultImage();
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

}
