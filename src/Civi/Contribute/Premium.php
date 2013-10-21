<?php

namespace Civi\Contribute;

use Doctrine\ORM\Mapping as ORM;

/**
 * Premium
 *
 * @ORM\Table(name="civicrm_premiums")
 * @ORM\Entity
 */
class Premium extends \Civi\Core\Entity
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
     * @ORM\Column(name="entity_table", type="string", length=64, nullable=false)
     */
    private $entityTable;

    /**
     * @var integer
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=false)
     */
    private $entityId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="premiums_active", type="boolean", nullable=false)
     */
    private $premiumsActive = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="premiums_intro_title", type="string", length=255, nullable=true)
     */
    private $premiumsIntroTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="premiums_intro_text", type="text", nullable=true)
     */
    private $premiumsIntroText;

    /**
     * @var string
     *
     * @ORM\Column(name="premiums_contact_email", type="string", length=100, nullable=true)
     */
    private $premiumsContactEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="premiums_contact_phone", type="string", length=50, nullable=true)
     */
    private $premiumsContactPhone;

    /**
     * @var boolean
     *
     * @ORM\Column(name="premiums_display_min_contribution", type="boolean", nullable=false)
     */
    private $premiumsDisplayMinContribution;

    /**
     * @var string
     *
     * @ORM\Column(name="premiums_nothankyou_label", type="string", length=255, nullable=true)
     */
    private $premiumsNothankyouLabel;

    /**
     * @var integer
     *
     * @ORM\Column(name="premiums_nothankyou_position", type="integer", nullable=true)
     */
    private $premiumsNothankyouPosition = '1';



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
     * Set entityTable
     *
     * @param string $entityTable
     * @return Premium
     */
    public function setEntityTable($entityTable)
    {
        $this->entityTable = $entityTable;

        return $this;
    }

    /**
     * Get entityTable
     *
     * @return string 
     */
    public function getEntityTable()
    {
        return $this->entityTable;
    }

    /**
     * Set entityId
     *
     * @param integer $entityId
     * @return Premium
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get entityId
     *
     * @return integer 
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set premiumsActive
     *
     * @param boolean $premiumsActive
     * @return Premium
     */
    public function setPremiumsActive($premiumsActive)
    {
        $this->premiumsActive = $premiumsActive;

        return $this;
    }

    /**
     * Get premiumsActive
     *
     * @return boolean 
     */
    public function getPremiumsActive()
    {
        return $this->premiumsActive;
    }

    /**
     * Set premiumsIntroTitle
     *
     * @param string $premiumsIntroTitle
     * @return Premium
     */
    public function setPremiumsIntroTitle($premiumsIntroTitle)
    {
        $this->premiumsIntroTitle = $premiumsIntroTitle;

        return $this;
    }

    /**
     * Get premiumsIntroTitle
     *
     * @return string 
     */
    public function getPremiumsIntroTitle()
    {
        return $this->premiumsIntroTitle;
    }

    /**
     * Set premiumsIntroText
     *
     * @param string $premiumsIntroText
     * @return Premium
     */
    public function setPremiumsIntroText($premiumsIntroText)
    {
        $this->premiumsIntroText = $premiumsIntroText;

        return $this;
    }

    /**
     * Get premiumsIntroText
     *
     * @return string 
     */
    public function getPremiumsIntroText()
    {
        return $this->premiumsIntroText;
    }

    /**
     * Set premiumsContactEmail
     *
     * @param string $premiumsContactEmail
     * @return Premium
     */
    public function setPremiumsContactEmail($premiumsContactEmail)
    {
        $this->premiumsContactEmail = $premiumsContactEmail;

        return $this;
    }

    /**
     * Get premiumsContactEmail
     *
     * @return string 
     */
    public function getPremiumsContactEmail()
    {
        return $this->premiumsContactEmail;
    }

    /**
     * Set premiumsContactPhone
     *
     * @param string $premiumsContactPhone
     * @return Premium
     */
    public function setPremiumsContactPhone($premiumsContactPhone)
    {
        $this->premiumsContactPhone = $premiumsContactPhone;

        return $this;
    }

    /**
     * Get premiumsContactPhone
     *
     * @return string 
     */
    public function getPremiumsContactPhone()
    {
        return $this->premiumsContactPhone;
    }

    /**
     * Set premiumsDisplayMinContribution
     *
     * @param boolean $premiumsDisplayMinContribution
     * @return Premium
     */
    public function setPremiumsDisplayMinContribution($premiumsDisplayMinContribution)
    {
        $this->premiumsDisplayMinContribution = $premiumsDisplayMinContribution;

        return $this;
    }

    /**
     * Get premiumsDisplayMinContribution
     *
     * @return boolean 
     */
    public function getPremiumsDisplayMinContribution()
    {
        return $this->premiumsDisplayMinContribution;
    }

    /**
     * Set premiumsNothankyouLabel
     *
     * @param string $premiumsNothankyouLabel
     * @return Premium
     */
    public function setPremiumsNothankyouLabel($premiumsNothankyouLabel)
    {
        $this->premiumsNothankyouLabel = $premiumsNothankyouLabel;

        return $this;
    }

    /**
     * Get premiumsNothankyouLabel
     *
     * @return string 
     */
    public function getPremiumsNothankyouLabel()
    {
        return $this->premiumsNothankyouLabel;
    }

    /**
     * Set premiumsNothankyouPosition
     *
     * @param integer $premiumsNothankyouPosition
     * @return Premium
     */
    public function setPremiumsNothankyouPosition($premiumsNothankyouPosition)
    {
        $this->premiumsNothankyouPosition = $premiumsNothankyouPosition;

        return $this;
    }

    /**
     * Get premiumsNothankyouPosition
     *
     * @return integer 
     */
    public function getPremiumsNothankyouPosition()
    {
        return $this->premiumsNothankyouPosition;
    }
}
