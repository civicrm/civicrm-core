<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2010-2011, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <giorgio.sironi@asp-poli.it>
 * @copyright  2010-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.2.0
 */

/**
 * Object representing a DOM element.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <giorgio.sironi@asp-poli.it>
 * @copyright  2010-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.2.6
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.0
 * @method string attribute($name) Retrieves an element's attribute
 * @method void clear() Empties the content of a form element.
 * @method void click() 
 * @method string css($propertyName) Retrieves the value of a CSS property
 * @method bool displayed() Checks an element's visibility
 * @method bool enabled() Checks a form element's state
 * @method bool equals(PHPUnit_Extensions_Selenium2TestCase_Element $another) Checks if the two elements are the same on the page
 * @method array location() Retrieves the element's position in the page: keys 'x' and 'y' in the returned array
 * @method string name() Retrieves the tag name
 * @method bool selected() Checks the state of an option or other form element
 * @method array size() Retrieves the dimensions of the element: 'width' and 'height' of the returned array
 * @method void submit() Submits a form; can be called on its children
 * @method string value($newValue = NULL) Get or set value of form elements
 * @method string text() Get content of ordinary elements
 */
class PHPUnit_Extensions_Selenium2TestCase_Element
    extends PHPUnit_Extensions_Selenium2TestCase_CommandsHolder
{
    /**
     * @return integer
     */
    public function getId()
    {
        return $this->url->lastSegment();
    }

    /**
     * @return array    class names
     */
    protected function initCommands()
    {
        return array(
            'attribute' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_Attribute',
            'clear' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'click' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_Click',
            'css' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_Css',
            'displayed' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'enabled' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'equals' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_Equals',
            'location' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'name' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'selected' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'size' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'submit' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'text' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'value' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_Value'
        );
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function element(PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria)
    {
        $value = $this->postCommand('element', $criteria);
        return self::fromResponseValue($value, $this->url->ascend(), $this->driver);
    }

    /**
     * @return array    instances of PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function elements(PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria)
    {
        $values = $this->postCommand('elements', $criteria);
        $elements = array();
        foreach ($values as $value) {
            $elements[] = self::fromResponseValue($value, $this->url->ascend(), $this->driver);
        }
        return $elements;
    }

    public static function fromResponseValue(array $value, PHPUnit_Extensions_Selenium2TestCase_URL $parentFolder, PHPUnit_Extensions_Selenium2TestCase_Driver $driver)
    {
        if (!isset($value['ELEMENT'])) {
            throw new InvalidArgumentException('Element not found.');
        }
        $url = $parentFolder->descend($value['ELEMENT']);
        return new self($driver, $url);
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_ElementCriteria
     */
    protected function criteria($using)
    {
        return new PHPUnit_Extensions_Selenium2TestCase_ElementCriteria($using);
    }
}
