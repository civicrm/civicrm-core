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


use Dompdf\Dompdf;
use Dompdf\Options;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_PDF_Utils {

  /**
   * @param array $text
   *   List of HTML snippets.
   * @param string $fileName
   *   The logical filename to display.
   *   Ex: "HelloWorld.pdf".
   * @param bool $output
   *   FALSE to display PDF. TRUE to return as string.
   * @param array|int|null $pdfFormat
   *   Unclear. Possibly PdfFormat or formValues.
   *
   * @return string|void
   */
  public static function html2pdf($text, $fileName = 'civicrm.pdf', $output = FALSE, $pdfFormat = NULL) {
    if (is_array($text)) {
      $pages = &$text;
    }
    else {
      $pages = [$text];
    }
    // Get PDF Page Format
    $format = CRM_Core_BAO_PdfFormat::getDefaultValues();
    if (is_array($pdfFormat)) {
      // PDF Page Format parameters passed in
      $format = array_merge($format, $pdfFormat);
    }
    elseif (!empty($pdfFormat)) {
      // PDF Page Format ID passed in
      $format = CRM_Core_BAO_PdfFormat::getById($pdfFormat);
    }
    $paperSize = CRM_Core_BAO_PaperSize::getByName($format['paper_size']);
    $paper_width = self::convertMetric($paperSize['width'], $paperSize['metric'], 'pt');
    $paper_height = self::convertMetric($paperSize['height'], $paperSize['metric'], 'pt');
    // dompdf requires dimensions in points
    $paper_size = [0, 0, $paper_width, $paper_height];
    $orientation = CRM_Core_BAO_PdfFormat::getValue('orientation', $format);

    if (\Civi::settings()->get('weasyprint_path')) {
      if ($orientation == 'landscape') {
        $css_pdf_width = $paperSize['height'];
        $css_pdf_height = $paperSize['width'];
      }
      else {
        $css_pdf_width = $paperSize['width'];
        $css_pdf_height = $paperSize['height'];
      }
      $css_page_size = " size: {$css_pdf_width}{$paperSize['metric']} {$css_pdf_height}{$paperSize['metric']};";
    }
    else {
      $css_page_size = "";
    }

    $metric = CRM_Core_BAO_PdfFormat::getValue('metric', $format);
    $t = CRM_Core_BAO_PdfFormat::getValue('margin_top', $format);
    $r = CRM_Core_BAO_PdfFormat::getValue('margin_right', $format);
    $b = CRM_Core_BAO_PdfFormat::getValue('margin_bottom', $format);
    $l = CRM_Core_BAO_PdfFormat::getValue('margin_left', $format);

    $margins = [$metric, $t, $r, $b, $l];

    // Add a special region for the HTML header of PDF files:
    $pdfHeaderRegion = CRM_Core_Region::instance('export-document-header', FALSE);
    $htmlHeader = ($pdfHeaderRegion) ? $pdfHeaderRegion->render('', FALSE) : '';

    $html = "
<html>
  <head>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/>
    <style>@page { margin: {$t}{$metric} {$r}{$metric} {$b}{$metric} {$l}{$metric};$css_page_size }</style>
    <style type=\"text/css\">@import url(" . CRM_Core_Config::singleton()->userFrameworkResourceURL . "css/print.css);</style>
    {$htmlHeader}
  </head>
  <body>
    <div id=\"crm-container\">\n";

    // Strip <html>, <header>, and <body> tags from each page

    $htmlElementstoStrip = [
      '<head[^>]*?>.*?</head>',
      '<script[^>]*?>.*?</script>',
      '<body>',
      '</body>',
      '<html[^>]*?>',
      '</html>',
      '<!DOCTYPE[^>]*?>',
    ];
    foreach ($pages as & $page) {
      foreach ($htmlElementstoStrip as $pattern) {
        $page = mb_eregi_replace($pattern, '', $page);
      }
    }
    // Glue the pages together
    $html .= implode("\n<div style=\"page-break-after: always\"></div>\n", $pages);
    $html .= "
    </div>
  </body>
</html>";
    if (\Civi::settings()->get('weasyprint_path')) {
      return self::_html2pdf_weasyprint($html, $output, $fileName);
    }
    elseif (\Civi::settings()->get('wkhtmltopdfPath')) {
      return self::_html2pdf_wkhtmltopdf($paper_size, $orientation, $margins, $html, $output, $fileName);
    }
    else {
      return self::_html2pdf_dompdf($paper_size, $orientation, $html, $output, $fileName);
    }
  }

  /**
   * @param $paper_size
   * @param $orientation
   * @param $html
   * @param $output
   * @param string $fileName
   *
   * @return string
   */
  public static function _html2pdf_dompdf($paper_size, $orientation, $html, $output, $fileName) {
    $options = self::getDompdfOptions();
    $dompdf = new DOMPDF($options);
    $dompdf->setPaper($paper_size, $orientation);
    $dompdf->loadHtml($html);
    $dompdf->render();

    if ($output) {
      return $dompdf->output();
    }
    // CRM-19183 remove .pdf extension from filename
    $fileName = basename($fileName, ".pdf");
    if (CIVICRM_UF === 'UnitTests') {
      // Streaming content will 'die' in unit tests unless ob_start()
      // has been called.
      throw new CRM_Core_Exception_PrematureExitException('_html2pdf_dompdf called', [
        'html' => $html,
        'fileName' => $fileName,
        'output' => 'pdf',
      ]);
    }
    $dompdf->stream($fileName);
  }

  /**
   * @param string $html
   * @param bool $output
   * @param string $fileName
   */
  public static function _html2pdf_weasyprint($html, $output, $fileName) {
    $weasyprint = new Pontedilana\PhpWeasyPrint\Pdf(\Civi::settings()->get('weasyprint_path'));
    $pdf = $weasyprint->getOutputFromHtml($html);
    if ($output) {
      return $pdf;
    }
    else {
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/pdf');
      CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
      echo $pdf;
    }
  }

  /**
   * @param float|int[] $paper_size
   * @param string $orientation
   * @param array $margins
   * @param string $html
   * @param bool $output
   * @param string $fileName
   */
  public static function _html2pdf_wkhtmltopdf($paper_size, $orientation, $margins, $html, $output, $fileName) {
    $snappy = new Knp\Snappy\Pdf(\Civi::settings()->get('wkhtmltopdfPath'));
    $snappy->setOption("page-width", $paper_size[2] . "pt");
    $snappy->setOption("page-height", $paper_size[3] . "pt");
    $snappy->setOption("orientation", $orientation);
    $snappy->setOption("margin-top", $margins[1] . $margins[0]);
    $snappy->setOption("margin-right", $margins[2] . $margins[0]);
    $snappy->setOption("margin-bottom", $margins[3] . $margins[0]);
    $snappy->setOption("margin-left", $margins[4] . $margins[0]);
    $pdf = $snappy->getOutputFromHtml($html);
    if ($output) {
      return $pdf;
    }
    else {
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/pdf');
      CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
      echo $pdf;
    }
  }

  /**
   * convert value from one metric to another.
   *
   * @param int $value
   * @param string $from
   * @param string $to
   * @param int|null $precision
   *
   * @return float|int
   */
  public static function convertMetric($value, $from, $to, $precision = NULL) {
    switch ($from . $to) {
      case 'incm':
        $value *= 2.54;
        break;

      case 'inmm':
        $value *= 25.4;
        break;

      case 'inpt':
        $value *= 72;
        break;

      case 'cmin':
        $value /= 2.54;
        break;

      case 'cmmm':
        $value *= 10;
        break;

      case 'cmpt':
        $value *= 72 / 2.54;
        break;

      case 'mmin':
        $value /= 25.4;
        break;

      case 'mmcm':
        $value /= 10;
        break;

      case 'mmpt':
        $value *= 72 / 25.4;
        break;

      case 'ptin':
        $value /= 72;
        break;

      case 'ptcm':
        $value *= 2.54 / 72;
        break;

      case 'ptmm':
        $value *= 25.4 / 72;
        break;
    }
    if (!is_null($precision)) {
      $value = round($value, $precision);
    }
    return $value;
  }

  /**
   * Allow setting some dompdf options.
   *
   * We don't support all the available dompdf options.
   *
   * @return \Dompdf\Options
   */
  private static function getDompdfOptions(): Options {
    $options = new Options();
    $settings = [
      // CRM-12165 - Remote file support required for image handling so default to TRUE
      'enable_remote' => \Civi::settings()->get('dompdf_enable_remote') ?? TRUE,
    ];
    // only set these ones if a setting exists for them
    foreach (['font_dir', 'chroot', 'log_output_file'] as $setting) {
      $value = \Civi::settings()->get("dompdf_$setting");
      if (isset($value)) {
        $settings[$setting] = Civi::paths()->getPath($value);
      }
    }

    // core#4791 - Set cache dir to prevent files being generated in font dir
    $cacheDir = self::getCacheDir($settings);
    if ($cacheDir !== "") {
      $settings['font_cache'] = $cacheDir;
    }
    $options->set($settings);
    return $options;
  }

  /**
   * Get location of cache folder.
   *
   * @param array $settings
   * @return string
   */
  private static function getCacheDir(array $settings): string {
    // Use subfolder of custom font dir if it is writable
    if (isset($settings['font_dir']) && is_writable($settings['font_dir'])) {
      $cacheDir = $settings['font_dir'] . DIRECTORY_SEPARATOR . 'font_cache';
    }
    else {
      $cacheDir = CRM_Core_Config::singleton()->uploadDir . '/font_cache';
    }
    // Try to create dir if it doesn't exist or return empty string
    if ((!is_dir($cacheDir)) && (!mkdir($cacheDir))) {
      $cacheDir = "";
    }
    return $cacheDir;
  }

}
