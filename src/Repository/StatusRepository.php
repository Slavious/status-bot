<?php

namespace App\Repository;

use App\Entity\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Status|null find($id, $lockMode = null, $lockVersion = null)
 * @method Status|null findOneBy(array $criteria, array $orderBy = null)
 * @method Status[]    findAll()
 * @method Status[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Status::class);
    }

    public const PERIOD_DAY = 'day';
    public const PERIOD_WEEK = 'week';
    public const PERIOD_MONTH = 'month';
    public const PERIOD_YEAR = 'year';

    /**
     * @param $site
     * @param string $period
     * @return int|mixed|string
     */
    public function getLogsBySite($site, $period)
    {
        $periodStart = $periodEnd = $groupBy = false;
        switch ($period) {
            case self::PERIOD_DAY:
                $periodStart = (new \DateTime('today'))->format('Y-m-d H:m:s');
                $periodEnd = (new \DateTime('tomorrow'))->format('Y-m-d H:m:s');
                $groupBy = 'log.datetime';
                break;
            case self::PERIOD_WEEK:
                $periodStart = (new \DateTime('first day of this week'))->format('Y-m-d H:m:s');
                $periodEnd = (new \DateTime('last day of this week'))->format('Y-m-d H:m:s');
                $groupBy = 'DAY(log.datetime)';
                break;
            case self::PERIOD_MONTH:
                $periodStart = (new \DateTime('first day of this month'))->format('Y-m-d H:m:s');
                $periodEnd = (new \DateTime('last day of this month'))->format('Y-m-d H:m:s');
                $groupBy = 'MONTH(log.datetime)';
                break;
            case self::PERIOD_YEAR:
                $periodStart = (new \DateTime('first day of January this year'))->format('Y-m-d H:m:s');
                $periodEnd = (new \DateTime('last day of December this year'))->format('Y-m-d H:m:s');
                $groupBy = 'YEAR(log.datetime)';
                break;
        }
        return $this->createQueryBuilder('log')
            ->select('log.datetime as datetime', 'log.latency as latency', "$groupBy as group")
            ->where('log.log_site = :site')->setParameter('site', $site)
            ->andWhere('log.datetime BETWEEN :start AND :end')
                ->setParameter('start', $periodStart)
                ->setParameter('end', $periodEnd)
            ->orderBy('log.datetime')
            ->groupBy('group')
            ->getQuery()->getResult()
        ;
    }
}
