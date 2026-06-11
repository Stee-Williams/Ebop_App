<?php

namespace App\Repository;

use App\Entity\Engagement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Engagement>
 */
class EngagementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Engagement::class);
    }

    public function generateNextNumero(?int $year = null): string
    {
        $year ??= (int) date('Y');
        $pattern = '/^ENG-(\d+)-' . $year . '$/';

        $rows = $this->createQueryBuilder('e')
            ->select('e.numero')
            ->where('e.numero LIKE :prefix')
            ->setParameter('prefix', 'ENG-%' . $year)
            ->getQuery()
            ->getArrayResult();

        $max = 0;
        foreach ($rows as $row) {
            $numero = $row['numero'] ?? '';
            if (preg_match($pattern, $numero, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return sprintf('ENG-%03d-%d', $max + 1, $year);
    }

    //    /**
    //     * @return Engagement[] Returns an array of Engagement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Engagement
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
