<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class for a group of elements used to input dates (and times).
 * 
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: date.php,v 1.62 2009/04/04 21:34:02 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * Class for a group of form elements
 */
require_once 'HTML/QuickForm/group.php';
/**
 * Class for <select></select> elements
 */
require_once 'HTML/QuickForm/select.php';

/**
 * Class for a group of elements used to input dates (and times).
 * 
 * Inspired by original 'date' element but reimplemented as a subclass
 * of HTML_QuickForm_group
 * 
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 3.2.11
 * @since       3.1
 */
class HTML_QuickForm_date extends HTML_QuickForm_group
{
    // {{{ properties

   /**
    * Various options to control the element's display.
    * 
    * @access   private
    * @var      array
    */
    var $_options = array(
        'format'           => 'dMY',
        'minYear'          => 2001,
        'maxYear'          => 2012,
        'addEmptyOption'   => false,
        'emptyOptionValue' => '',
        'emptyOptionText'  => '&nbsp;',
        'optionIncrement'  => array('i' => 1, 's' => 1)
    );

   /**
    * These complement separators, they are appended to the resultant HTML
    * @access   private
    * @var      array
    */
    var $_wrap = array('', '');

   /**
    * Locale array build from CRM_Utils_Date-provided names
    * 
    * @access   private
    * @var      array
    */
    var $_locale = array();

    // }}}
    // {{{ constructor

   /**
    * Class constructor
    * 
    * The following keys may appear in $options array:
    * - 'language': date language
    * - 'format': Format of the date, based on PHP's date() function.
    *   The following characters are currently recognised in format string:
    *   <pre>  
    *       D => Short names of days
    *       l => Long names of days
    *       d => Day numbers
    *       M => Short names of months
    *       F => Long names of months
    *       m => Month numbers
    *       Y => Four digit year
    *       y => Two digit year
    *       h => 12 hour format
    *       H => 23 hour  format
    *       i => Minutes
    *       s => Seconds
    *       a => am/pm
    *       A => AM/PM
    *   </pre>
    * - 'minYear': Minimum year in year select
    * - 'maxYear': Maximum year in year select
    * - 'addEmptyOption': Should an empty option be added to the top of
    *    each select box?
    * - 'emptyOptionValue': The value passed by the empty option.
    * - 'emptyOptionText': The text displayed for the empty option.
    * - 'optionIncrement': Step to increase the option values by (works for 'i' and 's')
    *
    * @access   public
    * @param    string  Element's name
    * @param    mixed   Label(s) for an element
    * @param    array   Options to control the element's display
    * @param    mixed   Either a typical HTML attribute string or an associative array
    */
    function HTML_QuickForm_date($elementName = null, $elementLabel = null, $options = array(), $attributes = null)
    {
        $this->_locale = array( 
                               'weekdays_short'=> CRM_Utils_Date::getAbbrWeekdayNames(), 
                               'weekdays_long' => CRM_Utils_Date::getFullWeekdayNames(), 
                               'months_short'  => CRM_Utils_Date::getAbbrMonthNames(), 
                               'months_long'   => CRM_Utils_Date::getFullMonthNames() 
                               );
        $this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_appendName = true;
        $this->_type = 'date';
        // set the options, do not bother setting bogus ones
        if (is_array($options)) {
            foreach ($options as $name => $value) {
                if (isset($this->_options[$name])) {
                    if (is_array($value) && is_array($this->_options[$name])) {
                        $this->_options[$name] = @array_merge($this->_options[$name], $value);
                    } else {
                        $this->_options[$name] = $value;
                    }
                }
            }
        }
    }

    // }}}
    // {{{ _createElements()

    function _createElements()
    {
        $this->_separator = $this->_elements = array();
        $separator =  '';
        $locale    =& $this->_locale;
        $backslash =  false;
        for ($i = 0, $length = strlen($this->_options['format']); $i < $length; $i++) {
            $sign = $this->_options['format']{$i};
            if ($backslash) {
                $backslash  = false;
                $separator .= $sign;
            } else {
                $loadSelect = true;
                switch ($sign) {
                    case 'D':
                        // Sunday is 0 like with 'w' in date()
                        $options = $locale['weekdays_short'];
                        $emptyText = ts('-day of week-');
                        break;
                    case 'l':
                        $options = $locale['weekdays_long'];
                        $emptyText = ts('-day of week-');
                        break;
                    case 'd':
                        $options = $this->_createOptionList(1, 31);
                        $emptyText = ts('-day-');
                        break;
                    case 'j':
                        // the no-zero-padding option (CRM-2793)
                        $options = $this->_createOptionList(1, 31, 1, false);
                        $emptyText = ts('-day-');
                        break;
                    case 'M':
                        $options = $locale['months_short'];
                        array_unshift($options , '');
                        unset($options[0]);
                        $emptyText = ts('-month-');
                        break;
                    case 'm':
                        $options = $this->_createOptionList(1, 12);
                        $emptyText = ts('-month-');
                        break;
                    case 'F':
                        $options = $locale['months_long'];
                        array_unshift($options , '');
                        unset($options[0]);
                        $emptyText = ts('-month-');
                        break;
                    case 'Y':
                        $options = $this->_createOptionList(
                            $this->_options['minYear'],
                            $this->_options['maxYear'], 
                            $this->_options['minYear'] > $this->_options['maxYear']? -1: 1
                        );
                        $emptyText = ts('-year-');
                        break;
                    case 'y':
                        $options = $this->_createOptionList(
                            $this->_options['minYear'],
                            $this->_options['maxYear'],
                            $this->_options['minYear'] > $this->_options['maxYear']? -1: 1
                        );
                        array_walk($options, create_function('&$v,$k','$v = substr($v,-2);')); 
                        $emptyText = ts('-year-');
                        break;
                    case 'h':
                        $options = $this->_createOptionList(1, 12);
                        $emptyText = ts('-hour-');
                        break;
                    case 'g':
                        $options = $this->_createOptionList(1, 12);
                        array_walk($options, create_function('&$v,$k', '$v = intval($v);'));
                        break;
                    case 'H':
                        $options = $this->_createOptionList(0, 23);
                        $emptyText = ts('-hour-');
                        break;
                    case 'i':
                        $options = $this->_createOptionList(0, 59, $this->_options['optionIncrement']['i']);
                        $emptyText = ts('-min-');
                        break;
                    case 's':
                        $options = $this->_createOptionList(0, 59, $this->_options['optionIncrement']['s']);
                        $emptyText = ts('-sec-');
                        break;
                    case 'a':
                        $options = array('am' => 'am', 'pm' => 'pm');
                        $emptyText = '-am/pm-';
                        break;
                    case 'A':
                        $options = array('AM' => 'AM', 'PM' => 'PM');
                        $emptyText = '-AM/PM-';
                        break;
                    case 'W':
                        $options = $this->_createOptionList(1, 53);
                        break;
                    case '\\':
                        $backslash  = true;
                        $loadSelect = false;
                        break;
                    default:
                        $separator .= (' ' == $sign? '&nbsp;': $sign);
                        $loadSelect = false;
                }
    
                if ($loadSelect) {
                    if (0 < count($this->_elements)) {
                        $this->_separator[] = $separator;
                    } else {
                        $this->_wrap[0] = $separator;
                    }
                    $separator = '';
                    // Should we add an empty option to the top of the select?
                    if (!is_array($this->_options['addEmptyOption']) && $this->_options['addEmptyOption'] || 
                        is_array($this->_options['addEmptyOption']) && !empty($this->_options['addEmptyOption'][$sign])) {

                        // Using '+' array operator to preserve the keys
                        if (is_array($this->_options['emptyOptionText']) && !empty($this->_options['emptyOptionText'][$sign])) {
                            $text = $emptyText ? $emptyText : $this->_options['emptyOptionText'][$sign];
                            $options = array($this->_options['emptyOptionValue'] => $text) + $options;
                        } else {
                            $text = $emptyText ? $emptyText : $this->_options['emptyOptionText'];
                            $options = array($this->_options['emptyOptionValue'] => $text) + $options;
                        }
                    }
                  
                    //modified autogenerated id for date select boxes.
                    $attribs = $this->getAttributes();
                    $elementName = $this->getName();
                    $attribs['id'] = $elementName.'['.$sign.']';
                    
                    $this->_elements[] = new HTML_QuickForm_select($sign, null, $options, $attribs);
                }
            }
        }
        $this->_wrap[1] = $separator . ($backslash? '\\': '');
    }

    // }}}
    // {{{ _createOptionList()

   /**
    * Creates an option list containing the numbers from the start number to the end, inclusive
    *
    * @param    int     The start number
    * @param    int     The end number
    * @param    int     Increment by this value
    * @param    bool    Whether to pad the result with leading zero (CRM-2793)
    * @access   private
    * @return   array   An array of numeric options.
    */
    function _createOptionList($start, $end, $step = 1, $pad = true)
    {
        for ($i = $start, $options = array(); $start > $end? $i >= $end: $i <= $end; $i += $step) {
            $options[$i] = $pad ? sprintf('%02d', $i) : sprintf('%d', $i);
        }
        return $options;
    }

    // }}}
    // {{{ _trimLeadingZeros()

   /**
    * Trims leading zeros from the (numeric) string
    *
    * @param    string  A numeric string, possibly with leading zeros
    * @return   string  String with leading zeros removed
    */
    function _trimLeadingZeros($str)
    {
        if (0 == strcmp($str, $this->_options['emptyOptionValue'])) {
            return $str;
        }
        $trimmed = ltrim($str, '0');
        return strlen($trimmed)? $trimmed: '0';
    }

    // }}}
    // {{{ setValue()

    function setValue($value)
    {
        if (empty($value)) {
            $value = array();
        } elseif (is_scalar($value)) {
            if (!is_numeric($value)) {
                $value = strtotime($value);
            }
            // might be a unix epoch, then we fill all possible values
            $arr = explode('-', date('w-j-n-Y-g-G-i-s-a-A-W', (int)$value));
            $value = array(
                'D' => $arr[0],
                'l' => $arr[0],
                'd' => $arr[1],
                'M' => $arr[2],
                'm' => $arr[2],
                'F' => $arr[2],
                'Y' => $arr[3],
                'y' => $arr[3],
                'h' => $arr[4],
                'g' => $arr[4],
                'H' => $arr[5],
                'i' => $this->_trimLeadingZeros($arr[6]),
                's' => $this->_trimLeadingZeros($arr[7]),
                'a' => $arr[8],
                'A' => $arr[9],
                'W' => $this->_trimLeadingZeros($arr[10])
            );
        } else {
            $value = array_map(array($this, '_trimLeadingZeros'), $value);
        }
        parent::setValue($value);
    }

    // }}}
    // {{{ toHtml()

    function toHtml()
    {
        include_once('HTML/QuickForm/Renderer/Default.php');
        $renderer = new HTML_QuickForm_Renderer_Default();
        $renderer->setElementTemplate('{element}');
        parent::accept($renderer);
        return $this->_wrap[0] . $renderer->toHtml() . $this->_wrap[1];
    }

    // }}}
    // {{{ accept()

    function accept(&$renderer, $required = false, $error = null)
    {
        $renderer->renderElement($this, $required, $error);
    }

    // }}}
    // {{{ onQuickFormEvent()

    function onQuickFormEvent($event, $arg, &$caller)
    {
        if ('updateValue' == $event) {
            // we need to call setValue(), 'cause the default/constant value
            // may be in fact a timestamp, not an array
            return HTML_QuickForm_element::onQuickFormEvent($event, $arg, $caller);
        } else {
            return parent::onQuickFormEvent($event, $arg, $caller);
        }
    }

    // }}}
}
?>
