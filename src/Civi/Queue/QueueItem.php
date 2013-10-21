<?php

namespace Civi\Queue;

use Doctrine\ORM\Mapping as ORM;

/**
 * QueueItem
 *
 * @ORM\Table(name="civicrm_queue_item", indexes={@ORM\Index(name="index_queueids", columns={"queue_name", "weight", "id"})})
 * @ORM\Entity
 */
class QueueItem extends \Civi\Core\Entity
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
     * @ORM\Column(name="queue_name", type="string", length=64, nullable=false)
     */
    private $queueName;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    private $weight;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="submit_time", type="datetime", nullable=false)
     */
    private $submitTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="release_time", type="datetime", nullable=true)
     */
    private $releaseTime;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;



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
     * Set queueName
     *
     * @param string $queueName
     * @return QueueItem
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;

        return $this;
    }

    /**
     * Get queueName
     *
     * @return string 
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return QueueItem
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
     * Set submitTime
     *
     * @param \DateTime $submitTime
     * @return QueueItem
     */
    public function setSubmitTime($submitTime)
    {
        $this->submitTime = $submitTime;

        return $this;
    }

    /**
     * Get submitTime
     *
     * @return \DateTime 
     */
    public function getSubmitTime()
    {
        return $this->submitTime;
    }

    /**
     * Set releaseTime
     *
     * @param \DateTime $releaseTime
     * @return QueueItem
     */
    public function setReleaseTime($releaseTime)
    {
        $this->releaseTime = $releaseTime;

        return $this;
    }

    /**
     * Get releaseTime
     *
     * @return \DateTime 
     */
    public function getReleaseTime()
    {
        return $this->releaseTime;
    }

    /**
     * Set data
     *
     * @param string $data
     * @return QueueItem
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string 
     */
    public function getData()
    {
        return $this->data;
    }
}
