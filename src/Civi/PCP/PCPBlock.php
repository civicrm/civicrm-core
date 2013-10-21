<?php

namespace Civi\PCP;

use Doctrine\ORM\Mapping as ORM;

/**
 * PCPBlock
 *
 * @ORM\Table(name="civicrm_pcp_block", indexes={@ORM\Index(name="FK_civicrm_pcp_block_supporter_profile_id", columns={"supporter_profile_id"})})
 * @ORM\Entity
 */
class PCPBlock extends \Civi\Core\Entity
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
     * @ORM\Column(name="entity_table", type="string", length=64, nullable=true)
     */
    private $entityTable;

    /**
     * @var integer
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=false)
     */
    private $entityId;

    /**
     * @var string
     *
     * @ORM\Column(name="target_entity_type", type="string", length=255, nullable=false)
     */
    private $targetEntityType = 'contribute';

    /**
     * @var integer
     *
     * @ORM\Column(name="target_entity_id", type="integer", nullable=false)
     */
    private $targetEntityId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_approval_needed", type="boolean", nullable=true)
     */
    private $isApprovalNeeded;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_tellfriend_enabled", type="boolean", nullable=true)
     */
    private $isTellfriendEnabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="tellfriend_limit", type="integer", nullable=true)
     */
    private $tellfriendLimit;

    /**
     * @var string
     *
     * @ORM\Column(name="link_text", type="string", length=255, nullable=true)
     */
    private $linkText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="notify_email", type="string", length=255, nullable=true)
     */
    private $notifyEmail;

    /**
     * @var \Civi\Core\UFGroup
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\UFGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="supporter_profile_id", referencedColumnName="id")
     * })
     */
    private $supporterProfile;



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
     * @return PCPBlock
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
     * @return PCPBlock
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
     * Set targetEntityType
     *
     * @param string $targetEntityType
     * @return PCPBlock
     */
    public function setTargetEntityType($targetEntityType)
    {
        $this->targetEntityType = $targetEntityType;

        return $this;
    }

    /**
     * Get targetEntityType
     *
     * @return string 
     */
    public function getTargetEntityType()
    {
        return $this->targetEntityType;
    }

    /**
     * Set targetEntityId
     *
     * @param integer $targetEntityId
     * @return PCPBlock
     */
    public function setTargetEntityId($targetEntityId)
    {
        $this->targetEntityId = $targetEntityId;

        return $this;
    }

    /**
     * Get targetEntityId
     *
     * @return integer 
     */
    public function getTargetEntityId()
    {
        return $this->targetEntityId;
    }

    /**
     * Set isApprovalNeeded
     *
     * @param boolean $isApprovalNeeded
     * @return PCPBlock
     */
    public function setIsApprovalNeeded($isApprovalNeeded)
    {
        $this->isApprovalNeeded = $isApprovalNeeded;

        return $this;
    }

    /**
     * Get isApprovalNeeded
     *
     * @return boolean 
     */
    public function getIsApprovalNeeded()
    {
        return $this->isApprovalNeeded;
    }

    /**
     * Set isTellfriendEnabled
     *
     * @param boolean $isTellfriendEnabled
     * @return PCPBlock
     */
    public function setIsTellfriendEnabled($isTellfriendEnabled)
    {
        $this->isTellfriendEnabled = $isTellfriendEnabled;

        return $this;
    }

    /**
     * Get isTellfriendEnabled
     *
     * @return boolean 
     */
    public function getIsTellfriendEnabled()
    {
        return $this->isTellfriendEnabled;
    }

    /**
     * Set tellfriendLimit
     *
     * @param integer $tellfriendLimit
     * @return PCPBlock
     */
    public function setTellfriendLimit($tellfriendLimit)
    {
        $this->tellfriendLimit = $tellfriendLimit;

        return $this;
    }

    /**
     * Get tellfriendLimit
     *
     * @return integer 
     */
    public function getTellfriendLimit()
    {
        return $this->tellfriendLimit;
    }

    /**
     * Set linkText
     *
     * @param string $linkText
     * @return PCPBlock
     */
    public function setLinkText($linkText)
    {
        $this->linkText = $linkText;

        return $this;
    }

    /**
     * Get linkText
     *
     * @return string 
     */
    public function getLinkText()
    {
        return $this->linkText;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return PCPBlock
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
     * Set notifyEmail
     *
     * @param string $notifyEmail
     * @return PCPBlock
     */
    public function setNotifyEmail($notifyEmail)
    {
        $this->notifyEmail = $notifyEmail;

        return $this;
    }

    /**
     * Get notifyEmail
     *
     * @return string 
     */
    public function getNotifyEmail()
    {
        return $this->notifyEmail;
    }

    /**
     * Set supporterProfile
     *
     * @param \Civi\Core\UFGroup $supporterProfile
     * @return PCPBlock
     */
    public function setSupporterProfile(\Civi\Core\UFGroup $supporterProfile = null)
    {
        $this->supporterProfile = $supporterProfile;

        return $this;
    }

    /**
     * Get supporterProfile
     *
     * @return \Civi\Core\UFGroup 
     */
    public function getSupporterProfile()
    {
        return $this->supporterProfile;
    }
}
