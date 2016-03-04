<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Filter Array Nested: Indent the array structures
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
 * Filter Array Nested: Indent the array structures
 * Ex.
 * <CODE>
 *    $aMyArray = array(
 *        array(
 *            array(
 *                array(
 *                    'el'=>1,
 *                    'el'=>2
 *                )
 *            )
 *        )
 *    );
 * </CODE>
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
class PHP_Beautifier_Filter_ArrayNested extends PHP_Beautifier_Filter
{
    public function t_parenthesis_open($sTag) 
    {
        $this->oBeaut->add($sTag);
        if ($this->oBeaut->getControlParenthesis() == T_ARRAY) {
            $this->oBeaut->addNewLine();
            $this->oBeaut->incIndent();
            $this->oBeaut->addIndent();
        }
    }
    public function t_parenthesis_close($sTag) 
    {
        $this->oBeaut->removeWhitespace();
        if ($this->oBeaut->getControlParenthesis() == T_ARRAY) {
            $this->oBeaut->decIndent();
            if ($this->oBeaut->getPreviousTokenContent() != '(') {
                $this->oBeaut->addNewLine();
                $this->oBeaut->addIndent();
            }
            $this->oBeaut->add($sTag . ' ');
        } else {
            $this->oBeaut->add($sTag . ' ');
        }
    }
    public function t_comma($sTag) 
    {
        if ($this->oBeaut->getControlParenthesis() != T_ARRAY) {
            $this->oBeaut->add($sTag . ' ');
        } else {
            $this->oBeaut->add($sTag);
            $this->oBeaut->addNewLine();
            $this->oBeaut->addIndent();
        }
    }
}
?>