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

use BadMethodCallException;
use CallbackFilterIterator;
use Countable;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use League\Csv\Polyfill\EmptyEscapeParser;
use SplFileObject;
use TypeError;
use function array_combine;
use function array_filter;
use function array_pad;
use function array_slice;
use function array_unique;
use function gettype;
use function is_array;
use function iterator_count;
use function iterator_to_array;
use function mb_strlen;
use function mb_substr;
use function sprintf;
use function strlen;
use function substr;
use const PHP_VERSION_ID;
use const STREAM_FILTER_READ;

/**
 * A class to parse and read records from a CSV document.
 *
 * @method array fetchOne(int $nth_record = 0) Returns a single record from the CSV
 * @method Generator fetchColumn(string|int $column_index) Returns the next value from a single CSV record field
 * @method Generator fetchPairs(string|int $offset_index = 0, string|int $value_index = 1) Fetches the next key-value pairs from the CSV document
 */
class Reader extends AbstractCsv implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * header offset.
     *
     * @var int|null
     */
    protected $header_offset;

    /**
     * header record.
     *
     * @var string[]
     */
    protected $header = [];

    /**
     * records count.
     *
     * @var int
     */
    protected $nb_records = -1;

    /**
     * {@inheritdoc}
     */
    protected $stream_filter_mode = STREAM_FILTER_READ;

    /**
     * {@inheritdoc}
     */
    public static function createFromPath(string $path, string $open_mode = 'r', $context = null)
    {
        return parent::createFromPath($path, $open_mode, $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function resetProperties()
    {
        parent::resetProperties();
        $this->nb_records = -1;
        $this->header = [];
    }

    /**
     * Returns the header offset.
     *
     * If no CSV header offset is set this method MUST return null
     *
     * @return int|null
     */
    public function getHeaderOffset()
    {
        return $this->header_offset;
    }

    /**
     * Returns the CSV record used as header.
     *
     * The returned header is represented as an array of string values
     *
     * @return string[]
     */
    public function getHeader(): array
    {
        if (null === $this->header_offset) {
            return $this->header;
        }

        if ([] !== $this->header) {
            return $this->header;
        }

        $this->header = $this->setHeader($this->header_offset);

        return $this->header;
    }

    /**
     * Determine the CSV record header.
     *
     * @throws Exception If the header offset is set and no record is found or is the empty array
     *
     * @return string[]
     */
    protected function setHeader(int $offset): array
    {
        $header = $this->seekRow($offset);
        if (false === $header || [] === $header) {
            throw new Exception(sprintf('The header record does not exist or is empty at offset: `%s`', $offset));
        }

        if (0 === $offset) {
            return $this->removeBOM($header, mb_strlen($this->getInputBOM()), $this->enclosure);
        }

        return $header;
    }

    /**
     * Returns the row at a given offset.
     *
     * @return array|false
     */
    protected function seekRow(int $offset)
    {
        foreach ($this->getDocument() as $index => $record) {
            if ($offset === $index) {
                return $record;
            }
        }

        return false;
    }

    /**
     * Returns the document as an Iterator.
     */
    protected function getDocument(): Iterator
    {
        if (70400 > PHP_VERSION_ID && '' === $this->escape) {
            $this->document->setCsvControl($this->delimiter, $this->enclosure);

            return EmptyEscapeParser::parse($this->document);
        }

        $this->document->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);
        $this->document->setCsvControl($this->delimiter, $this->enclosure, $this->escape);
        $this->document->rewind();

        return $this->document;
    }

    /**
     * Strip the BOM sequence from a record.
     *
     * @param string[] $record
     *
     * @return string[]
     */
    protected function removeBOM(array $record, int $bom_length, string $enclosure): array
    {
        if (0 === $bom_length) {
            return $record;
        }

        $record[0] = mb_substr($record[0], $bom_length);
        if ($enclosure.$enclosure != substr($record[0].$record[0], strlen($record[0]) - 1, 2)) {
            return $record;
        }

        $record[0] = substr($record[0], 1, -1);

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, array $arguments)
    {
        static $whitelisted = ['fetchColumn' => 1, 'fetchOne' => 1, 'fetchPairs' => 1];
        if (isset($whitelisted[$method])) {
            return (new ResultSet($this->getRecords(), $this->getHeader()))->$method(...$arguments);
        }

        throw new BadMethodCallException(sprintf('%s::%s() method does not exist', static::class, $method));
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if (-1 === $this->nb_records) {
            $this->nb_records = iterator_count($this->getRecords());
        }

        return $this->nb_records;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Iterator
    {
        return $this->getRecords();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return iterator_to_array($this->getRecords(), false);
    }

    /**
     * Returns the CSV records as an iterator object.
     *
     * Each CSV record is represented as a simple array containing strings or null values.
     *
     * If the CSV document has a header record then each record is combined
     * to the header record and the header record is removed from the iterator.
     *
     * If the CSV document is inconsistent. Missing record fields are
     * filled with null values while extra record fields are strip from
     * the returned object.
     *
     * @param string[] $header an optional header to use instead of the CSV document header
     */
    public function getRecords(array $header = []): Iterator
    {
        $header = $this->computeHeader($header);
        $normalized = static function ($record): bool {
            return is_array($record) && $record != [null];
        };
        $bom = $this->getInputBOM();
        $document = $this->getDocument();

        $records = $this->stripBOM(new CallbackFilterIterator($document, $normalized), $bom);
        if (null !== $this->header_offset) {
            $records = new CallbackFilterIterator($records, function (array $record, int $offset): bool {
                return $offset !== $this->header_offset;
            });
        }

        return $this->combineHeader($records, $header);
    }

    /**
     * Returns the header to be used for iteration.
     *
     * @param string[] $header
     *
     * @throws Exception If the header contains non unique column name
     *
     * @return string[]
     */
    protected function computeHeader(array $header)
    {
        if ([] === $header) {
            $header = $this->getHeader();
        }

        if ($header === array_unique(array_filter($header, 'is_string'))) {
            return $header;
        }

        throw new Exception('The header record must be empty or a flat array with unique string values');
    }

    /**
     * Combine the CSV header to each record if present.
     *
     * @param string[] $header
     */
    protected function combineHeader(Iterator $iterator, array $header): Iterator
    {
        if ([] === $header) {
            return $iterator;
        }

        $field_count = count($header);
        $mapper = static function (array $record) use ($header, $field_count): array {
            if (count($record) != $field_count) {
                $record = array_slice(array_pad($record, $field_count, null), 0, $field_count);
            }

            return array_combine($header, $record);
        };

        return new MapIterator($iterator, $mapper);
    }

    /**
     * Strip the BOM sequence from the returned records if necessary.
     */
    protected function stripBOM(Iterator $iterator, string $bom): Iterator
    {
        if ('' === $bom) {
            return $iterator;
        }

        $bom_length = mb_strlen($bom);
        $mapper = function (array $record, int $index) use ($bom_length): array {
            if (0 !== $index) {
                return $record;
            }

            return $this->removeBOM($record, $bom_length, $this->enclosure);
        };

        return new MapIterator($iterator, $mapper);
    }

    /**
     * Selects the record to be used as the CSV header.
     *
     * Because the header is represented as an array, to be valid
     * a header MUST contain only unique string value.
     *
     * @param int|null $offset the header record offset
     *
     * @throws Exception if the offset is a negative integer
     *
     * @return static
     */
    public function setHeaderOffset($offset): self
    {
        if ($offset === $this->header_offset) {
            return $this;
        }

        if (!is_nullable_int($offset)) {
            throw new TypeError(sprintf(__METHOD__.'() expects 1 Argument to be null or an integer %s given', gettype($offset)));
        }

        if (null !== $offset && 0 > $offset) {
            throw new Exception(__METHOD__.'() expects 1 Argument to be greater or equal to 0');
        }

        $this->header_offset = $offset;
        $this->resetProperties();

        return $this;
    }
}
