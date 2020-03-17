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
   * @param null $pdfFormat
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
    <style>@page { margin: {$t}{$metric} {$r}{$metric} {$b}{$metric} {$l}{$metric}; }</style>
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
    if (CRM_Core_Config::singleton()->wkhtmltopdfPath) {
      return self::_html2pdf_wkhtmltopdf($paper_size, $orientation, $margins, $html, $output, $fileName);
    }
    else {
      return self::_html2pdf_dompdf($paper_size, $orientation, $html, $output, $fileName);
    }
  }

  /**
   * Convert html to tcpdf.
   *
   * @param $paper_size
   * @param $orientation
   * @param $margins
   * @param $html
   * @param $output
   * @param $fileName
   * @param $stationery_path
   */
  public static function _html2pdf_tcpdf($paper_size, $orientation, $margins, $html, $output, $fileName, $stationery_path) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Utils_PDF::_html2pdf_dompdf');
    return self::_html2pdf_dompdf($paper_size, $orientation, $margins, $html, $output, $fileName);
    // Documentation on the TCPDF library can be found at: http://www.tcpdf.org
    // This function also uses the FPDI library documented at: http://www.setasign.com/products/fpdi/about/
    // Syntax borrowed from https://github.com/jake-mw/CDNTaxReceipts/blob/master/cdntaxreceipts.functions.inc
    require_once 'tcpdf/tcpdf.php';
    // This library is only in the 'packages' area as of version 4.5
    require_once 'FPDI/fpdi.php';

    $paper_size_arr = [$paper_size[2], $paper_size[3]];

    $pdf = new TCPDF($orientation, 'pt', $paper_size_arr);
    $pdf->Open();

    if (is_readable($stationery_path)) {
      $pdf->SetStationery($stationery_path);
    }

    $pdf->SetAuthor('');
    $pdf->SetKeywords('CiviCRM.org');
    $pdf->setPageUnit($margins[0]);
    $pdf->SetMargins($margins[4], $margins[1], $margins[2], TRUE);

    $pdf->setJPEGQuality('100');
    $pdf->SetAutoPageBreak(TRUE, $margins[3]);

    $pdf->AddPage();

    $ln = TRUE;
    $fill = FALSE;
    $reset_parm = FALSE;
    $cell = FALSE;
    $align = '';

    // output the HTML content
    $pdf->writeHTML($html, $ln, $fill, $reset_parm, $cell, $align);

    // reset pointer to the last page
    $pdf->lastPage();

    // close and output the PDF
    $pdf->Close();
    $pdf_file = 'CiviLetter' . '.pdf';
    $pdf->Output($pdf_file, 'D');
    CRM_Utils_System::civiExit();
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
    // CRM-12165 - Remote file support required for image handling.
    $options = new Options();
    $options->set('isRemoteEnabled', TRUE);

    $dompdf = new DOMPDF($options);
    $dompdf->set_paper($paper_size, $orientation);
    $dompdf->load_html($html);
    $dompdf->render();

    if ($output) {
      return $dompdf->output();
    }
    else {
      // CRM-19183 remove .pdf extension from filename
      $fileName = basename($fileName, ".pdf");
      $dompdf->stream($fileName);
    }
  }

  /**
   * @param $paper_size
   * @param $orientation
   * @param $margins
   * @param $html
   * @param $output
   * @param string $fileName
   */
  public static function _html2pdf_wkhtmltopdf($paper_size, $orientation, $margins, $html, $output, $fileName) {
    require_once 'packages/snappy/src/autoload.php';
    $config = CRM_Core_Config::singleton();
    $snappy = new Knp\Snappy\Pdf($config->wkhtmltopdfPath);
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
   * @param $value
   * @param $from
   * @param $to
   * @param null $precision
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

}
