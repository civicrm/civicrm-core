<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * UFMatch
 *
 * @ORM\Table(name="civicrm_uf_match", uniqueConstraints={@ORM\UniqueConstraint(name="UI_uf_name_domain_id", columns={"uf_name", "domain_id"}), @ORM\UniqueConstraint(name="UI_contact_domain_id", columns={"contact_id", "domain_id"})}, indexes={@ORM\Index(name="I_civicrm_uf_match_uf_id", columns={"uf_id"}), @ORM\Index(name="FK_civicrm_uf_match_domain_id", columns={"domain_id"}), @ORM\Index(name="IDX_6EB23255E7A1254A", columns={"contact_id"})})
 * @ORM\Entity
 */
class UFMatch extends \Civi\Core\Entity
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
     * @ORM\Column(name="uf_id", type="integer", nullable=false)
     */
    private $ufId;

    /**
     * @var string
     *
     * @ORM\Column(name="uf_name", type="string", length=128, nullable=true)
     */
    private $ufName;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=5, nullable=true)
     */
    private $language;

    /**
     * @var \Civi\Core\Domain
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Domain")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="domain_id", referencedColumnName="id")
     * })
     */
    private $domain;

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
     * Set ufId
     *
     * @param integer $ufId
     * @return UFMatch
     */
    public function setUfId($ufId)
    {
        $this->ufId = $ufId;

        return $this;
    }

    /**
     * Get ufId
     *
     * @return integer 
     */
    public function getUfId()
    {
        return $this->ufId;
    }

    /**
     * Set ufName
     *
     * @param string $ufName
     * @return UFMatch
     */
    public function setUfName($ufName)
    {
        $this->ufName = $ufName;

        return $this;
    }

    /**
     * Get ufName
     *
     * @return string 
     */
    public function getUfName()
    {
        return $this->ufName;
    }

    /**
     * Set language
     *
     * @param string $language
     * @return UFMatch
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return string 
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set domain
     *
     * @param \Civi\Core\Domain $domain
     * @return UFMatch
     */
    public function setDomain(\Civi\Core\Domain $domain = null)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return \Civi\Core\Domain 
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return UFMatch
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
