<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocBlock
 *
 * @ORM\Table(name="civicrm_loc_block", indexes={@ORM\Index(name="FK_civicrm_loc_block_address_id", columns={"address_id"}), @ORM\Index(name="FK_civicrm_loc_block_email_id", columns={"email_id"}), @ORM\Index(name="FK_civicrm_loc_block_phone_id", columns={"phone_id"}), @ORM\Index(name="FK_civicrm_loc_block_im_id", columns={"im_id"}), @ORM\Index(name="FK_civicrm_loc_block_address_2_id", columns={"address_2_id"}), @ORM\Index(name="FK_civicrm_loc_block_email_2_id", columns={"email_2_id"}), @ORM\Index(name="FK_civicrm_loc_block_phone_2_id", columns={"phone_2_id"}), @ORM\Index(name="FK_civicrm_loc_block_im_2_id", columns={"im_2_id"})})
 * @ORM\Entity
 */
class LocBlock extends \Civi\Core\Entity
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
     * @var \Civi\Core\Address
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Address")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     * })
     */
    private $address;

    /**
     * @var \Civi\Core\Email
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Email")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="email_id", referencedColumnName="id")
     * })
     */
    private $email;

    /**
     * @var \Civi\Core\Phone
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Phone")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="phone_id", referencedColumnName="id")
     * })
     */
    private $phone;

    /**
     * @var \Civi\Core\IM
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\IM")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="im_id", referencedColumnName="id")
     * })
     */
    private $im;

    /**
     * @var \Civi\Core\Address
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Address")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="address_2_id", referencedColumnName="id")
     * })
     */
    private $address2;

    /**
     * @var \Civi\Core\Email
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Email")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="email_2_id", referencedColumnName="id")
     * })
     */
    private $email2;

    /**
     * @var \Civi\Core\Phone
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Phone")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="phone_2_id", referencedColumnName="id")
     * })
     */
    private $phone2;

    /**
     * @var \Civi\Core\IM
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\IM")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="im_2_id", referencedColumnName="id")
     * })
     */
    private $im2;



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
     * Set address
     *
     * @param \Civi\Core\Address $address
     * @return LocBlock
     */
    public function setAddress(\Civi\Core\Address $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return \Civi\Core\Address 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set email
     *
     * @param \Civi\Core\Email $email
     * @return LocBlock
     */
    public function setEmail(\Civi\Core\Email $email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return \Civi\Core\Email 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set phone
     *
     * @param \Civi\Core\Phone $phone
     * @return LocBlock
     */
    public function setPhone(\Civi\Core\Phone $phone = null)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return \Civi\Core\Phone 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set im
     *
     * @param \Civi\Core\IM $im
     * @return LocBlock
     */
    public function setIm(\Civi\Core\IM $im = null)
    {
        $this->im = $im;

        return $this;
    }

    /**
     * Get im
     *
     * @return \Civi\Core\IM 
     */
    public function getIm()
    {
        return $this->im;
    }

    /**
     * Set address2
     *
     * @param \Civi\Core\Address $address2
     * @return LocBlock
     */
    public function setAddress2(\Civi\Core\Address $address2 = null)
    {
        $this->address2 = $address2;

        return $this;
    }

    /**
     * Get address2
     *
     * @return \Civi\Core\Address 
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * Set email2
     *
     * @param \Civi\Core\Email $email2
     * @return LocBlock
     */
    public function setEmail2(\Civi\Core\Email $email2 = null)
    {
        $this->email2 = $email2;

        return $this;
    }

    /**
     * Get email2
     *
     * @return \Civi\Core\Email 
     */
    public function getEmail2()
    {
        return $this->email2;
    }

    /**
     * Set phone2
     *
     * @param \Civi\Core\Phone $phone2
     * @return LocBlock
     */
    public function setPhone2(\Civi\Core\Phone $phone2 = null)
    {
        $this->phone2 = $phone2;

        return $this;
    }

    /**
     * Get phone2
     *
     * @return \Civi\Core\Phone 
     */
    public function getPhone2()
    {
        return $this->phone2;
    }

    /**
     * Set im2
     *
     * @param \Civi\Core\IM $im2
     * @return LocBlock
     */
    public function setIm2(\Civi\Core\IM $im2 = null)
    {
        $this->im2 = $im2;

        return $this;
    }

    /**
     * Get im2
     *
     * @return \Civi\Core\IM 
     */
    public function getIm2()
    {
        return $this->im2;
    }
}
