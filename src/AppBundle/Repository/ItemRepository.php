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


use AppBundle\Entity\Item;
use Doctrine\ORM\EntityRepository;

class ItemRepository extends EntityRepository
{
    /**
     * @return Item[]
     */
    public function findAll()
    {
        return $this->findBy([], ['name' => 'ASC']);
    }

    /**
     * @return Item[]
     */
    public function findAllWithNoContributor()
    {
        $qb = $this->createQueryBuilder('i');
        $qb->where('i.contributor IS NULL');
        $qb->orderBy('i.name', 'ASC');

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int
     */
    public function getAllItemsCount()
    {
        return count($this->findAll());
    }

    /**
     * @return int
     */
    public function getMissingItemsCount()
    {
        return count($this->findAllWithNoContributor());
    }

    /**
     * @return int
     */
    public function getPledgedItemsCount()
    {
        return $this->getAllItemsCount() - $this->getMissingItemsCount();
    }
}
