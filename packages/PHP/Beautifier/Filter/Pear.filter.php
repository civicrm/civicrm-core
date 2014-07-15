<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Filter the code to make it compatible with PEAR Coding Standards
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
 * Require PEAR_Config
 */
require_once ('PEAR/Config.php');
/**
 * Filter the code to make it compatible with PEAR Coding Standards
 *
 * The default filter, {@link PHP_Beautifier_Filter_Default} have most of the specs
 * but adhere more to GNU C.
 * So, this filter make the following modifications:
 * - Add 2 newlines after Break in switch statements. Break indent is the same of previous line
 * - Brace in function definition put on a new line, same indent of 'function' construct
 * - Comments started with '#' are replaced with '//'
 * - Open tags are replaced with '<?php'
 * - T_OPEN_TAG_WITH_ECHO replaced with <?php echo
 * - With setting 'add_header', the filter add one of the standard PEAR comment header
 *   (php, bsd, apache, lgpl, pear) or any file as licence header. Use:
 * <code>
 * $oBeaut->addFilter('Pear',array('add_header'=>'php'));
 * </code>
 * Two extra options allows to break the spec about newline before braces
 * on function and classes. By default, they are set to true. Use 
 * <code>
 * $oBeaut->addFilter('Pear',array('newline_class'=>false, 'newline_function'=>false));
 * </code> 
 * @category   PHP
 * @package PHP_Beautifier
 * @subpackage Filter
 * @author Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2006 Claudio Bustos
 * @link     http://pear.php.net/package/PHP_Beautifier
 * @link     http://beautifyphp.sourceforge.net
 * @link http://pear.php.net/manual/en/standards.php
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id:$
 */
class PHP_Beautifier_Filter_Pear extends PHP_Beautifier_Filter
{
    protected $aSettings = array('add_header' => false, 'newline_class' => true, 'newline_function' => true, 'switch_without_indent'=> true);
    protected $sDescription = 'Filter the code to make it compatible with PEAR Coding Specs';
    private $bOpenTag = false;
    function t_open_tag_with_echo($sTag) 
    {
        $this->oBeaut->add("<?php echo ");
    }
    function t_close_brace($sTag) 
    {
        if($this->oBeaut->getControlSeq() == T_SWITCH and $this->getSetting('switch_without_indent')) {
            $this->oBeaut->removeWhitespace();
            $this->oBeaut->decIndent();
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->add($sTag);
            $this->oBeaut->addNewLineIndent();            
        } else {
            return PHP_Beautifier_Filter::BYPASS;
        }
    }
    function t_semi_colon($sTag) 
    {
        // A break statement and the next statement are separated by an empty line
        if ($this->oBeaut->isPreviousTokenConstant(T_BREAK)) {
            $this->oBeaut->removeWhitespace();
            $this->oBeaut->add($sTag); // add the semicolon
            $this->oBeaut->addNewLine(); // empty line
            $this->oBeaut->addNewLineIndent();
        } elseif ($this->oBeaut->getControlParenthesis() == T_FOR) {
            // The three terms in the head of a for loop are separated by the string "; "
            $this->oBeaut->removeWhitespace();
            $this->oBeaut->add($sTag . " "); // Bug 8327
            
        } else {
            return PHP_Beautifier_Filter::BYPASS;
        }
    }
    function t_case($sTag) 
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->decIndent();
        if ($this->oBeaut->isPreviousTokenConstant(T_BREAK, 2)) {
            $this->oBeaut->addNewLine();
        }
        $this->oBeaut->addNewLineIndent();
        $this->oBeaut->add($sTag . ' ');
        //$this->oBeaut->incIndent();
        
    }
    function t_default($sTag) 
    {
        $this->t_case($sTag);
    }
    function t_break($sTag) 
    {
        $this->oBeaut->add($sTag);
        if ($this->oBeaut->isNextTokenConstant(T_LNUMBER)) {
            $this->oBeaut->add(" ");
        }        
    }
    function t_open_brace($sTag) 
    {
        if ($this->oBeaut->openBraceDontProcess()) {
            $this->oBeaut->add($sTag);
        } elseif ($this->oBeaut->getControlSeq() == T_SWITCH and $this->getSetting('switch_without_indent')) {
            $this->oBeaut->add($sTag);
            $this->oBeaut->incIndent();    
        } else {
            $bypass = true;
            if ($this->oBeaut->getControlSeq() == T_CLASS and $this->getSetting('newline_class')) {
                $bypass = false;
            }
            if ($this->oBeaut->getControlSeq() == T_FUNCTION and $this->getSetting('newline_function')) {
                $bypass = false;
            }
            if ($bypass) {
                return PHP_Beautifier_Filter::BYPASS;
            }
            $this->oBeaut->removeWhitespace();
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->add($sTag);
            $this->oBeaut->incIndent();
            $this->oBeaut->addNewLineIndent();
        }
    }
    function t_comment($sTag) 
    {
        if ($sTag{0} != '#') {
            return PHP_Beautifier_Filter::BYPASS;
        }
        $oFilterDefault = new PHP_Beautifier_Filter_Default($this->oBeaut);
        $sTag = '//' . substr($sTag, 1);
        return $oFilterDefault->t_comment($sTag);
    }
    function t_open_tag($sTag) 
    {
        // find PEAR header comment
        $this->oBeaut->add("<?php");
        $this->oBeaut->addNewLineIndent();
        if (!$this->bOpenTag) {
            $this->bOpenTag = true;
            // store the comment and search for word 'license'
            $sComment = '';
            $x = 1;
            while ($this->oBeaut->isNextTokenConstant(T_COMMENT, $x)) {
                $sComment.= $this->oBeaut->getNextTokenContent($x);
                $x++;
            }
            if (stripos($sComment, 'license') === FALSE) {
                $this->addHeaderComment();
            }
        }
    }
    function preProcess() 
    {
        $this->bOpenTag = false;
    }
    function addHeaderComment() 
    {
        if (!($sLicense = $this->getSetting('add_header'))) {
            return;
        }
        // if Header is a path, try to load the file
        if (file_exists($sLicense)) {
            $sDataPath = $sLicense;
        } else {
            $oConfig = PEAR_Config::singleton();
            $sDataPath = PHP_Beautifier_Common::normalizeDir($oConfig->get('data_dir')) . 'PHP_Beautifier/licenses/' . $sLicense . '.txt';
        }
        if (file_exists($sDataPath)) {
            $sLicenseText = file_get_contents($sDataPath);
        } else {
            throw (new Exception("Can't load license '" . $sLicense . "'"));
        }
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->addNewLine();
        $this->oBeaut->add($sLicenseText);
        $this->oBeaut->addNewLineIndent();
    }
}
?>
