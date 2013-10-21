<?php

namespace Civi\Friend;

use Doctrine\ORM\Mapping as ORM;

/**
 * Friend
 *
 * @ORM\Table(name="civicrm_tell_friend")
 * @ORM\Entity
 */
class Friend extends \Civi\Core\Entity
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
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="intro", type="text", nullable=true)
     */
    private $intro;

    /**
     * @var string
     *
     * @ORM\Column(name="suggested_message", type="text", nullable=true)
     */
    private $suggestedMessage;

    /**
     * @var string
     *
     * @ORM\Column(name="general_link", type="string", length=255, nullable=true)
     */
    private $generalLink;

    /**
     * @var string
     *
     * @ORM\Column(name="thankyou_title", type="string", length=255, nullable=true)
     */
    private $thankyouTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="thankyou_text", type="text", nullable=true)
     */
    private $thankyouText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;



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
     * @return Friend
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
     * @return Friend
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
     * Set title
     *
     * @param string $title
     * @return Friend
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
     * Set intro
     *
     * @param string $intro
     * @return Friend
     */
    public function setIntro($intro)
    {
        $this->intro = $intro;

        return $this;
    }

    /**
     * Get intro
     *
     * @return string 
     */
    public function getIntro()
    {
        return $this->intro;
    }

    /**
     * Set suggestedMessage
     *
     * @param string $suggestedMessage
     * @return Friend
     */
    public function setSuggestedMessage($suggestedMessage)
    {
        $this->suggestedMessage = $suggestedMessage;

        return $this;
    }

    /**
     * Get suggestedMessage
     *
     * @return string 
     */
    public function getSuggestedMessage()
    {
        return $this->suggestedMessage;
    }

    /**
     * Set generalLink
     *
     * @param string $generalLink
     * @return Friend
     */
    public function setGeneralLink($generalLink)
    {
        $this->generalLink = $generalLink;

        return $this;
    }

    /**
     * Get generalLink
     *
     * @return string 
     */
    public function getGeneralLink()
    {
        return $this->generalLink;
    }

    /**
     * Set thankyouTitle
     *
     * @param string $thankyouTitle
     * @return Friend
     */
    public function setThankyouTitle($thankyouTitle)
    {
        $this->thankyouTitle = $thankyouTitle;

        return $this;
    }

    /**
     * Get thankyouTitle
     *
     * @return string 
     */
    public function getThankyouTitle()
    {
        return $this->thankyouTitle;
    }

    /**
     * Set thankyouText
     *
     * @param string $thankyouText
     * @return Friend
     */
    public function setThankyouText($thankyouText)
    {
        $this->thankyouText = $thankyouText;

        return $this;
    }

    /**
     * Get thankyouText
     *
     * @return string 
     */
    public function getThankyouText()
    {
        return $this->thankyouText;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Friend
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
}
