<?php

namespace Civi\Api4\Action\Job;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Search\EmailSearchDisplays;

class SendSKReports extends AbstractAction {

  /**
   * Run Job in nonProductionEnvironment
   * @var bool
   */
  protected $runInNonProductionEnvironment = FALSE;

  /**
   * Language
   * @var string
   */
  protected $language = NULL;

  public function _run(Result $result) {
    $displays = EmailSearchDisplays::findDisplaysDue();
    foreach ($displays as $id => $display) {
      self::sendEmailReport($display);
      $display['next_run'] = EmailSearchDisplays::calculateNextRunDate($display);
      SearchDisplay::update(FALSE)
        ->addValue('settings', $display)
        ->addWhere('id', '=', $id)
        ->execute();
      $result[] = $id;
    }
  }

  private static function sendEmailReport(array $display) {
    $contactIDs = $display['contactIds'];
    $savedSearch = SavedSearch::get(FALSE)
      ->addWhere('id', '=', $display['savedSearch'])
      ->execute()
      ->first();
    SearchDisplay::emailReport(FALSE)
      ->setFilters($display['filters'])
      ->setContactID(implode(',', $contactIDs))
      ->setTemplateID($display['messageTemplateId'][0])
      ->setSubject($display['subject'])
      ->setFileName($display['fileName'])
      ->setReportName($display['reportName'])
      ->setDisplay($display['searchDisplay'])
      ->setSavedSearch($savedSearch['name'])
      ->setFormat('pdf')
      ->execute();
  }

}
