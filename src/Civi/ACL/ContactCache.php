<?php

namespace Civi\ACL;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContactCache
 *
 * @ORM\Table(name="civicrm_acl_contact_cache", uniqueConstraints={@ORM\UniqueConstraint(name="UI_user_contact_operation", columns={"user_id", "contact_id", "operation"})}, indexes={@ORM\Index(name="FK_civicrm_acl_contact_cache_contact_id", columns={"contact_id"}), @ORM\Index(name="IDX_89646CA6A76ED395", columns={"user_id"})})
 * @ORM\Entity
 */
class ContactCache extends \Civi\Core\Entity
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
     * @ORM\Column(name="operation", type="string", nullable=false)
     */
    private $operation;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

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
     * Set operation
     *
     * @param string $operation
     * @return ContactCache
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation
     *
     * @return string 
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set user
     *
     * @param \Civi\Contact\Contact $user
     * @return ContactCache
     */
    public function setUser(\Civi\Contact\Contact $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Civi\Contact\Contact 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return ContactCache
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
