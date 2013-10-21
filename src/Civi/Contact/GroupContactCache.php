<?php

namespace Civi\Contact;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupContactCache
 *
 * @ORM\Table(name="civicrm_group_contact_cache", uniqueConstraints={@ORM\UniqueConstraint(name="UI_contact_group", columns={"contact_id", "group_id"})}, indexes={@ORM\Index(name="FK_civicrm_group_contact_cache_group_id", columns={"group_id"}), @ORM\Index(name="IDX_C21BE230E7A1254A", columns={"contact_id"})})
 * @ORM\Entity
 */
class GroupContactCache extends \Civi\Core\Entity
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set group
     *
     * @param \Civi\Contact\Group $group
     * @return GroupContactCache
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
     * @return GroupContactCache
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
