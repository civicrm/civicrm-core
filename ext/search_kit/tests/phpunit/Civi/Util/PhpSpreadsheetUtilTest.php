<?php
declare(strict_types = 1);

namespace Civi\Util;

use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Util\PhpSpreadsheetUtil
 *
 * @group headless
 */
final class PhpSpreadsheetUtilTest extends TestCase implements HeadlessInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testCrmDateFormatToFormatCode(): void {
    static::assertSame('mmmm d, yyyy  h:mm AM/PM', PhpSpreadsheetUtil::crmDateFormatToFormatCode('%B %E%f, %Y %l:%M %P'));
    static::assertSame('mm/dd/yyyy', PhpSpreadsheetUtil::crmDateFormatToFormatCode('%m/%d/%Y'));
    static::assertSame('dd.mm.yyyy, hh:mm', PhpSpreadsheetUtil::crmDateFormatToFormatCode('%d.%m.%Y, %H:%M Uhr'));
    static::assertSame('h', PhpSpreadsheetUtil::crmDateFormatToFormatCode("%l o'clock"));

    // No real world formats, just for test.
    static::assertSame('mm/dd/yyyy', PhpSpreadsheetUtil::crmDateFormatToFormatCode('%mTest/%d/%Y'));
    static::assertSame('mm:dd/yyyy', PhpSpreadsheetUtil::crmDateFormatToFormatCode('%mTe:st/%d/%Y'));
  }

}
