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
namespace Civi\Report;

/**
 * Base Report Output Handler
 */
class OutputHandlerBase implements OutputHandlerInterface {

  /**
   * This is for convenience since otherwise several functions
   * would take it as a parameter.
   *
   * @var \CRM_Report_Form
   */
  protected $form;

  /**
   * Getter for $form
   *
   * @return \CRM_Report_Form
   */
  public function getForm():\CRM_Report_Form {
    return $this->form;
  }

  /**
   * Setter for $form
   *
   * @param \CRM_Report_Form $form
   */
  public function setForm(\CRM_Report_Form $form) {
    $this->form = $form;
  }

  /**
   * Are we a suitable output handler based on the given form?
   *
   * The class member $form isn't set yet at this point since we don't
   * even know if we're in play yet, so the form is a parameter.
   *
   * @param \CRM_Report_Form $form
   *
   * @return bool
   */
  public function isOutputHandlerFor(\CRM_Report_Form $form):bool {
    return FALSE;
  }

  /**
   * Return the download filename. This should be the "clean" name, not
   * a munged temporary filename.
   *
   * @return string
   */
  public function getFileName():string {
    return '';
  }

  /**
   * Return the html body of the email.
   *
   * @return string
   */
  public function getMailBody():string {
    return '';
  }

  /**
   * Return the report contents as a string.
   *
   * @return string
   */
  public function getOutputString():string {
    return '';
  }

  /**
   * Set headers as appropriate and send the output to the browser.
   */
  public function download() {
  }

  /**
   * Mime type of the attachment.
   *
   * @return string
   */
  public function getMimeType():string {
    return 'text/html';
  }

  /**
   * Charset of the attachment.
   *
   * The default of '' means charset is not specified in the mimepart,
   * which is normal for binary attachments, but for text attachments you
   * should specify something like 'utf-8'.
   *
   * @return string
   */
  public function getCharset():string {
    return '';
  }

  /**
   * Hide/show various elements in the output, but generally for a handler
   * this is always set to TRUE.
   *
   * @return bool
   */
  public function isPrintOnly():bool {
    return TRUE;
  }

  /**
   * Use a pager, but for a handler this would be FALSE since paging
   * is a UI element.
   *
   * @return bool
   */
  public function isAddPaging():bool {
    return FALSE;
  }

  /**
   * Create absolute urls for links. Generally for a handler
   * this is always set to TRUE, but for example for 'print' it's displayed
   * on the site so it can be relative.
   * @todo Couldn't it just always be absolute?
   *
   * @return bool
   */
  public function isAbsoluteUrl():bool {
    return TRUE;
  }

}
