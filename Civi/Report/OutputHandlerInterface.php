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
 * @package Civi\Report
 */
interface OutputHandlerInterface {

  /**
   * Getter for $form.
   *
   * It's suggested to extend \Civi\Report\OutputHandlerBase and then this will
   * be handled for you.
   *
   * @return \CRM_Report_Form
   */
  public function getForm():\CRM_Report_Form;

  /**
   * Setter for $form.
   *
   * It's suggested to extend \Civi\Report\OutputHandlerBase and then this will
   * be handled for you.
   *
   * @param \CRM_Report_Form $form
   */
  public function setForm(\CRM_Report_Form $form);

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
  public function isOutputHandlerFor(\CRM_Report_Form $form):bool;

  /**
   * Return the download filename. This should be the "clean" name, not
   * a munged temporary filename.
   *
   * @return string
   */
  public function getFileName():string;

  /**
   * Return the html body of the email.
   *
   * @return string
   */
  public function getMailBody():string;

  /**
   * Return the report contents as a string.
   *
   * @return string
   */
  public function getOutputString():string;

  /**
   * Set headers as appropriate and send the output to the browser.
   */
  public function download();

  /**
   * Mime type of the attachment.
   *
   * @return string
   */
  public function getMimeType():string;

  /**
   * Charset of the attachment.
   *
   * The default of '' means charset is not specified in the mimepart,
   * which is normal for binary attachments, but for text attachments you
   * should specify something like 'utf-8'.
   *
   * @return string
   */
  public function getCharset():string;

  /**
   * Hide/show various elements in the output, but generally for a handler
   * this is always set to TRUE.
   *
   * @return bool
   */
  public function isPrintOnly():bool;

  /**
   * Use a pager, but for a handler this would be FALSE since paging
   * is a UI element.
   *
   * @return bool
   */
  public function isAddPaging():bool;

  /**
   * Create absolute urls for links. Generally for a handler
   * this is always set to TRUE, but for example for 'print' it's displayed
   * on the site so it can be relative.
   * @todo Couldn't it just always be absolute?
   *
   * @return bool
   */
  public function isAbsoluteUrl():bool;

}
