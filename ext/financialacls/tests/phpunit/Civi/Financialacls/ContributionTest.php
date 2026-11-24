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
namespace Civi\Financialacls;

use Civi\Api4\Contribution;
use Civi\Api4\Order;
use Civi\Api4\Payment;

require_once 'BaseTestClass.php';

/**
 * Class ContributionTest
 * @group headless
 */
class ContributionTest extends BaseTestClass {

  /**
   * Test the annual query returns a correct result when multiple line items
   * are present.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function testAnnualWithMultipleLineItems(): void {
    $this->createContributionWithTwoLineItems();
    $this->addFinancialAclPermissions([['view', 'Donation']]);
    $sql = \CRM_Contribute_BAO_Contribution::getAnnualQuery([$this->ids['Contact']['logged_in']]);
    $result = \CRM_Core_DAO::executeQuery($sql);
    $result->fetch();
    $this->assertSame(0, $result->N);

    // It didn't find any rows cos it is restricted to only find contributions where all lines are visible.
    \CRM_Core_DAO::executeQuery('UPDATE civicrm_line_item SET financial_type_id = ' . $this->getFinancialTypeID('Donation'));
    $result = \CRM_Core_DAO::executeQuery($sql);
    $result->fetch();
    $this->assertSame('300.00', $result->amount);
    $this->assertSame('1', $result->count);
  }

  /**
   * Create a contribution with 2 line items.
   *
   * This also involves creating t
   *
   * @param string $identifier
   *   Name to identify price set.
   */
  protected function createContributionWithTwoLineItems(string $identifier = 'Donation'): void {
    $priceSet = $this->createTestEntity('PriceSet', [
      'title' => 'Price Set',
      'name' => 'price_set',
      'financial_type_id.name' => 'Donation',
      'extends:name' => ['CiviEvent'],
    ], $identifier);
    $this->createTestEntity('PriceField', [
      'label' => 'Price Field',
      'name' => 'price_field',
      'html_type' => 'CheckBox',
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'price_set_id' => $priceSet['id'],
      'is_enter_qty' => 1,
      'financial_type_id.name' => 'Donation',
    ], $identifier . '-1');
    $this->createTestEntity('PriceField', [
      'label' => 'Price Field',
      'name' => 'price_field-2',
      'html_type' => 'CheckBox',
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'price_set_id' => $priceSet['id'],
      'is_enter_qty' => 1,
      'financial_type_id.name' => 'Donation',
    ], $identifier . '-2');
    $this->createTestEntity('PriceFieldValue', [
      'name' => 'Price Field 1',
      'label' => 'Price Field 1',
      'value' => 100,
      'weight' => 1,
      'amount' => 100,
      'price_field_id' => $this->ids['PriceField'][$identifier . '-1'],
      'financial_type_id:name' => 'Donation',
    ], $identifier . '-1');
    $this->createTestEntity('PriceFieldValue', [
      'name' => 'Price Field 2',
      'label' => 'Price Field 2',
      'value' => 200,
      'weight' => 2,
      'amount' => 200,
      'price_field_id' => $this->ids['PriceField'][$identifier . '-2'],
      'financial_type_id:name' => 'Donation',
    ], $identifier . '-2');

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts'];
    $order = Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $this->createLoggedInUser(),
        'financial_type_id:name' => 'Donation',
      ])
      ->addLineItem([
        'price_field_id' => $this->ids['PriceField'][$identifier . '-1'],
        'price_field_value_id' => $this->ids['PriceFieldValue'][$identifier . '-1'],
        'label' => 'price 1',
        'qty' => 1,
        'line_total' => 100,
        'financial_type_id:name' => 'Event Fee',
        'entity_table' => 'civicrm_contribution',
      ])
      ->addLineItem([
        'price_field_id' => $this->ids['PriceField'][$identifier . '-2'],
        'price_field_value_id' => $this->ids['PriceFieldValue'][$identifier . '-2'],
        'label' => 'price 2',
        'qty' => 1,
        'line_total' => 200,
        'financial_type_id:name' => 'Event Fee',
        'entity_table' => 'civicrm_contribution',
      ])
      ->execute()->first();
    Payment::create(FALSE)
      ->setValues([
        'contribution_id' => $order['id'],
        'total_amount' => 300,
      ])
      ->execute();
  }

  /**
   * Recurring rows are checked against the recurring contribution, not a contribution sharing its ID.
   */
  public function testRecurringLinksIgnoreContributionWithSameID(): void {
    $this->createContributionWithTwoLineItems();
    $contributionID = Contribution::get(FALSE)->execute()->single()['id'];
    $recurID = $this->createRecurringContribution('Donation');
    \CRM_Core_DAO::executeQuery('UPDATE civicrm_contribution_recur SET id = %1 WHERE id = %2', [
      1 => [$contributionID, 'Integer'],
      2 => [$recurID, 'Integer'],
    ]);
    $this->setRecurringLinkPermissions([['view', 'Donation'], ['edit', 'Donation']]);
    $this->assertSame(['View', 'Edit'], $this->getRecurringLinkNames($contributionID));
  }

  /**
   * A recurring contribution of a type the user may not edit loses its Edit link.
   */
  public function testRecurringLinksForRestrictedType(): void {
    $recurID = $this->createRecurringContribution('Event Fee');
    $this->setRecurringLinkPermissions([['view', 'Event Fee']]);
    $this->assertSame(['View'], $this->getRecurringLinkNames($recurID));
  }

  private function createRecurringContribution(string $financialType): int {
    return $this->createTestEntity('ContributionRecur', [
      'contact_id' => $this->individualCreate(),
      'amount' => 10,
      'frequency_interval' => 1,
      'financial_type_id:name' => $financialType,
    ])['id'];
  }

  private function setRecurringLinkPermissions(array $aclPermissions): void {
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'view all contacts', 'edit contributions']);
    $this->addFinancialAclPermissions($aclPermissions);
  }

  private function getRecurringLinkNames(int $recurID): array {
    $links = [
      ['name' => 'View', 'bit' => \CRM_Core_Action::VIEW],
      ['name' => 'Edit', 'bit' => \CRM_Core_Action::UPDATE],
    ];
    $mask = NULL;
    $values = ['crid' => $recurID];
    \CRM_Utils_Hook::links('contribution.selector.recurring', 'Contribution', $recurID, $links, $mask, $values);
    return array_column($links, 'name');
  }

  public function testSuperPermissions(): void {
    // With no financial ACLs.
    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
      'view all contacts',
    ]);
    $this->createLoggedInUser();
    $visibleFTs = \CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search');
    $this->assertSame(0, count($visibleFTs));

    // With financial ACLs.
    $permissions = array_merge(\CRM_Core_Config::singleton()->userPermissionClass->permissions, [
      'view contributions of all types',
      'delete contributions of all types',
      'add contributions of all types',
      'edit contributions of all types',
    ]);
    $this->setPermissions($permissions);
    // Clear pseudoconstant lookup cache.
    unset(\Civi::$statics['CRM_Core_PseudoConstant']);
    $visibleFTs = \CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search');
    $this->assertSame(4, count($visibleFTs));
  }

}
