<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * DB_Table_QuickForm creates HTML_QuickForm objects from DB_Table properties.
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Paul M. Jones <pmjones@php.net>
 *                          David C. Morse <morse@php.net>
 *                          Mark Wiesemann <wiesemann@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the 
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products 
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category Database
 * @package  DB_Table
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   David C. Morse <morse@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  CVS: $Id: QuickForm.php,v 1.45 2008/03/28 20:00:38 wiesemann Exp $
 * @link     http://pear.php.net/package/DB_Table
 */

/**
* Needed to build forms.
*/
require_once 'HTML/QuickForm.php';

/**
* US-English messages for some QuickForm rules.  Moritz Heidkamp
* suggested this approach for easier i18n.
*/
if (! isset($GLOBALS['_DB_TABLE']['qf_rules'])) {
    $GLOBALS['_DB_TABLE']['qf_rules'] = array(
      'required'  => 'The item %s is required.',
      'numeric'   => 'The item %s must be numbers only.',
      'maxlength' => 'The item %s can have no more than %d characters.'
    );
}

/**
* If you want to use an extended HTML_QuickForm object, you can specify the
* class name in $_DB_TABLE['qf_class_name'].
* ATTENTION: You have to include the class file yourself, DB_Table does
* not take care of this!
*/
if (!isset($GLOBALS['_DB_TABLE']['qf_class_name'])) {
    $GLOBALS['_DB_TABLE']['qf_class_name'] = 'HTML_QuickForm';
}

/**
 * DB_Table_QuickForm creates HTML_QuickForm objects from DB_Table properties.
 * 
 * DB_Table_QuickForm provides HTML form creation facilities based on
 * DB_Table column definitions transformed into HTML_QuickForm elements.
 * 
 * @category Database
 * @package  DB_Table
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   David C. Morse <morse@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @version  Release: 1.5.6
 * @link     http://pear.php.net/package/DB_Table
 */

class DB_Table_QuickForm {
    
    
    /**
    * 
    * Build a form based on DB_Table column definitions.
    * 
    * @static
    * 
    * @access public
    * 
    * @param array $cols A sequential array of DB_Table column definitions
    * from which to create form elements.
    * 
    * @param string $arrayName By default, the form will use the names
    * of the columns as the names of the form elements.  If you pass
    * $arrayName, the column names will become keys in an array named
    * for this parameter.
    * 
    * @param array $args An associative array of optional arguments to
    * pass to the QuickForm object.  The keys are...
    *
    * 'formName' : String, name of the form; defaults to the name of the
    * table.
    * 
    * 'method' : String, form method; defaults to 'post'.
    * 
    * 'action' : String, form action; defaults to
    * $_SERVER['REQUEST_URI'].
    * 
    * 'target' : String, form target target; defaults to '_self'
    * 
    * 'attributes' : Associative array, extra attributes for <form>
    * tag; the key is the attribute name and the value is attribute
    * value.
    * 
    * 'trackSubmit' : Boolean, whether to track if the form was
    * submitted by adding a special hidden field
    * 
    * @param string $clientValidate By default, validation will match
    * the 'qf_client' value from the column definition.  However,
    * if you set $clientValidate to true or false, this will
    * override the value from the column definition.
    *
    * @param array $formFilters An array with filter function names or
    * callbacks that will be applied to all form elements.
    * 
    * @return object HTML_QuickForm
    * 
    * @see HTML_QuickForm
    *
    * @see DB_Table_QuickForm::createForm()
    * 
    */
    
    function &getForm($cols, $arrayName = null, $args = array(),
        $clientValidate = null, $formFilters = null)
    {
        $form = DB_Table_QuickForm::createForm($args);
        DB_Table_QuickForm::addElements($form, $cols, $arrayName);
        DB_Table_QuickForm::addRules($form, $cols, $arrayName, $clientValidate);
        DB_Table_QuickForm::addFilters($form, $cols, $arrayName, $formFilters);
        
        return $form;
    }
    
    
    /**
    * 
    * Creates an empty form object.
    *
    * In case you want more control over your form, you can call this function
    * to create it, then add whatever elements you want.
    *
    * @static
    * 
    * @access public
    * 
    * @author Ian Eure <ieure@php.net>
    * 
    * @param array $args An associative array of optional arguments to
    * pass to the QuickForm object.  The keys are...
    *
    * 'formName' : String, name of the form; defaults to the name of the
    * table.
    * 
    * 'method' : String, form method; defaults to 'post'.
    * 
    * 'action' : String, form action; defaults to
    * $_SERVER['REQUEST_URI'].
    * 
    * 'target' : String, form target target; defaults to '_self'
    * 
    * 'attributes' : Associative array, extra attributes for <form>
    * tag; the key is the attribute name and the value is attribute
    * value.
    * 
    * 'trackSubmit' : Boolean, whether to track if the form was
    * submitted by adding a special hidden field
    * 
    * @return object HTML_QuickForm
    * 
    */
    
    function &createForm($args = array())
    {
        if (isset($args['formName'])) {
            $formName = $args['formName'];
        } elseif (isset($this)) {
            $formName = $this->table;
        } else {
            $formName = '_db_table_form_';
        }
            
        $method = isset($args['method'])
            ? $args['method'] : 'post';
        
        $action = isset($args['action'])
            ? $args['action'] : $_SERVER['REQUEST_URI'];
        
        $target = isset($args['target'])
            ? $args['target'] : '_self';
        
        $attributes = isset($args['attributes'])
            ? $args['attributes'] : null;
        
        $trackSubmit = isset($args['trackSubmit'])
            ? $args['trackSubmit'] : false;
        
        $form = new $GLOBALS['_DB_TABLE']['qf_class_name']($formName, $method,
            $action, $target, $attributes, $trackSubmit);

        return $form;
    }
    
    
    /**
    * 
    * Adds DB_Table columns to a pre-existing HTML_QuickForm object.
    * 
    * @author Ian Eure <ieure@php.net>
    * 
    * @static
    * 
    * @access public
    * 
    * @param object &$form An HTML_QuickForm object.
    * 
    * @param array $cols A sequential array of DB_Table column definitions
    * from which to create form elements.
    * 
    * @param string $arrayName By default, the form will use the names
    * of the columns as the names of the form elements.  If you pass
    * $arrayName, the column names will become keys in an array named
    * for this parameter.
    * 
    * @return void
    * 
    */
    
    function addElements(&$form, $cols, $arrayName = null)
    {
        $elements = DB_Table_QuickForm::getElements($cols, $arrayName);
        $cols_keys = array_keys($cols);
        foreach (array_keys($elements) as $k) {
        
            $element =& $elements[$k];
            
            // are we adding a group?
            if (is_array($element)) {
                
                // get the label for the group.  have to do it this way
                // because the group of elements does not itself have a
                // label, there are only the labels for the individual
                // elements.
                $tmp = $cols[$cols_keys[$k]];
                if (! isset($tmp['qf_label'])) {
                    $label = $cols_keys[$k];
                    if ($arrayName) {
                        $label = $arrayName . "[$label]";
                    }
                } else {
                    $label = $tmp['qf_label'];
                }
                   
                // set the element name
                if ($arrayName) {
                    $name = $arrayName . '[' . $cols_keys[$k] . ']';
                } else {
                	$name = $cols_keys[$k];
                }

                // fix the column definition temporarily to get the separator
                // for the group
                $col = $cols[$cols_keys[$k]];
                DB_Table_QuickForm::fixColDef($col, $name);

                // done
                $group =& $form->addGroup($element, $name, $label,
                                          $col['qf_groupsep']);

                // set default value (if given) for radio elements
                // (reason: QF "resets" the checked state, when adding a group)
                if ($tmp['qf_type'] == 'radio' && isset($tmp['qf_setvalue'])) {
                    $form->setDefaults(array($name => $tmp['qf_setvalue']));
                }

            } elseif (is_object($element)) {
                $form->addElement($element);
            }
        }
    }

    /**
    * 
    * Gets controls for a list of columns
    * 
    * @author Ian Eure <ieure@php.net>
    * 
    * @static
    * 
    * @access public
    * 
    * @param object &$form An HTML_QuickForm object.
    * 
    * @param array $cols A sequential array of DB_Table column definitions
    * from which to create form elements.
    * 
    * @param string $arrayName By default, the form will use the names
    * of the columns as the names of the form elements.  If you pass
    * $arrayName, the column names will become keys in an array named
    * for this parameter.
    * 
    * @return array Form elements
    * 
    */
    
    function &getElements($cols, $arrayName = null)
    {
        $elements = array();
        
        foreach ($cols as $name => $col) {
            
            if ($arrayName) {
                $elemname = $arrayName . "[$name]";
            } else {
                $elemname = $name;
            }
            
            DB_Table_QuickForm::fixColDef($col, $elemname);

            $elements[] = DB_Table_QuickForm::getElement($col, $elemname);
        }
        
        return $elements;
    }
    
    
    /**
    * 
    * Build a single QuickForm element based on a DB_Table column.
    * 
    * @static
    * 
    * @access public
    * 
    * @param array $col A DB_Table column definition.
    * 
    * @param string $elemname The name to use for the generated QuickForm
    * element.
    * 
    * @return object HTML_QuickForm_Element
    * 
    */
    
    function &getElement($col, $elemname)
    {
        if (isset($col['qf_setvalue'])) {
            $setval = $col['qf_setvalue'];
        }
        
        switch ($col['qf_type']) {
        
        case 'advcheckbox':
        case 'checkbox':
            
            $element =& HTML_QuickForm::createElement(
                'advcheckbox',
                $elemname,
                $col['qf_label'],
                isset($col['qf_label_append']) ?
                    $col['qf_label_append'] : null,
                $col['qf_attrs'],
                $col['qf_vals']
            );
            
            // WARNING: advcheckbox elements in HTML_QuickForm v3.2.2
            // and earlier do not honor setChecked(); they will always
            // be un-checked, unless a POST value sets them.  Upgrade
            // to QF 3.2.3 or later.
            if (isset($setval) && $setval == true) {
                $element->setChecked(true);
            } else {
                $element->setChecked(false);
            }
            
            break;
            
        case 'autocomplete':
        
            $element =& HTML_QuickForm::createElement(
                $col['qf_type'],
                $elemname,
                $col['qf_label'],
                $col['qf_vals'],
                $col['qf_attrs']
            );
            
            if (isset($setval)) {
                $element->setValue($setval);
            }
            
            break;
            
        case 'date':
        
            if (! isset($col['qf_opts']['format'])) {
                $col['qf_opts']['format'] = 'Y-m-d';
            }
            
            $element =& HTML_QuickForm::createElement(
                'date',
                $elemname,
                $col['qf_label'],
                $col['qf_opts'],
                $col['qf_attrs']
            );
            
            if (isset($setval)) {
                $element->setValue($setval);
            }
            
            break;
            
        case 'time':
        
            if (! isset($col['qf_opts']['format'])) {
                $col['qf_opts']['format'] = 'H:i:s';
            }
            
            $element =& HTML_QuickForm::createElement(
                'date',
                $elemname,
                $col['qf_label'],
                $col['qf_opts'],
                $col['qf_attrs']
            );
            
            if (isset($setval)) {
                $element->setValue($setval);
            }
            
            break;

        case 'timestamp':
        
            if (! isset($col['qf_opts']['format'])) {
                $col['qf_opts']['format'] = 'Y-m-d H:i:s';
            }
            
            $element =& HTML_QuickForm::createElement(
                'date',
                $elemname,
                $col['qf_label'],
                $col['qf_opts'],
                $col['qf_attrs']
            );
            
            if (isset($setval)) {
                $element->setValue($setval);
            }
            
            break;
        
        case 'hidden':
        
            $element =& HTML_QuickForm::createElement(
                $col['qf_type'],
                $elemname,
                null,
                $col['qf_attrs']
            );
            
            if (isset($setval)) {
                $element->setValue($setval);
            }
            
            break;
            
            
        case 'radio':
        
            $element = array();
            
            foreach ((array) $col['qf_vals'] as $btnvalue => $btnlabel) {
                
                $element[] =& HTML_QuickForm::createElement(
                    $col['qf_type'],
                    null, // elemname not added because this is a group
                    null,
                    $btnlabel,
                    $btnvalue,
                    $col['qf_attrs']
                );
                
            }
            
            break;
            
        case 'select':
            
            $element =& HTML_QuickForm::createElement(
                $col['qf_type'],
                $elemname,
                $col['qf_label'],
                $col['qf_vals'],
                $col['qf_attrs']
            );
            
            if (isset($setval)) {
                $element->setSelected($setval);
            }
            
            break;
            
        case 'password':
        case 'text':
        case 'textarea':
        
            if (! isset($col['qf_attrs']['maxlength']) &&
                isset($col['size'])) {
                $col['qf_attrs']['maxlength'] = $col['size'];
            }
            
            $element =& HTML_QuickForm::createElement(
                $col['qf_type'],
                $elemname,
                $col['qf_label'],
                $col['qf_attrs']
            );
            
            if (isset($setval)) {
                $element->setValue($setval);
            }
            
            break;
        
        case 'static':
            $element =& HTML_QuickForm::createElement(
                $col['qf_type'],
                $elemname,
                $col['qf_label'],
                (isset($setval) ? $setval : '')
            );
            break;

        case 'hierselect':

            $element =& HTML_QuickForm::createElement(
                $col['qf_type'],
                $elemname,
                $col['qf_label'],
                $col['qf_attrs'],
                $col['qf_groupsep']
            );

            if (isset($setval)) {
                $element->setValue($setval);
            }

            break;

        case 'jscalendar':

            $element =& HTML_QuickForm::createElement(
                $col['qf_type'],
                $elemname,
                $col['qf_label'],
                $col['qf_opts'],
                $col['qf_attrs']
            );

            if (isset($setval)) {
                $element->setValue($setval);
            }

            break;

        case 'header':

            $element =& HTML_QuickForm::createElement(
                $col['qf_type'],
                $elemname
            );

            if (isset($setval)) {
                $element->setValue($setval);
            }

            break;

        case 'static':

            $element =& HTML_QuickForm::createElement(
                $col['qf_type'],
                $elemname,
                $col['qf_label']
            );

            if (isset($setval)) {
                $element->setValue($setval);
            }

            break;

        case 'link':

            $element =& HTML_QuickForm::createElement(
                $col['qf_type'],
                $elemname,
                $col['qf_label'],
                $col['qf_href'], // link href
                $setval,  // link text
                $col['qf_attrs']
            );

            break;

        case 'reset':
        case 'submit':

            $element =& HTML_QuickForm::createElement(
                $col['qf_type'],
                $elemname,
                null,
                $col['qf_attrs']
            );

            if (isset($setval)) {
                $element->setValue($setval);
            }

            break;

        case 'callback':  // custom QF elements that need more than
                          // the standard parameters
                          // code from Arne Bippes <arne.bippes@brandao.de>

            if (is_callable(array($col['qf_callback'], 'createElement'))) {
                // Does an object with name from $col['qf_callback'] and
                // a method with name 'createElement' exist?
                $ret_value = call_user_func_array(
                    array($col['qf_callback'], 'createElement'),
                    array(&$element, &$col, &$elemname, &$setval));
            }
            elseif (is_callable($col['qf_callback'])) {
                // Does a method with name from $col['qf_callback'] exist?
                $ret_value = call_user_func_array(
                    $col['qf_callback'],
                    array(&$element, &$col, &$elemname, &$setval));
            }
            if ($ret_value) {
                break;
            }
            // fall into default block of switch statement:
            // - if $col['qf_callback'] is ...
            //   - not a valid object
            //   - a valid object, but a method 'createElement' doesn't exist
            //   - not a valid method name
            // - if an error occured in 'createElement' or in the method
            
        default:
            
            /**
            * @author Moritz Heidkamp <moritz.heidkamp@invision-team.de>
            */
            
            // not a recognized type.  is it registered with QuickForm?
            if (HTML_QuickForm::isTypeRegistered($col['qf_type'])) {
                
                // yes, create it with some minimalist parameters
                $element =& HTML_QuickForm::createElement(
                    $col['qf_type'],
                    $elemname,
                    $col['qf_label'],
                    $col['qf_attrs']
                );
                
                // set its default value, if there is one
                if (isset($setval)) {
                    $element->setValue($setval);
                }
                
            } else {
                // element type is not registered with QuickForm.
                $element = null;
            }
            
            break;

        }
        
        // done
        return $element;
    }
    
    
    /**
    * 
    * Build an array of form elements based from DB_Table columns.
    * 
    * @static
    * 
    * @access public
    * 
    * @param array $cols A sequential array of DB_Table column
    * definitions from which to create form elements.
    * 
    * @param string $arrayName By default, the form will use the names
    * of the columns as the names of the form elements.  If you pass
    * $arrayName, the column names will become keys in an array named
    * for this parameter.
    * 
    * @return array An array of HTML_QuickForm_Element objects.
    * 
    */
    
    function &getGroup($cols, $arrayName = null)
    {
        $group = array();
        
        foreach ($cols as $name => $col) {
            
            if ($arrayName) {
                $elemname = $arrayName . "[$name]";
            } else {
                $elemname = $name;
            }
            
            DB_Table_QuickForm::fixColDef($col, $elemname);
            
            $group[] = DB_Table_QuickForm::getElement($col, $elemname);
        }
        
        return $group;
    }
    
    
    /**
    * 
    * Adds static form elements like 'header', 'static', 'submit' or 'reset' to
    * a pre-existing HTML_QuickForm object.
    * 
    * @static
    * 
    * @access public
    * 
    * @param object &$form An HTML_QuickForm object.
    * 
    * @param array $elements A sequential array of form element definitions.
    * 
    * @return void
    * 
    */
    
    function addStaticElements(&$form, $elements)
    {
        foreach ($elements as $name => $elemDef) {

            DB_Table_QuickForm::fixColDef($elemDef, $name);

            $element = DB_Table_QuickForm::getElement($elemDef, $name);

            if (!is_object($element)) {
                continue;
            }

            if (isset($elemDef['before']) && !empty($elemDef['before'])) {
                $form->insertElementBefore($element, $elemDef['before']);
            } else {
                $form->addElement($element);
            }
        }
    }
    
    
    /**
    *
    * Adds DB_Table filters to a pre-existing HTML_QuickForm object.
    *
    * @static
    *
    * @access public
    *
    * @param object &$form An HTML_QuickForm object.
    *
    * @param array $cols A sequential array of DB_Table column definitions
    * from which to create form elements.
    *
    * @param string $arrayName By default, the form will use the names
    * of the columns as the names of the form elements.  If you pass
    * $arrayName, the column names will become keys in an array named
    * for this parameter.
    *
    * @param array $formFilters An array with filter function names or
    * callbacks that will be applied to all form elements.
    *
    * @return void
    *
    */
    function addFilters(&$form, $cols, $arrayName = null,
        $formFilters = null)
    {
        foreach ($cols as $name => $col) {
            if ($arrayName) {
                $elemname = $arrayName . "[$name]";
            } else {
                $elemname = $name;
            }

            DB_Table_QuickForm::fixColDef($col, $elemname);

            foreach (array_keys($col['qf_filters']) as $fk) {
                $form->applyFilter($elemname, $col['qf_filters'][$fk]);
            }
        }

        if (is_array($formFilters)) {
            foreach (array_keys($formFilters) as $fk) {
                $form->applyFilter('__ALL__', $formFilters[$fk]);
            }
        }
    }


    /**
    * 
    * Adds element rules to a pre-existing HTML_QuickForm object.
    * 
    * @static
    * 
    * @access public
    * 
    * @param object &$form An HTML_QuickForm object.
    * 
    * @param array $cols A sequential array of DB_Table column definitions
    * from which to create form elements.
    * 
    * @param string $arrayName By default, the form will use the names
    * of the columns as the names of the form elements.  If you pass
    * $arrayName, the column names will become keys in an array named
    * for this parameter.
    * 
    * @param string $clientValidate By default, validation will match
    * the 'qf_client' value from the column definition.  However,
    * if you set $clientValidate to true or false, this will
    * override the value from the column definition.
    * 
    * @return void
    * 
    */
    
    function addRules(&$form, $cols, $arrayName = null,
        $clientValidate = null)
    {
        foreach ($cols as $name => $col) {
            
            if ($arrayName) {
                $elemname = $arrayName . "[$name]";
            } else {
                $elemname = $name;
            }
            
            // make sure all necessary elements are in place
            DB_Table_QuickForm::fixColDef($col, $elemname);
            
            // if clientValidate is specified, override the column
            // definition.  otherwise use the col def as it is.
            if (! is_null($clientValidate)) {
                // override
                if ($clientValidate) {
                    $validate = 'client';
                } else {
                    $validate = 'server';
                }
            } else {
                // use as-is
                if ($col['qf_client']) {
                    $validate = 'client';
                } else {
                    $validate = 'server';
                }
            }
            
            // **always** override these rules to make them 
            // server-side only.  suggested by Mark Wiesemann,
            // debugged by Hero Wanders.
            $onlyServer = array('filename', 'maxfilesize', 'mimetype',
                'uploadedfile');
            
            // loop through the rules and add them
            foreach ($col['qf_rules'] as $type => $opts) {
                
                // some rules (e.g. rules for file elements) can only be
                // checked on the server; therefore, don't use client-side
                // validation for these rules
                $ruleValidate = $validate;
                if (in_array($type, $onlyServer)) {
                    $ruleValidate = 'server';
                }
                
                switch ($type) {
                    
                case 'alphanumeric':
                case 'email':
                case 'lettersonly':
                case 'nonzero':
                case 'nopunctuation':
                case 'numeric':
                case 'required':
                case 'uploadedfile':
                    // $opts is the error message
                    $message = $opts;
                    $format = null;
                    break;
                
                case 'filename':
                case 'maxfilesize':
                case 'maxlength':
                case 'mimetype':
                case 'minlength':
                case 'regex':
                    // $opts[0] is the message
                    // $opts[1] is the size, mimetype, or regex
                    $message = $opts[0];
                    $format = $opts[1];
                    break;
                
                default:
                    // by Alex Hoebart: this should allow any registered rule.
                    if (!in_array($type, $form->getRegisteredRules())) {
                        // rule is not registered ==> do not add a rule
                        continue;
                    }
                    if (is_array($opts)) {
                        // $opts[0] is the message
                        // $opts[1] is the size or regex
                        $message = $opts[0];
                        $format = $opts[1];
                    } else {
                        // $opts is the error message
                        $message = $opts;
                        $format = null;
                    }
                    break;
                }
                
                switch ($col['qf_type']) {

                case 'date':
                case 'time':
                case 'timestamp':
                    // date "elements" are groups ==> use addGroupRule()
                    $form->addGroupRule($elemname, $message, $type, $format,
                        null, $ruleValidate);
                    break;

                default:  // use addRule() for all other elements
                    $form->addRule($elemname, $message, $type, $format,
                        $ruleValidate);
                    break;

                }

            }
        }
    }
    
    
    /**
    * 
    * "Fixes" a DB_Table column definition for QuickForm.
    * 
    * Makes it so that all the 'qf_*' key constants are populated
    * with appropriate default values; also checks the 'require'
    * value (if not set, defaults to false).
    * 
    * @static
    * 
    * @access public
    * 
    * @param array &$col A DB_Table column definition.
    * 
    * @param string $elemname The name for the target form element.
    * 
    * @return void
    * 
    */
    
    function fixColDef(&$col, $elemname)
    {    
        // always have a "require" value, false if not set
        if (! isset($col['require'])) {
            $col['require'] = false;
        }
        
        // array of acceptable values, typically for
        // 'select' or 'radio'
        if (! isset($col['qf_vals'])) {
            $col['qf_vals'] = null;
        }
        
        // are we doing client validation in addition to 
        // server validation?  by default, no.
        if (! isset($col['qf_client'])) {
            $col['qf_client'] = false;
        }

        if (! isset($col['qf_filters'])) {
            $col['qf_filters'] = array();
        }
        
        // the element type; if not set,
        // assigns an element type based on the column type.
        // by default, the type is 'text' (unless there are
        // values, in which case the type is 'select')
        if (! isset($col['qf_type'])) {
        
            // if $col['type'] is not set, set it to null
            // ==> in the switch statement below, the
            //     default case will be used
            if (!isset($col['type'])) {
                $col['type'] = null;
            }

            switch ($col['type']) {
            
            case 'boolean':
                $col['qf_type'] = 'checkbox';
                $col['qf_vals'] = array(0,1);
                break;
            
            case 'date':
                $col['qf_type'] = 'date';
                break;
                
            case 'time':
                $col['qf_type'] = 'time';
                break;
                
            case 'timestamp':
                $col['qf_type'] = 'timestamp';
                break;
                
            case 'clob':
                $col['qf_type'] = 'textarea';
                break;
                
            default:
                if (isset($col['qf_vals'])) {
                    $col['qf_type'] = 'select';
                } else {
                    $col['qf_type'] = 'text';
                }
                break;

            }
        }
        
        // label for the element; defaults to the element
        // name.  adds both quickform label and table-header
        // label if qf_label is not set.
        if (! isset($col['qf_label'])) {
            if (isset($col['label'])) {
                $col['qf_label'] = $col['label'];
            }
            else {
                $col['qf_label'] = $elemname . ':';
            }
        }
        
        // special options for the element, typically used
        // for 'date' element types
        if (! isset($col['qf_opts'])) {
            $col['qf_opts'] = array();
        }
        
        // array of additional HTML attributes for the element
        if (! isset($col['qf_attrs'])) {
            // setting to array() generates an error in HTML_Common
            $col['qf_attrs'] = null;
        }
        
        // array of QuickForm validation rules to apply
        if (! isset($col['qf_rules'])) {
            $col['qf_rules'] = array();
        }
        
        // if the element is hidden, then we're done
        // (adding rules to hidden elements is mostly useless)
        if ($col['qf_type'] == 'hidden') {
            return;
        }
        
        // code to keep BC for the separator for grouped QF elements
        if (isset($col['qf_radiosep'])) {
            $col['qf_groupsep'] = $col['qf_radiosep'];
        }

        // add a separator for grouped elements
        if (!isset($col['qf_groupsep'])) {
            $col['qf_groupsep'] = '<br />';
        }
        
        // $col['qf_set_default_rules'] === false allows to turn off
        // the automatic creation of QF rules for this "column"
        // (suggested by Arne Bippes)
        if (isset($col['qf_set_default_rules']) &&
                  $col['qf_set_default_rules'] === false) {
            return;
        }        

        // the element is required
        // ==> set 'uploadedfile' (for file elements) or 'required' (for all
        // other elements) rule if it is was not already set
        $req_rule_name = ($col['qf_type'] == 'file') ? 'uploadedfile' : 'required';
        if (!isset($col['qf_rules'][$req_rule_name]) && $col['require']) {

            $col['qf_rules'][$req_rule_name] = sprintf(
                $GLOBALS['_DB_TABLE']['qf_rules']['required'],
                $col['qf_label']
            );

        }

        // for file elements the 'numeric' and 'maxlength' rules must not be set
        if ($col['qf_type'] == 'file') {
            return;
        }

        $numeric = array('smallint', 'integer', 'bigint', 'decimal', 
            'single', 'double');

        // the element is numeric
        if (!isset($col['qf_rules']['numeric']) && isset($col['type']) &&
            in_array($col['type'], $numeric)) {

            $col['qf_rules']['numeric'] = sprintf(
                $GLOBALS['_DB_TABLE']['qf_rules']['numeric'],
                $col['qf_label']
            );

        }

        // the element has a maximum length
        if (!isset($col['qf_rules']['maxlength']) && isset($col['size'])) {

            $max = $col['size'];

            $msg = sprintf(
                $GLOBALS['_DB_TABLE']['qf_rules']['maxlength'],
                $col['qf_label'],
                $max
            );

            $col['qf_rules']['maxlength'] = array($msg, $max);
        }
    }
}

?>
