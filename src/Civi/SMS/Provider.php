<?php

namespace Civi\SMS;

use Doctrine\ORM\Mapping as ORM;

/**
 * Provider
 *
 * @ORM\Table(name="civicrm_sms_provider")
 * @ORM\Entity
 */
class Provider extends \Civi\Core\Entity
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
     * @ORM\Column(name="title", type="string", length=64, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @var integer
     *
     * @ORM\Column(name="api_type", type="integer", nullable=false)
     */
    private $apiType;

    /**
     * @var string
     *
     * @ORM\Column(name="api_url", type="string", length=128, nullable=true)
     */
    private $apiUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="api_params", type="text", nullable=true)
     */
    private $apiParams;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '0';



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
     * @return Provider
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
     * Set title
     *
     * @param string $title
     * @return Provider
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return Provider
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return Provider
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set apiType
     *
     * @param integer $apiType
     * @return Provider
     */
    public function setApiType($apiType)
    {
        $this->apiType = $apiType;

        return $this;
    }

    /**
     * Get apiType
     *
     * @return integer 
     */
    public function getApiType()
    {
        return $this->apiType;
    }

    /**
     * Set apiUrl
     *
     * @param string $apiUrl
     * @return Provider
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;

        return $this;
    }

    /**
     * Get apiUrl
     *
     * @return string 
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * Set apiParams
     *
     * @param string $apiParams
     * @return Provider
     */
    public function setApiParams($apiParams)
    {
        $this->apiParams = $apiParams;

        return $this;
    }

    /**
     * Get apiParams
     *
     * @return string 
     */
    public function getApiParams()
    {
        return $this->apiParams;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return Provider
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Provider
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }
}
