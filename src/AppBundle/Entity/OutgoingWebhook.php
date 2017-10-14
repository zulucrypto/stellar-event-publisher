<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\OutgoingWebhookRepository")
 * @ORM\Table(name="outgoing_webhooks")
 */
class OutgoingWebhook
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    protected $id;

    /**
     * The type of event that generated this webhook
     *
     * See the SOURCE_ constants in DeliveryDestination
     *
     * @var string
     *
     * @ORM\Column(name="sourceType", type="string", length=255, nullable=true)
     */
    protected $sourceType;

    /**
     * The URL that the payload will be delivered to
     *
     * @var string
     *
     * @ORM\Column(name="destination", type="text", nullable=false)
     */
    protected $destination;

    /**
     * Json-encoded payload that will be delivered to the webhook
     *
     * @var string
     *
     * @ORM\Column(name="payload", type="json_array", nullable=true)
     */
    protected $payload;

    /**
     * How many times delivery has failed
     *
     * @var int
     *
     * @ORM\Column(name="numFailures", type="integer", nullable=false)
     */
    protected $numFailures;

    /**
     * When this webhook was added to the queue to be delivered
     *
     * @var \DateTime
     *
     * @ORM\Column(name="queuedAt", type="datetime", nullable=false)
     */
    protected $queuedAt;

    /**
     * The last time delivery was attempted
     *
     * @var \DateTime
     *
     * @ORM\Column(name="lastAttemptedAt", type="datetime", nullable=true)
     */
    protected $lastAttemptedAt;

    /**
     * When the payload was successfully delivered
     *
     * @var \DateTime
     *
     * @ORM\Column(name="deliveredAt", type="datetime", nullable=true)
     */
    protected $deliveredAt;

    /**
     * Text description explaining why the most recent delivery failed
     *
     * @var string
     *
     * @ORM\Column(name="lastFailureReason", type="string", length=255, nullable=true)
     */
    protected $lastFailureReason;

    public function __construct($sourceType, $destination, $payload = [])
    {
        $this->sourceType = $sourceType;
        $this->destination = $destination;
        $this->payload = $payload;

        $this->numFailures = 0;
        $this->queuedAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @param string $destination
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    /**
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param string $payload
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return int
     */
    public function getNumFailures()
    {
        return $this->numFailures;
    }

    /**
     * @param int $numFailures
     */
    public function setNumFailures($numFailures)
    {
        $this->numFailures = $numFailures;
    }

    /**
     * @return \DateTime
     */
    public function getLastAttemptedAt()
    {
        return $this->lastAttemptedAt;
    }

    /**
     * @param \DateTime $lastAttemptedAt
     */
    public function setLastAttemptedAt($lastAttemptedAt)
    {
        $this->lastAttemptedAt = $lastAttemptedAt;
    }

    /**
     * @return \DateTime
     */
    public function getDeliveredAt()
    {
        return $this->deliveredAt;
    }

    /**
     * @param \DateTime $deliveredAt
     */
    public function setDeliveredAt($deliveredAt)
    {
        $this->deliveredAt = $deliveredAt;
    }

    /**
     * @return \DateTime
     */
    public function getQueuedAt()
    {
        return $this->queuedAt;
    }

    /**
     * @param \DateTime $queuedAt
     */
    public function setQueuedAt($queuedAt)
    {
        $this->queuedAt = $queuedAt;
    }

    /**
     * @return string
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * @param string $sourceType
     */
    public function setSourceType($sourceType)
    {
        $this->sourceType = $sourceType;
    }

    /**
     * @return string
     */
    public function getLastFailureReason()
    {
        return $this->lastFailureReason;
    }

    /**
     * @param string $lastFailureReason
     */
    public function setLastFailureReason($lastFailureReason)
    {
        $this->lastFailureReason = $lastFailureReason;
    }
}