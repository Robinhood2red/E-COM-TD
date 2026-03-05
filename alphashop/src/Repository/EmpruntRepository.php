<?php

namespace App\Repository;

use App\Entity\Emprunt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Emprunt>
 */
class EmpruntRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Emprunt::class);
    }

    /**
     * @return Emprunt[] Returns an array of Emprunt objects
     * CRITÈRE : Cette méthode permet d'afficher uniquement les livres non rendus.
     */
    public function findEnCours(): array
    {
        return $this->createQueryBuilder('e')
             ->andWhere('e.dateRetour IS NULL') 
             ->orderBy('e.dateEmprunt', 'ASC') 
             ->getQuery()
             ->getResult();
    }
}