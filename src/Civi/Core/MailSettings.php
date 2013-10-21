<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailSettings
 *
 * @ORM\Table(name="civicrm_mail_settings")
 * @ORM\Entity
 */
class MailSettings extends \Civi\Core\Entity
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
     * @ORM\Column(name="domain_id", type="integer", nullable=false)
     */
    private $domainId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault;

    /**
     * @var string
     *
     * @ORM\Column(name="domain", type="string", length=255, nullable=true)
     */
    private $domain;

    /**
     * @var string
     *
     * @ORM\Column(name="localpart", type="string", length=255, nullable=true)
     */
    private $localpart;

    /**
     * @var string
     *
     * @ORM\Column(name="return_path", type="string", length=255, nullable=true)
     */
    private $returnPath;

    /**
     * @var string
     *
     * @ORM\Column(name="protocol", type="string", length=255, nullable=true)
     */
    private $protocol;

    /**
     * @var string
     *
     * @ORM\Column(name="server", type="string", length=255, nullable=true)
     */
    private $server;

    /**
     * @var integer
     *
     * @ORM\Column(name="port", type="integer", nullable=true)
     */
    private $port;

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
     * @var boolean
     *
     * @ORM\Column(name="is_ssl", type="boolean", nullable=true)
     */
    private $isSsl;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     */
    private $source;



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
     * Set domainId
     *
     * @param integer $domainId
     * @return MailSettings
     */
    public function setDomainId($domainId)
    {
        $this->domainId = $domainId;

        return $this;
    }

    /**
     * Get domainId
     *
     * @return integer 
     */
    public function getDomainId()
    {
        return $this->domainId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return MailSettings
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
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return MailSettings
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
     * Set domain
     *
     * @param string $domain
     * @return MailSettings
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string 
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set localpart
     *
     * @param string $localpart
     * @return MailSettings
     */
    public function setLocalpart($localpart)
    {
        $this->localpart = $localpart;

        return $this;
    }

    /**
     * Get localpart
     *
     * @return string 
     */
    public function getLocalpart()
    {
        return $this->localpart;
    }

    /**
     * Set returnPath
     *
     * @param string $returnPath
     * @return MailSettings
     */
    public function setReturnPath($returnPath)
    {
        $this->returnPath = $returnPath;

        return $this;
    }

    /**
     * Get returnPath
     *
     * @return string 
     */
    public function getReturnPath()
    {
        return $this->returnPath;
    }

    /**
     * Set protocol
     *
     * @param string $protocol
     * @return MailSettings
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Get protocol
     *
     * @return string 
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Set server
     *
     * @param string $server
     * @return MailSettings
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get server
     *
     * @return string 
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Set port
     *
     * @param integer $port
     * @return MailSettings
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get port
     *
     * @return integer 
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return MailSettings
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
     * @return MailSettings
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
     * Set isSsl
     *
     * @param boolean $isSsl
     * @return MailSettings
     */
    public function setIsSsl($isSsl)
    {
        $this->isSsl = $isSsl;

        return $this;
    }

    /**
     * Get isSsl
     *
     * @return boolean 
     */
    public function getIsSsl()
    {
        return $this->isSsl;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return MailSettings
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string 
     */
    public function getSource()
    {
        return $this->source;
    }
}
