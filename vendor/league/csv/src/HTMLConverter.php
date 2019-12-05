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

use DOMDocument;
use DOMElement;
use DOMException;
use Traversable;
use function preg_match;

/**
 * Converts tabular data into an HTML Table string.
 */
class HTMLConverter
{
    /**
     * table class attribute value.
     *
     * @var string
     */
    protected $class_name = 'table-csv-data';

    /**
     * table id attribute value.
     *
     * @var string
     */
    protected $id_value = '';

    /**
     * @var XMLConverter
     */
    protected $xml_converter;

    /**
     * New Instance.
     */
    public function __construct()
    {
        $this->xml_converter = (new XMLConverter())
            ->rootElement('table')
            ->recordElement('tr')
            ->fieldElement('td')
        ;
    }

    /**
     * Convert an Record collection into a DOMDocument.
     *
     * @param array|Traversable $records the tabular data collection
     */
    public function convert($records): string
    {
        /** @var DOMDocument $doc */
        $doc = $this->xml_converter->convert($records);

        /** @var DOMElement $table */
        $table = $doc->getElementsByTagName('table')->item(0);
        $table->setAttribute('class', $this->class_name);
        $table->setAttribute('id', $this->id_value);

        return $doc->saveHTML($table);
    }

    /**
     * HTML table class name setter.
     *
     * @throws DOMException if the id_value contains any type of whitespace
     */
    public function table(string $class_name, string $id_value = ''): self
    {
        if (preg_match(",\s,", $id_value)) {
            throw new DOMException("the id attribute's value must not contain whitespace (spaces, tabs etc.)");
        }
        $clone = clone $this;
        $clone->class_name = $class_name;
        $clone->id_value = $id_value;

        return $clone;
    }

    /**
     * HTML tr record offset attribute setter.
     */
    public function tr(string $record_offset_attribute_name): self
    {
        $clone = clone $this;
        $clone->xml_converter = $this->xml_converter->recordElement('tr', $record_offset_attribute_name);

        return $clone;
    }

    /**
     * HTML td field name attribute setter.
     */
    public function td(string $fieldname_attribute_name): self
    {
        $clone = clone $this;
        $clone->xml_converter = $this->xml_converter->fieldElement('td', $fieldname_attribute_name);

        return $clone;
    }
}
