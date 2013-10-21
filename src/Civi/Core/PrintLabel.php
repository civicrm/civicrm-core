<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * PrintLabel
 *
 * @ORM\Table(name="civicrm_print_label", indexes={@ORM\Index(name="FK_civicrm_print_label_created_id", columns={"created_id"})})
 * @ORM\Entity
 */
class PrintLabel extends \Civi\Core\Entity
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
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="label_format_name", type="string", length=255, nullable=true)
     */
    private $labelFormatName;

    /**
     * @var integer
     *
     * @ORM\Column(name="label_type_id", type="integer", nullable=true)
     */
    private $labelTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved = '1';

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="created_id", referencedColumnName="id")
     * })
     */
    private $created;



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
     * Set title
     *
     * @param string $title
     * @return PrintLabel
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return PrintLabel
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return PrintLabel
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set labelFormatName
     *
     * @param string $labelFormatName
     * @return PrintLabel
     */
    public function setLabelFormatName($labelFormatName)
    {
        $this->labelFormatName = $labelFormatName;

        return $this;
    }

    /**
     * Get labelFormatName
     *
     * @return string 
     */
    public function getLabelFormatName()
    {
        return $this->labelFormatName;
    }

    /**
     * Set labelTypeId
     *
     * @param integer $labelTypeId
     * @return PrintLabel
     */
    public function setLabelTypeId($labelTypeId)
    {
        $this->labelTypeId = $labelTypeId;

        return $this;
    }

    /**
     * Get labelTypeId
     *
     * @return integer 
     */
    public function getLabelTypeId()
    {
        return $this->labelTypeId;
    }

    /**
     * Set data
     *
     * @param string $data
     * @return PrintLabel
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string 
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return PrintLabel
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return PrintLabel
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return PrintLabel
     */
    public function setIsReserved($isReserved)
    {
        $this->isReserved = $isReserved;

        return $this;
    }

    /**
     * Get isReserved
     *
     * @return boolean 
     */
    public function getIsReserved()
    {
        return $this->isReserved;
    }

    /**
     * Set created
     *
     * @param \Civi\Contact\Contact $created
     * @return PrintLabel
     */
    public function setCreated(\Civi\Contact\Contact $created = null)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \Civi\Contact\Contact 
     */
    public function getCreated()
    {
        return $this->created;
    }
}
