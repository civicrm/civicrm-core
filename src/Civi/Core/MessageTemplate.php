<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * MessageTemplate
 *
 * @ORM\Table(name="civicrm_msg_template", indexes={@ORM\Index(name="FK_civicrm_msg_template_pdf_format_id", columns={"pdf_format_id"})})
 * @ORM\Entity
 */
class MessageTemplate extends \Civi\Core\Entity
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
     * @ORM\Column(name="msg_title", type="string", length=255, nullable=true)
     */
    private $msgTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="msg_subject", type="text", nullable=true)
     */
    private $msgSubject;

    /**
     * @var string
     *
     * @ORM\Column(name="msg_text", type="text", nullable=true)
     */
    private $msgText;

    /**
     * @var string
     *
     * @ORM\Column(name="msg_html", type="text", nullable=true)
     */
    private $msgHtml;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="workflow_id", type="integer", nullable=true)
     */
    private $workflowId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved;

    /**
     * @var \Civi\Core\OptionValue
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\OptionValue")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pdf_format_id", referencedColumnName="id")
     * })
     */
    private $pdfFormat;



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
     * Set msgTitle
     *
     * @param string $msgTitle
     * @return MessageTemplate
     */
    public function setMsgTitle($msgTitle)
    {
        $this->msgTitle = $msgTitle;

        return $this;
    }

    /**
     * Get msgTitle
     *
     * @return string 
     */
    public function getMsgTitle()
    {
        return $this->msgTitle;
    }

    /**
     * Set msgSubject
     *
     * @param string $msgSubject
     * @return MessageTemplate
     */
    public function setMsgSubject($msgSubject)
    {
        $this->msgSubject = $msgSubject;

        return $this;
    }

    /**
     * Get msgSubject
     *
     * @return string 
     */
    public function getMsgSubject()
    {
        return $this->msgSubject;
    }

    /**
     * Set msgText
     *
     * @param string $msgText
     * @return MessageTemplate
     */
    public function setMsgText($msgText)
    {
        $this->msgText = $msgText;

        return $this;
    }

    /**
     * Get msgText
     *
     * @return string 
     */
    public function getMsgText()
    {
        return $this->msgText;
    }

    /**
     * Set msgHtml
     *
     * @param string $msgHtml
     * @return MessageTemplate
     */
    public function setMsgHtml($msgHtml)
    {
        $this->msgHtml = $msgHtml;

        return $this;
    }

    /**
     * Get msgHtml
     *
     * @return string 
     */
    public function getMsgHtml()
    {
        return $this->msgHtml;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return MessageTemplate
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
     * Set workflowId
     *
     * @param integer $workflowId
     * @return MessageTemplate
     */
    public function setWorkflowId($workflowId)
    {
        $this->workflowId = $workflowId;

        return $this;
    }

    /**
     * Get workflowId
     *
     * @return integer 
     */
    public function getWorkflowId()
    {
        return $this->workflowId;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return MessageTemplate
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
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return MessageTemplate
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
     * Set pdfFormat
     *
     * @param \Civi\Core\OptionValue $pdfFormat
     * @return MessageTemplate
     */
    public function setPdfFormat(\Civi\Core\OptionValue $pdfFormat = null)
    {
        $this->pdfFormat = $pdfFormat;

        return $this;
    }

    /**
     * Get pdfFormat
     *
     * @return \Civi\Core\OptionValue 
     */
    public function getPdfFormat()
    {
        return $this->pdfFormat;
    }
}
