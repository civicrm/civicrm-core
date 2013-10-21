<?php

namespace Civi\Financial;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentProcessor
 *
 * @ORM\Table(name="civicrm_payment_processor", uniqueConstraints={@ORM\UniqueConstraint(name="UI_name_test_domain_id", columns={"name", "is_test", "domain_id"})}, indexes={@ORM\Index(name="FK_civicrm_payment_processor_domain_id", columns={"domain_id"}), @ORM\Index(name="FK_civicrm_payment_processor_payment_processor_type_id", columns={"payment_processor_type_id"})})
 * @ORM\Entity
 */
class PaymentProcessor extends \Civi\Core\Entity
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
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_test", type="boolean", nullable=true)
     */
    private $isTest;

    /**
     * @var string
     *
     * @ORM\Column(name="user_name", type="string", length=255, nullable=true)
     */
    private $userName;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="signature", type="string", length=255, nullable=true)
     */
    private $signature;

    /**
     * @var string
     *
     * @ORM\Column(name="url_site", type="string", length=255, nullable=true)
     */
    private $urlSite;

    /**
     * @var string
     *
     * @ORM\Column(name="url_api", type="string", length=255, nullable=true)
     */
    private $urlApi;

    /**
     * @var string
     *
     * @ORM\Column(name="url_recur", type="string", length=255, nullable=true)
     */
    private $urlRecur;

    /**
     * @var string
     *
     * @ORM\Column(name="url_button", type="string", length=255, nullable=true)
     */
    private $urlButton;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="class_name", type="string", length=255, nullable=true)
     */
    private $className;

    /**
     * @var integer
     *
     * @ORM\Column(name="billing_mode", type="integer", nullable=false)
     */
    private $billingMode;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_recur", type="boolean", nullable=true)
     */
    private $isRecur;

    /**
     * @var integer
     *
     * @ORM\Column(name="payment_type", type="integer", nullable=true)
     */
    private $paymentType = '1';

    /**
     * @var \Civi\Core\Domain
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Domain")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="domain_id", referencedColumnName="id")
     * })
     */
    private $domain;

    /**
     * @var \Civi\Financial\PaymentProcessorType
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\PaymentProcessorType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payment_processor_type_id", referencedColumnName="id")
     * })
     */
    private $paymentProcessorType;



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
     * @return PaymentProcessor
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
     * Set description
     *
     * @param string $description
     * @return PaymentProcessor
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return PaymentProcessor
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

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return PaymentProcessor
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
     * Set isTest
     *
     * @param boolean $isTest
     * @return PaymentProcessor
     */
    public function setIsTest($isTest)
    {
        $this->isTest = $isTest;

        return $this;
    }

    /**
     * Get isTest
     *
     * @return boolean 
     */
    public function getIsTest()
    {
        return $this->isTest;
    }

    /**
     * Set userName
     *
     * @param string $userName
     * @return PaymentProcessor
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get userName
     *
     * @return string 
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return PaymentProcessor
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
     * Set signature
     *
     * @param string $signature
     * @return PaymentProcessor
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature
     *
     * @return string 
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Set urlSite
     *
     * @param string $urlSite
     * @return PaymentProcessor
     */
    public function setUrlSite($urlSite)
    {
        $this->urlSite = $urlSite;

        return $this;
    }

    /**
     * Get urlSite
     *
     * @return string 
     */
    public function getUrlSite()
    {
        return $this->urlSite;
    }

    /**
     * Set urlApi
     *
     * @param string $urlApi
     * @return PaymentProcessor
     */
    public function setUrlApi($urlApi)
    {
        $this->urlApi = $urlApi;

        return $this;
    }

    /**
     * Get urlApi
     *
     * @return string 
     */
    public function getUrlApi()
    {
        return $this->urlApi;
    }

    /**
     * Set urlRecur
     *
     * @param string $urlRecur
     * @return PaymentProcessor
     */
    public function setUrlRecur($urlRecur)
    {
        $this->urlRecur = $urlRecur;

        return $this;
    }

    /**
     * Get urlRecur
     *
     * @return string 
     */
    public function getUrlRecur()
    {
        return $this->urlRecur;
    }

    /**
     * Set urlButton
     *
     * @param string $urlButton
     * @return PaymentProcessor
     */
    public function setUrlButton($urlButton)
    {
        $this->urlButton = $urlButton;

        return $this;
    }

    /**
     * Get urlButton
     *
     * @return string 
     */
    public function getUrlButton()
    {
        return $this->urlButton;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return PaymentProcessor
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set className
     *
     * @param string $className
     * @return PaymentProcessor
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Get className
     *
     * @return string 
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Set billingMode
     *
     * @param integer $billingMode
     * @return PaymentProcessor
     */
    public function setBillingMode($billingMode)
    {
        $this->billingMode = $billingMode;

        return $this;
    }

    /**
     * Get billingMode
     *
     * @return integer 
     */
    public function getBillingMode()
    {
        return $this->billingMode;
    }

    /**
     * Set isRecur
     *
     * @param boolean $isRecur
     * @return PaymentProcessor
     */
    public function setIsRecur($isRecur)
    {
        $this->isRecur = $isRecur;

        return $this;
    }

    /**
     * Get isRecur
     *
     * @return boolean 
     */
    public function getIsRecur()
    {
        return $this->isRecur;
    }

    /**
     * Set paymentType
     *
     * @param integer $paymentType
     * @return PaymentProcessor
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    /**
     * Get paymentType
     *
     * @return integer 
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * Set domain
     *
     * @param \Civi\Core\Domain $domain
     * @return PaymentProcessor
     */
    public function setDomain(\Civi\Core\Domain $domain = null)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return \Civi\Core\Domain 
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set paymentProcessorType
     *
     * @param \Civi\Financial\PaymentProcessorType $paymentProcessorType
     * @return PaymentProcessor
     */
    public function setPaymentProcessorType(\Civi\Financial\PaymentProcessorType $paymentProcessorType = null)
    {
        $this->paymentProcessorType = $paymentProcessorType;

        return $this;
    }

    /**
     * Get paymentProcessorType
     *
     * @return \Civi\Financial\PaymentProcessorType 
     */
    public function getPaymentProcessorType()
    {
        return $this->paymentProcessorType;
    }
}
