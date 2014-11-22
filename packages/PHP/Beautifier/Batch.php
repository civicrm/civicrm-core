<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
* Definition of  PHP_Beautifier_Batch
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
* Require PHP_Beautifier_Decorator
*/
require_once 'Decorator.php';
/**
* Require PHP_Beautifier_Batch_Output
*/
require_once 'Batch/Output.php';
// ArrayNested->off();
// ArrayNested->on();

/**
* Adds functionality to handle multiple files.
* - STDIN  : As normal
* - STDOUT : Send all the scripts, prepended with the name of the original route
* - One in, one out: as normal
* - Multiple In, one out: determine the type of out.
*   - Without '/' at the end, same as STDOUT.
*   - With '/' at the end, copy the base structure and copy all the scripts
*
* You must define an input file. By default, the output is "./", so the saving
* of files will be done on the directory of your command prompt.
*
* If the file out end in .tgz, the output will be a tar archive. The same action
* will be obtained with {@link setCompress()} to true
* Use:
* <code>
* require "PHP/Beautifier.php";
* require "PHP/Beautifier/Batch.php";
* $oBeaut= new PHP_Beautifier();
* $oBatch= new PHP_Beautifier_Batch($oBeaut); // Decorator
* $oBatch->setInputFile(__FILE__);
* $oBatch->setOutputFile(dirname(__FILE__)."/beautified/");
* $oBatch->process();
* $oBatch->save();
* </code>
*
* @category     PHP
* @package      PHP_Beautifier
* @subpackage   Batch
* @author       Claudio Bustos <cdx@users.sourceforge.com>
* @copyright    2004-2006 Claudio Bustos
* @link         http://pear.php.net/package/PHP_Beautifier
* @link         http://beautifyphp.sourceforge.net
* @license      http://www.php.net/license/3_0.txt  PHP License 3.0
* @version      Release: 0.1.14
*/
class PHP_Beautifier_Batch extends PHP_Beautifier_Decorator {
    /**
    * Compression method (for now, false, 'gz' and 'bz2')
    * @var string
    */
    private $sCompress = false;
    /**
    * Array or STDIN of paths to parse
    * @var array
    */
    private $mPreInputFiles = array();
    /**
    * Path to the output
    */
    private $sPreOutputFile = './';
    /**
    * @var PHP_Beautifier_Batch_Output
    */
    private $oBatchOutput;
    /**
    * @var array    PHP_Beautifier_Batch_Input
    */
    private $aBatchInputs;
    public $mInputFiles;
    /**
    * Output mode.  Could be {@link PHP_Beautifier_Batch::FILES} or
    *               {@link PHP_Beautifier_Batch::DIRECTORY}
    */
    private $sOutputMode;
    const FILES = 'Files';
    const DIRECTORY = 'Directory';
    /**
    * Recursive search on dirs
    * @var bool
    */
    public $bRecursive = false;
    // public methods, overloaded from PHP_Beautifier
    
    /**
    * Set recursive search for files in dirs on
    * @param bool
    */
    public function setRecursive($bRecursive = true) 
    {
        $this->bRecursive = $bRecursive;
    }
    /**
    * Set compression on/off
    * @param mixed bool(false, true for gzip) or string ('gz' or 'gz2')
    */
    public function setCompress($mCompress = true) 
    {
        if ($mCompress === true) {
            $mCompress = 'gz';
        } elseif (!$mCompress) {
            $mCompress = false;
        } elseif (!is_string($mCompress)) {
            throw (new Exception('You have to define a mode for compress'));
        }
        $this->sCompress = $mCompress;
    }
    /**
    * Set the input(s) files
    * Could be STDIN or a name, with special chars (?,*)
    * @param mixed STDIN or string(path)
    * @return bool
    */
    public function setInputFile($mFiles) 
    {
        $bCli = (php_sapi_name() == 'cli');
        if ($bCli and $this->mPreInputFiles == STDIN and $mFiles != STDIN) {
            throw (new Exception("Hey, you already defined STDIN,dude"));
        } elseif ($bCli and $mFiles == STDIN) {
            $this->mPreInputFiles = STDIN;
        } else {
            // ArrayNested->off()
            if (is_string($mFiles)) {
                $mFiles = array($mFiles);
            }
            // ArrayNested->on()
            $this->mPreInputFiles = array_merge($this->mPreInputFiles, $mFiles);
        }
        return true;
    }
    /**
    * Set the output file
    * Could be STDOUT or a path to a file or dir (with '/' at the end)
    * @param mixed STDOUT or string (path)
    * @return true
    */
    public function setOutputFile($sFile) 
    {
        if (!is_string($sFile) and !(php_sapi_name() == 'cli' and $sFile == STDOUT)) {
            throw (new Exception("Accept only string or STDOUT"));
        }
        $this->sPreOutputFile = $sFile;
        return true;
    }
    private function setInputFilePost() 
    {
        $bCli = php_sapi_name() == 'cli';
        // ArrayNested->off()
        if ($bCli and $this->mPreInputFiles == STDIN) {
            $mInputFiles = array(STDIN);
        } else {
            $mInputFiles = array();
            foreach($this->mPreInputFiles as $sPath) {
                $mInputFiles = array_merge($mInputFiles, PHP_Beautifier_Common::getFilesByGlob($sPath, $this->bRecursive));
            }
        }
        // now, we create stream references for compressed files....
        foreach($mInputFiles as $sFile) {
            // First, tar files
            if (!($bCli and $sFile == STDIN) and preg_match("/(.tgz|\.tar\.gz|\.tar\.bz2|\.tar)$/", $sFile, $aMatch)) {
                if (strpos($aMatch[1], 'gz') !== FALSE) {
                    $sCompress = 'gz';
                } elseif (strpos($aMatch[1], 'bz2') !== FALSE) {
                    $sCompress = 'bz2';
                } elseif (strpos($aMatch[1], 'tar') !== FALSE) {
                    $sCompress = false;
                }
                $oTar = new Archive_Tar($sFile, $sCompress);
                foreach($oTar->listContent() as $aInput) {
                    if (empty($aInput['typeflag'])) {
                        $this->mInputFiles[] = 'tarz://'.$sFile.'#'.$aInput['filename'];
                    }
                }
            } else {
                $this->mInputFiles[] = $sFile;
            }
        }
        if (!$this->mInputFiles) {
            throw (new Exception("Can't match any file"));
        }
        return true;
        // ArrayNested->on()
        
    }
    private function setOutputFilePost() 
    {
        if (php_sapi_name() == 'cli' and $this->sPreOutputFile == STDOUT) {
            $this->sOutputMode = PHP_Beautifier_Batch::FILES;
        } else {
            $sPath = str_replace(DIRECTORY_SEPARATOR, '/', $this->sPreOutputFile);
            if (!$sPath) {
                $sPath = "./";
            }
            // determine file or dir
            if (substr($sPath, -1) != '/' and !is_dir($sPath)) {
                $this->sOutputMode = PHP_Beautifier_Batch::FILES;
                // Define compression mode
                if (preg_match("/\.(gz|bz2|tar)$/", $sPath, $aMatch)) {
                    $this->sCompress = $aMatch[1];
                }
            } else {
                $this->sOutputMode = PHP_Beautifier_Batch::DIRECTORY;
            }
        }
        return true;
    }
    /**
    * Create the real references to files
    * @return bool
    * @throws Exception
    */
    public function process() 
    {
        if (!$this->mPreInputFiles) {
            throw (new Exception('Input file not defined'));
        } else {
            $this->setInputFilePost();
            $this->setOutputFilePost();
        }
        if (!$this->mInputFiles) {
            throw (new Exception(implode(',', $this->mPreInputFiles) ." doesn't match any files"));
        } else {
            return true;
        }
    }
    private function getBatchEngine() 
    {
        $sCompress = ($this->sCompress) ? ucfirst($this->sCompress) : '';
        $sClass = $this->sOutputMode.$sCompress;
        $sClassEngine = 'PHP_Beautifier_Batch_Output_'.$sClass;
        $sClassFile = PHP_Beautifier_Common::normalizeDir(dirname(__FILE__)) .'Batch/Output/'.$sClass.'.php';
        if (!file_exists($sClassFile)) {
            throw (new Exception("Doesn't exists file definition for $sClass ($sClassFile)"));
        } else {
            include_once ($sClassFile);
            if (!class_exists($sClassEngine)) {
                throw (new Exception("$sClassFile exists, but $sClassEngine isn't defined"));
            } else {
                return new $sClassEngine($this);
            }
        }
    }
    /**
    * Save the beautified sources to file(s)
    * @return bool
    * @throws Exception
    */
    public function save($sFile = null) 
    {
        $oBatchEngine = $this->getBatchEngine();
        return $oBatchEngine->save();
    }
    /**
    * Return a string with the content of the file(s)
    * @return string
    */
    public function get() 
    {
        $oBatchEngine = $this->getBatchEngine();
        return $oBatchEngine->get();
    }
    public function show() 
    {
        echo $this->get();
    }
    /**
    * Allows subclass of {@link PHP_Beautifier_Batch_Engine} call methods of {@link $oBeaut}
    * @param    PHP_Beautifier_Batch_Engine
    * @param    string method to call
    * @param    array  array of args
    * @return   mixed
    */
    public function callBeautifier(PHP_Beautifier_Batch_Output $oEngine, $sMethod, $aArgs = array()) 
    {
        return @call_user_func_array(array(
            $this->oBeaut,
            $sMethod
        ) , $aArgs);
    }
    public function getInputFiles() 
    {
        return $this->mInputFiles;
    }
    public function getOutputPath() 
    {
        return $this->sPreOutputFile;
    }
}
?>
