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

require_once 'TbsZip/tbszip.php';

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

    self::printDoc($phpWord, $ext, $fileName);
  }

  /**
   * @param object|string $phpWord
   * @param string $ext
   * @param string $fileName
   */
  public static function printDoc($phpWord, $ext, $fileName) {
    $formats = array(
      'docx' => 'Word2007',
      'odt' => 'ODText',
      'html' => 'HTML',
      // todo
      'pdf' => 'PDF',
    );

    if (realpath($phpWord)) {
      $phpWord = \PhpOffice\PhpWord\IOFactory::load($phpWord, $formats[$ext]);
    }

    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, $formats[$ext]);

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

    $fileType = ($type == 'docx') ? 'Word2007' : 'ODText';
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($path, $fileType);
    $phpWord->save($absPath, 'HTML');

    // return the html content for tokenreplacment and eventually used for document download
    if ($returnContent) {
      $filename = fopen($absPath, 'r');
      $content = fread($filename, filesize($absPath));
      fclose($filename);
      return array($content, array_search($type, CRM_Core_SelectValues::documentApplicationType()));
    }

    return \Civi::paths()->getUrl($uploadDir) . $newFile;
  }

  /**
   * Extract content of docx/odt file as text and later used for token replacement
   * @param string $filePath  Document file path
   * @param string $docType  File type of document
   * @param bool $returnZipObj  Return clsTbsZip object along with content?
   *
   * @return string|array
   *   File content of document as text or array of content and clsTbsZip object
   */
  public static function doc2Text($filePath, $docType, $returnZipObj = FALSE) {
    $dataFile = ($docType == 'docx') ? 'word/document.xml' : 'content.xml';

    $zip = new clsTbsZip();
    $zip->Open($filePath);
    $content = $zip->FileRead($dataFile);

    if ($returnZipObj) {
      return array($content, $zip);
    }

    return $content;
  }

  /**
   * Modify contents of docx/odt file(s) and later merged into one final document
   *
   * @param string $filePath Document file path
   * @param array $contents content of formatted/token-replaced document
   * @param string $docType Document type e.g. odt/docx
   */
  public static function printDocuments($filePath, $contents, $docType) {
    $ooxmlMap = array(
      'docx' => array(
        'dataFile' => 'word/document.xml',
        'startTag' => '<w:body>',
        // TODO need to provide proper ooxml tag for pagebreak
        'pageBreak' => '<w:pgMar></w:pgMar>',
        'endTag' => '</w:body></w:document>',
      ),
      'odt' => array(
        'dataFile' => 'content.xml',
        'startTag' => '<office:body>',
        'pageBreak' => '<text:p text:style-name="Standard"></text:p>',
        'endTag' => '</office:body></office:document-content>',
      ),
    );

    $dataMap = $ooxmlMap[$docType];
    list($finalContent, $zip) = self::doc2Text($filePath, $docType, TRUE);

    // token-replaced document contents of each contact will be merged into final document
    foreach ($contents as $key => $content) {
      if ($key == 0) {
        $finalContent = $content;
        continue;
      }

      // 1. fetch the start position of document body
      // 2. later fetch only the body part starting from position $start
      // 3. replace closing body tag with pageBreak
      // 4. append the $content to the finalContent
      $start = strpos($content, $dataMap['startTag']);
      $content = substr($content, $start);
      $content = str_replace($dataMap['startTag'], $dataMap['pageBreak'], $content);
      $finalContent = str_replace($dataMap['endTag'], $content, $finalContent);
    }

    //replace the loaded document file content located at $filePath with $finaContent
    $zip->FileReplace($dataMap['dataFile'], $finalContent, TBSZIP_STRING);

    // get and path of civicrm upload directory and then construct the filepath of final document
    $uploadDir = Civi::settings()->get('uploadDir');
    $absPath = Civi::paths()->getPath($uploadDir) . "CiviLetter.$docType";

    // cleanup temporary document file created earlier if any
    if (file_exists($absPath)) {
      unlink($absPath);
    }
    // save the file document in civicrm upload directory, later used to download
    $zip->Flush(TBSZIP_FILE, $absPath);

    self::printDoc($absPath, $docType, "CiviLetter.$docType");
  }

}
