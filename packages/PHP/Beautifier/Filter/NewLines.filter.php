<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * New Lines: Add extra new lines after o before specific contents
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
 * @since      File available since Release 0.1.2
 * @version    CVS: $Id:$
 */
/**
 * New Lines: Add new lines after o before specific contents
 * The settings are 'before' and 'after'. As value, use a colon separated
 * list of contents or tokens
 *
 * Command line example:
 *
 * <code>php_beautifier --filters "NewLines(before=if:switch:T_CLASS,after=T_COMMENT:function)"</code>
 * @category   PHP
 * @package PHP_Beautifier
 * @subpackage Filter
 * @author Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2006 Claudio Bustos
 * @link     http://pear.php.net/package/PHP_Beautifier
 * @link     http://beautifyphp.sourceforge.net
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: 0.1.14
 * @since      Class available since Release 0.1.2
 */
class PHP_Beautifier_Filter_NewLines extends PHP_Beautifier_Filter
{
    protected $aSettings = array(
        'before' => false,
        'after' => false
    );
    protected $sDescription = 'Add new lines after or before specific contents';
    private $aBeforeToken = array();
    private $aBeforeContent = array();
    private $aAfterToken = array();
    private $aAfterContent = array();
    public function __construct(PHP_Beautifier $oBeaut, $aSettings = array()) 
    {
        parent::__construct($oBeaut, $aSettings);
        $this->addSettingDefinition('before', 'text', 'List of contents to put new lines before, separated by colons');
        $this->addSettingDefinition('after', 'text', 'List of contents to put new lines after, separated by colons');
        if (!empty($this->aSettings['before'])) {
            $aBefore = explode(':', str_replace(' ', '', $this->aSettings['before']));
            foreach($aBefore as $sBefore) {
                if (defined($sBefore)) {
                    $this->aBeforeToken[] = constant($sBefore);
                } else {
                    $this->aBeforeContent[] = $sBefore;
                }
            }
        }
        if (!empty($this->aSettings['after'])) {
            $aAfter = explode(':', str_replace(' ', '', $this->aSettings['after']));
            foreach($aAfter as $sAfter) {
                if (defined($sAfter)) {
                    $this->aAfterToken[] = constant($sAfter);
                } else {
                    $this->aAfterContent[] = $sAfter;
                }
            }
        }
        $this->oBeaut->setNoDeletePreviousSpaceHack();
    }
    public function __call($sMethod, $aArgs) 
    {
        $iToken = $this->aToken[0];
        $sContent = $this->aToken[1];
        if (in_array($sContent, $this->aBeforeContent) or in_array($iToken, $this->aBeforeToken)) {
            $this->oBeaut->addNewLineIndent();
        }
        if (in_array($sContent, $this->aAfterContent) or in_array($iToken, $this->aAfterToken)) {
            $this->oBeaut->setBeforeNewLine($this->oBeaut->sNewLine . '/**ndps**/');
        }
        return PHP_Beautifier_Filter::BYPASS;
    }
}
?>