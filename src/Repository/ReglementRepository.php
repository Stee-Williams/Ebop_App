<?php

namespace App\Repository;

use App\Entity\Reglement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reglement>
 */
class ReglementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reglement::class);
    }

    public function generateNextReference(?int $year = null): string
    {
        $year ??= (int) date('Y');
        $pattern = '/^REG-(\d+)-' . $year . '$/';

        $rows = $this->createQueryBuilder('r')
            ->select('r.reference')
            ->where('r.reference LIKE :prefix')
            ->setParameter('prefix', 'REG-%' . $year)
            ->getQuery()
            ->getArrayResult();

        $max = 0;
        foreach ($rows as $row) {
            $reference = $row['reference'] ?? '';
            if (preg_match($pattern, $reference, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return sprintf('REG-%03d-%d', $max + 1, $year);
    }
}
