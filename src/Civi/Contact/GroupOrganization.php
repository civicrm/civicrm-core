<?php

namespace Civi\Contact;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupOrganization
 *
 * @ORM\Table(name="civicrm_group_organization", uniqueConstraints={@ORM\UniqueConstraint(name="UI_group_organization", columns={"group_id", "organization_id"})}, indexes={@ORM\Index(name="FK_civicrm_group_organization_organization_id", columns={"organization_id"}), @ORM\Index(name="IDX_661DF194FE54D947", columns={"group_id"})})
 * @ORM\Entity
 */
class GroupOrganization extends \Civi\Core\Entity
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
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     * })
     */
    private $organization;



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
     * @return GroupOrganization
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
     * Set organization
     *
     * @param \Civi\Contact\Contact $organization
     * @return GroupOrganization
     */
    public function setOrganization(\Civi\Contact\Contact $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return \Civi\Contact\Contact 
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
