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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Utils_PDF_Document.
 */
class CRM_Utils_PDF_Document {

  public static $ooxmlMap = [
    'docx' => [
      'dataFile' => 'word/document.xml',
      'startTag' => '<w:body>',
      'pageBreak' => '<w:p><w:pPr><w:pStyle w:val="Normal"/><w:rPr></w:rPr></w:pPr><w:r><w:rPr></w:rPr></w:r><w:r><w:br w:type="page"/></w:r></w:p>',
      'endTag' => '</w:body></w:document>',
    ],
    'odt' => [
      'dataFile' => 'content.xml',
      'startTag' => '<office:body>',
      'pageBreak' => '<text:p text:style-name="Standard"></text:p>',
      'endTag' => '</office:body></office:document-content>',
    ],
  ];

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
  public static function html2doc($pages, $fileName, $format = []) {
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
    $pageStyle = [
      'orientation' => CRM_Core_BAO_PdfFormat::getValue('orientation', $format),
      'pageSizeW' => self::toTwip($paperSize['width'], $paperSize['metric']),
      'pageSizeH' => self::toTwip($paperSize['height'], $paperSize['metric']),
      'marginTop' => self::toTwip(CRM_Core_BAO_PdfFormat::getValue('margin_top', $format), $metric),
      'marginRight' => self::toTwip(CRM_Core_BAO_PdfFormat::getValue('margin_right', $format), $metric),
      'marginBottom' => self::toTwip(CRM_Core_BAO_PdfFormat::getValue('margin_bottom', $format), $metric),
      'marginLeft' => self::toTwip(CRM_Core_BAO_PdfFormat::getValue('margin_left', $format), $metric),
    ];
    if (CIVICRM_UF === 'UnitTests') {
      // Streaming content will 'die' in unit tests unless ob_start()
      // has been called.
      throw new CRM_Core_Exception_PrematureExitException('_html2doc called', [
        'html' => $pages,
        'fileName' => $fileName,
        'pageStyle' => $pageStyle,
      ]);
    }

    $ext = pathinfo($fileName, PATHINFO_EXTENSION);

    $phpWord = new \PhpOffice\PhpWord\PhpWord();

    $phpWord->getDocInfo()
      ->setCreator(CRM_Core_DAO::getFieldValue('CRM_Contact_BAO_Contact', CRM_Core_Session::getLoggedInContactID(), 'display_name'));

    foreach ((array) $pages as $page => $html) {
      $section = $phpWord->addSection($pageStyle + ['breakType' => 'nextPage']);
      \PhpOffice\PhpWord\Shared\Html::addHtml($section, str_replace(['{literal}', '{/literal}'], '', $html));
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
    $formats = [
      'docx' => 'Word2007',
      'odt' => 'ODText',
      'html' => 'HTML',
      // todo
      'pdf' => 'PDF',
    ];

    if (realpath($fileName)) {
      $phpWord = \PhpOffice\PhpWord\IOFactory::load($fileName, $formats[$ext]);
    }

    //CRM-20015
    \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(TRUE);
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, $formats[$ext]);

    CRM_Utils_System::setHttpHeader('Content-Type', "application/$ext");
    CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    $objWriter->save("php://output");
  }

  /**
   * @param int $value
   * @param string $metric
   * @return float
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
   *   Return extracted content of document in HTML and document type
   */
  public static function docReader($path, $type) {
    $type = array_search($type, CRM_Core_SelectValues::documentApplicationType());
    $fileType = ($type == 'docx') ? 'Word2007' : 'ODText';

    $phpWord = \PhpOffice\PhpWord\IOFactory::load($path, $fileType);
    $phpWordHTML = new \PhpOffice\PhpWord\Writer\HTML($phpWord);

    // return the html content for tokenreplacment and eventually used for document download
    return [$phpWordHTML->getWriterPart('Body')->write(), $type];
  }

  /**
   * Extract content of docx/odt file
   *
   * @param string $filePath  Document file path
   * @param string $docType  File type of document
   *
   * @return array
   *   [string, ZipArchive]
   */
  public static function unzipDoc($filePath, $docType) {
    $dataFile = self::$ooxmlMap[$docType]['dataFile'];

    $tempFile = CRM_Utils_File::tempnam('civi_doc_');
    if (!copy($filePath, $tempFile)) {
      @unlink($tempFile);
      throw new CRM_Core_Exception(ts('Unable to read document template'));
    }

    $zip = new ZipArchive();
    if ($zip->open($tempFile) !== TRUE) {
      @unlink($tempFile);
      throw new CRM_Core_Exception(ts('Unable to open document archive'));
    }
    $content = $zip->getFromName($dataFile);
    if ($content === FALSE) {
      $zip->close();
      @unlink($tempFile);
      throw new CRM_Core_Exception(ts('Unable to read content from document archive'));
    }

    return [$content, $zip];
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
   * @param ZipArchive $zip
   *   Zip archive
   * @param bool $returnFinalContent
   *   Return the content of file document as a string used in unit test
   *
   * @return string|void
   */
  public static function printDocuments($contents, $fileName, $docType, $zip, $returnFinalContent = FALSE) {
    $dataMap = self::$ooxmlMap[$docType];
    $tempFile = $zip->filename;

    $finalContent = $zip->getFromName($dataMap['dataFile']);
    if ($finalContent === FALSE) {
      $zip->close();
      @unlink($tempFile);
      throw new CRM_Core_Exception(ts('Unable to read content from document archive'));
    }

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
      $zip->close();
      @unlink($tempFile);
      return $finalContent;
    }

    // Replace the loaded document file content with $finalContent and stream for download
    $zip->addFromString($dataMap['dataFile'], $finalContent);
    $zip->close();

    $mimeType = CRM_Core_SelectValues::documentApplicationType()[$docType] ?? 'application/octet-stream';
    CRM_Utils_System::setHttpHeader('Cache-Control', 'no-store');
    CRM_Utils_System::setHttpHeader('Content-Type', $mimeType);
    CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename="' . basename($fileName) . '"');
    CRM_Utils_System::setHttpHeader('Content-Length', filesize($tempFile));
    // Stream in 8 KB chunks to avoid buffering the whole archive in PHP memory.
    $handle = fopen($tempFile, 'rb');
    if ($handle === FALSE) {
      @unlink($tempFile);
      throw new CRM_Core_Exception(ts('Unable to read generated document archive'));
    }
    try {
      while (!feof($handle)) {
        echo fread($handle, 8192);
      }
    }
    finally {
      fclose($handle);
      @unlink($tempFile);
    }
  }

}
