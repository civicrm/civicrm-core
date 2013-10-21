<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Website
 *
 * @ORM\Table(name="civicrm_website", indexes={@ORM\Index(name="UI_website_type_id", columns={"website_type_id"}), @ORM\Index(name="FK_civicrm_website_contact_id", columns={"contact_id"})})
 * @ORM\Entity
 */
class Website extends \Civi\Core\Entity
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
     * @ORM\Column(name="url", type="string", length=128, nullable=true)
     */
    private $url;

    /**
     * @var integer
     *
     * @ORM\Column(name="website_type_id", type="integer", nullable=true)
     */
    private $websiteTypeId;

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
     * Set url
     *
     * @param string $url
     * @return Website
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set websiteTypeId
     *
     * @param integer $websiteTypeId
     * @return Website
     */
    public function setWebsiteTypeId($websiteTypeId)
    {
        $this->websiteTypeId = $websiteTypeId;

        return $this;
    }

    /**
     * Get websiteTypeId
     *
     * @return integer 
     */
    public function getWebsiteTypeId()
    {
        return $this->websiteTypeId;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Website
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
