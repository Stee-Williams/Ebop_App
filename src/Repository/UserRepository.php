<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // =========================
    // FIND USER BY MATRICULE
    // =========================
    public function findByMatricule(string $matricule): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.matricule = :matricule')
            ->setParameter('matricule', $matricule)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // =========================
    // VERIFY LOGIN (optional helper)
    // =========================
    public function findUserForLogin(string $matricule): ?User
    {
        return $this->findOneBy([
            'matricule' => $matricule
        ]);
    }

    // =========================
    // LIST USERS WITH ROLE
    // =========================
    public function findAllWithRoles(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.role', 'r')
            ->addSelect('r')
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // =========================
    // CHECK IF MATRICULE EXISTS
    // =========================
    public function matriculeExists(string $matricule): bool
    {
        return (bool) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.matricule = :matricule')
            ->setParameter('matricule', $matricule)
            ->getQuery()
            ->getSingleScalarResult();
    }
}