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
 * Browser session for Selenium 2: main point of entry for functionality.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <giorgio.sironi@asp-poli.it>
 * @copyright  2010-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.2.6
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.0
 */
class PHPUnit_Extensions_Selenium2TestCase_Session
    extends PHPUnit_Extensions_Selenium2TestCase_CommandsHolder
{
    /**
     * @var string  the base URL for this session,
     *              which all relative URLs will refer to
     */
    private $baseUrl;

    /**
     * @var boolean
     */
    private $stopped = FALSE;

    public function __construct($driver,
                                PHPUnit_Extensions_Selenium2TestCase_URL $url,
                                PHPUnit_Extensions_Selenium2TestCase_URL $baseUrl)
    {
        $this->baseUrl = $baseUrl;
        parent::__construct($driver, $url);
    }

    protected function initCommands()
    {
        $baseUrl = $this->baseUrl;
        return array(
            'acceptAlert' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_AcceptAlert',
            'alertText' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_AlertText',
            'back' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'dismissAlert' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_DismissAlert',
            'execute' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'executeAsync' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'forward' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'frame' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Frame',
            'refresh' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'screenshot' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'source' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_GenericAccessor',
            'title' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_GenericAccessor',
            'url' => function ($jsonParameters, $commandUrl) use ($baseUrl) {
                return new PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Url($jsonParameters, $commandUrl, $baseUrl);
            },
            'window' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Window',
            'windowHandle' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_GenericAccessor',
            'windowHandles' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_GenericAccessor'
        );
    }

    /**
     * @params string $commandClass     a class name, descending from
                                        PHPUnit_Extensions_Selenium2TestCase_Command
     * @return callable
     */
    private function factoryMethod($commandClass)
    {
        return function($jsonParameters, $url) use ($commandClass) {
            return new $commandClass($jsonParameters, $url);
        };
    }

    public function __destruct()
    {
        $this->stop();
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_URL
     */
    public function getSessionUrl()
    {
        return $this->url;
    }

    /**
     * Closed the browser.
     * @return void
     */
    public function stop()
    {
        if ($this->stopped) {
            return;
        }
        $this->driver->curl('DELETE', $this->url);
        $this->stopped = TRUE;
    }

    /**
     * @param string $value     e.g. 'container'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byClassName($value)
    {
        return $this->by('class name', $value);
    }

    /**
     * @param string $value     e.g. 'div.container'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byCssSelector($value)
    {
        return $this->by('css selector', $value);
    }

    /**
     * @param string $value     e.g. 'uniqueId'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byId($value)
    {
        return $this->by('id', $value);
    }

    /**
     * @param string $value     e.g. 'email_address'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byName($value)
    {
        return $this->by('name', $value);
    }

    /**
     * @param string $value     e.g. '/div[@attribute="value"]'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byXPath($value)
    {
        return $this->by('xpath', $value);
    }

    /**
     * @param string $strategy     supported by JsonWireProtocol element/ command
     * @param string $value
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    protected function by($strategy, $value)
    {
        return $this->element($this->using($strategy)->value($value));
    }

    /**
     * @param string $strategy
     * @return PHPUnit_Extensions_Selenium2TestCase_ElementCriteria
     */
    public function using($strategy)
    {
        return new PHPUnit_Extensions_Selenium2TestCase_ElementCriteria($strategy);
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function element(PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria)
    {
        $value = $this->postCommand('element', $criteria);
        return PHPUnit_Extensions_Selenium2TestCase_Element::fromResponseValue($value,
                                                                               $this->url->descend('element'),
                                                                               $this->driver);
    }

    /**
     * @return array    instances of PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function elements(PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria)
    {
        $values = $this->postCommand('elements', $criteria);
        $elements = array();
        foreach ($values as $value) {
            $elements[] = PHPUnit_Extensions_Selenium2TestCase_Element::fromResponseValue($value, $this->url->descend('element'), $this->driver);
        }
        return $elements;
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Element_Select
     */
    public function select(PHPUnit_Extensions_Selenium2TestCase_Element $element)
    {
        return PHPUnit_Extensions_Selenium2TestCase_Element_Select::fromElement($element);
    }

    /**
     * @param string $id    id attribute, e.g. 'container'
     * @return void
     */
    public function clickOnElement($id)
    {
        return $this->element($this->using('id')->value($id))->click();
    }

    public function timeouts()
    {
        return new PHPUnit_Extensions_Selenium2TestCase_Session_Timeouts($this->driver,
                                                                         $this->url->descend('timeouts'));
    }

    /**
     * @return string   a BLOB of a PNG file
     */
    public function currentScreenshot()
    {
        return base64_decode($this->screenshot());
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Window
     */
    public function currentWindow()
    {
        $url = $this->url->descend('window')->descend($this->windowHandle());
        return new PHPUnit_Extensions_Selenium2TestCase_Window($this->driver, $url);
    }

    public function closeWindow()
    {
        $this->driver->curl('DELETE', $this->url->descend('window'));
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Session_Cookie
     */
    public function cookie()
    {
        $url = $this->url->descend('cookie');
        return new PHPUnit_Extensions_Selenium2TestCase_Session_Cookie($this->driver, $url);
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Session_Storage
     */
    public function localStorage()
    {
        $url = $this->url->addCommand('localStorage');
        return new PHPUnit_Extensions_Selenium2TestCase_Session_Storage($this->driver, $url);
    }
}
