<?php

namespace Civi\Contribute;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContributionProduct
 *
 * @ORM\Table(name="civicrm_contribution_product", indexes={@ORM\Index(name="FK_civicrm_contribution_product_contribution_id", columns={"contribution_id"}), @ORM\Index(name="FK_civicrm_contribution_product_financial_type_id", columns={"financial_type_id"})})
 * @ORM\Entity
 */
class ContributionProduct extends \Civi\Core\Entity
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
     * @ORM\Column(name="product_id", type="integer", nullable=false)
     */
    private $productId;

    /**
     * @var string
     *
     * @ORM\Column(name="product_option", type="string", length=255, nullable=true)
     */
    private $productOption;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer", nullable=true)
     */
    private $quantity;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="fulfilled_date", type="date", nullable=true)
     */
    private $fulfilledDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="date", nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="date", nullable=true)
     */
    private $endDate;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;

    /**
     * @var \Civi\Contribute\Contribution
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contribute\Contribution")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contribution_id", referencedColumnName="id")
     * })
     */
    private $contribution;

    /**
     * @var \Civi\Financial\Type
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\Type")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="financial_type_id", referencedColumnName="id")
     * })
     */
    private $financialType;



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
     * Set productId
     *
     * @param integer $productId
     * @return ContributionProduct
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;

        return $this;
    }

    /**
     * Get productId
     *
     * @return integer 
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Set productOption
     *
     * @param string $productOption
     * @return ContributionProduct
     */
    public function setProductOption($productOption)
    {
        $this->productOption = $productOption;

        return $this;
    }

    /**
     * Get productOption
     *
     * @return string 
     */
    public function getProductOption()
    {
        return $this->productOption;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return ContributionProduct
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set fulfilledDate
     *
     * @param \DateTime $fulfilledDate
     * @return ContributionProduct
     */
    public function setFulfilledDate($fulfilledDate)
    {
        $this->fulfilledDate = $fulfilledDate;

        return $this;
    }

    /**
     * Get fulfilledDate
     *
     * @return \DateTime 
     */
    public function getFulfilledDate()
    {
        return $this->fulfilledDate;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return ContributionProduct
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return ContributionProduct
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return ContributionProduct
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set contribution
     *
     * @param \Civi\Contribute\Contribution $contribution
     * @return ContributionProduct
     */
    public function setContribution(\Civi\Contribute\Contribution $contribution = null)
    {
        $this->contribution = $contribution;

        return $this;
    }

    /**
     * Get contribution
     *
     * @return \Civi\Contribute\Contribution 
     */
    public function getContribution()
    {
        return $this->contribution;
    }

    /**
     * Set financialType
     *
     * @param \Civi\Financial\Type $financialType
     * @return ContributionProduct
     */
    public function setFinancialType(\Civi\Financial\Type $financialType = null)
    {
        $this->financialType = $financialType;

        return $this;
    }

    /**
     * Get financialType
     *
     * @return \Civi\Financial\Type 
     */
    public function getFinancialType()
    {
        return $this->financialType;
    }
}
