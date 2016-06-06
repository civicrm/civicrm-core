<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Utils_PDF_Document {

  /**
   * @param array $pages
   * @param string $fileName
   * @param array|int $format
   */
  public static function html2doc($pages, $fileName, $format = array()) {
    if (is_array($format)) {
      // PDF Page Format parameters passed in - merge with defaults
      $format += CRM_Core_BAO_PdfFormat::getDefaultValues();
    }
    else {
      // PDF Page Format ID passed in
      $format = CRM_Core_BAO_PdfFormat::getById($format);
    }
    $paperSize = CRM_Core_BAO_PaperSize::getByName($format['paper_size']);

    $metric = CRM_Core_BAO_PdfFormat::getValue('metric', $format);
    $pageStyle = array(
      'orientation' => CRM_Core_BAO_PdfFormat::getValue('orientation', $format),
      'pageSizeW' => self::toTwip($paperSize['width'], $paperSize['metric']),
      'pageSizeH' => self::toTwip($paperSize['height'], $paperSize['metric']),
      'marginTop' => self::toTwip(CRM_Core_BAO_PdfFormat::getValue('margin_top', $format), $metric),
      'marginRight' => self::toTwip(CRM_Core_BAO_PdfFormat::getValue('margin_right', $format), $metric),
      'marginBottom' => self::toTwip(CRM_Core_BAO_PdfFormat::getValue('margin_bottom', $format), $metric),
      'marginLeft' => self::toTwip(CRM_Core_BAO_PdfFormat::getValue('margin_left', $format), $metric),
    );

    $ext = pathinfo($fileName, PATHINFO_EXTENSION);

    $phpWord = new \PhpOffice\PhpWord\PhpWord();

    $phpWord->getDocInfo()
      ->setCreator(CRM_Core_DAO::getFieldValue('CRM_Contact_BAO_Contact', CRM_Core_Session::getLoggedInContactID(), 'display_name'));

    foreach ((array) $pages as $page => $html) {
      $section = $phpWord->addSection($pageStyle + array('breakType' => 'nextPage'));
      \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html);
    }
    $formats = array(
      'docx' => 'Word2007',
      'odt' => 'ODText',
      'html' => 'HTML',
      // todo
      'pdf' => 'PDF',
    );
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, $formats[$ext]);

    // TODO: Split document generation and output into separate functions
    CRM_Utils_System::setHttpHeader('Content-Type', "application/$ext");
    CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    $objWriter->save("php://output");
  }

  /**
   * @param $value
   * @param $metric
   * @return int
   */
  public static function toTwip($value, $metric) {
    $point = CRM_Utils_PDF_Utils::convertMetric($value, $metric, 'pt');
    return \PhpOffice\PhpWord\Shared\Converter::pointToTwip($point);
  }

  /**
   * @param array $path  docx/odt file path
   * @param string $type  File type
   * @param bool $returnContent extract content of docx/odt file as a text, later used for token replacement
   *
   * @return string
   *    Return filepath of created html copy of document OR extracted content of document
   */
  public static function docReader($path, $type, $returnContent = FALSE) {
    // get path of civicrm upload directory which is used for temporary file storage
    $uploadDir = Civi::settings()->get('uploadDir');

    // build the path of of new html file
    $pathInfo = pathinfo($path);
    $newFile = $pathInfo['filename'] . ".html";
    $absPath = Civi::paths()->getPath($uploadDir) . $newFile;

    // cleanup temporary html file created for preview
    if (file_exists($absPath)) {
      unlink($absPath);
    }

    if ($returnContent) {
      return self::doc2Text($path, $type);
    }

    $fileType = ($type == 'docx') ? 'Word2007' : 'ODText';
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($path, $fileType);
    $phpWord->save($absPath, 'HTML');

    return \Civi::paths()->getUrl($uploadDir) . $newFile;
  }

  /**
   * Extract content of docx/odt file as text and later used for token replacement
   * @param string $filePath Document file path
   * @param string $type File type of document
   *
   * @return string
   *   File content of document as text
   */
  public static function doc2Text($filePath, $type) {
    $content = '';
    $docType = array_search($type, CRM_Core_SelectValues::documentApplicationType());
    $dataFile = ($docType == 'docx') ? "word/document.xml" : "content.xml";

    $zip = zip_open($filePath);

    if (!$zip || is_numeric($zip)) {
      return $content;
    }

    while ($zip_entry = zip_read($zip)) {
      if (zip_entry_open($zip, $zip_entry) == FALSE || zip_entry_name($zip_entry) != $dataFile) {
        continue;
      }
      $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
      zip_entry_close($zip_entry);
    }

    zip_close($zip);

    $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
    $content = str_replace('</w:r></w:p>', "\r\n", $content);
    $striped_content = strip_tags($content);

    return nl2br($striped_content);
  }

}
