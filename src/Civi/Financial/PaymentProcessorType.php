<?php

namespace Civi\Financial;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentProcessorType
 *
 * @ORM\Table(name="civicrm_payment_processor_type", uniqueConstraints={@ORM\UniqueConstraint(name="UI_name", columns={"name"})})
 * @ORM\Entity
 */
class PaymentProcessorType extends \Civi\Core\Entity
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
     * @ORM\Column(name="title", type="string", length=127, nullable=true)
     */
    private $title;

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
     * @var string
     *
     * @ORM\Column(name="user_name_label", type="string", length=255, nullable=true)
     */
    private $userNameLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="password_label", type="string", length=255, nullable=true)
     */
    private $passwordLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="signature_label", type="string", length=255, nullable=true)
     */
    private $signatureLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="subject_label", type="string", length=255, nullable=true)
     */
    private $subjectLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="class_name", type="string", length=255, nullable=true)
     */
    private $className;

    /**
     * @var string
     *
     * @ORM\Column(name="url_site_default", type="string", length=255, nullable=true)
     */
    private $urlSiteDefault;

    /**
     * @var string
     *
     * @ORM\Column(name="url_api_default", type="string", length=255, nullable=true)
     */
    private $urlApiDefault;

    /**
     * @var string
     *
     * @ORM\Column(name="url_recur_default", type="string", length=255, nullable=true)
     */
    private $urlRecurDefault;

    /**
     * @var string
     *
     * @ORM\Column(name="url_button_default", type="string", length=255, nullable=true)
     */
    private $urlButtonDefault;

    /**
     * @var string
     *
     * @ORM\Column(name="url_site_test_default", type="string", length=255, nullable=true)
     */
    private $urlSiteTestDefault;

    /**
     * @var string
     *
     * @ORM\Column(name="url_api_test_default", type="string", length=255, nullable=true)
     */
    private $urlApiTestDefault;

    /**
     * @var string
     *
     * @ORM\Column(name="url_recur_test_default", type="string", length=255, nullable=true)
     */
    private $urlRecurTestDefault;

    /**
     * @var string
     *
     * @ORM\Column(name="url_button_test_default", type="string", length=255, nullable=true)
     */
    private $urlButtonTestDefault;

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
     * @return PaymentProcessorType
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
     * @return PaymentProcessorType
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
     * Set description
     *
     * @param string $description
     * @return PaymentProcessorType
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
     * @return PaymentProcessorType
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
     * @return PaymentProcessorType
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
     * Set userNameLabel
     *
     * @param string $userNameLabel
     * @return PaymentProcessorType
     */
    public function setUserNameLabel($userNameLabel)
    {
        $this->userNameLabel = $userNameLabel;

        return $this;
    }

    /**
     * Get userNameLabel
     *
     * @return string 
     */
    public function getUserNameLabel()
    {
        return $this->userNameLabel;
    }

    /**
     * Set passwordLabel
     *
     * @param string $passwordLabel
     * @return PaymentProcessorType
     */
    public function setPasswordLabel($passwordLabel)
    {
        $this->passwordLabel = $passwordLabel;

        return $this;
    }

    /**
     * Get passwordLabel
     *
     * @return string 
     */
    public function getPasswordLabel()
    {
        return $this->passwordLabel;
    }

    /**
     * Set signatureLabel
     *
     * @param string $signatureLabel
     * @return PaymentProcessorType
     */
    public function setSignatureLabel($signatureLabel)
    {
        $this->signatureLabel = $signatureLabel;

        return $this;
    }

    /**
     * Get signatureLabel
     *
     * @return string 
     */
    public function getSignatureLabel()
    {
        return $this->signatureLabel;
    }

    /**
     * Set subjectLabel
     *
     * @param string $subjectLabel
     * @return PaymentProcessorType
     */
    public function setSubjectLabel($subjectLabel)
    {
        $this->subjectLabel = $subjectLabel;

        return $this;
    }

    /**
     * Get subjectLabel
     *
     * @return string 
     */
    public function getSubjectLabel()
    {
        return $this->subjectLabel;
    }

    /**
     * Set className
     *
     * @param string $className
     * @return PaymentProcessorType
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
     * Set urlSiteDefault
     *
     * @param string $urlSiteDefault
     * @return PaymentProcessorType
     */
    public function setUrlSiteDefault($urlSiteDefault)
    {
        $this->urlSiteDefault = $urlSiteDefault;

        return $this;
    }

    /**
     * Get urlSiteDefault
     *
     * @return string 
     */
    public function getUrlSiteDefault()
    {
        return $this->urlSiteDefault;
    }

    /**
     * Set urlApiDefault
     *
     * @param string $urlApiDefault
     * @return PaymentProcessorType
     */
    public function setUrlApiDefault($urlApiDefault)
    {
        $this->urlApiDefault = $urlApiDefault;

        return $this;
    }

    /**
     * Get urlApiDefault
     *
     * @return string 
     */
    public function getUrlApiDefault()
    {
        return $this->urlApiDefault;
    }

    /**
     * Set urlRecurDefault
     *
     * @param string $urlRecurDefault
     * @return PaymentProcessorType
     */
    public function setUrlRecurDefault($urlRecurDefault)
    {
        $this->urlRecurDefault = $urlRecurDefault;

        return $this;
    }

    /**
     * Get urlRecurDefault
     *
     * @return string 
     */
    public function getUrlRecurDefault()
    {
        return $this->urlRecurDefault;
    }

    /**
     * Set urlButtonDefault
     *
     * @param string $urlButtonDefault
     * @return PaymentProcessorType
     */
    public function setUrlButtonDefault($urlButtonDefault)
    {
        $this->urlButtonDefault = $urlButtonDefault;

        return $this;
    }

    /**
     * Get urlButtonDefault
     *
     * @return string 
     */
    public function getUrlButtonDefault()
    {
        return $this->urlButtonDefault;
    }

    /**
     * Set urlSiteTestDefault
     *
     * @param string $urlSiteTestDefault
     * @return PaymentProcessorType
     */
    public function setUrlSiteTestDefault($urlSiteTestDefault)
    {
        $this->urlSiteTestDefault = $urlSiteTestDefault;

        return $this;
    }

    /**
     * Get urlSiteTestDefault
     *
     * @return string 
     */
    public function getUrlSiteTestDefault()
    {
        return $this->urlSiteTestDefault;
    }

    /**
     * Set urlApiTestDefault
     *
     * @param string $urlApiTestDefault
     * @return PaymentProcessorType
     */
    public function setUrlApiTestDefault($urlApiTestDefault)
    {
        $this->urlApiTestDefault = $urlApiTestDefault;

        return $this;
    }

    /**
     * Get urlApiTestDefault
     *
     * @return string 
     */
    public function getUrlApiTestDefault()
    {
        return $this->urlApiTestDefault;
    }

    /**
     * Set urlRecurTestDefault
     *
     * @param string $urlRecurTestDefault
     * @return PaymentProcessorType
     */
    public function setUrlRecurTestDefault($urlRecurTestDefault)
    {
        $this->urlRecurTestDefault = $urlRecurTestDefault;

        return $this;
    }

    /**
     * Get urlRecurTestDefault
     *
     * @return string 
     */
    public function getUrlRecurTestDefault()
    {
        return $this->urlRecurTestDefault;
    }

    /**
     * Set urlButtonTestDefault
     *
     * @param string $urlButtonTestDefault
     * @return PaymentProcessorType
     */
    public function setUrlButtonTestDefault($urlButtonTestDefault)
    {
        $this->urlButtonTestDefault = $urlButtonTestDefault;

        return $this;
    }

    /**
     * Get urlButtonTestDefault
     *
     * @return string 
     */
    public function getUrlButtonTestDefault()
    {
        return $this->urlButtonTestDefault;
    }

    /**
     * Set billingMode
     *
     * @param integer $billingMode
     * @return PaymentProcessorType
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
     * @return PaymentProcessorType
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
     * @return PaymentProcessorType
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
}
