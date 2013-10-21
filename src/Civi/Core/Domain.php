<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Domain
 *
 * @ORM\Table(name="civicrm_domain", uniqueConstraints={@ORM\UniqueConstraint(name="UI_name", columns={"name"})}, indexes={@ORM\Index(name="FK_civicrm_domain_contact_id", columns={"contact_id"})})
 * @ORM\Entity
 */
class Domain extends \Civi\Core\Entity
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
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="config_backend", type="text", nullable=true)
     */
    private $configBackend;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=32, nullable=true)
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="locales", type="text", nullable=true)
     */
    private $locales;

    /**
     * @var string
     *
     * @ORM\Column(name="locale_custom_strings", type="text", nullable=true)
     */
    private $localeCustomStrings;

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
     * Set name
     *
     * @param string $name
     * @return Domain
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
     * @return Domain
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
     * Set configBackend
     *
     * @param string $configBackend
     * @return Domain
     */
    public function setConfigBackend($configBackend)
    {
        $this->configBackend = $configBackend;

        return $this;
    }

    /**
     * Get configBackend
     *
     * @return string 
     */
    public function getConfigBackend()
    {
        return $this->configBackend;
    }

    /**
     * Set version
     *
     * @param string $version
     * @return Domain
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set locales
     *
     * @param string $locales
     * @return Domain
     */
    public function setLocales($locales)
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * Get locales
     *
     * @return string 
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Set localeCustomStrings
     *
     * @param string $localeCustomStrings
     * @return Domain
     */
    public function setLocaleCustomStrings($localeCustomStrings)
    {
        $this->localeCustomStrings = $localeCustomStrings;

        return $this;
    }

    /**
     * Get localeCustomStrings
     *
     * @return string 
     */
    public function getLocaleCustomStrings()
    {
        return $this->localeCustomStrings;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Domain
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
