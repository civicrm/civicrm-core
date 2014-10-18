<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
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
  static function html2pdf(&$text, $fileName = 'civicrm.pdf', $output = FALSE, $pdfFormat = NULL) {
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
    $paperSize    = CRM_Core_BAO_PaperSize::getByName($format['paper_size']);
    $paper_width  = self::convertMetric($paperSize['width'], $paperSize['metric'], 'pt');
    $paper_height = self::convertMetric($paperSize['height'], $paperSize['metric'], 'pt');
    // dompdf requires dimensions in points
    $paper_size  = array(0, 0, $paper_width, $paper_height);
    $orientation = CRM_Core_BAO_PdfFormat::getValue('orientation', $format);
    $metric      = CRM_Core_BAO_PdfFormat::getValue('metric', $format);
    $t           = CRM_Core_BAO_PdfFormat::getValue('margin_top', $format);
    $r           = CRM_Core_BAO_PdfFormat::getValue('margin_right', $format);
    $b           = CRM_Core_BAO_PdfFormat::getValue('margin_bottom', $format);
    $l           = CRM_Core_BAO_PdfFormat::getValue('margin_left', $format);

    $stationery_path_partial  = CRM_Core_BAO_PdfFormat::getValue('stationery', $format);

    $stationery_path = NULL;
    if (strlen($stationery_path_partial)) {
      $doc_root = $_SERVER['DOCUMENT_ROOT'];
      $stationery_path = $doc_root . "/" . $stationery_path_partial;
    }

    $margins     = array($metric,$t,$r,$b,$l);

    $config = CRM_Core_Config::singleton();
    $html = "
<html>
  <head>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/>
    <style>@page { margin: {$t}{$metric} {$r}{$metric} {$b}{$metric} {$l}{$metric}; }</style>
    <style type=\"text/css\">@import url({$config->userFrameworkResourceURL}css/print.css);</style>
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

  static function _html2pdf_tcpdf($paper_size, $orientation, $margins, $html, $output, $fileName, $stationery_path) {
    // Documentation on the TCPDF library can be found at: http://www.tcpdf.org
    // This function also uses the FPDI library documented at: http://www.setasign.com/products/fpdi/about/
    // Syntax borrowed from https://github.com/jake-mw/CDNTaxReceipts/blob/master/cdntaxreceipts.functions.inc
    require_once 'tcpdf/tcpdf.php';
    require_once('FPDI/fpdi.php'); // This library is only in the 'packages' area as of version 4.5

    $paper_size_arr  = array( $paper_size[2], $paper_size[3]);

    $pdf = new TCPDF($orientation, 'pt', $paper_size_arr);
    $pdf->Open();

    if (is_readable($stationery_path)){
      $pdf->SetStationery( $stationery_path );
    }

    $pdf->SetAuthor('');
    $pdf->SetKeywords('CiviCRM.org');
    $pdf->setPageUnit( $margins[0] ) ;
    $pdf->SetMargins($margins[4], $margins[1], $margins[2], true);

    $pdf->setJPEGQuality('100');
    $pdf->SetAutoPageBreak(true, $margins[3]);

    $pdf->AddPage();

    $ln = true ;
    $fill = false ;
    $reset_parm = false;
    $cell = false;
    $align = '' ;

    // output the HTML content
    $pdf->writeHTML($html, $ln, $fill, $reset_parm, $cell, $align);

    // reset pointer to the last page
    $pdf->lastPage();

    // close and output the PDF
    $pdf->Close();
    $pdf_file =  'CiviLetter'.'.pdf';
    $pdf->Output($pdf_file, 'D');
    CRM_Utils_System::civiExit(1);
  }

  /**
   * @param $paper_size
   * @param $orientation
   * @param $html
   * @param $output
   * @param $fileName
   *
   * @return string
   */
  static function _html2pdf_dompdf($paper_size, $orientation, $html, $output, $fileName) {
    require_once 'packages/dompdf/dompdf_config.inc.php';
    spl_autoload_register('DOMPDF_autoload');
    $dompdf = new DOMPDF();
    $dompdf->set_paper($paper_size, $orientation);
    $dompdf->load_html($html);
    $dompdf->render();

    if ($output) {
      return $dompdf->output();
    }
    else {
      $dompdf->stream($fileName);
    }
  }

  /**
   * @param $paper_size
   * @param $orientation
   * @param $margins
   * @param $html
   * @param $output
   * @param $fileName
   */
  static function _html2pdf_wkhtmltopdf($paper_size, $orientation, $margins, $html, $output, $fileName) {
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
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="' . $fileName . '"');
      echo $pdf;
    }
  }

  /*
   * function to convert value from one metric to another
   */
  /**
   * @param $value
   * @param $from
   * @param $to
   * @param null $precision
   *
   * @return float|int
   */
  static function convertMetric($value, $from, $to, $precision = NULL) {
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
   * @param $fileName
   * @param $searchPath
   * @param $values
   * @param int $numPages
   * @param bool $echo
   * @param string $output
   * @param string $creator
   * @param string $author
   * @param string $title
   */
  static function &pdflib($fileName,
    $searchPath,
    &$values,
    $numPages = 1,
    $echo     = TRUE,
    $output   = 'College_Match_App',
    $creator  = 'CiviCRM',
    $author   = 'http://www.civicrm.org/',
    $title    = '2006 College Match Scholarship Application'
  ) {
    try {
      $pdf = new PDFlib();
      $pdf->set_parameter("compatibility", "1.6");
      $pdf->set_parameter("licensefile", "/home/paras/bin/license/pdflib.txt");

      if ($pdf->begin_document('', '') == 0) {
        CRM_Core_Error::statusBounce("PDFlib Error: " . $pdf->get_errmsg());
      }

      $config = CRM_Core_Config::singleton();
      $pdf->set_parameter('resourcefile', $config->templateDir . '/Quest/pdf/pdflib.upr');
      $pdf->set_parameter('textformat', 'utf8');

      /* Set the search path for fonts and PDF files */

      $pdf->set_parameter('SearchPath', $searchPath);

      /* This line is required to avoid problems on Japanese systems */

      $pdf->set_parameter('hypertextencoding', 'winansi');

      $pdf->set_info('Creator', $creator);
      $pdf->set_info('Author', $author);
      $pdf->set_info('Title', $title);

      $blockContainer = $pdf->open_pdi($fileName, '', 0);
      if ($blockContainer == 0) {
        CRM_Core_Error::statusBounce('PDFlib Error: ' . $pdf->get_errmsg());
      }

      for ($i = 1; $i <= $numPages; $i++) {
        $page = $pdf->open_pdi_page($blockContainer, $i, '');
        if ($page == 0) {
          CRM_Core_Error::statusBounce('PDFlib Error: ' . $pdf->get_errmsg());
        }

        /* dummy page size */
        $pdf->begin_page_ext(20, 20, '');

        /* This will adjust the page size to the block container's size. */

        $pdf->fit_pdi_page($page, 0, 0, 'adjustpage');


        $status = array();
        /* Fill all text blocks with dynamic data */

        foreach ($values as $key => $value) {
          if (is_array($value)) {
            continue;
          }

          // pdflib does like the forward slash character, hence convert
          $value = str_replace('/', '_', $value);

          $res = $pdf->fill_textblock($page,
            $key,
            $value,
            'embedding encoding=winansi'
          );

          /**
           if ( $res == 0 ) {
           CRM_Core_Error::debug( "$key, $value: $res", $pdf->get_errmsg( ) );
           } else {
           CRM_Core_Error::debug( "SUCCESS: $key, $value", null );
           }
           **/
        }

        $pdf->end_page_ext('');
        $pdf->close_pdi_page($page);
      }

      $pdf->end_document('');
      $pdf->close_pdi($blockContainer);

      $buf = $pdf->get_buffer();
      $len = strlen($buf);

      if ($echo) {
        header('Content-type: application/pdf');
        header("Content-Length: $len");
        header("Content-Disposition: inline; filename={$output}.pdf");
        echo $buf;
        CRM_Utils_System::civiExit();
      }
      else {
        return $buf;
      }
    }
    catch(PDFlibException$excp) {
      CRM_Core_Error::statusBounce('PDFlib Error: Exception' .
        "[" . $excp->get_errnum() . "] " . $excp->get_apiname() . ": " .
        $excp->get_errmsg()
      );
    }
    catch(Exception$excp) {
      CRM_Core_Error::statusBounce("PDFlib Error: " . $excp->get_errmsg());
    }
  }
}

