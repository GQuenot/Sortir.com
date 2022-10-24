<?php

namespace App\Repository;

use App\Entity\Activity;
use DateTime;
use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Activity>
 *
 * @method Activity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activity[]    findAll()
 * @method Activity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function save(Activity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Activity $entity, bool $flush = false): void
    {
        foreach ($entity->getParticipants() as $participant) {
            $entity->removeParticipant($participant);
        }

        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllPublish()
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.state', 's')
            ->where("s.label NOT LIKE :create")
            ->andWhere(":currentDate < DATE_ADD(a.activityDate, 1, 'MONTH')")
            ->setParameter('create','Créée')
            ->setParameter('currentDate',new \DateTime());

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function findAllNotPublish(Participant $participant)
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.state', 's')
            ->where("s.label LIKE :create")
            ->andWhere('a.organizer = :organizer')
            ->setParameter('create','Créée')
            ->setParameter('organizer', $participant);

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function findByFilter(Participant $participant, array $filters)
    {
        $filter = $filters[0];
        $qb = $this->createQueryBuilder('a')
            ->join('a.state', 's')
            ->where("s.label NOT LIKE :create")
            ->andWhere(":currentDate < DATE_ADD(a.activityDate, 1, 'MONTH')")
            ->setParameter('create','Créée')
            ->setParameter('currentDate',new \DateTime());

        switch ($filter) {

            case isset($filter['isParticipant']) && isset($filter['isNotParticipant']) && isset($filter['isOrganizer']):
            case isset($filter['isNotParticipant']) && isset($filter['isOrganizer']):
            case isset($filter['isParticipant']) && isset($filter['isNotParticipant']):
                $qb->join('a.participants', 'p');
                break;

            case isset($filters[0]['isParticipant']) && isset($filters[0]['isOrganizer']):
                $qb->join('a.participants', 'p')
                    ->andWhere(':participant IN(p.id)')
                    ->setParameter('participant', $participant);
                break;
        }

        if (isset($filter['isNotParticipant']) && !isset($filter['isParticipant']) && !isset($filter['isOrganizer'])) {

            $qb->join('a.participants', 'p')
                ->andWhere(':participant NOT IN(p.id)')
                ->setParameter('participant', $participant->getId())
                ->groupBy('p.id');

            if ($filter['site'] !== "") {

                $qb->andHaving('a.site = :site')
                    ->setParameter('site', $filter['site']);
            }

            if ($filter['search'] !== "") {

                $qb->andHaving("a.name LIKE :search")
                    ->setParameter('search', "%" . $filter['search'] . "%");
            }

            if ($filter['startDate'] !== "") {

                $qb->andHaving('a.activityDate BETWEEN :startDate AND :endDate')
                    ->setParameter('startDate', $filter['startDate'])
                    ->setParameter('endDate', $filter['endDate']);
            }

            if (isset($filter['pastActivities'])) {

                $qb->join('a.state', 's')
                    ->orWhere("s.label LIKE 'Passée'");
            }
        }

        if (isset($filter['isParticipant']) && !isset($filter['isNotParticipant']) && !isset($filter['isOrganizer'])) {

            $qb->join('a.participants', 'p')
                ->andWhere(':participant IN(p.id)')
                ->setParameter('participant', $participant);
        }

        if (isset($filter['isOrganizer']) && !isset($filter['isNotParticipant']) && !isset($filter['isParticipant'])) {
            $qb->andWhere('a.organizer = :organizer')
                ->setParameter('organizer', $participant);
        }

        if (!isset($filter['isNotParticipant'])) {

            if ($filter['site'] !== "") {

                $qb->andWhere('a.site = :site')
                    ->setParameter('site', $filter['site']);
            }

            if ($filters[0]['search'] !== "") {

                $qb->andWhere("a.name LIKE :search")
                    ->setParameter('search', "%" . $filter['search'] . "%");
            }

            if ($filter['startDate'] !== "") {

                $qb->andHaving('a.activityDate BETWEEN :startDate AND :endDate')
                    ->setParameter('startDate', $filter['startDate'])
                    ->setParameter('endDate', $filter['endDate']);
            }

            if (isset($filter['pastActivities'])) {

                $qb->join('a.state', 's')
                    ->andWhere("s.label LIKE 'Passée'");
            }
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function findActivityNotArchived()
    {
        $myDate = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-1 month" ) );

        $queryBuilder = $this->createQueryBuilder('a')
            ->andWhere('a.activityDate > :val')
            ->setParameter('val', $myDate);

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }


    public function findActivitiesStarted()
    {
        $today = new DateTime();
        $queryBuilder = $this->createQueryBuilder('s')
            ->andWhere('s.activityDate <= :val')
            ->setParameter('val', $today)
            ->andWhere('s.state != 6');

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    public function findActivitiesPassed()
    {
        $today = new DateTime();
        $qb = $this->createQueryBuilder('p')
            ->where(":val >= DATE_ADD(p.activityDate, p.duration, 'MINUTE')")
            ->setParameter('val', $today)
            ->andWhere('p.state != 6');

        $query = $qb->getQuery();
        return $query->getResult();
    }

    public function findInscriptionClosed()
    {
        $today = new DateTime();
        $queryBuilder = $this->createQueryBuilder('i')
            ->andWhere('i.subLimitDate <= :val')
            ->andWhere('i.activityDate >= :val')
            ->setParameter('val', $today);

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

//    /**
//     * @return Activity[] Returns an array of Activity objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Activity
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
