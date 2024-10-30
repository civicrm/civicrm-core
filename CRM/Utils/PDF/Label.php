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
 * Class to print labels in Avery or custom formats
 * functionality and smarts to the base PDF_Label.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Utils_PDF_Label
 */
class CRM_Utils_PDF_Label extends TCPDF {

  // make these properties public due to
  // CRM-5880
  /**
   * Default label format values
   * @var array
   */
  public $defaults;
  /**
   * Current label format values
   * @var array
   */
  public $format;
  /**
   * Name of format
   * @var string
   */
  public $formatName;
  /**
   * Left margin of labels
   * @var float
   */
  public $marginLeft;
  /**
   * Top margin of labels
   * @var float
   */
  public $marginTop;
  /**
   * Horizontal space between 2 labels
   * @var float
   */
  public $xSpace;
  /**
   * Vertical space between 2 labels
   * @var float
   */
  public $ySpace;
  /**
   * Number of labels horizontally
   * @var float
   */
  public $xNumber;
  /**
   * Number of labels vertically
   * @var float
   */
  public $yNumber;
  /**
   * Width of label
   * @var float
   */
  public $width;
  /**
   * Height of label
   * @var float
   */
  public $height;
  /**
   * Line Height of label - used in event code
   * @var float
   */
  public $lineHeight = 0;
  /**
   * Space between text and left edge of label
   * @var float
   */
  public $paddingLeft;
  /**
   * Space between text and top edge of label
   * @var float
   */
  public $paddingTop;
  /**
   * Character size (in points)
   * @var float
   */
  public $charSize;
  /**
   * Metric used for all PDF doc measurements
   * @var string
   */
  public $metricDoc;
  /**
   * Name of the font
   * @var string
   */
  public $fontName;
  /**
   * 'B' bold, 'I' italic, 'BI' bold+italic
   * @var string
   */
  public $fontStyle;
  /**
   * Paper size name
   * @var string
   */
  public $paperSize;
  /**
   * Paper orientation
   * @var string
   */
  public $orientation;
  /**
   * Paper dimensions array (w, h)
   * @var array
   */
  public $paper_dimensions;
  /**
   * Counter for positioning labels
   * @var float
   */
  public $countX = 0;
  /**
   * Counter for positioning labels
   * @var float
   */
  public $countY = 0;

  /**
   * Custom method for generating label, called against $this->generatorObject
   *
   * @var string|null
   */
  protected $generatorMethod = NULL;

  /**
   * Custom object used for generating label, used alongside $this->generatorMethod
   *
   * @var object
   */
  protected $generatorObject;

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
      $value = $this->defaults[$name] ?? NULL;
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
    $this->paper_dimensions = [$w, $h];
  }

  /**
   * Generate the pdf of one label (can be modified using SetGenerator)
   *
   * @param string $text
   */
  public function generateLabel($text) {
    // paddingLeft is used for both left & right padding so needs to be
    // subtracted twice from width to get the width that is available for text
    $args = [
      'w' => $this->width - 2 * $this->paddingLeft,
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
    ];

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
      call_user_func_array([$this->generatorObject, $this->generatorMethod], [$texte]);
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
    $fontLabel = [
      'courier' => ts('Courier'),
      'helvetica' => ts('Helvetica'),
      'times' => ts('Times New Roman'),
      'dejavusans' => ts('Deja Vu Sans (UTF-8)'),
    ];

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
