<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cache
 *
 * @ORM\Table(name="civicrm_cache", uniqueConstraints={@ORM\UniqueConstraint(name="UI_group_path_date", columns={"group_name", "path", "created_date"})}, indexes={@ORM\Index(name="FK_civicrm_cache_component_id", columns={"component_id"})})
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
     * @var string
     *
     * @ORM\Column(name="group_name", type="string", length=32, nullable=false)
     */
    private $groupName;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired_date", type="datetime", nullable=true)
     */
    private $expiredDate;

    /**
     * @var \Civi\Core\Component
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Component")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="component_id", referencedColumnName="id")
     * })
     */
    private $component;



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
     * Set groupName
     *
     * @param string $groupName
     * @return Cache
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;

        return $this;
    }

    /**
     * Get groupName
     *
     * @return string 
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Cache
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set data
     *
     * @param string $data
     * @return Cache
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string 
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return Cache
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Get createdDate
     *
     * @return \DateTime 
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * Set expiredDate
     *
     * @param \DateTime $expiredDate
     * @return Cache
     */
    public function setExpiredDate($expiredDate)
    {
        $this->expiredDate = $expiredDate;

        return $this;
    }

    /**
     * Get expiredDate
     *
     * @return \DateTime 
     */
    public function getExpiredDate()
    {
        return $this->expiredDate;
    }

    /**
     * Set component
     *
     * @param \Civi\Core\Component $component
     * @return Cache
     */
    public function setComponent(\Civi\Core\Component $component = null)
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get component
     *
     * @return \Civi\Core\Component 
     */
    public function getComponent()
    {
        return $this->component;
    }
}
