<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */


use Dompdf\Dompdf;
use Dompdf\Options;
/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Utils_PDF_Utils {

  /**
   * @param $text
   * @param string $fileName
   * @param bool $output
   * @param null $pdfFormat
   *
   * @return string|void
   */
  public static function html2pdf(&$text, $fileName = 'civicrm.pdf', $output = FALSE, $pdfFormat = NULL) {
    if (is_array($text)) {
      $pages = &$text;
    }
    else {
      $pages = array($text);
    }
    // Get PDF Page Format
    $format = CRM_Core_BAO_PdfFormat::getDefaultValues();
    if (is_array($pdfFormat)) {
      // PDF Page Format parameters passed in
      $format = array_merge($format, $pdfFormat);
    }
    else {
      // PDF Page Format ID passed in
      $format = CRM_Core_BAO_PdfFormat::getById($pdfFormat);
    }
    $paperSize = CRM_Core_BAO_PaperSize::getByName($format['paper_size']);
    $paper_width = self::convertMetric($paperSize['width'], $paperSize['metric'], 'pt');
    $paper_height = self::convertMetric($paperSize['height'], $paperSize['metric'], 'pt');
    // dompdf requires dimensions in points
    $paper_size = array(0, 0, $paper_width, $paper_height);
    $orientation = CRM_Core_BAO_PdfFormat::getValue('orientation', $format);
    $metric = CRM_Core_BAO_PdfFormat::getValue('metric', $format);
    $t = CRM_Core_BAO_PdfFormat::getValue('margin_top', $format);
    $r = CRM_Core_BAO_PdfFormat::getValue('margin_right', $format);
    $b = CRM_Core_BAO_PdfFormat::getValue('margin_bottom', $format);
    $l = CRM_Core_BAO_PdfFormat::getValue('margin_left', $format);

    $stationery_path_partial = CRM_Core_BAO_PdfFormat::getValue('stationery', $format);

    $stationery_path = NULL;
    if (strlen($stationery_path_partial)) {
      $doc_root = $_SERVER['DOCUMENT_ROOT'];
      $stationery_path = $doc_root . "/" . $stationery_path_partial;
    }

    $margins = array($metric, $t, $r, $b, $l);

    $config = CRM_Core_Config::singleton();

    // Add a special region for the HTML header of PDF files:
    $pdfHeaderRegion = CRM_Core_Region::instance('export-document-header', FALSE);
    $htmlHeader = ($pdfHeaderRegion) ? $pdfHeaderRegion->render('', FALSE) : '';

    $html = "
<html>
  <head>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/>
    <style>@page { margin: {$t}{$metric} {$r}{$metric} {$b}{$metric} {$l}{$metric}; }</style>
    <style type=\"text/css\">@import url({$config->userFrameworkResourceURL}css/print.css);</style>
    {$htmlHeader}
  </head>
  <body>
    <div id=\"crm-container\">\n";

    // Strip <html>, <header>, and <body> tags from each page
    $htmlElementstoStrip = array(
      '@<head[^>]*?>.*?</head>@siu',
      '@<script[^>]*?>.*?</script>@siu',
      '@<body>@siu',
      '@</body>@siu',
      '@<html[^>]*?>@siu',
      '@</html>@siu',
      '@<!DOCTYPE[^>]*?>@siu',
    );
    $htmlElementsInstead = array('', '', '', '', '', '');
    foreach ($pages as & $page) {
      $page = preg_replace($htmlElementstoStrip,
        $htmlElementsInstead,
        $page
      );
    }
    // Glue the pages together
    $html .= implode("\n<div style=\"page-break-after: always\"></div>\n", $pages);
    $html .= "
    </div>
  </body>
</html>";
    if ($config->wkhtmltopdfPath) {
      return self::_html2pdf_wkhtmltopdf($paper_size, $orientation, $margins, $html, $output, $fileName);
    }
    else {
      return self::_html2pdf_dompdf($paper_size, $orientation, $html, $output, $fileName);
      //return self::_html2pdf_tcpdf($paper_size, $orientation, $margins, $html, $output, $fileName,  $stationery_path);
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
    // Documentation on the TCPDF library can be found at: http://www.tcpdf.org
    // This function also uses the FPDI library documented at: http://www.setasign.com/products/fpdi/about/
    // Syntax borrowed from https://github.com/jake-mw/CDNTaxReceipts/blob/master/cdntaxreceipts.functions.inc
    require_once 'tcpdf/tcpdf.php';
    require_once 'FPDI/fpdi.php'; // This library is only in the 'packages' area as of version 4.5

    $paper_size_arr = array($paper_size[2], $paper_size[3]);

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
    CRM_Utils_System::civiExit(1);
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
