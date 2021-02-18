<?php

declare(strict_types=1);

namespace SampleMetrics\Core\Database\Repository;

use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Limit;
use SampleMetrics\Core\Database\BaseRepository;
use SampleMetrics\Core\Database\Model\Product;

/**
 * Class ProductRepository
 * @package SampleMetrics\Core\Database\Repository
 */
class ProductRepository extends BaseRepository
{
    protected string $tableName = 'product';

    /**
     * @param array $params
     *
     * @return Product
     */
    public function getNewModel(array $params): Product
    {
        return new Product($params);
    }

    public function getProduct(int $id): Product
    {
        $selectStatement = $this->
        getConnection()->select(['*'])
            ->from($this->tableName)
            ->where(
                new Conditional('id', '=', $id)
            );
        $stmt = $selectStatement->execute();
        $result = $stmt->fetchAll()[0] ?? [];
        return $this->getNewModel($result);
    }
}
