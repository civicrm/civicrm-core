<?php

namespace Civi\Contribute;

use Civi\Checkout\CheckoutOptionUtils;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class CheckoutOptionUtilsTest extends TestCase implements HeadlessInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testMapCardParamsExpandsYear(): void {
    $currentYear = (int) date('Y');
    $shortYear = substr((string) ($currentYear + 1), 2, 2);

    $mapped = CheckoutOptionUtils::mapCardParams([
      'expiry_month' => '04',
      'expiry_year' => $shortYear,
      'amount' => 10,
    ]);

    $this->assertEquals('04', $mapped['month']);
    $this->assertEquals($currentYear + 1, $mapped['year']);
    // Original expiry_ prefixed keys should still be present - this is
    // an additive mapping, not a rename.
    $this->assertEquals('04', $mapped['expiry_month']);
    $this->assertEquals(10, $mapped['amount']);
  }

  public function testMapCardParamsLeavesUnrelatedParamsUntouched(): void {
    $mapped = CheckoutOptionUtils::mapCardParams(['amount' => 10]);
    $this->assertEquals(['amount' => 10], $mapped);
  }

  public function testValidateExpiryDateAcceptsCurrentMonthAndYear(): void {
    $now = getdate();
    $month = str_pad((string) $now['mon'], 2, '0', STR_PAD_LEFT);
    $year = substr((string) $now['year'], 2, 2);
    $this->assertTrue(CheckoutOptionUtils::validateExpiryDate($month, $year));
  }

  public function testValidateExpiryDateRejectsPastYear(): void {
    $lastYear = substr((string) ((int) date('Y') - 1), 2, 2);
    $this->assertFalse(CheckoutOptionUtils::validateExpiryDate('01', $lastYear));
  }

  public function testValidateExpiryDateRejectsPastMonthInCurrentYear(): void {
    $now = getdate();
    if ($now['mon'] === 1) {
      $this->markTestSkipped('Cannot construct a past month in January without crossing years.');
    }
    $pastMonth = str_pad((string) ($now['mon'] - 1), 2, '0', STR_PAD_LEFT);
    $year = substr((string) $now['year'], 2, 2);
    $this->assertFalse(CheckoutOptionUtils::validateExpiryDate($pastMonth, $year));
  }

  public function testValidateExpiryDateRejectsMistypedShortYearAsTooFarInFuture(): void {
    // A mistyped/nonsense year like "94" expands to 2094 under a naive
    // current-century assumption, which is technically in the future -
    // the max-offset check is what actually catches this.
    $this->assertFalse(CheckoutOptionUtils::validateExpiryDate('06', '94'));
  }

  public function testValidateExpiryDateRejectsInvalidMonth(): void {
    $year = substr((string) ((int) date('Y') + 1), 2, 2);
    $this->assertFalse(CheckoutOptionUtils::validateExpiryDate('13', $year));
    $this->assertFalse(CheckoutOptionUtils::validateExpiryDate('00', $year));
  }

}
