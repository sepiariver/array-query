<?php

namespace ArrayQuery;

use ArrayQuery\Exceptions\EmptyArrayException;
use ArrayQuery\Exceptions\InvalidArrayException;
use ArrayQuery\Exceptions\NotValidCriterionOperatorException;
use ArrayQuery\Exceptions\NotValidKeyElementInArrayException;
use ArrayQuery\Exceptions\NotValidLimitsOfArrayException;
use ArrayQuery\Exceptions\NotValidSortingOperatorException;
use ArrayQuery\Filters\CriterionFilter;
use ArrayQuery\Filters\SortingFilter;
use ArrayQuery\Filters\LimitFilter;

class QueryBuilder
{
    /**
     * @var array
     */
    private $criteria;

    /**
     * @var array
     */
    private $sortedBy;

    /**
     * @var array
     */
    private $limit;

    /**
     * @var array
     */
    private $array;

    public function __construct(array $array)
    {
        $this->setArray($array);
    }

    /**
     * @param array $array
     * @return static
     */
    public static function create(array $array)
    {
        return new static($array);
    }

    /**
     * @param $array
     *
     * @throws EmptyArrayException
     */
    private function setArray(array $array)
    {
        if (empty($array)) {
            throw new EmptyArrayException('Empty array provided.');
        }

        $this->array = $array;
    }

    /**
     * @param $key
     * @param $value
     * @param string $operator
     * @return $this
     * @throws NotValidCriterionOperatorException
     */
    public function addCriterion($key, $value, $operator = '=')
    {
        if (!$this->isAValidCriterionOperator($operator)) {
            throw new NotValidCriterionOperatorException($operator.' is not a valid operator.');
        }

        $this->criteria[] = [
            'key' => $key,
            'value' => $value,
            'operator' => $operator,
        ];

        return $this;
    }

    /**
     * @param $operator
     * @return bool
     */
    private function isAValidCriterionOperator($operator)
    {
        return in_array($operator, array_keys(CriterionFilter::$operatorsMap));
    }

    /**
     * @param $key
     * @param string $operator
     *
     * @return $this
     *
     * @throws NotValidSortingOperatorException
     */
    public function sortedBy($key, $operator = 'ASC')
    {
        if (!$this->isAValidSortingOperator($operator)) {
            throw new NotValidSortingOperatorException($operator.' is not a valid sorting operator.');
        }

        $this->sortedBy = [
            'key' => $key,
            'order' => $operator,
        ];

        return $this;
    }

    /**
     * @param $operator
     * @return bool
     */
    private function isAValidSortingOperator($operator)
    {
        return in_array($operator, SortingFilter::$operatorsMap);
    }

    /**
     * @param $offset
     * @param $length
     * @return $this
     * @throws NotValidLimitsOfArrayException
     */
    public function limit($offset, $length)
    {
        if (!is_integer($offset)) {
            throw new NotValidLimitsOfArrayException($offset.' must be an integer.');
        }

        if (!is_integer($length)) {
            throw new NotValidLimitsOfArrayException($length.' must be an integer.');
        }

        if ($offset > $length) {
            throw new NotValidLimitsOfArrayException($offset.' must be an < than '.$length.'.');
        }

        if ($length > count($this->array)) {
            throw new NotValidLimitsOfArrayException($length.' must be an > than array count.');
        }

        $this->limit = [
            'offset' => $offset,
            'lenght' => $length,
        ];

        return $this;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        $results = $this->applySortingFilter($this->applyLimitFilter($this->applyCriteriaFilter()));

        return array_map([$this, 'castElementToArray'], $results);
    }

    /**
     * @param array $array
     * @return array
     */
    private function applySortingFilter(array $array)
    {
        return SortingFilter::filter($array, $this->sortedBy);
    }

    /**
     * @param array $array
     * @return array
     */
    private function applyLimitFilter(array $array)
    {
        return LimitFilter::filter($array, $this->limit);
    }

    /**
     * @return array
     */
    private function applyCriteriaFilter()
    {
        if (count($this->criteria) === 0) {
            return $this->array;
        }

        foreach ($this->criteria as $criterion) {
            $results = array_filter(
                (isset($results)) ? $results : $this->array, function ($element) use ($criterion) {
                    return CriterionFilter::filter($criterion, $element);
                });
        }

        return $results;
    }

    /**
     * @param $element
     * @return array
     */
    private function castElementToArray($element)
    {
        return (array) $element;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return count($this->getResults());
    }
}