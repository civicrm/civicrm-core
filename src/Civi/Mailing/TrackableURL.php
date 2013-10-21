<?php

namespace Civi\Mailing;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackableURL
 *
 * @ORM\Table(name="civicrm_mailing_trackable_url", indexes={@ORM\Index(name="FK_civicrm_mailing_trackable_url_mailing_id", columns={"mailing_id"})})
 * @ORM\Entity
 */
class TrackableURL extends \Civi\Core\Entity
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
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    private $url;

    /**
     * @var \Civi\Mailing\Mailing
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Mailing")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="mailing_id", referencedColumnName="id")
     * })
     */
    private $mailing;



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
     * Set url
     *
     * @param string $url
     * @return TrackableURL
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set mailing
     *
     * @param \Civi\Mailing\Mailing $mailing
     * @return TrackableURL
     */
    public function setMailing(\Civi\Mailing\Mailing $mailing = null)
    {
        $this->mailing = $mailing;

        return $this;
    }

    /**
     * Get mailing
     *
     * @return \Civi\Mailing\Mailing 
     */
    public function getMailing()
    {
        return $this->mailing;
    }
}
