<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Herim Vasquez <vasquezh@iro.umontreal.ca>                   |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+
//
// $Id: hierselect.php,v 1.12 2004/10/20 10:03:49 avb Exp $

require_once('HTML/QuickForm/group.php');
require_once('HTML/QuickForm/select.php');

/**
 * Class to dynamically create two or more HTML Select elements
 * The first select changes the content of the second select and so on.
 * This element is considered as a group. Selects will be named
 * groupName[0], groupName[1], groupName[2]...
 *
 * @author       Herim Vasquez <vasquezh@iro.umontreal.ca>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 * @version      1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_hierselect extends HTML_QuickForm_group
{   
    // {{{ properties

    /**
     * Options for all the select elements
     *
     * Format is a bit more complex as we need to know which options
     * are related to the ones in the previous select:
     *
     * Ex:
     * // first select
     * $select1[0] = 'Pop';
     * $select1[1] = 'Classical';
     * $select1[2] = 'Funeral doom';
     *
     * // second select
     * $select2[0][0] = 'Red Hot Chil Peppers';
     * $select2[0][1] = 'The Pixies';
     * $select2[1][0] = 'Wagner';
     * $select2[1][1] = 'Strauss';
     * $select2[2][0] = 'Pantheist';
     * $select2[2][1] = 'Skepticism';
     *
     * // If only need two selects 
     * //     - and using the depracated functions
     * $sel =& $form->addElement('hierselect', 'cds', 'Choose CD:');
     * $sel->setMainOptions($select1);
     * $sel->setSecOptions($select2);
     *
     * //     - and using the new setOptions function
     * $sel =& $form->addElement('hierselect', 'cds', 'Choose CD:');
     * $sel->setOptions(array($select1, $select2));
     *
     * // If you have a third select with prices for the cds
     * $select3[0][0][0] = '15.00$';
     * $select3[0][0][1] = '17.00$';
     * etc
     *
     * // You can now use
     * $sel =& $form->addElement('hierselect', 'cds', 'Choose CD:');
     * $sel->setOptions(array($select1, $select2, $select3));
     * 
     * @var       array
     * @access    private
     */
    var $_options = array();
    
    /**
     * Number of select elements on this group
     *
     * @var       int
     * @access    private
     */
    var $_nbElements = 0;

    /**
     * The javascript used to set and change the options
     *
     * @var       string
     * @access    private
     */
    var $_js = '';
    
    /**
    * The javascript array name
    */
    var $_jsArrayName = '';

    // }}}
    // {{{ constructor

    /**
     * Class constructor
     * 
     * @param     string    $elementName    (optional)Input field name attribute
     * @param     string    $elementLabel   (optional)Input field label in form
     * @param     mixed     $attributes     (optional)Either a typical HTML attribute string 
     *                                      or an associative array. Date format is passed along the attributes.
     * @param     mixed     $separator      (optional)Use a string for one separator,
     *                                      use an array to alternate the separators.
     * @access    public
     * @return    void
     */
    function HTML_QuickForm_hierselect($elementName=null, $elementLabel=null, $attributes=null, $separator=null)
    {
        $this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        if (isset($separator)) {
            $this->_separator = $separator;
        }
        $this->_type = 'hierselect';
        $this->_appendName = true;
    } //end constructor

    // }}}
    // {{{ setOptions()

    /**
     * Initialize the array structure containing the options for each select element.
     * Call the functions that actually do the magic.
     *
     * @param     array    $options    Array of options defining each element
     *
     * @access    public
     * @return    void
     */
    function setOptions($options)
    {
        $this->_options = $options;

        if (empty($this->_elements)) {
            $this->_nbElements = count($this->_options);
            $this->_createElements();
        } else {
            // setDefaults has probably been called before this function
            // check if all elements have been created
            $totalNbElements = count($this->_options);
            for ($i = $this->_nbElements; $i < $totalNbElements; $i ++) {
                $this->_elements[] = new HTML_QuickForm_select($i, null, array(), $this->getAttributes());
                $this->_nbElements++;
            }
        }
        
        $this->_setOptions();
        $this->_setJS();
    } // end func setMainOptions

    // }}}
    // {{{ setMainOptions()
    
    /**
     * Sets the options for the first select element. Deprecated. setOptions() should be used.
     *
     * @param     array     $array    Options for the first select element
     *
     * @access    public
     * @return    void
     */
    function setMainOptions($array)
    {
        $this->_options[0] = $array;

        if (empty($this->_elements)) {
            $this->_nbElements = 2;
            $this->_createElements();
        }
    } // end func setMainOptions
    
    // }}}
    // {{{ setSecOptions()
    
    /**
     * Sets the options for the second select element. Deprecated. setOptions() should be used.
     * The main _options array is initialized and the _setOptions function is called.
     *
     * @param     array     $array    Options for the second select element
     *
     * @access    public
     * @return    void
     */
    function setSecOptions($array)
    {
        $this->_options[1] = $array;

        if (empty($this->_elements)) {
            $this->_nbElements = 2;
            $this->_createElements();
        } else {
            // setDefaults has probably been called before this function
            // check if all elements have been created
            $totalNbElements = 2;
            for ($i = $this->_nbElements; $i < $totalNbElements; $i ++) {
                $this->_elements[] = new HTML_QuickForm_select($i, null, array(), $this->getAttributes());
                $this->_nbElements++;
            }
        }
        
        $this->_setOptions();
        $this->_setJS();
    } // end func setSecOptions
    
    // }}}
    // {{{ _setOptions()
    
    /**
     * Sets the options for each select element
     *
     * @access    private
     * @return    void
     */
    function _setOptions()
    {
        $toLoad = '';
        foreach (array_keys($this->_elements) AS $key) {
            if (eval("return isset(\$this->_options[{$key}]{$toLoad});") ) {
                $array = eval("return \$this->_options[{$key}]{$toLoad};");
                if (is_array($array)) {
                    $select =& $this->_elements[$key];
                    $select->_options = array();
                    $select->loadArray($array);

                    $value  = is_array($v = $select->getValue()) ? $v[0] : key($array);
                    $toLoad .= '[\''.$value.'\']';
                }
            }
        }
    } // end func _setOptions
    
    // }}}
    // {{{ setValue()

    /**
     * Sets values for group's elements
     * 
     * @param     array     $value    An array of 2 or more values, for the first,
     *                                the second, the third etc. select
     *
     * @access    public
     * @return    void
     */
    function setValue($value)
    {
        $this->_nbElements = count($value);
        parent::setValue($value);
        $this->_setOptions();
    } // end func setValue
    
    // }}}
    // {{{ _createElements()

    /**
     * Creates all the elements for the group
     * 
     * @access    private
     * @return    void
     */
    function _createElements()
    {
        //hack to add id attribute for hier select
        $attributes = $this->getAttributes();
        $id = null;
        if ( isset( $attributes['id'] ) ) {
            $id = "{$attributes['id']}";
        }

        for ($i = 0; $i < $this->_nbElements; $i++) {
            if ( isset( $id ) ) {
                $attributes['id'] = "{$id}_{$i}";
            }

            $this->_elements[] = new HTML_QuickForm_select($i, null, array(), $attributes);
        }
    } // end func _createElements

    // }}}
    // {{{ _setJS()
    
    /**
     * Set the JavaScript for each select element (excluding de main one).
     *
     * @access    private
     * @return    void
     */
    function _setJS()
    {
        static $jsArrayName = null;

        $this->_js = $js = '';
        if ( ! $jsArrayName ) {
            $this->_jsArrayName = 'hs_' . preg_replace('/\[|\]/', '_', $this->getName());
            for ($i = 1; $i < $this->_nbElements; $i++) {
                $this->_setJSArray($this->_jsArrayName, $this->_options[$i], $js);
            }
            $jsArrayName = $this->_jsArrayName;
        } else {
            $this->_jsArrayName = $jsArrayName;
        }
    } // end func _setJS
    
    // }}}
    // {{{ _setJSArray()
    
    /**
     * Recursively builds the JavaScript array defining the options that a select
     * element can have.
     *
     * @param       string      $grpName    Group Name attribute
     * @param       array       $options    Select element options
     * @param       string      $js         JavaScript definition is build using this variable
     * @param       string      $optValue   The value for the current JavaScript option
     *
     * @access      private
     * @return      void
     */
    function _setJSArray($grpName, $options, &$js, $optValue = '')
    {
        static $jsNameCache = array( );
        if (is_array($options)) {
            $js = '';
            // For a hierselect containing 3 elements:
            //      if option 1 has been selected for the 1st element
            //      and option 3 has been selected for the 2nd element,
            //      then the javascript array containing the values to load 
            //      on the 3rd element will have the following name:   grpName_1_3
            $name  = ($optValue === '') ? $grpName : $grpName.'_'.$optValue;
            foreach($options AS $k => $v) {
                $this->_setJSArray($name, $v, $js, $k);
            }
            
            // if $js !== '' add it to the JavaScript

            if ( $js !== '' ) {
                // check if we have already this js in cache, if so reuse it
                $cacheKey = md5( $js );
                if ( array_key_exists( $cacheKey, $jsNameCache ) ) {
                    $this->_js .= "$name = {$jsNameCache[$cacheKey]}\n";
                } else {
                    $this->_js .= $name." = {\n".$js."\n}\n";
                    $jsNameCache[$cacheKey] = $name;
                }
            }
            $js = '';
        } else {
            // $js empty means that we are adding the first element to the JavaScript.
            if ($js != '') {
                $js .= ",\n";
            }
            $js .= '"'.$optValue.'":"'.addcslashes($options,'"').'"';
        }
    }

    // }}}
    // {{{ toHtml()

    /**
     * Returns Html for the group
     * 
     * @access      public
     * @return      string
     */
    function toHtml()
    {
        if ($this->_flagFrozen) {
            $this->_js = '';
        } else {
            // set the onchange attribute for each element
            $keys               = array_keys($this->_elements);
            $nbElements         = count($keys);
            $nbElementsUsingFnc = $nbElements - 1; // last element doesn't need it
            for ($i = 0; $i < $nbElementsUsingFnc; $i++) {
                $select =& $this->_elements[$keys[$i]];
                $select->updateAttributes(
                    array('onChange' => 'swapOptions(this.form, \''.$this->getName().'\', '.$keys[$i].', '.$nbElements.', \''.$this->_jsArrayName.'\');')
                );
            }
            
            // create the js function to call
            if (!defined('HTML_QUICKFORM_HIERSELECT_EXISTS')) {
                $this->_js .= "function swapOptions(frm, grpName, eleIndex, nbElements, arName)\n"
                             ."{\n"
                             ."    var n = \"\";\n"
                             ."    var ctl;\n\n"
                             ."    for (var i = 0; i < nbElements; i++) {\n"
                             ."        ctl = frm[grpName+'['+i+']'];\n"
                             ."        if (!ctl) {\n"
                             ."            ctl = frm[grpName+'['+i+'][]'];\n"
                             ."        }\n"
                             ."        if (i <= eleIndex) {\n"
                             ."            n += \"_\"+ctl.value;\n"
                             ."        } else {\n"
                             ."            ctl.length = 0;\n"
                             ."        }\n"
                             ."    }\n\n"
                             ."    var t = eval(\"typeof(\"+arName + n +\")\");\n"
                             ."    if (t != 'undefined') {\n"
                             ."        var the_array = eval(arName+n);\n"
                             ."        var j = 0;\n"
                             ."        n = eleIndex + 1;\n"
                             ."        ctl = frm[grpName+'['+ n +']'];\n"
                             ."        if (!ctl) {\n"
                             ."            ctl = frm[grpName+'['+ n +'][]'];\n"
                             ."        }\n"
                             ."        ctl.style.display = 'inline';\n" 
                             ."        for (var i in the_array) {\n"
                             ."            opt = new Option(the_array[i], i, false, false);\n"
                             ."            ctl.options[j++] = opt;\n"
                             ."        }\n"
                             ."    } else {\n"
                             ."        n = eleIndex + 1;\n"
                             ."        ctl = frm[grpName+'['+n+']'];\n"
                             ."        if (!ctl) {\n"
                             ."            ctl = frm[grpName+'['+ n +'][]'];\n"
                             ."        }\n"
                             ."        if (ctl) {\n"
                             ."            ctl.style.display = 'none';\n"
                             ."        }\n"
                             ."    }\n"
                             ."    if (eleIndex+1 < nbElements) {\n"
                             ."        swapOptions(frm, grpName, eleIndex+1, nbElements, arName);\n"
                             ."    }\n"
                             ."}\n";
                define('HTML_QUICKFORM_HIERSELECT_EXISTS', true);
            }
        }
        include_once('HTML/QuickForm/Renderer/Default.php');
        $renderer = new HTML_QuickForm_Renderer_Default();
        $renderer->setElementTemplate('{element}');
        parent::accept($renderer);
        $result = null;
        if ( ! empty( $this->_js ) ) {
            $result .= "<script type=\"text/javascript\">\n//<![CDATA[\n" . $this->_js . "//]]>\n</script>";
        }
        return $result .
               $renderer->toHtml();
    } // end func toHtml

    // }}}
    // {{{ accept()

   /**
    * Accepts a renderer
    *
    * @param object     An HTML_QuickForm_Renderer object
    * @param bool       Whether a group is required
    * @param string     An error message associated with a group
    * @access public
    * @return void 
    */
    function accept(&$renderer, $required = false, $error = null)
    {
        $renderer->renderElement($this, $required, $error);
    } // end func accept

    // }}}
    // {{{ onQuickFormEvent()

    function onQuickFormEvent($event, $arg, &$caller)
    {
        if ('updateValue' == $event) {
            // we need to call setValue() so that the secondary option
            // matches the main option
            return HTML_QuickForm_element::onQuickFormEvent($event, $arg, $caller);
        } else {
            return parent::onQuickFormEvent($event, $arg, $caller);
        }
    } // end func onQuickFormEvent

    // }}}    
} // end class HTML_QuickForm_hierselect
?>
