<?php

namespace Civi\Contact;

use Doctrine\ORM\Mapping as ORM;

/**
 * SavedSearch
 *
 * @ORM\Table(name="civicrm_saved_search", indexes={@ORM\Index(name="FK_civicrm_saved_search_mapping_id", columns={"mapping_id"})})
 * @ORM\Entity
 */
class SavedSearch extends \Civi\Core\Entity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="form_values", type="text", nullable=true)
     */
    private $formValues;

    /**
     * @var integer
     *
     * @ORM\Column(name="search_custom_id", type="integer", nullable=true)
     */
    private $searchCustomId;

    /**
     * @var string
     *
     * @ORM\Column(name="where_clause", type="text", nullable=true)
     */
    private $whereClause;

    /**
     * @var string
     *
     * @ORM\Column(name="select_tables", type="text", nullable=true)
     */
    private $selectTables;

    /**
     * @var string
     *
     * @ORM\Column(name="where_tables", type="text", nullable=true)
     */
    private $whereTables;

    /**
     * @var \Civi\Core\Mapping
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Mapping")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="mapping_id", referencedColumnName="id")
     * })
     */
    private $mapping;



    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set formValues
     *
     * @param string $formValues
     * @return SavedSearch
     */
    public function setFormValues($formValues)
    {
        $this->formValues = $formValues;

        return $this;
    }

    /**
     * Get formValues
     *
     * @return string 
     */
    public function getFormValues()
    {
        return $this->formValues;
    }

    /**
     * Set searchCustomId
     *
     * @param integer $searchCustomId
     * @return SavedSearch
     */
    public function setSearchCustomId($searchCustomId)
    {
        $this->searchCustomId = $searchCustomId;

        return $this;
    }

    /**
     * Get searchCustomId
     *
     * @return integer 
     */
    public function getSearchCustomId()
    {
        return $this->searchCustomId;
    }

    /**
     * Set whereClause
     *
     * @param string $whereClause
     * @return SavedSearch
     */
    public function setWhereClause($whereClause)
    {
        $this->whereClause = $whereClause;

        return $this;
    }

    /**
     * Get whereClause
     *
     * @return string 
     */
    public function getWhereClause()
    {
        return $this->whereClause;
    }

    /**
     * Set selectTables
     *
     * @param string $selectTables
     * @return SavedSearch
     */
    public function setSelectTables($selectTables)
    {
        $this->selectTables = $selectTables;

        return $this;
    }

    /**
     * Get selectTables
     *
     * @return string 
     */
    public function getSelectTables()
    {
        return $this->selectTables;
    }

    /**
     * Set whereTables
     *
     * @param string $whereTables
     * @return SavedSearch
     */
    public function setWhereTables($whereTables)
    {
        $this->whereTables = $whereTables;

        return $this;
    }

    /**
     * Get whereTables
     *
     * @return string 
     */
    public function getWhereTables()
    {
        return $this->whereTables;
    }

    /**
     * Set mapping
     *
     * @param \Civi\Core\Mapping $mapping
     * @return SavedSearch
     */
    public function setMapping(\Civi\Core\Mapping $mapping = null)
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * Get mapping
     *
     * @return \Civi\Core\Mapping 
     */
    public function getMapping()
    {
        return $this->mapping;
    }
}
