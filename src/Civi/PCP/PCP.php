<?php

namespace Civi\PCP;

use Doctrine\ORM\Mapping as ORM;

/**
 * PCP
 *
 * @ORM\Table(name="civicrm_pcp", indexes={@ORM\Index(name="FK_civicrm_pcp_contact_id", columns={"contact_id"})})
 * @ORM\Entity
 */
class PCP extends \Civi\Core\Entity
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
     * @var integer
     *
     * @ORM\Column(name="status_id", type="integer", nullable=false)
     */
    private $statusId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="intro_text", type="text", nullable=true)
     */
    private $introText;

    /**
     * @var string
     *
     * @ORM\Column(name="page_text", type="text", nullable=true)
     */
    private $pageText;

    /**
     * @var string
     *
     * @ORM\Column(name="donate_link_text", type="string", length=255, nullable=true)
     */
    private $donateLinkText;

    /**
     * @var integer
     *
     * @ORM\Column(name="page_id", type="integer", nullable=false)
     */
    private $pageId;

    /**
     * @var string
     *
     * @ORM\Column(name="page_type", type="string", length=64, nullable=true)
     */
    private $pageType = 'contribute';

    /**
     * @var integer
     *
     * @ORM\Column(name="pcp_block_id", type="integer", nullable=false)
     */
    private $pcpBlockId;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_thermometer", type="integer", nullable=true)
     */
    private $isThermometer = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="is_honor_roll", type="integer", nullable=true)
     */
    private $isHonorRoll = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="goal_amount", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $goalAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '0';

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     * })
     */
    private $contact;



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
     * Set statusId
     *
     * @param integer $statusId
     * @return PCP
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
     * Set title
     *
     * @param string $title
     * @return PCP
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
     * Set introText
     *
     * @param string $introText
     * @return PCP
     */
    public function setIntroText($introText)
    {
        $this->introText = $introText;

        return $this;
    }

    /**
     * Get introText
     *
     * @return string 
     */
    public function getIntroText()
    {
        return $this->introText;
    }

    /**
     * Set pageText
     *
     * @param string $pageText
     * @return PCP
     */
    public function setPageText($pageText)
    {
        $this->pageText = $pageText;

        return $this;
    }

    /**
     * Get pageText
     *
     * @return string 
     */
    public function getPageText()
    {
        return $this->pageText;
    }

    /**
     * Set donateLinkText
     *
     * @param string $donateLinkText
     * @return PCP
     */
    public function setDonateLinkText($donateLinkText)
    {
        $this->donateLinkText = $donateLinkText;

        return $this;
    }

    /**
     * Get donateLinkText
     *
     * @return string 
     */
    public function getDonateLinkText()
    {
        return $this->donateLinkText;
    }

    /**
     * Set pageId
     *
     * @param integer $pageId
     * @return PCP
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;

        return $this;
    }

    /**
     * Get pageId
     *
     * @return integer 
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * Set pageType
     *
     * @param string $pageType
     * @return PCP
     */
    public function setPageType($pageType)
    {
        $this->pageType = $pageType;

        return $this;
    }

    /**
     * Get pageType
     *
     * @return string 
     */
    public function getPageType()
    {
        return $this->pageType;
    }

    /**
     * Set pcpBlockId
     *
     * @param integer $pcpBlockId
     * @return PCP
     */
    public function setPcpBlockId($pcpBlockId)
    {
        $this->pcpBlockId = $pcpBlockId;

        return $this;
    }

    /**
     * Get pcpBlockId
     *
     * @return integer 
     */
    public function getPcpBlockId()
    {
        return $this->pcpBlockId;
    }

    /**
     * Set isThermometer
     *
     * @param integer $isThermometer
     * @return PCP
     */
    public function setIsThermometer($isThermometer)
    {
        $this->isThermometer = $isThermometer;

        return $this;
    }

    /**
     * Get isThermometer
     *
     * @return integer 
     */
    public function getIsThermometer()
    {
        return $this->isThermometer;
    }

    /**
     * Set isHonorRoll
     *
     * @param integer $isHonorRoll
     * @return PCP
     */
    public function setIsHonorRoll($isHonorRoll)
    {
        $this->isHonorRoll = $isHonorRoll;

        return $this;
    }

    /**
     * Get isHonorRoll
     *
     * @return integer 
     */
    public function getIsHonorRoll()
    {
        return $this->isHonorRoll;
    }

    /**
     * Set goalAmount
     *
     * @param string $goalAmount
     * @return PCP
     */
    public function setGoalAmount($goalAmount)
    {
        $this->goalAmount = $goalAmount;

        return $this;
    }

    /**
     * Get goalAmount
     *
     * @return string 
     */
    public function getGoalAmount()
    {
        return $this->goalAmount;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return PCP
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string 
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return PCP
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
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return PCP
     */
    public function setContact(\Civi\Contact\Contact $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return \Civi\Contact\Contact 
     */
    public function getContact()
    {
        return $this->contact;
    }
}
