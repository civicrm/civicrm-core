<?php
namespace Civi\API\Subscriber;

use Civi\Api4\Contact;
use Civi\Core\Locale;

/**
 * @group headless
 */
class I18nSubscriberTest extends \CiviUnitTestCase {

  /**
   * A failed APIv4 call with `language` must not leave its locale active.
   */
  public function testLocaleRestoredAfterFailedApi4Call(): void {
    $before = Locale::detect()->nominal;
    $this->hookClass->setHook('civicrm_pre', [$this, 'throwOnIndividual']);

    try {
      Contact::create(FALSE)
        ->setLanguage('fr_FR')
        ->addValue('contact_type', 'Individual')
        ->addValue('first_name', 'Fails')
        ->execute();
      $this->fail('Expected exception from hook');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals('Ruh-roh', $e->getMessage());
    }

    $this->assertEquals($before, Locale::detect()->nominal);
  }

  /**
   * A failed APIv3 call with `options.language` must not leave its locale active.
   */
  public function testLocaleRestoredAfterFailedApi3Call(): void {
    $before = Locale::detect()->nominal;
    $this->hookClass->setHook('civicrm_pre', [$this, 'throwOnIndividual']);

    try {
      civicrm_api3('Contact', 'create', [
        'contact_type' => 'Individual',
        'first_name' => 'Fails',
        'options' => ['language' => 'fr_FR'],
      ]);
      $this->fail('Expected exception from hook');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals('Ruh-roh', $e->getMessage());
    }

    $this->assertEquals($before, Locale::detect()->nominal);
  }

  /**
   * A failed nested call restores the outer call's locale, not the site default.
   */
  public function testLocaleRestoredAfterFailedNestedCall(): void {
    $before = Locale::detect()->nominal;
    $localeAfterInnerFailure = NULL;
    $this->hookClass->setHook('civicrm_pre', function ($op, $objectName, $id, &$params) use (&$localeAfterInnerFailure) {
      if ($objectName !== 'Individual' || $op !== 'create') {
        return;
      }
      if ($params['first_name'] === 'Inner') {
        throw new \CRM_Core_Exception('Ruh-roh');
      }
      try {
        Contact::create(FALSE)
          ->setLanguage('de_DE')
          ->addValue('contact_type', 'Individual')
          ->addValue('first_name', 'Inner')
          ->execute();
      }
      catch (\CRM_Core_Exception $e) {
        $localeAfterInnerFailure = Locale::detect()->nominal;
      }
    });

    Contact::create(FALSE)
      ->setLanguage('fr_FR')
      ->addValue('contact_type', 'Individual')
      ->addValue('first_name', 'Outer')
      ->execute();

    $this->assertEquals('fr_FR', $localeAfterInnerFailure);
    $this->assertEquals($before, Locale::detect()->nominal);
  }

  /**
   * Implements hook_civicrm_pre().
   *
   * @throws \CRM_Core_Exception
   */
  public function throwOnIndividual($op, $objectName, $id, &$params): void {
    if ($objectName === 'Individual' && $op === 'create') {
      throw new \CRM_Core_Exception('Ruh-roh');
    }
  }

}
