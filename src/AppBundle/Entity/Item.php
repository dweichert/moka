<?php
/**
 * This file is part of moka.
 *
 * (c) David Weichert <info@davidweichert.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Item.
 *
 * Requested items users can pledge to contribute.
 *
 * @ORM\Table(name="item")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ItemRepository")
 */
class Item
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="due", type="datetime", nullable=true)
     */
    private $due;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="items")
     * @ORM\JoinColumn(name="contributor", referencedColumnName="id", nullable=true)
     */
    private $contributor;

    /**
     * Weight allows to influence the order of items.
     *
     * The higher the number, the higher the weight. Heavier items sink to the
     * bottom of the item list, lighter items float at the top.
     *
     * @var int
     *
     * @ORM\Column(name="weight", type="integer")
     */
    private $weight;

    /**
     * Class ID.
     *
     * Items of the same class come into existence as copies of the original
     * item of this class are spawned.
     *
     * @var string
     *
     * @ORM\Column(name="class", type="string", length=20)
     */
    private $class;

    /**
     * Number of times an item of this class can spawn.
     *
     * @var int
     *
     * @ORM\Column(name="spawn", type="integer")
     */
    private $spawn;


    /**
     * @var bool
     *
     * @ORM\Column(name="fertile", type="boolean", nullable=false)
     */
    private $fertile;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Item
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Item
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Item
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set due
     *
     * @param \DateTime $due
     *
     * @return Item
     */
    public function setDue($due)
    {
        $this->due = $due;

        return $this;
    }

    /**
     * Get due
     *
     * @return \DateTime
     */
    public function getDue()
    {
        return $this->due;
    }

    /**
     * Set contributor
     *
     * @param User $contributor
     *
     * @return Item
     */
    public function setContributor(User $contributor = null)
    {
        $this->contributor = $contributor;

        return $this;
    }

    /**
     * Get contributor
     *
     * @return User
     */
    public function getContributor()
    {
        return $this->contributor;
    }

    /**
     * Get weight.
     *
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set weight.
     *
     * @param int $weight
     *
     * @return Item
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get class.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set class.
     *
     * Sets class name to unique id if no name is given. Maximum 20 characters
     * allowed, longer class names will be truncated to 20 characters.
     *
     * @param string $name OPTIONAL
     *
     * @return Item
     */
    public function setClass($name = null)
    {
        if ($name) {
            $this->class = substr($name, 0, 19);
        } else {
            $this->class = uniqid();
        }

        return $this;
    }

    /**
     * Get number of spawn events left.
     *
     * Returns either a positive integer specifying the number of times this
     * item can be spawned or -1 for unlimited to indicate that this item can be
     * spawned any number of times. 0 indicates the item cannot spawn.
     *
     * @return int
     */
    public function getSpawn()
    {
        return $this->spawn;
    }

    /**
     * Get maximum allowed number of items of this class.
     *
     * @return int
     */
    public function getMaxNumber()
    {
        return $this->spawn + 1;
    }

    /**
     * Set number of spawn events left.
     *
     * Allows for either a positive integer specifying the number of times this
     * item can be spawned or -1 for unlimited to indicate that this item can be
     * spawned any number of times. 0 indicates the item cannot spawn.
     *
     * @param int $spawn OPTIONAL defaults to 0
     *
     * @return Item
     */
    public function setSpawn($spawn = 0)
    {
        $this->spawn = $spawn;

        return $this;
    }

    /**
     * Fertile items can spawn.
     *
     * @return boolean
     */
    public function isFertile()
    {
        return $this->fertile;
    }

    /**
     * Fertile items can spawn.
     *
     * @param boolean $fertile
     * @return Item
     */
    public function setFertile($fertile)
    {
        $this->fertile = $fertile;

        return $this;
    }



}

