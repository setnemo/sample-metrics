<?php

declare(strict_types=1);

namespace SampleMetrics\Core\Database\Model;

use SampleMetrics\Core\Database\BaseModel;

class Product extends BaseModel
{
    /**
     * @var int
     */
    protected int $id;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $description;

    /**
     * @var string
     */
    protected string $image;

    /**
     * @var int
     */
    protected int $price;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }
}
