<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Abstract class to superclass all batch class
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
 * @subpackage Batch
 * @author Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2006 Claudio Bustos
 * @link     http://pear.php.net/package/PHP_Beautifier
 * @link     http://beautifyphp.sourceforge.net
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id:$
 */
/**
 * Abstract class to superclass all batch class
 *
 * @category   PHP
 * @package PHP_Beautifier
 * @author Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2006 Claudio Bustos
 * @link     http://pear.php.net/package/PHP_Beautifier
 * @link     http://beautifyphp.sourceforge.net
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: 0.1.14
 */
abstract class PHP_Beautifier_Batch_Output
{
    protected $oBatch;
    public function __construct(PHP_Beautifier_Batch $oBatch)
    {
        $this->oBatch = $oBatch;
    }
    protected function beautifierSetInputFile($sFile)
    {
        return $this->oBatch->callBeautifier($this, 'setInputFile', array(
            $sFile
        ));
    }
    protected function beautifierProcess()
    {
        return $this->oBatch->callBeautifier($this, 'process');
    }
    protected function beautifierGet()
    {
        return $this->oBatch->callBeautifier($this, 'get');
    }
    protected function beautifierSave($sFile)
    {
        return $this->oBatch->callBeautifier($this, 'save', array(
            $sFile
        ));
    }
    public function get()
    {
    }
    public function save()
    {
    }
}
?>