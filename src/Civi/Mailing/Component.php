<?php

namespace Civi\Mailing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Component
 *
 * @ORM\Table(name="civicrm_mailing_component")
 * @ORM\Entity
 */
class Component extends \Civi\Core\Entity
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
     * @ORM\Column(name="component_type", type="string", nullable=true)
     */
    private $componentType;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="body_html", type="text", nullable=true)
     */
    private $bodyHtml;

    /**
     * @var string
     *
     * @ORM\Column(name="body_text", type="text", nullable=true)
     */
    private $bodyText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault = '0';

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
     * Set name
     *
     * @param string $name
     * @return Component
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
     * Set componentType
     *
     * @param string $componentType
     * @return Component
     */
    public function setComponentType($componentType)
    {
        $this->componentType = $componentType;

        return $this;
    }

    /**
     * Get componentType
     *
     * @return string 
     */
    public function getComponentType()
    {
        return $this->componentType;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return Component
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set bodyHtml
     *
     * @param string $bodyHtml
     * @return Component
     */
    public function setBodyHtml($bodyHtml)
    {
        $this->bodyHtml = $bodyHtml;

        return $this;
    }

    /**
     * Get bodyHtml
     *
     * @return string 
     */
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }

    /**
     * Set bodyText
     *
     * @param string $bodyText
     * @return Component
     */
    public function setBodyText($bodyText)
    {
        $this->bodyText = $bodyText;

        return $this;
    }

    /**
     * Get bodyText
     *
     * @return string 
     */
    public function getBodyText()
    {
        return $this->bodyText;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return Component
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
     * @return Component
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
