<?php

namespace Civi\CCase;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contact
 *
 * @ORM\Table(name="civicrm_case_contact", indexes={@ORM\Index(name="UI_case_contact_id", columns={"case_id", "contact_id"}), @ORM\Index(name="FK_civicrm_case_contact_contact_id", columns={"contact_id"}), @ORM\Index(name="IDX_DC6A4087CF10D4F5", columns={"case_id"})})
 * @ORM\Entity
 */
class Contact extends \Civi\Core\Entity
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
     * @var \Civi\CCase\CCase
     *
     * @ORM\ManyToOne(targetEntity="Civi\CCase\CCase")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="case_id", referencedColumnName="id")
     * })
     */
    private $case;

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set case
     *
     * @param \Civi\CCase\CCase $case
     * @return Contact
     */
    public function setCase(\Civi\CCase\CCase $case = null)
    {
        $this->case = $case;

        return $this;
    }

    /**
     * Get case
     *
     * @return \Civi\CCase\CCase 
     */
    public function getCase()
    {
        return $this->case;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Contact
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
}
