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

namespace League\Csv;

use OutOfRangeException;
use php_user_filter;
use Traversable;
use TypeError;
use function array_combine;
use function array_map;
use function array_walk;
use function gettype;
use function in_array;
use function is_iterable;
use function is_numeric;
use function mb_convert_encoding;
use function mb_list_encodings;
use function preg_match;
use function sprintf;
use function stream_bucket_append;
use function stream_bucket_make_writeable;
use function stream_filter_register;
use function stream_get_filters;
use function strpos;
use function strtolower;
use function substr;

/**
 * Converts resource stream or tabular data content charset.
 */
class CharsetConverter extends php_user_filter
{
    const FILTERNAME = 'convert.league.csv';

    /**
     * the filter name used to instantiate the class with.
     *
     * @var string
     */
    public $filtername;

    /**
     * Contents of the params parameter passed to stream_filter_append
     * or stream_filter_prepend functions.
     *
     * @var mixed
     */
    public $params;

    /**
     * The records input encoding charset.
     *
     * @var string
     */
    protected $input_encoding = 'UTF-8';

    /**
     * The records output encoding charset.
     *
     * @var string
     */
    protected $output_encoding = 'UTF-8';

    /**
     * Static method to add the stream filter to a {@link AbstractCsv} object.
     */
    public static function addTo(AbstractCsv $csv, string $input_encoding, string $output_encoding): AbstractCsv
    {
        self::register();

        return $csv->addStreamFilter(self::getFiltername($input_encoding, $output_encoding));
    }

    /**
     * Static method to register the class as a stream filter.
     */
    public static function register()
    {
        $filtername = self::FILTERNAME.'.*';
        if (!in_array($filtername, stream_get_filters(), true)) {
            stream_filter_register($filtername, self::class);
        }
    }

    /**
     * Static method to return the stream filter filtername.
     */
    public static function getFiltername(string $input_encoding, string $output_encoding): string
    {
        return sprintf(
            '%s.%s/%s',
            self::FILTERNAME,
            self::filterEncoding($input_encoding),
            self::filterEncoding($output_encoding)
        );
    }

    /**
     * Filter encoding charset.
     *
     * @throws OutOfRangeException if the charset is malformed or unsupported
     */
    protected static function filterEncoding(string $encoding): string
    {
        static $encoding_list;
        if (null === $encoding_list) {
            $list = mb_list_encodings();
            $encoding_list = array_combine(array_map('strtolower', $list), $list);
        }

        $key = strtolower($encoding);
        if (isset($encoding_list[$key])) {
            return $encoding_list[$key];
        }

        throw new OutOfRangeException(sprintf('The submitted charset %s is not supported by the mbstring extension', $encoding));
    }

    /**
     * {@inheritdoc}
     */
    public function onCreate()
    {
        $prefix = self::FILTERNAME.'.';
        if (0 !== strpos($this->filtername, $prefix)) {
            return false;
        }

        $encodings = substr($this->filtername, strlen($prefix));
        if (!preg_match(',^(?<input>[-\w]+)\/(?<output>[-\w]+)$,', $encodings, $matches)) {
            return false;
        }

        try {
            $this->input_encoding = $this->filterEncoding($matches['input']);
            $this->output_encoding = $this->filterEncoding($matches['output']);
            return true;
        } catch (OutOfRangeException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($res = stream_bucket_make_writeable($in)) {
            $res->data = @mb_convert_encoding($res->data, $this->output_encoding, $this->input_encoding);
            $consumed += $res->datalen;
            stream_bucket_append($out, $res);
        }

        return PSFS_PASS_ON;
    }

    /**
     * Convert Csv records collection into UTF-8.
     *
     * @param array|Traversable $records
     *
     * @return array|Traversable
     */
    public function convert($records)
    {
        if (!is_iterable($records)) {
            throw new TypeError(sprintf('%s() expects argument passed to be iterable, %s given', __METHOD__, gettype($records)));
        }

        if ($this->output_encoding === $this->input_encoding) {
            return $records;
        }

        if (is_array($records)) {
            return array_map($this, $records);
        }

        return new MapIterator($records, $this);
    }

    /**
     * Enable using the class as a formatter for the {@link Writer}.
     */
    public function __invoke(array $record): array
    {
        array_walk($record, [$this, 'encodeField']);

        return $record;
    }

    /**
     * Walker method to convert the offset and the value of a CSV record field.
     *
     * @param mixed $value
     * @param mixed $offset
     */
    protected function encodeField(&$value, &$offset)
    {
        if (null !== $value && !is_numeric($value)) {
            $value = mb_convert_encoding((string) $value, $this->output_encoding, $this->input_encoding);
        }

        if (!is_numeric($offset)) {
            $offset = mb_convert_encoding((string) $offset, $this->output_encoding, $this->input_encoding);
        }
    }

    /**
     * Sets the records input encoding charset.
     */
    public function inputEncoding(string $encoding): self
    {
        $encoding = $this->filterEncoding($encoding);
        if ($encoding === $this->input_encoding) {
            return $this;
        }

        $clone = clone $this;
        $clone->input_encoding = $encoding;

        return $clone;
    }

    /**
     * Sets the records output encoding charset.
     */
    public function outputEncoding(string $encoding): self
    {
        $encoding = $this->filterEncoding($encoding);
        if ($encoding === $this->output_encoding) {
            return $this;
        }

        $clone = clone $this;
        $clone->output_encoding = $encoding;

        return $clone;
    }
}
