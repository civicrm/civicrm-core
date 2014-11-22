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
 * URL Value Object allowing easy concatenation.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <giorgio.sironi@asp-poli.it>
 * @copyright  2010-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.2.6
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.0
 */
final class PHPUnit_Extensions_Selenium2TestCase_URL
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param string $host
     * @param int port
     * @return PHPUnit_Extensions_Selenium2TestCase_URL
     */
    public static function fromHostAndPort($host, $port)
    {
        return new self("http://{$host}:{$port}");
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * @param string $addition
     * @return PHPUnit_Extensions_Selenium2TestCase_URL
     */
    public function descend($addition)
    {
        $newValue = rtrim($this->value, '/')
                  . '/'
                  . ltrim($addition, '/');
        return new self($newValue);
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_URL
     */
    public function ascend()
    {
        $lastSlash = strrpos($this->value, "/");
        $newValue = substr($this->value, 0, $lastSlash);
        return new self($newValue);
    }

    /**
     * @return string
     */
    public function lastSegment()
    {
        $segments = explode('/', $this->value);
        return end($segments);
    }

    /**
     * @param string $command
     * @return PHPUnit_Extensions_Selenium2TestCase_URL
     */
    public function addCommand($command)
    {
        return $this->descend($this->camelCaseToUnderScores($command));
    }

    /**
     * @param string $newUrl
     * @return PHPUnit_Extensions_Selenium2TestCase_URL
     */
    public function jump($newUrl)
    {
        if ($this->isAbsolute($newUrl)) {
            return new self($newUrl);
        } else {
            return $this->descend($newUrl);
        }
    }

    private function camelCaseToUnderScores($string)
    {
        $string = preg_replace('/([A-Z]{1,1})/', ' \1', $string);
        $string = strtolower($string);
        return str_replace(' ', '_', $string);
    }

    private function isAbsolute($urlValue)
    {
        return preg_match('/^(http|https):\/\//', $urlValue) > 0;
    }
}
