<?php

/**
 * League.Csv (https://csv.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Csv\Polyfill;

use Generator;
use League\Csv\Stream;
use SplFileObject;
use TypeError;
use function explode;
use function get_class;
use function gettype;
use function in_array;
use function is_object;
use function ltrim;
use function rtrim;
use function sprintf;
use function str_replace;
use function substr;

/**
 * A Polyfill to PHP's SplFileObject to enable parsing the CSV document
 * without taking into account the escape character.
 *
 * @see https://php.net/manual/en/function.fgetcsv.php
 * @see https://php.net/manual/en/function.fgets.php
 * @see https://tools.ietf.org/html/rfc4180
 * @see http://edoceo.com/utilitas/csv-file-format
 *
 * @internal used internally to parse a CSV document without using the escape character
 */
final class EmptyEscapeParser
{
    /**
     * @internal
     */
    const FIELD_BREAKS = [false, '', "\r\n", "\n", "\r"];

    /**
     * @var SplFileObject|Stream
     */
    private static $document;

    /**
     * @var string
     */
    private static $delimiter;

    /**
     * @var string
     */
    private static $enclosure;

    /**
     * @var string
     */
    private static $trim_mask;

    /**
     * @var string|bool
     */
    private static $line;

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Converts the document into a CSV record iterator.
     *
     * In PHP7.4+ you'll be able to do
     *
     * <code>
     * $file = new SplFileObject('/path/to/file.csv', 'r');
     * $file->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);
     * $file->setCsvControl($delimiter, $enclosure, '');
     * foreach ($file as $record) {
     *    //$record escape mechanism is blocked by the empty string
     * }
     * </code>
     *
     * In PHP7.3- you can do
     *
     * <code>
     * $file = new SplFileObject('/path/to/file.csv', 'r');
     * $it = EmptyEscapeParser::parse($file); //parsing will be done while ignoring the escape character value.
     * foreach ($it as $record) {
     *    //fgetcsv is not directly use hence the escape char is not taken into account
     * }
     * </code>
     *
     * Each record array contains strings elements.
     *
     * @param SplFileObject|Stream $document
     *
     * @return Generator|array[]
     */
    public static function parse($document): Generator
    {
        self::$document = self::filterDocument($document);
        list(self::$delimiter, self::$enclosure, ) = self::$document->getCsvControl();
        self::$trim_mask = str_replace([self::$delimiter, self::$enclosure], '', " \t\0\x0B");
        self::$document->setFlags(0);
        self::$document->rewind();
        while (self::$document->valid()) {
            $record = self::extractRecord();
            if (!in_array(null, $record, true)) {
                yield $record;
            }
        }
    }

    /**
     * Filters the submitted document.
     *
     * @param SplFileObject|Stream $document
     *
     * @return SplFileObject|Stream
     */
    private static function filterDocument($document)
    {
        if ($document instanceof Stream || $document instanceof SplFileObject) {
            return $document;
        }

        throw new TypeError(sprintf(
            '%s::parse expects parameter 1 to be a %s or a SplFileObject object, %s given',
            self::class,
            Stream::class,
            is_object($document) ? get_class($document) : gettype($document)
        ));
    }

    /**
     * Extracts a record form the CSV document.
     */
    private static function extractRecord(): array
    {
        $record = [];
        self::$line = self::$document->fgets();
        do {
            $method = 'extractFieldContent';
            $buffer = ltrim(self::$line, self::$trim_mask);
            if (($buffer[0] ?? '') === self::$enclosure) {
                $method = 'extractEnclosedFieldContent';
                self::$line = $buffer;
            }

            $record[] = self::$method();
        } while (false !== self::$line);

        return $record;
    }

    /**
     * Extracts the content from a field without enclosure.
     *
     * - Field content can not spread on multiple document lines.
     * - Content must be preserved.
     * - Trailing line-breaks must be removed.
     *
     * @return string|null
     */
    private static function extractFieldContent()
    {
        if (in_array(self::$line, self::FIELD_BREAKS, true)) {
            self::$line = false;

            return null;
        }

        list($content, self::$line) = explode(self::$delimiter, self::$line, 2) + [1 => false];
        if (false === self::$line) {
            return rtrim($content, "\r\n");
        }

        return $content;
    }

    /**
     * Extracts the content from a field with enclosure.
     *
     * - Field content can spread on multiple document lines.
     * - Content between consecutive enclosure characters must be preserved.
     * - Double enclosure sequence must be replaced by single enclosure character.
     * - Trailing line break must be removed if they are not part of the field content.
     * - Invalid field content is treated as per fgetcsv behavior.
     *
     * @return string|null
     */
    private static function extractEnclosedFieldContent()
    {
        if ((self::$line[0] ?? '') === self::$enclosure) {
            self::$line = substr(self::$line, 1);
        }

        $content = '';
        while (false !== self::$line) {
            list($buffer, $remainder) = explode(self::$enclosure, self::$line, 2) + [1 => false];
            $content .= $buffer;
            self::$line = $remainder;
            if (false !== self::$line) {
                break;
            }

            if (self::$document->valid()) {
                self::$line = self::$document->fgets();
                continue;
            }

            if ($buffer === rtrim($content, "\r\n")) {
                return null;
            }
        }

        if (in_array(self::$line, self::FIELD_BREAKS, true)) {
            self::$line = false;
            if (!self::$document->valid()) {
                return $content;
            }

            return rtrim($content, "\r\n");
        }

        $char = self::$line[0] ?? '';
        if ($char === self::$delimiter) {
            self::$line = substr(self::$line, 1);

            return $content;
        }

        if ($char === self::$enclosure) {
            return $content.self::$enclosure.self::extractEnclosedFieldContent();
        }

        return $content.self::extractFieldContent();
    }
}
