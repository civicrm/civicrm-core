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

use Civi\Api4\Address;
use Civi\Api4\Email;
use Civi\Api4\Phone;
use Civi\Token\AbstractTokenSubscriber;
use Civi\Token\TokenRow;

/**
 * Class CRM_Case_Tokens
 *
 * Generate "case.*" tokens.
 */
class CRM_Core_DomainTokens extends AbstractTokenSubscriber {
  /**
   * @var string
   *   Token prefix
   */
  public $entity = 'domain';

  /**
   * @var array
   *   List of tokens provided by this class
   *   Array(string $fieldName => string $label).
   */
  public $tokenNames;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct($this->entity, $this->getDomainTokens());
  }

  public function getDomainTokens(): array {
    return [
      'name' => ts('Domain Name'),
      'address' => ts('Domain (Organization) Full Address'),
      'street_address' => ts('Domain (Organization) Street Address'),
      'supplemental_address_1' => ts('Domain (Organization) Supplemental Address'),
      'supplemental_address_2' => ts('Domain (Organization) Supplemental Address 2'),
      'supplemental_address_3' => ts('Domain (Organization) Supplemental Address 3'),
      'city' => ts('Domain (Organization) City'),
      'postal_code' => ts('Domain (Organization) Postal Code'),
      'state_province_id:label' => ts('Domain (Organization) State'),
      'state_province_id:abbr' => ts('Domain (Organization) State Abbreviation'),
      'country_id:label' => ts('Domain (Organization) Country'),
      'phone' => ts('Domain (Organization) Phone'),
      'email' => ts('Domain (Organization) Email'),
      'id' => ts('Domain ID'),
      'description' => ts('Domain Description'),
      'now' => ts('Current time/date'),
      'base_url' => ts('Domain absolute base url'),
      'tax_term' => ts('Sales tax term (e.g VAT)'),
      'empowered_by_civicrm_image_url' => ts('Empowered By CiviCRM Image'),
    ];
  }

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL): void {
    if ($field === 'now') {
      $nowObj = (new \DateTime())->setTimestamp(\CRM_Utils_Time::time());
      $row->format('text/html')->tokens($entity, $field, $nowObj);
      return;
    }
    $row->format('text/html')->tokens($entity, $field, self::getDomainTokenValues()[$field] ?? '');
    $row->format('text/plain')->tokens($entity, $field, self::getDomainTokenValues(NULL, FALSE)[$field] ?? '');
  }

  /**
   * Get the tokens available for the domain.
   *
   * This function will be made protected soon...
   *
   * @param int|null $domainID
   * @param bool $html
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @internal
   *
   * @todo - make this non-static & protected. Remove last deprecated fn that calls it.
   */
  public static function getDomainTokenValues(?int $domainID = NULL, bool $html = TRUE): array {
    if (!$domainID) {
      $domainID = CRM_Core_Config::domainID();
    }
    $cacheKey = __CLASS__ . 'domain_tokens' . $html . '_' . $domainID . '_' . CRM_Core_I18n::getLocale();
    if (!Civi::cache('metadata')->has($cacheKey)) {
      if (CRM_Core_Config::domainID() === $domainID) {
        $domain = CRM_Core_BAO_Domain::getDomain();
      }
      else {
        $domain = new CRM_Core_BAO_Domain();
        $domain->find(TRUE);
      }
      $tokens = [
        'name' => $domain->name ?? '',
        'id' => $domain->id,
        'description' => $domain->description ?? '',
      ];
      $addressFields = [
        'street_address',
        'supplemental_address_1',
        'supplemental_address_2',
        'supplemental_address_3',
        'city',
        'state_province_id:label',
        'state_province_id:abbr',
        'country_id:label',
        'country_id:abbr',
        'county_id:label',
        'county_id:abbr',
        'postal_code',
        'postal_code_suffix',
        'country_id.region_id:label',
      ];
      $address = Address::get(FALSE)
        ->addWhere('contact_id', '=', $domain->contact_id)
        ->setSelect($addressFields)
        ->addOrderBy('is_primary', 'DESC')
        ->execute()->first() ?? [];
      unset($address['id']);
      $tokens += $address;
      if ($html) {
        $tokens['address'] = str_replace("\n", '<br />', (CRM_Utils_Address::formatVCard($address)));
      }
      else {
        $tokens['address'] = CRM_Utils_Address::format($address);
      }

      $tokens['phone'] = Phone::get(FALSE)
        ->addWhere('contact_id', '=', $domain->contact_id)
        ->addOrderBy('is_primary', 'DESC')
        ->addSelect('phone')->execute()->first()['phone'] ?? '';
      $tokens['email'] = Email::get(FALSE)
        ->addWhere('contact_id', '=', $domain->contact_id)
        ->addOrderBy('is_primary', 'DESC')
        ->addSelect('email')->execute()->first()['email'] ?? '';
      ;
      $tokens['base_url'] = Civi::paths()->getVariable('cms.root', 'url');
      $tokens['empowered_by_civicrm_image_url'] = CRM_Core_Config::singleton()->userFrameworkResourceURL . 'i/civi99.png';
      $tokens['tax_term'] = (string) Civi::settings()->get('tax_term');
      Civi::cache('metadata')->set($cacheKey, $tokens);
    }
    return Civi::cache('metadata')->get($cacheKey);
  }

}
