<?php

namespace Civi\Contact;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupContact
 *
 * @ORM\Table(name="civicrm_group_contact", uniqueConstraints={@ORM\UniqueConstraint(name="UI_contact_group", columns={"contact_id", "group_id"})}, indexes={@ORM\Index(name="FK_civicrm_group_contact_group_id", columns={"group_id"}), @ORM\Index(name="FK_civicrm_group_contact_location_id", columns={"location_id"}), @ORM\Index(name="FK_civicrm_group_contact_email_id", columns={"email_id"}), @ORM\Index(name="IDX_6516EF2E7A1254A", columns={"contact_id"})})
 * @ORM\Entity
 */
class GroupContact extends \Civi\Core\Entity
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
     * @ORM\Column(name="status", type="string", nullable=true)
     */
    private $status;

    /**
     * @var \Civi\Contact\Group
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Group")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     * })
     */
    private $group;

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
     * @var \Civi\Core\LocBlock
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\LocBlock")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="location_id", referencedColumnName="id")
     * })
     */
    private $location;

    /**
     * @var \Civi\Core\Email
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Email")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="email_id", referencedColumnName="id")
     * })
     */
    private $email;



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
     * Set status
     *
     * @param string $status
     * @return GroupContact
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set group
     *
     * @param \Civi\Contact\Group $group
     * @return GroupContact
     */
    public function setGroup(\Civi\Contact\Group $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return \Civi\Contact\Group 
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return GroupContact
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
     * Set location
     *
     * @param \Civi\Core\LocBlock $location
     * @return GroupContact
     */
    public function setLocation(\Civi\Core\LocBlock $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \Civi\Core\LocBlock 
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set email
     *
     * @param \Civi\Core\Email $email
     * @return GroupContact
     */
    public function setEmail(\Civi\Core\Email $email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return \Civi\Core\Email 
     */
    public function getEmail()
    {
        return $this->email;
    }
}
