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
     * @param string $order
     * @return Item[]
     */
    public function findAll($order = 'weight-asc')
    {
        switch ($order) {
            case 'name-desc':
                return $this->findBy([], ['name' => 'DESC']);
            case 'due-asc':
                return $this->findBy([], ['due' => 'ASC']);
            case 'due-desc':
                return $this->findBy([], ['due' => 'DESC']);
            case 'name-asc':
                return $this->findBy([], ['name' => 'ASC']);
            case 'weight-asc':
            default:
                return $this->findBy([], ['weight' => 'ASC']);
        }
    }

    /**
     * @param string $order
     * @return Item[]
     */
    public function findAllWithNoContributor($order = 'name-asc')
    {
        $qb = $this->createQueryBuilder('i');
        $qb->where('i.contributor IS NULL');
        switch ($order) {
            case 'name-desc':
                $qb->orderBy('i.name', 'DESC');
                break;
            case 'due-asc':
                $qb->orderBy('i.due', 'ASC');
                break;
            case 'due-desc':
                $qb->orderBy('i.due', 'DESC');
                break;
            case 'name-asc':
                $qb->orderBy('i.name', 'ASC');
                break;
            case 'weight-asc':
            default:
                $qb->orderBy('i.weight', 'ASC');
                break;
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Item[]
     */
    public function findAllItemsThatCanSpawn()
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->where('i.fertile = 1')
            ->andWhere('i.spawn <> 0');

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
