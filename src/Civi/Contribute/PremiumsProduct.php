<?php

namespace Civi\Contribute;

use Doctrine\ORM\Mapping as ORM;

/**
 * PremiumsProduct
 *
 * @ORM\Table(name="civicrm_premiums_product", indexes={@ORM\Index(name="FK_civicrm_premiums_product_premiums_id", columns={"premiums_id"}), @ORM\Index(name="FK_civicrm_premiums_product_product_id", columns={"product_id"}), @ORM\Index(name="FK_civicrm_premiums_product_financial_type_id", columns={"financial_type_id"})})
 * @ORM\Entity
 */
class PremiumsProduct extends \Civi\Core\Entity
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
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    private $weight;

    /**
     * @var \Civi\Contribute\Premium
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contribute\Premium")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="premiums_id", referencedColumnName="id")
     * })
     */
    private $premiums;

    /**
     * @var \Civi\Contribute\Product
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contribute\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $product;

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
     * Set weight
     *
     * @param integer $weight
     * @return PremiumsProduct
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set premiums
     *
     * @param \Civi\Contribute\Premium $premiums
     * @return PremiumsProduct
     */
    public function setPremiums(\Civi\Contribute\Premium $premiums = null)
    {
        $this->premiums = $premiums;

        return $this;
    }

    /**
     * Get premiums
     *
     * @return \Civi\Contribute\Premium 
     */
    public function getPremiums()
    {
        return $this->premiums;
    }

    /**
     * Set product
     *
     * @param \Civi\Contribute\Product $product
     * @return PremiumsProduct
     */
    public function setProduct(\Civi\Contribute\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \Civi\Contribute\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set financialType
     *
     * @param \Civi\Financial\Type $financialType
     * @return PremiumsProduct
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
