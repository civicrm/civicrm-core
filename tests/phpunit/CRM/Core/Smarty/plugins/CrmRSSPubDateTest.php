<?php

/**
 * Class CRM_Core_Smarty_plugins_CrmRSSPubDateTest
 * @group headless
 */
class CRM_Core_Smarty_plugins_CrmRSSPubDateTest extends CiviUnitTestCase {

  const FIXED_DATE = '2022-06-20 13:14:15';
  const FIXED_DATE_RSS = 'Mon, 20 Jun 2022 13:14:15';

  /**
   * DataProvider for testRSSPubDate
   * @return array
   */
  public function dateList(): array {
    // explicit indexes to make it easier to see which one failed
    return [
      // Note we need to calculate the timezone offset each time based on the
      // date in question, because DST.
      0 => ['2021-02-03 13:14:15', 'Wed, 03 Feb 2021 13:14:15 ' . (new DateTime('2021-02-03 13:14:15'))->format('O')],
      1 => ['2021-02-03', 'Wed, 03 Feb 2021 00:00:00 ' . (new DateTime('2021-02-03'))->format('O')],
      2 => ['2021-12-13 04:05:06', 'Mon, 13 Dec 2021 04:05:06 ' . (new DateTime('2021-12-13 04:05:06'))->format('O')],
      3 => ['2021-12-13 04:05', 'Mon, 13 Dec 2021 04:05:00 ' . (new DateTime('2021-12-13 04:05'))->format('O')],
    ];
  }

  /**
   * @dataProvider dateList
   * @param string $input
   * @param string $expected
   */
  public function testRSSPubDate(string $input, string $expected): void {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('the_date', $input);
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{$the_date|crmRSSPubDate}');
    $this->assertEquals($expected, $actual);
  }

  /**
   * DataProvider for testRSSPubDateBad
   * @return array
   */
  public function dateListBad(): array {
    $fixedDate = self::FIXED_DATE_RSS . ' ' . (new DateTime(self::FIXED_DATE))->format('O');
    // explicit indexes to make it easier to see which one failed
    return [
      0 => ['', $fixedDate],
      1 => [NULL, $fixedDate],
      // smarty itself gives an error here before even getting to the modifier function, so we can't easily test this
      // 2 => [[], ''],
      2 => ['nap time', $fixedDate],
      3 => ['0', $fixedDate],
      4 => [0, $fixedDate],
      5 => [1, $fixedDate],
    ];
  }

  /**
   * Test that invalid inputs return "today"'s date.
   *
   * @dataProvider dateListBad
   *
   * @param mixed $input
   * @param string $expected
   *
   * @throws \CRM_Core_Exception
   */
  public function testRSSPubDateBad($input, string $expected): void {
    putenv('TIME_FUNC=frozen');
    CRM_Utils_Time::setTime(self::FIXED_DATE);

    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('the_date', $input);
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{$the_date|crmRSSPubDate}');
    $this->assertEquals($expected, $actual);
  }

}
