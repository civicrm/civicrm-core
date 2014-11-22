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
 * @since      File available since Release 1.2.2
 */

/**
 * Object representing a <select> element.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <giorgio.sironi@asp-poli.it>
 * @copyright  2010-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.2.6
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.2
 */
class PHPUnit_Extensions_Selenium2TestCase_Element_Select
    extends PHPUnit_Extensions_Selenium2TestCase_Element
{
    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Element_Select
     */
    public static function fromElement(PHPUnit_Extensions_Selenium2TestCase_Element $element)
    {
        return new self($element->driver, $element->url);
    }

    /**
     * @return string
     */
    public function selectedLabel()
    {
        return $this->selectedOption()->text();
    }

    /**
     * @return string
     */
    public function selectedValue()
    {
        return $this->selectedOption()->value();
    }

    /**
     * @param string $label the text appearing in the option
     * @return void
     */
    public function selectOptionByLabel($label)
    {
        $toSelect = $this->criteria('xpath')->value("//option[.='$label']");
        $this->selectOptionByCriteria($toSelect);
    }

    /**
     * @param string $value the value attribute of the option
     * @return void
     */
    public function selectOptionByValue($value)
    {
        $toSelect = $this->criteria('xpath')->value("//option[@value='$value']");
        $this->selectOptionByCriteria($toSelect);
    }

    /**
     * @param PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $localCriteria  condiotions for selecting an option
     * @return void
     */
    public function selectOptionByCriteria(PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $localCriteria)
    {
        $option = $this->element($localCriteria);
        $option->click();
    }

    private function selectedOption()
    {
        foreach ($this->options() as $option) {
            if ($option->selected()) {
                return $option;
            }
        }
    }

    private function options()
    {
        $onlyTheOptions = $this->criteria('css selector')->value('option');
        return $this->elements($onlyTheOptions);
    }
}
