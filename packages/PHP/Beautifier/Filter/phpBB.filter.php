<?php
/**
 * Filter the code to make it compatible with phpBB Coding Standards
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
 * @author Jim Wigginton <terrafrost@php.net>
 * @copyright  2008 Jim Wigginton
 * @link     http://pear.php.net/package/PHP_Beautifier
 * @license    http://www.gnu.org/licenses/lgpl.html  LGPL
 * @version    CVS: $Id:$
 */
/**
 * Require PEAR_Config
 */
require_once ('PEAR/Config.php');
/**
 * Filter the code to make it compatible with phpBB Coding Standards
 *
 * Among other differences from the PEAR Coding Standards, the phpBB coding standards use BSD style indenting.
 *
 * @category   PHP
 * @package PHP_Beautifier
 * @subpackage Filter
 * @author Jim Wigginton <terrafrost@php.net>
 * @copyright  2008 Jim Wigginton
 * @link     http://pear.php.net/package/PHP_Beautifier
 * @license    http://www.gnu.org/licenses/lgpl.html  LGPL
 * @version    Release: 0.0.1
 */
class PHP_Beautifier_Filter_phpBB extends PHP_Beautifier_Filter
{
    protected $sDescription = 'Filter the code to make it compatible with phpBB Coding Standards';
    private $iNestedIfs = 0;
    public function __construct(PHP_Beautifier $oBeaut, $aSettings = array())
    {
        parent::__construct($oBeaut, $aSettings);
        $oBeaut->setIndentChar("\t");
        $oBeaut->setIndentNumber(1);
        $oBeaut->setNewLine(PHP_EOL);
    }
    function t_open_brace($sTag)
    {
        $this->oBeaut->addNewLineIndent();
        $this->oBeaut->add($sTag);
        $this->oBeaut->incIndent();
        $this->oBeaut->addNewLineIndent();
    }
    function t_close_brace($sTag)
    {
        if ($this->oBeaut->getMode('string_index') or $this->oBeaut->getMode('double_quote')) {
            $this->oBeaut->add($sTag);
        } else {
            $this->oBeaut->removeWhitespace();
            $this->oBeaut->decIndent();
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->add($sTag);
            $this->oBeaut->addNewLineIndent();
        }
    }
    function t_else($sTag)
    {
        $this->oBeaut->add($sTag);
        if (!$this->oBeaut->isNextTokenContent('{')) {
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->add('{');
            $this->oBeaut->incIndent();
            $this->oBeaut->addNewLineIndent();
            $this->iNestedIfs++;
        }
    }
    function t_semi_colon($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add($sTag);
        if ($this->oBeaut->getControlParenthesis() != T_FOR) {
            if ($this->iNestedIfs > 0) {
                $this->oBeaut->decIndent();
                $this->oBeaut->addNewLineIndent();
                $this->oBeaut->add('}');
                $this->iNestedIfs--;
            }
            $this->oBeaut->addNewLineIndent();
        }
    }
    function preProcess()
    {
        $this->iNestedIfs = 0;
    }
}
?>