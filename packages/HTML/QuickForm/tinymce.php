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
class HTML_QuickForm_TinyMCE extends HTML_QuickForm_textarea
{
    /**
     * The width of the editor in pixels or percent
     *
     * @var string
     * @access public
     */
    var $Width = '95%';
    
    /**
     * The height of the editor in pixels or percent
     *
     * @var string
     * @access public
     */
    var $Height = '400';
    
    /**
     * The path where to find the editor files
     *
     * @var string
     * @access public
     */
    var $BasePath = 'packages/tinymce/jscripts/tiny_mce/';
    
    /**
     * Check for browser compatibility
     *
     * @var boolean
     * @access public
     */
    var $CheckBrowser = true;

    /**
     * Configuration settings for the editor
     *
     * @var array
     * @access public
     */
    var $Config = array();
    
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
    function HTML_QuickForm_TinyMCE($elementName=null, $elementLabel=null, $attributes=null, $options=array())
    {
        $attributes['class'] = 'tinymce';
        HTML_QuickForm_element::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'TinyMCE';
        
        if ( is_array($attributes) && array_key_exists( 'rows', $attributes ) && $attributes['rows'] <= 4 ) {
            $this->Height = 200;
        }
        if (is_array($options)) {
            $this->Config = $options;
        }
    }
    
    /**
     * Set config variable for TinyMCE
     *
     * @param mixed Key of config setting
     * @param mixed Value of config settinh
     * @access public
     * @return void     
     */
    function SetConfig($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->Config[$k] = $v;
            }
        } else {
            $this->Config[$key] = $value;
        }
    }
    
    /**
     * Check if the browser is compatible (IE 5.5+, Gecko > 20030210)
     *
     * @access public
     * @return boolean
     */
    function IsCompatible()
    {
        if (isset($_SERVER['HTTP_USER_AGENT']))
        {
            $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
            if (($msie = strpos($agent, 'msie')) !== false &&
                strpos($agent, 'opera') === false &&
                strpos($agent, 'mac') === false)
            {                
                return ((float) substr($agent, $msie + 5, 3) >= 5.5);
            } elseif (($gecko = strpos($agent, 'gecko/')) !== false) {
                return ((int) substr($agent, $gecko + 6, 8 ) >= 20030210);
            }             
            return true;
        }   
        return false;
    }

    
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
            // return textarea if incompatible
        } elseif (!$this->IsCompatible()) {
            return parent::toHtml();
            // return textarea
        } else {
            // load tinyMCEeditor
            $config = CRM_Core_Config::singleton( );
            $browseUrl = $config->userFrameworkResourceURL . 'packages/kcfinder/browse.php?opener=tinymce&cms=civicrm&type=';
            
            // tinymce is wierd, it needs to be loaded initially along with jquery
            $html = null;
            $html .= sprintf( '<script type="text/javascript">
        var configArray = [{
        
        theme : "advanced",
        editor_selector : "form-TinyMCE",
        plugins : "safari,spellchecker,layer,table,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,pagebreak",
        theme_advanced_buttons1    : "separator,bold,italic,underline,|,fontselect,fontsizeselect",
        theme_advanced_buttons1_add: "separator,forecolor,backcolor,separator,link,unlink,separator,image,hr,emotions",
        theme_advanced_buttons2    : "separator,numlist,bullist,|,outdent,indent,cite,separator,justifyleft,justifycenter,justifyright",
        theme_advanced_buttons2_add: "justifyfull,separator,pastetext,pasteword,|,spellchecker,separator,removeformat,separator,|,undo,redo,|,code,|,fullscreen,help",
        theme_advanced_buttons3    : "",
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_resize_horizontal : false,
        theme_advanced_resizing : true,
        apply_source_formatting : true,
        spellchecker_languages : "+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv",
        convert_urls : false,
        remove_script_host : false,
        width : "' . $this->Width .'%",
        file_browser_callback: "openKCFinder",
        setup : function(ed) {
                 ed.onInit.addToTop( function(){ 
                    var height = cj("#" + ed.editorId).attr("height");
                    cj("#" + ed.editorId + "_tbl").css("height", height);
                    cj("#" + ed.editorId + "_ifr").css("height", height);
                });
                ed.onKeyUp.add(function(ed, l) {
                    global_formNavigate = false;
                });
        }'.$this->getConfigString().'
        }];
 
        tinyMCE.settings = configArray[0];
        //remove the control if element is already having 
        tinyMCE.execCommand("mceRemoveControl", false,"' . $this->_attributes['id'] .'");
        tinyMCE.execCommand("mceAddControl"   , true, "' . $this->_attributes['id'] .'");

        function openKCFinder(field_name, url, type, win) {
            tinyMCE.activeEditor.windowManager.open({
                file: "'. $browseUrl .'" + type,
                title: "KCFinder",
                width: 700,
                height: 500,
                resizable: "yes",
                inline: true,
                close_previous: "no",
                popup_css: false
            }, {
                window: win,
                    input: field_name
            });
            return false;
        }
</script>' );
                        
            // include textarea as well (TinyMCE transforms it)
            $html .=  parent::toHTML();
            $html .= sprintf( '<script type="text/javascript">
                                 cj("#' . $this->_attributes['id'] .'").attr( "height","'.$this->Height.'px");
                              </script>' );
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
    
    /**
     * Returns the config variables for TinyMCE as a string
     * 
     * @access public
     * @return string
     */
    function getConfigString()
    {
        $append = ",\n";
        $configString = $append;
        foreach ( $this->Config as $k => $v ) {
            $configString .= $k . ' : ' . $v . $append;
        }
        return rtrim( $configString, $append );
    }
}

?>
