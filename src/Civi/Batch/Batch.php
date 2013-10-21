<?php

namespace Civi\Batch;

use Doctrine\ORM\Mapping as ORM;

/**
 * Batch
 *
 * @ORM\Table(name="civicrm_batch", uniqueConstraints={@ORM\UniqueConstraint(name="UI_name", columns={"name"})}, indexes={@ORM\Index(name="FK_civicrm_batch_created_id", columns={"created_id"}), @ORM\Index(name="FK_civicrm_batch_modified_id", columns={"modified_id"}), @ORM\Index(name="FK_civicrm_batch_saved_search_id", columns={"saved_search_id"})})
 * @ORM\Entity
 */
class Batch extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=64, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=64, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_date", type="datetime", nullable=true)
     */
    private $modifiedDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_id", type="integer", nullable=false)
     */
    private $statusId;

    /**
     * @var integer
     *
     * @ORM\Column(name="type_id", type="integer", nullable=true)
     */
    private $typeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="mode_id", type="integer", nullable=true)
     */
    private $modeId;

    /**
     * @var string
     *
     * @ORM\Column(name="total", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $total;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_count", type="integer", nullable=true)
     */
    private $itemCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="payment_instrument_id", type="integer", nullable=true)
     */
    private $paymentInstrumentId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="exported_date", type="datetime", nullable=true)
     */
    private $exportedDate;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;

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
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="modified_id", referencedColumnName="id")
     * })
     */
    private $modified;

    /**
     * @var \Civi\Contact\SavedSearch
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\SavedSearch")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="saved_search_id", referencedColumnName="id")
     * })
     */
    private $savedSearch;



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
     * @return Batch
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
     * Set title
     *
     * @param string $title
     * @return Batch
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
     * Set description
     *
     * @param string $description
     * @return Batch
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
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return Batch
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
     * Set modifiedDate
     *
     * @param \DateTime $modifiedDate
     * @return Batch
     */
    public function setModifiedDate($modifiedDate)
    {
        $this->modifiedDate = $modifiedDate;

        return $this;
    }

    /**
     * Get modifiedDate
     *
     * @return \DateTime 
     */
    public function getModifiedDate()
    {
        return $this->modifiedDate;
    }

    /**
     * Set statusId
     *
     * @param integer $statusId
     * @return Batch
     */
    public function setStatusId($statusId)
    {
        $this->statusId = $statusId;

        return $this;
    }

    /**
     * Get statusId
     *
     * @return integer 
     */
    public function getStatusId()
    {
        return $this->statusId;
    }

    /**
     * Set typeId
     *
     * @param integer $typeId
     * @return Batch
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;

        return $this;
    }

    /**
     * Get typeId
     *
     * @return integer 
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * Set modeId
     *
     * @param integer $modeId
     * @return Batch
     */
    public function setModeId($modeId)
    {
        $this->modeId = $modeId;

        return $this;
    }

    /**
     * Get modeId
     *
     * @return integer 
     */
    public function getModeId()
    {
        return $this->modeId;
    }

    /**
     * Set total
     *
     * @param string $total
     * @return Batch
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return string 
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set itemCount
     *
     * @param integer $itemCount
     * @return Batch
     */
    public function setItemCount($itemCount)
    {
        $this->itemCount = $itemCount;

        return $this;
    }

    /**
     * Get itemCount
     *
     * @return integer 
     */
    public function getItemCount()
    {
        return $this->itemCount;
    }

    /**
     * Set paymentInstrumentId
     *
     * @param integer $paymentInstrumentId
     * @return Batch
     */
    public function setPaymentInstrumentId($paymentInstrumentId)
    {
        $this->paymentInstrumentId = $paymentInstrumentId;

        return $this;
    }

    /**
     * Get paymentInstrumentId
     *
     * @return integer 
     */
    public function getPaymentInstrumentId()
    {
        return $this->paymentInstrumentId;
    }

    /**
     * Set exportedDate
     *
     * @param \DateTime $exportedDate
     * @return Batch
     */
    public function setExportedDate($exportedDate)
    {
        $this->exportedDate = $exportedDate;

        return $this;
    }

    /**
     * Get exportedDate
     *
     * @return \DateTime 
     */
    public function getExportedDate()
    {
        return $this->exportedDate;
    }

    /**
     * Set data
     *
     * @param string $data
     * @return Batch
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
     * Set created
     *
     * @param \Civi\Contact\Contact $created
     * @return Batch
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

    /**
     * Set modified
     *
     * @param \Civi\Contact\Contact $modified
     * @return Batch
     */
    public function setModified(\Civi\Contact\Contact $modified = null)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified
     *
     * @return \Civi\Contact\Contact 
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set savedSearch
     *
     * @param \Civi\Contact\SavedSearch $savedSearch
     * @return Batch
     */
    public function setSavedSearch(\Civi\Contact\SavedSearch $savedSearch = null)
    {
        $this->savedSearch = $savedSearch;

        return $this;
    }

    /**
     * Get savedSearch
     *
     * @return \Civi\Contact\SavedSearch 
     */
    public function getSavedSearch()
    {
        return $this->savedSearch;
    }
}
