<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

require_once 'TbsZip/tbszip.php';

/**
 * Class CRM_Utils_PDF_Document.
 */
class CRM_Utils_PDF_Document {

  public static $ooxmlMap = array(
    'docx' => array(
      'dataFile' => 'word/document.xml',
      'startTag' => '<w:body>',
      'pageBreak' => '<w:p><w:pPr><w:pStyle w:val="Normal"/><w:rPr></w:rPr></w:pPr><w:r><w:rPr></w:rPr></w:r><w:r><w:br w:type="page"/></w:r></w:p>',
      'endTag' => '</w:body></w:document>',
    ),
    'odt' => array(
      'dataFile' => 'content.xml',
      'startTag' => '<office:body>',
      'pageBreak' => '<text:p text:style-name="Standard"></text:p>',
      'endTag' => '</office:body></office:document-content>',
    ),
  );

  /**
   * Convert html to a Doc file.
   *
   * @param array $pages
   *   List of HTML snippets.
   * @param string $fileName
   *   The logical filename to return to client.
   *   Ex: "HelloWorld.odt".
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
   *   File extension/type.
   *   Ex: docx, odt, html.
   * @param string $fileName
   *   The logical filename to return to client.
   *   Ex: "HelloWorld.odt".
   *   Alternatively, a full path of a file to display. This seems sketchy.
   *   Ex: "/var/lib/data/HelloWorld.odt".
   */
  public static function printDoc($phpWord, $ext, $fileName) {
    $formats = array(
      'docx' => 'Word2007',
      'odt' => 'ODText',
      'html' => 'HTML',
      // todo
      'pdf' => 'PDF',
    );

    if (realpath($fileName)) {
      $phpWord = \PhpOffice\PhpWord\IOFactory::load($fileName, $formats[$ext]);
    }

    \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(TRUE); //CRM-20015
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
   *
   * @return array
   *    Return extracted content of document in HTML and document type
   */
  public static function docReader($path, $type) {
    $type = array_search($type, CRM_Core_SelectValues::documentApplicationType());
    $fileType = ($type == 'docx') ? 'Word2007' : 'ODText';

    $phpWord = \PhpOffice\PhpWord\IOFactory::load($path, $fileType);
    $phpWordHTML = new \PhpOffice\PhpWord\Writer\HTML($phpWord);

    // return the html content for tokenreplacment and eventually used for document download
    return array($phpWordHTML->getWriterPart('Body')->write(), $type);
  }

  /**
   * Extract content of docx/odt file
   *
   * @param string $filePath  Document file path
   * @param string $docType  File type of document
   *
   * @return array
   *   [string, clsTbsZip]
   */
  public static function unzipDoc($filePath, $docType) {
    $dataFile = self::$ooxmlMap[$docType]['dataFile'];

    $zip = new clsTbsZip();
    $zip->Open($filePath);
    $content = $zip->FileRead($dataFile);

    return array($content, $zip);
  }

  /**
   * Modify contents of docx/odt file(s) and later merged into one final document
   *
   * @param array $contents
   *   Content of formatted/token-replaced document.
   *   List of HTML snippets.
   * @param string $fileName
   *   The logical filename to return to client.
   *   Ex: "HelloWorld.odt".
   * @param string $docType
   *   Document type e.g. odt/docx
   * @param clsTbsZip $zip
   *   Zip archive
   * @param bool $returnFinalContent
   *   Return the content of file document as a string used in unit test
   *
   * @return string
   */
  public static function printDocuments($contents, $fileName, $docType, $zip, $returnFinalContent = FALSE) {
    $dataMap = self::$ooxmlMap[$docType];

    $finalContent = $zip->FileRead($dataMap['dataFile']);

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

    if ($returnFinalContent) {
      return $finalContent;
    }

    // Replace the loaded document file content located at $filePath with $finaContent
    $zip->FileReplace($dataMap['dataFile'], $finalContent, TBSZIP_STRING);

    $zip->Flush(TBSZIP_DOWNLOAD, $fileName);
  }

}
