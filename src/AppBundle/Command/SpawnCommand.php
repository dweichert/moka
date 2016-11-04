<?php
/**
 * This file is part of moka.
 *
 * (c) David Weichert <info@davidweichert.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Command;

use AppBundle\Entity\Item;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SpawnCommand extends ContainerAwareCommand
{
    const CAN_SPAWN_UNLIMITED = 1;

    protected function configure()
    {
        $this
            ->setName('moka:spawn')
            ->setDescription('Spawns new items.')
            ->setHelp("This command spawns new items.");
    }

    /**
     * Gets all items that can spawn, checks if there are no items of the same
     * class that have not been pledged and of those it spawns the ones that
     * have not reached their maximum spawn number or can spawn unlimited.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $itemRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Item');
        foreach ($itemRepository->findAllItemsThatCanSpawn() as $item) {
            /** @var Item $item */
            $itemsOfSameClass = $itemRepository->findBy(['class' => $item->getClass()]);
            if ($this->containsItemWithoutContributor($itemsOfSameClass)) {
                // don't spawn if there are items of the same class left that are not pledged
                continue;
            }
            // spawn if maximum spawn number has not been reached.
            if (count($itemsOfSameClass) < $item->getMaxNumber()) {
                $output->writeln(
                    sprintf('<fg=green>Spawning %s.</>', $item->getName())
                );
                $this->spawn($item);
            }
            if (self::CAN_SPAWN_UNLIMITED == $item->getSpawn()) {
                $this->spawn($item);
            }
        }

        return 0;
    }

    /**
     * Spawn a new item from given item.
     *
     * @param Item $item
     */
    private function spawn(Item $item)
    {
        $spawnedItem = new Item();
        $spawnedItem->setClass($item->getClass());
        $spawnedItem->setDescription($item->getDescription());
        $spawnedItem->setDue($item->getDue());
        $spawnedItem->setFertile(false);
        $spawnedItem->setName($item->getName());
        $spawnedItem->setSpawn(0);
        $spawnedItem->setUrl($item->getUrl());
        $spawnedItem->setWeight($item->getWeight());

        $em = clone $this->getContainer()->get('doctrine')->getManager();
        $em->persist($spawnedItem);
        $em->flush();
    }

    /**
     * Returns true if any of the given items has no contributor.
     *
     * @param Item[] $items
     * @return bool
     */
    private function containsItemWithoutContributor($items)
    {
        foreach ($items as $item) {
            if (is_null($item->getContributor())) {
                return true;
            }
        }

        return false;
    }
}
