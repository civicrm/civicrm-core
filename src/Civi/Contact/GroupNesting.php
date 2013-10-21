<?php

namespace Civi\Contact;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupNesting
 *
 * @ORM\Table(name="civicrm_group_nesting", indexes={@ORM\Index(name="FK_civicrm_group_nesting_child_group_id", columns={"child_group_id"}), @ORM\Index(name="FK_civicrm_group_nesting_parent_group_id", columns={"parent_group_id"})})
 * @ORM\Entity
 */
class GroupNesting extends \Civi\Core\Entity
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
     *   @ORM\JoinColumn(name="child_group_id", referencedColumnName="id")
     * })
     */
    private $childGroup;

    /**
     * @var \Civi\Contact\Group
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Group")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_group_id", referencedColumnName="id")
     * })
     */
    private $parentGroup;



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
     * Set childGroup
     *
     * @param \Civi\Contact\Group $childGroup
     * @return GroupNesting
     */
    public function setChildGroup(\Civi\Contact\Group $childGroup = null)
    {
        $this->childGroup = $childGroup;

        return $this;
    }

    /**
     * Get childGroup
     *
     * @return \Civi\Contact\Group 
     */
    public function getChildGroup()
    {
        return $this->childGroup;
    }

    /**
     * Set parentGroup
     *
     * @param \Civi\Contact\Group $parentGroup
     * @return GroupNesting
     */
    public function setParentGroup(\Civi\Contact\Group $parentGroup = null)
    {
        $this->parentGroup = $parentGroup;

        return $this;
    }

    /**
     * Get parentGroup
     *
     * @return \Civi\Contact\Group 
     */
    public function getParentGroup()
    {
        return $this->parentGroup;
    }
}
