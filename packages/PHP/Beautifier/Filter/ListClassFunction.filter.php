<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Create a list of functions and classes in the script
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 * @category   PHP
 * @package PHP_Beautifier
 * @subpackage Filter
 * @author Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2006 Claudio Bustos
 * @link     http://pear.php.net/package/PHP_Beautifier
 * @link     http://beautifyphp.sourceforge.net
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id:$
 */
/**
 * Create a list of functions and classes in the script
 * By default, this Filter puts the list at the beggining of the script.
 * If you want it in another position, put a comment like that
 * <pre>
 * // Class and Function List
 * </pre>
 * The script lookup for the string 'Class and Function List' in a comment and replace the entire comment with the list
 * The settings are
 * - list_functions: List functions (0 or 1). Default:1
 * - list_classes:   List classes (0 or 1).   Default:1
 * @todo List functions inside classes as methods
 * @category   PHP
 * @package PHP_Beautifier
 * @subpackage Filter
 * @author Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2006 Claudio Bustos
 * @link     http://pear.php.net/package/PHP_Beautifier
 * @link     http://beautifyphp.sourceforge.net
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: 0.1.14
 */
class PHP_Beautifier_Filter_ListClassFunction extends PHP_Beautifier_Filter
{
    protected $aFilterTokenFunctions = array(
        T_CLASS => 't_class',
        T_FUNCTION => 't_function',
        T_COMMENT => 't_comment',
        T_OPEN_TAG => 't_open_tag'
    );
    private $aFunctions = array();
    private $aClasses = array();
    private $iComment;
    private $iOpenTag = null;
    protected $aSettings = array(
        'list_functions' => true,
        'list_classes' => true
    );
    protected $sDescription = 'Create a list of functions and classes in the script';
    private $aInclude = array(
        'functions' => true,
        'classes' => true
    );
    public function __construct(PHP_Beautifier $oBeaut, $aSettings = array()) 
    {
        parent::__construct($oBeaut, $aSettings);
        $this->addSettingDefinition('list_functions', 'bool', 'List Functions inside the file');
        $this->addSettingDefinition('list_classes', 'bool', 'List Classes inside the file');
    }
    function t_function($sTag) 
    {
        if ($this->aInclude['functions']) {
            $sNext = $this->oBeaut->getNextTokenContent(1);
            if ($sNext == '&') {
                $sNext.= $this->oBeaut->getNextTokenContent(2);
            }
            array_push($this->aFunctions, $sNext);
        }
        return PHP_Beautifier_Filter::BYPASS;
    }
    function includeInList($sTag, $sValue) 
    {
        $this->aInclude[$sTag] = $sValue;
    }
    function t_class($sTag) 
    {
        if ($this->aInclude['classes']) {
            $sClassName = $this->oBeaut->getNextTokenContent(1);
            if ($this->oBeaut->isNextTokenConstant(T_EXTENDS, 2)) {
                $sClassName.= ' extends ' . $this->oBeaut->getNextTokenContent(3);
            }
            array_push($this->aClasses, $sClassName);
        }
        return PHP_Beautifier_Filter::BYPASS;
    }
    function t_doc_comment($sTag) 
    {
        if (strpos($sTag, 'Class and Function List') !== FALSE) {
            $this->iComment = $this->oBeaut->iCount;
        }
        return PHP_Beautifier_Filter::BYPASS;
    }
    function t_open_tag($sTag) 
    {
        if (is_null($this->iOpenTag)) {
            $this->iOpenTag = $this->oBeaut->iCount;
        }
        return PHP_Beautifier_Filter::BYPASS;
    }
    function postProcess() 
    {
        $sNL = $this->oBeaut->sNewLine;
        $aOut = array(
            "/**",
            "* Class and Function List:"
        );
        if ($this->getSetting('list_functions')) {
            $aOut[] = "* Function list:";
            foreach($this->aFunctions as $sFunction) {
                $aOut[] = "* - " . $sFunction . "()";
            }
        }
        if ($this->getSetting('list_classes')) {
            $aOut[] = "* Classes list:";
            foreach($this->aClasses as $sClass) {
                $aOut[] = "* - " . $sClass;
            }
        }
        $aOut[] = "*/";
        if ($this->iComment) {
            // Determine the previous Indent
            $sComment = $this->oBeaut->getTokenAssocText($this->iComment);
            if (preg_match("/" . addcslashes($sNL, "\r\n") . "([ \t]+)/ms", $sComment, $aMatch)) {
                $sPrevio = $sNL . $aMatch[1];
            } else {
                $sPrevio = $sNL;
            }
            $sText = implode($sPrevio, $aOut) . $sNL;
            $this->oBeaut->replaceTokenAssoc($this->iComment, $sText);
        } else {
            $sPrevio = $sNL /*.str_repeat($this->oBeaut->sIndentChar, $this->oBeaut->iIndentNumber)*/;
            $sTag = trim($this->oBeaut->getTokenAssocText($this->iOpenTag)) . "\n";
            $sText = $sPrevio . implode($sPrevio, $aOut);
            $this->oBeaut->replaceTokenAssoc($this->iOpenTag, rtrim($sTag) . $sText . $sPrevio);
        }
    }
}
?>