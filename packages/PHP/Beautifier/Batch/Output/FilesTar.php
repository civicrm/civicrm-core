<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
* Handle the batch process for one/multiple php files to one tar compressed file
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
* Require Archive_Tar
*/
include_once 'Archive/Tar.php';
/**
* Handle the batch process for one/multiple php files to one tar compressed file
* @category   PHP
* @package PHP_Beautifier
* @author Claudio Bustos <cdx@users.sourceforge.com>
* @copyright  2004-2006 Claudio Bustos
* @link     http://pear.php.net/package/PHP_Beautifier
* @link     http://beautifyphp.sourceforge.net
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    Release: 0.1.14
*/
class PHP_Beautifier_Batch_Output_FilesTar extends PHP_Beautifier_Batch_Output {
    protected $oTar;
    protected $sCompress=false;
    protected $sExt="tar";
    public function __construct(PHP_Beautifier_Batch $oBatch) 
    {
        parent::__construct($oBatch);
        $sOutput = $this->oBatch->getOutputPath();
        $sOutput = preg_replace("/(\.tar|\.tar\.gz|\.tgz|\.gz|\.tar\.bz2)$/", '', $sOutput) .".".$this->sExt;
        PHP_Beautifier_Common::createDir($sOutput);
        $this->oTar = new Archive_Tar($sOutput, $this->sCompress);
    }
    public function get() 
    {
        throw (new Exception("TODO"));
    }
    public function save() 
    {
        $aInputFiles = $this->oBatch->getInputFiles();
        $aOutputFiles = PHP_Beautifier_Common::getSavePath($aInputFiles);
        for ($x = 0;$x<count($aInputFiles);$x++) {
            $this->beautifierSetInputFile($aInputFiles[$x]);
            $this->beautifierProcess();
            $this->oTar->addString($aOutputFiles[$x], $this->beautifierGet());
        }
    }
}
?>