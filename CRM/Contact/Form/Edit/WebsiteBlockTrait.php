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

use Civi\Api4\Generic\Result;

/**
 * Form helper trait for including websites in forms.
 *
 * @internal not supported for use outside core - if you do use it ensure your
 *  code has adequate unit test cover.
 */
trait CRM_Contact_Form_Edit_WebsiteBlockTrait {

  use CRM_Contact_Form_Edit_BlockTrait;

  /**
   * @return \Civi\Api4\Generic\Result
   * @throws \CRM_Core_Exception
   */
  public function getExistingWebsites(): Result {
    return $this->getExistingBlocks('Website');
  }

  /**
   * Get the websites indexed numerically from 1.
   *
   * This reflects historical form requirements.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getExistingWebsitesReIndexed(): array {
    return $this->getExistingBlocks('Website', 1);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function addWebsiteBlockFields(int $blockNumber): void {
    // Website type select
    $this->addField("website[$blockNumber][website_type_id]", [
      'entity' => 'website',
      'class' => 'eight',
      'placeholder' => NULL,
      'title' => ts('Website Type %1', [1 => $blockNumber]),
    ]);

    // Website box
    $this->addField("website[$blockNumber][url]", [
      'entity' => 'website',
      'aria-label' => ts('Website URL %1', [1 => $blockNumber]),
      'placeholder' => 'https://example.org',
      'data-msg-url' => ts('Enter a valid website address, such as https://example.org'),
    ]);
    $this->addRule("website[$blockNumber][url]", ts('Enter a valid website address, such as https://example.org'), 'url');
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function saveWebsites(array $websites): void {
    $this->saveBlocks('Website', $websites);
  }

}
