<?php
namespace Civi\Afform;

use Civi\Api4\AfformSubmission;
use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class AfformSubmissionTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()->install(['org.civicrm.search_kit', 'org.civicrm.afform', 'org.civicrm.afform_admin'])->apply();
  }

  public function testGetFields():void {
    $fields = AfformSubmission::getFields(FALSE)
      ->setAction('get')
      ->execute()->indexBy('name');
    $this->assertTrue($fields['afform_name']['options']);
    $this->assertEquals(['name', 'label', 'description', 'abbr', 'icon', 'url'], $fields['afform_name']['suffixes']);
  }

}
