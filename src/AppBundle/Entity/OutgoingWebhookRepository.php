<?php


namespace AppBundle\Entity;


use Doctrine\ORM\EntityRepository;

class OutgoingWebhookRepository extends EntityRepository
{
    /**
     * @return OutgoingWebhook[]
     */
    public function findAwaitingDelivery($maxNumRetries)
    {
        return $this->createQueryBuilder('wh')
            ->where('
                wh.deliveredAt is null
                and
                wh.numFailures < :maxNumRetries
                and
                (
                    wh.lastAttemptedAt is null
                    or
                    wh.lastAttemptedAt < :tenMinutesAgo
                )
            ')
            ->setParameter('tenMinutesAgo', new \DateTime('-10 minutes'))
            ->setParameter('maxNumRetries', $maxNumRetries)
            ->orderBy('wh.queuedAt', 'ASC')
            ->getQuery()->getResult()
        ;
    }
}