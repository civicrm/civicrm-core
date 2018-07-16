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
 * Class to print labels in Avery or custom formats
 * functionality and smarts to the base PDF_Label.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Class CRM_Utils_PDF_Label
 */
class CRM_Utils_PDF_Label extends TCPDF {

  // make these properties public due to
  // CRM-5880
  // Default label format values
  public $defaults;
  // Current label format values
  public $format;
  // Name of format
  public $formatName;
  // Left margin of labels
  public $marginLeft;
  // Top margin of labels
  public $marginTop;
  // Horizontal space between 2 labels
  public $xSpace;
  // Vertical space between 2 labels
  public $ySpace;
  // Number of labels horizontally
  public $xNumber;
  // Number of labels vertically
  public $yNumber;
  // Width of label
  public $width;
  // Height of label
  public $height;
  // Line Height of label - used in event code
  public $lineHeight = 0;
  // Space between text and left edge of label
  public $paddingLeft;
  // Space between text and top edge of label
  public $paddingTop;
  // Character size (in points)
  public $charSize;
  // Metric used for all PDF doc measurements
  public $metricDoc;
  // Name of the font
  public $fontName;
  // 'B' bold, 'I' italic, 'BI' bold+italic
  public $fontStyle;
  // Paper size name
  public $paperSize;
  // Paper orientation
  public $orientation;
  // Paper dimensions array (w, h)
  public $paper_dimensions;
  // Counter for positioning labels
  public $countX = 0;
  // Counter for positioning labels
  public $countY = 0;

  /**
   * Constructor.
   *
   * @param $format
   *   Either the name of a Label Format in the Option Value table.
   *                  or an array of Label Format values.
   * @param string|\Unit $unit Unit of measure for the PDF document
   */
  public function __construct($format, $unit = 'mm') {
    if (is_array($format)) {
      // Custom format
      $tFormat = $format;
    }
    else {
      // Saved format
      $tFormat = CRM_Core_BAO_LabelFormat::getByName($format);
    }

    $this->LabelSetFormat($tFormat, $unit);
    parent::__construct($this->orientation, $this->metricDoc, $this->paper_dimensions);
    $this->generatorMethod = NULL;
    $this->SetFont($this->fontName, $this->fontStyle);
    $this->SetFontSize($this->charSize);
    $this->SetMargins(0, 0);
    $this->SetAutoPageBreak(FALSE);
    $this->setPrintHeader(FALSE);
    $this->setPrintFooter(FALSE);
  }

  /**
   * @param $objectinstance
   * @param string $methodname
   */
  public function SetGenerator($objectinstance, $methodname = 'generateLabel') {
    $this->generatorMethod = $methodname;
    $this->generatorObject = $objectinstance;
  }

  /**
   * @param string $name
   * @param bool $convert
   *
   * @return float|int|mixed
   */
  public function getFormatValue($name, $convert = FALSE) {
    if (isset($this->format[$name])) {
      $value = $this->format[$name];
      $metric = $this->format['metric'];
    }
    else {
      $value = CRM_Utils_Array::value($name, $this->defaults);
      $metric = $this->defaults['metric'];
    }
    if ($convert) {
      $value = CRM_Utils_PDF_Utils::convertMetric($value, $metric, $this->metricDoc);
    }
    return $value;
  }

  /**
   * initialize label format settings.
   *
   * @param $format
   * @param $unit
   */
  public function LabelSetFormat(&$format, $unit) {
    $this->defaults = CRM_Core_BAO_LabelFormat::getDefaultValues();
    $this->format = &$format;
    $this->formatName = $this->getFormatValue('name');
    $this->paperSize = $this->getFormatValue('paper-size');
    $this->orientation = $this->getFormatValue('orientation');
    $this->fontName = $this->getFormatValue('font-name');
    $this->charSize = $this->getFormatValue('font-size');
    $this->fontStyle = $this->getFormatValue('font-style');
    $this->xNumber = $this->getFormatValue('NX');
    $this->yNumber = $this->getFormatValue('NY');
    $this->metricDoc = $unit;
    $this->marginLeft = $this->getFormatValue('lMargin', TRUE);
    $this->marginTop = $this->getFormatValue('tMargin', TRUE);
    $this->xSpace = $this->getFormatValue('SpaceX', TRUE);
    $this->ySpace = $this->getFormatValue('SpaceY', TRUE);
    $this->width = $this->getFormatValue('width', TRUE);
    $this->height = $this->getFormatValue('height', TRUE);
    $this->paddingLeft = $this->getFormatValue('lPadding', TRUE);
    $this->paddingTop = $this->getFormatValue('tPadding', TRUE);
    $paperSize = CRM_Core_BAO_PaperSize::getByName($this->paperSize);
    $w = CRM_Utils_PDF_Utils::convertMetric($paperSize['width'], $paperSize['metric'], $this->metricDoc);
    $h = CRM_Utils_PDF_Utils::convertMetric($paperSize['height'], $paperSize['metric'], $this->metricDoc);
    $this->paper_dimensions = array($w, $h);
  }

  /**
   * Generate the pdf of one label (can be modified using SetGenerator)
   *
   * @param string $text
   */
  public function generateLabel($text) {
    $args = array(
      'w' => $this->width,
      'h' => 0,
      'txt' => $text,
      'border' => 0,
      'align' => 'L',
      'fill' => 0,
      'ln' => 0,
      'x' => '',
      'y' => '',
      'reseth' => TRUE,
      'stretch' => 0,
      'ishtml' => FALSE,
      'autopadding' => FALSE,
      'maxh' => $this->height,
    );

    CRM_Utils_Hook::alterMailingLabelParams($args);

    if ($args['ishtml'] == TRUE) {
      $this->writeHTMLCell($args['w'], $args['h'],
        $args['x'], $args['y'],
        $args['txt'], $args['border'],
        $args['ln'], $args['fill'],
        $args['reseth'], $args['align'],
        $args['autopadding']
      );
    }
    else {
      $this->multiCell($args['w'], $args['h'],
        $args['txt'], $args['border'],
        $args['align'], $args['fill'],
        $args['ln'], $args['x'],
        $args['y'], $args['reseth'],
        $args['stretch'], $args['ishtml'],
        $args['autopadding'], $args['maxh']
      );
    }
  }

  /**
   * Print a label.
   *
   * @param $texte
   */
  public function AddPdfLabel($texte) {
    if ($this->countX == $this->xNumber) {
      // Page full, we start a new one
      $this->AddPage();
      $this->countX = 0;
      $this->countY = 0;
    }

    $posX = $this->marginLeft + ($this->countX * ($this->width + $this->xSpace));
    $posY = $this->marginTop + ($this->countY * ($this->height + $this->ySpace));
    $this->SetXY($posX + $this->paddingLeft, $posY + $this->paddingTop);
    if ($this->generatorMethod) {
      call_user_func_array(array($this->generatorObject, $this->generatorMethod), array($texte));
    }
    else {
      $this->generateLabel($texte);
    }
    $this->countY++;

    if ($this->countY == $this->yNumber) {
      // End of column reached, we start a new one
      $this->countX++;
      $this->countY = 0;
    }
  }

  /**
   * Get the available font names.
   *
   * @return array
   */
  public function getFontNames() {
    // Define labels for TCPDF core fonts
    $fontLabel = array(
      'courier' => ts('Courier'),
      'helvetica' => ts('Helvetica'),
      'times' => ts('Times New Roman'),
      'dejavusans' => ts('Deja Vu Sans (UTF-8)'),
    );

    // Check to see if we have any additional fonts to add. You can specify more fonts in
    // civicrm.settings.php via: $config['CiviCRM Preferences']['additional_fonts']
    // CRM-13307
    $additionalFonts = Civi::settings()->get('additional_fonts');
    if (is_array($additionalFonts)) {
      $fontLabel = array_merge($fontLabel, $additionalFonts);
    }

    $tcpdfFonts = $this->fontlist;
    foreach ($tcpdfFonts as $fontName) {
      if (array_key_exists($fontName, $fontLabel)) {
        $list[$fontName] = $fontLabel[$fontName];
      }
    }

    return $list;
  }

}
