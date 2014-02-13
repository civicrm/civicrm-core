<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
* PHP_Beautifier_Batch_Files
* Handle the batch process for one/multiple php files to one out
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
* PHP_Beautifier_Batch_Files
* Handle the batch process for one/multiple php files to one out
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
class PHP_Beautifier_Batch_Output_Files extends PHP_Beautifier_Batch_Output {
    public function get() 
    {
        $aInputFiles = $this->oBatch->getInputFiles();
        if (count($aInputFiles) == 1) {
            $this->beautifierSetInputFile(reset($aInputFiles));
            $this->beautifierProcess();
            return $this->beautifierGet();
        } else {
            $sText = '';
            foreach($aInputFiles as $sFile) {
                $this->beautifierSetInputFile($sFile);
                $this->beautifierProcess();
                $sText.= $this->getWithHeader($sFile);
            }
            return $sText;
        }
    }
    private function getWithHeader($sFile) 
    {
        $sNewLine = $this->oBatch->callBeautifier($this, 'getNewLine');
        $sHeader = '- BEGIN OF '.$sFile.' -'.$sNewLine;
        $sLine = str_repeat('-', strlen($sHeader) -1) .$sNewLine;
        $sEnd = '- END OF '.$sFile.str_repeat(' ', strlen($sHeader) -strlen($sFile) -12) .' -'.$sNewLine;
        $sText = $sLine.$sHeader.$sLine.$sNewLine;
        $sText.= $this->beautifierGet();
        $sText.= $sNewLine.$sLine.$sEnd.$sLine.$sNewLine;
        return $sText;
    }
    public function save() 
    {
        $bCli = php_sapi_name() == 'cli';
        $sFile = $this->oBatch->getOutputPath();
        if ($bCli and $sFile == STDOUT) {
            $fp = STDOUT;
        } else {
            $fp = fopen($this->oBatch->getOutputPath() , "w");
        }
        if (!$fp) {
            throw (new Exception("Can't save file $sFile"));
        }
        $sText = $this->get();
        fputs($fp, $sText, strlen($sText));
        if (!($bCli and $fp == STDOUT)) {
            fclose($fp);
        }
        return true;
    }
}
?>