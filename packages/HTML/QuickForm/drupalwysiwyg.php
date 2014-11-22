<?php

require_once('HTML/QuickForm/textarea.php');

/**
 * HTML Quickform element for TinyMCE Editor
 *
 * TinyMCE is a WYSIWYG HTML editor which can be obtained from
 * http://tinymce.moxiecode.com/.
 *
 * @access       public
 */
class HTML_QuickForm_DrupalWysiwyg extends HTML_QuickForm_textarea
{

    var $format = NULL;
    
    /**
     * Class constructor
     *
     * @param   string  TinyMCE instance name
     * @param   string  TinyMCE instance label
     * @param   array   Config settings for TinyMCE
     * @param   string  Attributes for the textarea
     * @access  public
     * @return  void
     */
    function HTML_QuickForm_DrupalWysiwyg($elementName=null, $elementLabel=null, $attributes=null, $options=array())
    {
        HTML_QuickForm_element::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'DrupalWysiwyg';
        
    }   

/**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param     string    $event  Name of event
     * @param     mixed     $arg    event arguments
     * @param     object    &$caller calling object
     * @since     1.0
     * @access    public
     * @return    void
     */
    function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event) {
            case 'updateValue':
                civicrm_drupal_wysiwyg_update_value($this, $caller);
                break;
            case 'addElement':
                $format = $arg[0] . '_format';
                $this->format = $caller->get($format);
                if (!$this->format) $this->format = variable_get('civicrm_wysiwyg_input_format', (defined('FILTER_FORMAT_DEFAULT')?FILTER_FORMAT_DEFAULT:null));
                $caller->set($format, $this->format);
                parent::onQuickFormEvent($event, $arg, $caller);
                break;
            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }
        return true;
    } // end func onQuickFormLoad
    
    /**
     * Return the htmlarea in HTML
     *
     * @access public
     * @return string
     */
    function toHtml()
    {
        $html = null;
        // return frozen state
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
          $html = civicrm_drupal_get_wysiwyg_html($this->_attributes, $this->getValue(), $this->format);
          return '<div class="civicrm-drupal-wysiwyg">' . $html . '</div>';
        }
    }
    
    function exportValue(&$submitValues, $assoc = false) {
      return $this->_prepareValue($this->getValue(), $assoc);
    }

    /**
     * Returns the htmlarea content in HTML
     * 
     * @access public
     * @return string
     */
    function getFrozenHtml()
    {      
        return $this->getValue();
    }
}
