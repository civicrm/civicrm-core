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

use Civi\Api4\MessageTemplate;
use Civi\Token\TokenProcessor;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for Message templates
 * used by membership, contributions, event registrations, etc.
 */
class CRM_Admin_Form_MessageTemplates extends CRM_Core_Form {
  /**
   * which (and whether) mailing workflow this template belongs to
   * @var int
   */
  protected $_workflow_id = NULL;

  /**
   * Is document file is already loaded as default value?
   *
   * @var bool
   */
  protected $_is_document = FALSE;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * The ID of the message template being edited
   *
   * @var int
   */
  protected $_id;

  /**
   * The (current) message template database values
   *
   * @var array
   */
  protected $_values;

  /**
   * PreProcess form - load existing values.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->assign('action', $this->_action);
    $this->_values = [];
    if ($this->_id) {
      $this->_values = (array) MessageTemplate::get()->addWhere('id', '=', $this->_id)->setSelect(['*'])->execute()->first();
    }
  }

  /**
   * Set default values for the form.
   *
   * The default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $defaults = $this->_values;

    if (empty($defaults['pdf_format_id'])) {
      $defaults['pdf_format_id'] = 'null';
    }
    if (empty($defaults['file_type'])) {
      $defaults['file_type'] = 0;
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['is_active'] = 1;
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $documentInfo = CRM_Core_BAO_File::getEntityFile('civicrm_msg_template', $this->_id, TRUE);
      if (!empty($documentInfo)) {
        $defaults['file_type'] = 1;
        $this->_is_document = TRUE;
        $this->assign('attachment', $documentInfo);
      }
    }

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // For VIEW we only want Done button
    if ($this->_action & CRM_Core_Action::VIEW) {
      // currently, the above action is used solely for previewing default workflow templates
      $cancelURL = CRM_Utils_System::url('civicrm/admin/messageTemplates', 'selectedChild=workflow&reset=1');
      $cancelURL = str_replace('&amp;', '&', $cancelURL);
      $this->addButtons([
        [
          'type' => 'cancel',
          'name' => ts('Done'),
          'js' => ['onclick' => "location.href='{$cancelURL}'; return false;"],
          'isDefault' => TRUE,
        ],
      ]);
    }
    else {
      $this->_workflow_id = $this->_values['workflow_id'] ?? NULL;
      $this->checkUserPermission($this->_workflow_id);
      $this->assign('isWorkflow', (bool) ($this->_values['workflow_id'] ?? NULL));

      if ($this->_workflow_id) {
        $selectedChild = 'workflow';
      }
      else {
        $selectedChild = 'user';
      }

      $cancelURL = CRM_Utils_System::url('civicrm/admin/messageTemplates', "selectedChild={$selectedChild}&reset=1");
      $cancelURL = str_replace('&amp;', '&', $cancelURL);
      $buttons[] = [
        'type' => 'upload',
        'name' => $this->_action & CRM_Core_Action::DELETE ? ts('Delete') : ts('Save'),
        'isDefault' => TRUE,
      ];
      if (!($this->_action & CRM_Core_Action::DELETE)) {
        $buttons[] = [
          'type' => 'upload',
          'name' => ts('Save and Done'),
          'subName' => 'done',
        ];
      }
      $buttons[] = [
        'type' => 'cancel',
        'name' => ts('Cancel'),
        'js' => ['onclick' => "location.href='{$cancelURL}'; return false;"],
      ];
      $this->addButtons($buttons);
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->assign('msg_title', $this->_values['msg_title']);
      return;
    }

    $breadCrumb = [
      [
        'title' => ts('Message Templates'),
        'url' => CRM_Utils_System::url('civicrm/admin/messageTemplates', 'action=browse&reset=1'),
      ],
    ];
    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'msg_title', ts('Message Title'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_MessageTemplate', 'msg_title'), TRUE);

    $options = [ts('Compose On-screen'), ts('Upload Document')];
    $element = $this->addRadio('file_type', ts('Source'), $options);
    if ($this->_id) {
      $element->freeze();
    }

    $this->addElement('file', "file_id", ts('Upload Document'), 'size=30 maxlength=255');
    $this->addUploadElement("file_id");

    $this->add('text', 'msg_subject',
      ts('Message Subject'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_MessageTemplate', 'msg_subject')
    );

    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), ['schema' => ['contactId']]);
    $tokens = $tokenProcessor->listTokens();

    $this->assign('tokens', CRM_Utils_Token::formatTokensForDisplay($tokens));

    // if not a system message use a wysiwyg editor, CRM-5971 and dev/core#5154
    if (
      $this->_id && (
        CRM_Core_DAO::getFieldValue('CRM_Core_DAO_MessageTemplate', $this->_id, 'workflow_id') ||
        CRM_Core_DAO::getFieldValue('CRM_Core_DAO_MessageTemplate', $this->_id, 'workflow_name')
      )
    ) {
      $this->add('textarea', 'msg_html', ts('HTML Message'),
        ['cols' => 50, 'rows' => 6]
      );
    }
    else {
      $this->add('wysiwyg', 'msg_html', ts('HTML Message'),
        [
          'cols' => '80',
          'rows' => '8',
          'onkeyup' => "return verify(this)",
          'preset' => 'civimail',
        ]
      );
    }

    $this->add('textarea', 'msg_text', ts('Text Message'),
      ['cols' => 50, 'rows' => 6]
    );

    $this->add('select', 'pdf_format_id', ts('PDF Page Format'),
      [
        'null' => ts('- default -'),
      ] + CRM_Core_BAO_PdfFormat::getList(TRUE), FALSE
    );

    $this->add('advcheckbox', 'is_active', ts('Enabled?'));
    $this->addFormRule([__CLASS__, 'formRule'], $this);

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
      $this->setTitle(ts('View System Default Message Template'));
    }
  }

  /**
   * Restrict users access based on permission
   *
   * @param int $workflowId
   */
  private function checkUserPermission($workflowId) {
    if (isset($workflowId)) {
      $canView = CRM_Core_Permission::check('edit system workflow message templates');
    }
    else {
      $canView = CRM_Core_Permission::check('edit user-driven message templates');
    }

    if (!$canView && !CRM_Core_Permission::check('edit message templates')) {
      CRM_Core_Session::setStatus(ts('You do not have permission to view requested page.'), ts('Access Denied'));
      $url = CRM_Utils_System::url('civicrm/admin/messageTemplates', "reset=1");
      CRM_Utils_System::redirect($url);
    }
  }

  /**
   * Global form rule.
   *
   * @param array $params
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param self $self
   *
   * @return array
   *   array of errors
   */
  public static function formRule($params, $files, $self) {
    // If user uploads non-document file other than odt/docx
    if (!empty($files['file_id']['tmp_name']) &&
      array_search($files['file_id']['type'], CRM_Core_SelectValues::documentApplicationType()) == NULL
    ) {
      $errors['file_id'] = ts('Invalid document file format');
    }
    // If default is not set and no document file is uploaded
    elseif (empty($files['file_id']['tmp_name']) && !empty($params['file_type']) && !$self->_is_document) {
      //On edit page of docx/odt message template if user changes file type but forgot to upload document
      $errors['file_id'] = ts('Please upload document');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_MessageTemplate::deleteRecord(['id' => $this->_id]);
      CRM_Core_Session::setStatus(ts('Selected message template has been deleted.'), ts('Deleted'), 'success');

      $this->postProcessHook();
    }
    elseif ($this->_action & CRM_Core_Action::VIEW) {
      // currently, the above action is used solely for previewing default workflow templates
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/messageTemplates', 'selectedChild=workflow&reset=1'));
    }
    else {
      // store the submitted values in an array
      $params = $this->controller->exportValues();

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $params['id'] = $this->_id;
      }

      if (!empty($params['file_type'])) {
        unset($params['msg_html']);
        unset($params['msg_text']);
        CRM_Utils_File::formatFile($params, 'file_id');
      }
      // delete related file references if html/text/pdf template are chosen over document
      elseif (!empty($this->_id)) {
        $entityFileDAO = new CRM_Core_DAO_EntityFile();
        $entityFileDAO->entity_id = $this->_id;
        $entityFileDAO->entity_table = 'civicrm_msg_template';
        if ($entityFileDAO->find(TRUE)) {
          $fileDAO = new CRM_Core_DAO_File();
          $fileDAO->id = $entityFileDAO->file_id;
          $fileDAO->find(TRUE);
          $entityFileDAO->delete();
          $fileDAO->delete();
        }
      }

      $this->_workflow_id = $this->_values['workflow_id'] ?? NULL;
      if ($this->_workflow_id) {
        $params['workflow_id'] = $this->_workflow_id;
        $params['is_active'] = TRUE;
      }

      $messageTemplate = MessageTemplate::save()->setDefaults($params)->setRecords([['id' => $this->_id]])->execute()->first();

      // set the id on save, so it can be used in a extension using the posProcess hook
      $this->_id = $messageTemplate['id'];
      $this->postProcessHook();

      CRM_Core_Session::setStatus(ts('The Message Template \'%1\' has been saved.', [1 => $messageTemplate['msg_title']]), ts('Saved'), 'success');

      if (isset($this->_submitValues['_qf_MessageTemplates_upload'])) {
        // Save button was pressed
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/messageTemplates/add', "action=update&id={$messageTemplate['id']}&reset=1"));
      }
      // Save and done button was pressed
      if ($this->_workflow_id) {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/messageTemplates', 'selectedChild=workflow&reset=1'));
      }
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/messageTemplates', 'selectedChild=user&reset=1'));
    }
  }

  /**
   * Override
   * @return array
   */
  protected function getFieldsToExcludeFromPurification(): array {
    return ['msg_html'];
  }

}
