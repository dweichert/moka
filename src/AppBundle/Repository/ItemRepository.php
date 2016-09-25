<?php
/**
 * This file is part of moka.
 *
 * (c) David Weichert <info@davidweichert.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Repository;


use Doctrine\ORM\EntityRepository;

class ItemRepository extends EntityRepository
{
    public function findAllWithNoContributor()
    {
        $qb = $this->createQueryBuilder('i');
        $qb->where('i.contributor IS NULL');

        return $qb
            ->getQuery()
            ->getResult();
    }
}
