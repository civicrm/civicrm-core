<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tag
 *
 * @ORM\Table(name="civicrm_tag", uniqueConstraints={@ORM\UniqueConstraint(name="UI_name", columns={"name"})}, indexes={@ORM\Index(name="FK_civicrm_tag_parent_id", columns={"parent_id"}), @ORM\Index(name="FK_civicrm_tag_created_id", columns={"created_id"})})
 * @ORM\Entity
 */
class Tag extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=64, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_selectable", type="boolean", nullable=true)
     */
    private $isSelectable = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_tagset", type="boolean", nullable=true)
     */
    private $isTagset = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="used_for", type="string", length=64, nullable=true)
     */
    private $usedFor;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

    /**
     * @var \Civi\Core\Tag
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Tag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

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
     * Set name
     *
     * @param string $name
     * @return Tag
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
     * @return Tag
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
     * Set isSelectable
     *
     * @param boolean $isSelectable
     * @return Tag
     */
    public function setIsSelectable($isSelectable)
    {
        $this->isSelectable = $isSelectable;

        return $this;
    }

    /**
     * Get isSelectable
     *
     * @return boolean 
     */
    public function getIsSelectable()
    {
        return $this->isSelectable;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return Tag
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
     * Set isTagset
     *
     * @param boolean $isTagset
     * @return Tag
     */
    public function setIsTagset($isTagset)
    {
        $this->isTagset = $isTagset;

        return $this;
    }

    /**
     * Get isTagset
     *
     * @return boolean 
     */
    public function getIsTagset()
    {
        return $this->isTagset;
    }

    /**
     * Set usedFor
     *
     * @param string $usedFor
     * @return Tag
     */
    public function setUsedFor($usedFor)
    {
        $this->usedFor = $usedFor;

        return $this;
    }

    /**
     * Get usedFor
     *
     * @return string 
     */
    public function getUsedFor()
    {
        return $this->usedFor;
    }

    /**
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return Tag
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Get createdDate
     *
     * @return \DateTime 
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * Set parent
     *
     * @param \Civi\Core\Tag $parent
     * @return Tag
     */
    public function setParent(\Civi\Core\Tag $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Civi\Core\Tag 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set created
     *
     * @param \Civi\Contact\Contact $created
     * @return Tag
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
