<?php

namespace Civi\ACL;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cache
 *
 * @ORM\Table(name="civicrm_acl_cache", indexes={@ORM\Index(name="index_acl_id", columns={"acl_id"}), @ORM\Index(name="FK_civicrm_acl_cache_contact_id", columns={"contact_id"})})
 * @ORM\Entity
 */
class Cache extends \Civi\Core\Entity
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
     * @var \DateTime
     *
     * @ORM\Column(name="modified_date", type="date", nullable=true)
     */
    private $modifiedDate;

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
     * @var \Civi\ACL\ACL
     *
     * @ORM\ManyToOne(targetEntity="Civi\ACL\ACL")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="acl_id", referencedColumnName="id")
     * })
     */
    private $acl;



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
     * Set modifiedDate
     *
     * @param \DateTime $modifiedDate
     * @return Cache
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
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Cache
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

    /**
     * Set acl
     *
     * @param \Civi\ACL\ACL $acl
     * @return Cache
     */
    public function setAcl(\Civi\ACL\ACL $acl = null)
    {
        $this->acl = $acl;

        return $this;
    }

    /**
     * Get acl
     *
     * @return \Civi\ACL\ACL 
     */
    public function getAcl()
    {
        return $this->acl;
    }
}
