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
use Civi\Api4\Website;

/**
 * Form helper trait for including websites in forms.
 *
 * @internal not supported for use outside core - if you do use it ensure your
 *  code has adequate unit test cover.
 */
trait CRM_Contact_Form_Edit_WebsiteBlockTrait {

  /**
   * @var \Civi\Api4\Generic\Result
   */
  private Result $existingWebsites;

  /**
   * @return \Civi\Api4\Generic\Result
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getExistingWebsites(): Result {
    if (!isset($this->existingWebsites)) {
      $this->existingWebsites = Website::get(FALSE)
        ->addWhere('contact_id', '=', $this->getContactID())
        ->execute();
    }
    return $this->existingWebsites;
  }

  /**
   * Get the websites indexed numerically from 1.
   *
   * This reflects historical form requirements.
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getExistingWebsitesReIndexed(): array {
    $result = array_merge([0 => 1], (array) $this->getExistingWebsites());
    unset($result[0]);
    return $result;
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
    $existingWebsites = (array) $this->getExistingWebsites()->indexBy('id');
    foreach ($websites as $index => $website) {
      $id = $website['id'] ?? NULL;
      $dataExists = !CRM_Utils_System::isNull($website['url']);
      if (!$dataExists) {
        unset($websites[$index]);
        continue;
      }
      if (!array_key_exists('contact_id', $website)) {
        $websites[$index]['contact_id'] = $this->getContactID();
      }
      if ($id) {
        if (array_key_exists($id, $existingWebsites)) {
          // We unset this here because we are going to delete any existing
          // websites that were not in the incoming array.
          unset($existingWebsites[$id]);
        }
        else {
          // The id is not valid, this becomes a create.
          unset($website['id']);
        }
      }
    }
    if ($websites) {
      Website::save(FALSE)
        ->setRecords($websites)
        ->execute();
    }

    if (!empty($existingWebsites)) {
      Website::delete(FALSE)
        ->addWhere('id', 'IN', array_keys($existingWebsites))
        ->execute();
    }
  }

}
