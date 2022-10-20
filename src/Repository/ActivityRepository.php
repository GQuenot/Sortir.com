<?php

namespace App\Repository;

use App\Entity\Activity;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\Void_;


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

    public function findPartiesNotArchived()
    {
        $myDate = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-1 month" ) );
        dump($myDate);

        $queryBuilder = $this->createQueryBuilder('a')
            ->andWhere('a.activityDate > :val')
            ->setParameter('val', $myDate);

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    public function findActivitiesStarted()
    {
        $yesterday = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-1 day" ) );
        $tommorow = date("Y-m-d", strtotime( date( "Y-m-d h:i:s", strtotime( date("Y-m-d") ) ) . "+1 minutes" ) );
        dump($tommorow);


        $queryBuilder = $this->createQueryBuilder('a')
            ->andWhere('a.activityDate > :val')
            ->andWhere('a.activityDate < :val2')
            ->setParameter('val', $yesterday)
            ->setParameter('val2', $tommorow);

        $query = $queryBuilder->getQuery();

         foreach ( $query->getResult() as $row){
             dump($row);
         }

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
