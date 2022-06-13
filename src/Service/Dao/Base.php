<?php


namespace Mwenju\Common\Service\Dao;


use Hyperf\Database\Model\Collection;
use Hyperf\Utils\Collection as BaseCollection;
use Hyperf\Utils\Contracts\Arrayable;

abstract class Base
{
    public function pagination($builder, $offset = 0, $limit = 10, $columns = ['*'])
    {
        $count = $builder->count();

        $items = $builder->offset($offset)->limit($limit)->get($columns);

        return [$count, $items];
    }

    public function query($builder, $offset = 0, $limit = 10, $columns = ['*'])
    {
        return $builder->offset($offset)->limit($limit)->get($columns);
    }

    /**
     * Returns only the columns from the collection with the specified keys.
     *
     * @param null|array|string $keys
     */
    public function columns(Collection $items, $keys): BaseCollection
    {
        if (is_null($keys)) {
            return new BaseCollection([]);
        }
        $result = [];
        $isSingleColumn = is_string($keys);
        foreach ($items as $item) {
            if ($isSingleColumn) {
                $value = $item->{$keys} ?? null;
                $result[] = $value instanceof Arrayable ? $value->toArray() : $value;
            } else {
                $result[] = value(static function () use ($item, $keys) {
                    $res = [];
                    foreach ($keys as $key) {
                        $value = $item->{$key} ?? null;
                        $res[$key] = $value instanceof Arrayable ? $value->toArray() : $value;
                    }

                    return $res;
                });
            }
        }

        return new BaseCollection($result);
    }
}