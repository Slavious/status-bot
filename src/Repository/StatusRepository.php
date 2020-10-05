<?php

namespace App\Repository;

use App\Entity\Site;
use App\Entity\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use function Symfony\Component\String\s;

/**
 * @method Status|null find($id, $lockMode = null, $lockVersion = null)
 * @method Status|null findOneBy(array $criteria, array $orderBy = null)
 * @method Status[]    findAll()
 * @method Status[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatusRepository extends ServiceEntityRepository
{

    public const PERIOD_DAY = 'day';
    public const PERIOD_WEEK = 'week';
    public const PERIOD_MONTH = 'month';
    public const PERIOD_YEAR = 'year';

    public const CODE_OK = 200;
    public const CODE_SERVER_500 = 500;
    public const CODE_SERVER_502 = 502;
    public const CODE_SERVER_503 = 503;
    public const CODE_SERVER_504 = 504;
    public const CODE_SERVER_0 = 0;

    private $periodStart;
    private $periodEnd;
    private $groupBy;

    /**
     * StatusRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Status::class);
    }

    /**
     * @param $site
     * @param string $period
     * @return int|mixed|string
     */
    public function getLogsBySite($site, $period)
    {
        $this->getPeriod($period);

        return $this->createQueryBuilder('log')
            ->select('log.datetime as datetime', 'MAX(log.latency) as max_latency', 'AVG(log.latency) as latency', "$this->groupBy as group")
            ->where('log.log_site = :site')->setParameter('site', $site)
            ->andWhere('log.datetime BETWEEN :start AND :end')
                ->setParameter('start', $this->periodStart)
                ->setParameter('end', $this->periodEnd)
            ->orderBy('log.datetime')
            ->groupBy('group')
            ->getQuery()->getResult()
        ;
    }

    public function getPeriod($period)
    {
        switch ($period) {
            case self::PERIOD_DAY:
                $this->periodStart = (new \DateTime('today'))->format('Y-m-d H:m:s');
                $this->periodEnd = (new \DateTime('tomorrow'))->format('Y-m-d H:m:s');
                $this->groupBy = 'log.datetime';
                break;
            case self::PERIOD_WEEK:
                $this->periodStart = (new \DateTime('first day of this week'))->format('Y-m-d H:m:s');
                $this->periodEnd = (new \DateTime('last day of this week'))->format('Y-m-d H:m:s');
                $this->groupBy = 'DAY(log.datetime)';
                break;
            case self::PERIOD_MONTH:
                $this->periodStart = (new \DateTime('first day of this month'))->format('Y-m-d H:m:s');
                $this->periodEnd = (new \DateTime('last day of this month'))->format('Y-m-d H:m:s');
                $this->groupBy = 'MONTH(log.datetime)';
                break;
            case self::PERIOD_YEAR:
                $this->periodStart = (new \DateTime('first day of January this year'))->format('Y-m-d H:m:s');
                $this->periodEnd = (new \DateTime('last day of December this year'))->format('Y-m-d H:m:s');
                $this->groupBy = 'YEAR(log.datetime)';
                break;
        }
    }

    public function getLastStatus(Site $site)
    {
        return $this->findOneBy(['log_site' => $site], ['datetime' => 'DESC']);
    }
}
