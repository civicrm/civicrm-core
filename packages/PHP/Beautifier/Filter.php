<?php
/**
 * Definition of class PHP_Beautifier_Filter
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
 * PHP_Beautifier_Filter
 *
 * Definition for creation of Filters
 * For concrete details, please see {@link PHP_Beautifier_Filter_Default}
 * @category     PHP
 * @package      PHP_Beautifier
 * @subpackage   Filter
 * @tutorial     PHP_Beautifier/Filter/Filter.create.pkg
 * @author       Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright    2004-2006 Claudio Bustos
 * @link         http://pear.php.net/package/PHP_Beautifier
 * @link         http://beautifyphp.sourceforge.net
 * @license      http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version      Release: 0.1.14
 */
abstract class PHP_Beautifier_Filter
{
    /**
     * Stores a reference to main PHP_Beautifier
     * @var PHP_Beautifier
     */
    protected $oBeaut;
    /**
     * Associative array of functions to use when some token are found
     * @var array
     */
    protected $aFilterTokenFunctions = array();
    /**
     * Settings for the Filter
     * @var array
     */
    protected $aSettings = array();
    /**
     * Definition of the settings
     * Should be an associative array. The keys are the names of settings
     * and the values are an array with the keys 'type' and '
     * @var array
     */
    protected $aSettingsDefinition = array();
    /**
     * Description of the Filter
     * @var string
     */
    protected $sDescription = 'Filter for PHP_Beautifier';
    /**
     * If a method for parse Tokens of a Filter returns this, the control of the process
     * is handle by the next Filter
     */
    const BYPASS = 'BYPASS';
    /**
     * Switch to 'turn' on and off the filter
     * @var bool
     */
    protected $bOn = true;
    /**
     * Current token
     */
    protected $aToken = false;
    /**
     * Constructor
     * If you need to overload this (for example, to create a
     * definition for setting with {@link addSettingDefinition()}
     * remember call the parent constructor.
     * <code>
     * parent::__construct($oBeaut, $aSettings)
     * </code>
     * @param PHP_Beautifier
     * @param array settings for the Filter
     */
    public function __construct(PHP_Beautifier $oBeaut, $aSettings = array())
    {
        $this->oBeaut = $oBeaut;
        if ($aSettings) {
            $this->aSettings = $aSettings;
        }
    }
    /**
     * Add a setting definition
     * @param string
     */
    protected function addSettingDefinition($sSetting, $sType, $sDescription)
    {
        $this->aSettingsDefinition[$sSetting] = array(
            'type' => $sType,
            'description' => $sDescription
        );
    }
    /**
     * return @string
     */
    public function getName()
    {
        return str_ireplace('PHP_Beautifier_Filter_', '', get_class($this));
    }
    /**
     * Turn on the Filter
     * Use inside the code to beautify
     * Ex.
     * <code>
     * ...some code...
     * // ArrayNested->on()
     * ...other code ...
     * </code>
     */
    final public function on()
    {
        $this->bOn = true;
    }
    /**
     * Turn off the Filter
     * Use inside the code to beautify
     * Ex.
     * <code>
     * ...some code...
     * // ArrayNested->off()
     * ...other code ...
     * </code>
     */
    public function off()
    {
        $this->bOn = false;
    }
    /**
     * Get a setting of the Filter
     * @param string name of setting
     * @return mixed value of setting or false
     */
    final public function getSetting($sSetting)
    {
        return (array_key_exists($sSetting, $this->aSettings)) ? $this->aSettings[$sSetting] : false;
    }
    /**
     * Set a value of a Setting
     * @param string name of setting
     * @param mixed value of setting
     */
    final public function setSetting($sSetting, $sValue)
    {
        if (array_key_exists($sSetting, $this->aSettings)) {
            $this->aSettings[$sSetting] = $sValue;
        }
    }
    /**
     * Function called from {@link PHP_Beautifier::process()} to process the tokens.
     *
     * If the received token is one of the keys of {@link $aFilterTokenFunctions}
     * a function with the same name of the value of that key is called.
     * If the method doesn't exists, {@link __call()} is called, and return
     * {@link PHP_Beautifier_Filter::BYPASS}. PHP_Beautifier, now, call the next Filter is its list.
     * If the method exists, it can return true or {@link PHP_Beautifier_Filter::BYPASS}.
     * @param array token
     * @return bool true if the token is processed, false bypass to the next Filter
     * @see PHP_Beautifier::process()
     */
    public function handleToken($token)
    {
        $this->aToken = $token;
        if (!$this->bOn) {
            return false;
        }
        $sMethod = $sValue = false;
        if (array_key_exists($token[0], $this->aFilterTokenFunctions)) {
            $sMethod = $this->aFilterTokenFunctions[$token[0]];
            $sValue = $token[1];
        } elseif ($this->oBeaut->getTokenFunction($token[0])) {
            $sMethod = $this->oBeaut->getTokenFunction($token[0]);
        }
        $sValue = $token[1];
        if ($sMethod) {
            if ($this->oBeaut->iVerbose > 5) {
                echo $sMethod . ":" . trim($sValue) . "\n";
            }
            // return false if PHP_Beautifier_Filter::BYPASS
            return ($this->$sMethod($sValue) !== PHP_Beautifier_Filter::BYPASS);
        } else { // WEIRD!!! -> Add the same received
            $this->oBeaut->add($token[1]);
            PHP_Beautifier_Common::getLog()->log("Add same received:" . trim($token[1]) , PEAR_LOG_DEBUG);
            return true;
        }
        // never go here
        return false;
    }
    /**
     * @param string metodo
     * @param array arguments
     * @return mixed null or {@link PHP_Beautifier_Filter::BYPASS}
     */
    public function __call($sMethod, $aArgs)
    {
        return PHP_Beautifier_Filter::BYPASS;
    }
    /**
     * Called from {@link PHP_Beautifier::process()} at the beginning
     * of the processing
     * @return void
     */
    public function preProcess()
    {
    }
    /**
     * Called from {@link PHP_Beautifier::process()} at the end of processing
     * The post-process must be made in {@link PHP_Beautifier::$aOut}
     * @return void
     */
    public function postProcess()
    {
    }
    public function __sleep()
    {
        return array(
            'aSettings'
        );
    }
    public function getDescription()
    {
        return $this->sDescription;
    }
    public function __toString()
    {
        // php_beautifier->setBeautify(false);
            $sOut='Filter:      '.$this->getName()."\n".
                  "Description: ".$this->getDescription()."\n";
                  if (!$this->aSettingsDefinition) {
                      $sOut.= "Settings:    No declared settings";
                  } else {
                      $sOut.="Settings:\n";
                      foreach($this->aSettingsDefinition as $sSetting=>$aSettings) {
                          $sOut.=sprintf("- %s : %s (type %s)\n",$sSetting, $aSettings['description'], $aSettings['type']);
                      }
                  }
        // php_beautifier->setBeautify(true);
        return $sOut;
    }
}
?>