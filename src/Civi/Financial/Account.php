<?php

namespace Civi\Financial;

use Doctrine\ORM\Mapping as ORM;

/**
 * Account
 *
 * @ORM\Table(name="civicrm_financial_account", uniqueConstraints={@ORM\UniqueConstraint(name="UI_name", columns={"name"})}, indexes={@ORM\Index(name="FK_civicrm_financial_account_contact_id", columns={"contact_id"}), @ORM\Index(name="FK_civicrm_financial_account_parent_id", columns={"parent_id"})})
 * @ORM\Entity
 */
class Account extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="financial_account_type_id", type="integer", nullable=false)
     */
    private $financialAccountTypeId = '3';

    /**
     * @var string
     *
     * @ORM\Column(name="accounting_code", type="string", length=64, nullable=true)
     */
    private $accountingCode;

    /**
     * @var string
     *
     * @ORM\Column(name="account_type_code", type="string", length=64, nullable=true)
     */
    private $accountTypeCode;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_header_account", type="boolean", nullable=true)
     */
    private $isHeaderAccount = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deductible", type="boolean", nullable=true)
     */
    private $isDeductible = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_tax", type="boolean", nullable=true)
     */
    private $isTax = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="tax_rate", type="decimal", precision=10, scale=8, nullable=true)
     */
    private $taxRate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved;

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
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     * })
     */
    private $contact;

    /**
     * @var \Civi\Financial\Account
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;



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
     * @return Account
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
     * Set financialAccountTypeId
     *
     * @param integer $financialAccountTypeId
     * @return Account
     */
    public function setFinancialAccountTypeId($financialAccountTypeId)
    {
        $this->financialAccountTypeId = $financialAccountTypeId;

        return $this;
    }

    /**
     * Get financialAccountTypeId
     *
     * @return integer 
     */
    public function getFinancialAccountTypeId()
    {
        return $this->financialAccountTypeId;
    }

    /**
     * Set accountingCode
     *
     * @param string $accountingCode
     * @return Account
     */
    public function setAccountingCode($accountingCode)
    {
        $this->accountingCode = $accountingCode;

        return $this;
    }

    /**
     * Get accountingCode
     *
     * @return string 
     */
    public function getAccountingCode()
    {
        return $this->accountingCode;
    }

    /**
     * Set accountTypeCode
     *
     * @param string $accountTypeCode
     * @return Account
     */
    public function setAccountTypeCode($accountTypeCode)
    {
        $this->accountTypeCode = $accountTypeCode;

        return $this;
    }

    /**
     * Get accountTypeCode
     *
     * @return string 
     */
    public function getAccountTypeCode()
    {
        return $this->accountTypeCode;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Account
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
     * Set isHeaderAccount
     *
     * @param boolean $isHeaderAccount
     * @return Account
     */
    public function setIsHeaderAccount($isHeaderAccount)
    {
        $this->isHeaderAccount = $isHeaderAccount;

        return $this;
    }

    /**
     * Get isHeaderAccount
     *
     * @return boolean 
     */
    public function getIsHeaderAccount()
    {
        return $this->isHeaderAccount;
    }

    /**
     * Set isDeductible
     *
     * @param boolean $isDeductible
     * @return Account
     */
    public function setIsDeductible($isDeductible)
    {
        $this->isDeductible = $isDeductible;

        return $this;
    }

    /**
     * Get isDeductible
     *
     * @return boolean 
     */
    public function getIsDeductible()
    {
        return $this->isDeductible;
    }

    /**
     * Set isTax
     *
     * @param boolean $isTax
     * @return Account
     */
    public function setIsTax($isTax)
    {
        $this->isTax = $isTax;

        return $this;
    }

    /**
     * Get isTax
     *
     * @return boolean 
     */
    public function getIsTax()
    {
        return $this->isTax;
    }

    /**
     * Set taxRate
     *
     * @param string $taxRate
     * @return Account
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    /**
     * Get taxRate
     *
     * @return string 
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return Account
     */
    public function setIsReserved($isReserved)
    {
        $this->isReserved = $isReserved;

        return $this;
    }

    /**
     * Get isReserved
     *
     * @return boolean 
     */
    public function getIsReserved()
    {
        return $this->isReserved;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Account
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
     * @return Account
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
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Account
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
     * Set parent
     *
     * @param \Civi\Financial\Account $parent
     * @return Account
     */
    public function setParent(\Civi\Financial\Account $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Civi\Financial\Account 
     */
    public function getParent()
    {
        return $this->parent;
    }
}
