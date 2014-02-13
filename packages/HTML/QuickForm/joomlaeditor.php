<?php

require_once'HTML/QuickForm/textarea.php';

/**
 * HTML Quickform element for Joomla Default Editor
 *
 * Joomla global configuration includes the selection of a default editor
 * This class imports and translates the default editor into CiviCRM forms
 * 
 * @author       Brian Shaughnessy, based on other editor classes
 * @access       public
 */
class HTML_QuickForm_JoomlaEditor extends HTML_QuickForm_textarea
{
    /**
     * The width of the editor in pixels or percent
     *
     * @var string
     * @access public
     */
    var $width = '94%';
    
    /**
     * The height of the editor in pixels or percent
     *
     * @var string
     * @access public
     */
    var $height = '400';
    
    /**
     * Class constructor
     *
     * @param   string  Editor instance name
     * @param   string  Editor instance label
     * @param   array   Config settings for editor
     * @param   string  Attributes for the textarea
     * @access  public
     * @return  void
     */
    function HTML_QuickForm_JoomlaEditor( $elementName=null, $elementLabel=null, $attributes=null, $options=array() )
    {

        HTML_QuickForm_element::HTML_QuickForm_element( $elementName, $elementLabel, $attributes );
        $this->_persistantFreeze = true;
        $this->_type = 'JoomlaEditor';
        // set editor height smaller if schema defines rows as 4 or less
    if ( is_array( $attributes ) &&
      array_key_exists( 'rows', $attributes ) &&
      $attributes['rows'] <= 4
    ) {
            $this->height = 200;
        }
    }    
    
    /**
     * Return the htmlarea in HTML
     *
     * @access public
     * @return string
     */
    function toHtml()
    {
        jimport( 'joomla.html.editor' );
		$editor = JFactory::getEditor();
        
		if ( $this->_flagFrozen ) {
            return $this->getFrozenHtml();
        } else {
            $name = $this->getAttribute( 'name' );
            $html = null;
			
			//tinymce and its relatives require 'double-loading' when inside jquery tab
            $editorName = $editor->get( '_name' );
      if ( $editorName == 'jce' ||
        $editorName == 'tinymce'
      ) {
                    $html .= sprintf( '<script type="text/javascript">
					//reset the controls if called in jquery tab or via ajax 
        			tinyMCE.execCommand( "mceRemoveControl", false,"' . $this->_attributes['id'] .'" );
        			tinyMCE.execCommand( "mceAddControl"   , true, "' . $this->_attributes['id'] .'" );
					</script>' );

				$html .= sprintf( '<style type="text/css"> <!--
					#crm-container table.mceLayout td { border: none; } .button2-left { display:none; }
					--> </style> ' );
			}
			
			$html .= $editor->display( $name, $this->getValue(), $this->width, $this->height, '94', '20', false );
            return $html;
        }
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
