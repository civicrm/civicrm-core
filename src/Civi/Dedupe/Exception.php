<?php

namespace Civi\Dedupe;

use Doctrine\ORM\Mapping as ORM;

/**
 * Exception
 *
 * @ORM\Table(name="civicrm_dedupe_exception", uniqueConstraints={@ORM\UniqueConstraint(name="UI_contact_id1_contact_id2", columns={"contact_id1", "contact_id2"})}, indexes={@ORM\Index(name="FK_civicrm_dedupe_exception_contact_id2", columns={"contact_id2"}), @ORM\Index(name="IDX_91B1432B1532E61C", columns={"contact_id1"})})
 * @ORM\Entity
 */
class Exception extends \Civi\Core\Entity
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
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id1", referencedColumnName="id")
     * })
     */
    private $contact1;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id2", referencedColumnName="id")
     * })
     */
    private $contact2;



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
     * Set contact1
     *
     * @param \Civi\Contact\Contact $contact1
     * @return Exception
     */
    public function setContact1(\Civi\Contact\Contact $contact1 = null)
    {
        $this->contact1 = $contact1;

        return $this;
    }

    /**
     * Get contact1
     *
     * @return \Civi\Contact\Contact 
     */
    public function getContact1()
    {
        return $this->contact1;
    }

    /**
     * Set contact2
     *
     * @param \Civi\Contact\Contact $contact2
     * @return Exception
     */
    public function setContact2(\Civi\Contact\Contact $contact2 = null)
    {
        $this->contact2 = $contact2;

        return $this;
    }

    /**
     * Get contact2
     *
     * @return \Civi\Contact\Contact 
     */
    public function getContact2()
    {
        return $this->contact2;
    }
}
