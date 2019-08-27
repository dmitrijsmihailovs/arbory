<?php

namespace Arbory\Base\Admin\Grid;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Arbory\Base\Admin\Filter\FilterManager;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * Class Filter.
 */
class Filter implements FilterInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var QueryBuilder
     */
    protected $query;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var bool
     */
    protected $paginated = true;

    /**
     * @var int
     */
    protected $perPage;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * Filter constructor.
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->query = $model->newQuery();
        $this->request = request();
    }

    /**
     * @param Collection|Column[] $columns
     * @return void
     */
    protected function order(Collection $columns)
    {
        $orderBy = $this->request->get('_order_by');
        $orderDirection = $this->request->get('_order', 'asc');

        if (! $orderBy) {
            return;
        }

        $column = $columns->filter(function (Column $column) {
            return $column->isSortable();
        })->filter(static function (Column $column) use ($orderBy) {
            return $column->getName() === $orderBy;
        })->first();

        if (! $column) {
            return;
        }

        $this->query->orderBy($column->getName(), $orderDirection);
    }

    /**
     * @param Collection $columns
     */
    protected function filter(Collection $columns): void
    {
        if ($filterManager = $this->getFilterManager()) {
            $filterManager->apply($this->query);
        }
    }

    /**
     * @param $phrase
     * @param Collection|Column[] $columns
     */
    protected function search($phrase, $columns)
    {
        $keywords = explode(' ', $phrase);

        foreach ($keywords as $string) {
            $this->query->where(function (QueryBuilder $query) use ($string, $columns) {
                foreach ($columns as $column) {
                    if (! $column->isSearchable()) {
                        continue;
                    }

                    $column->searchConditions($query, $string);
                }
            });
        }
    }

    /**
     * @return Collection|LengthAwarePaginator
     */
    protected function loadItems()
    {
        $result = $this->query;

        if (! $this->isPaginated()) {
            return $result->get();
        }

        /** @var LengthAwarePaginator $result */
        $result = $this->query->paginate($this->getPerPage());

        if ($this->request->has('search')) {
            $result->appends([
                'search' => $this->request->get('search'),
            ]);
        }

        if ($this->request->has('_order_by') && $this->request->has('_order')) {
            $result->appends([
                '_order_by' => $this->request->get('_order_by'),
                '_order' => $this->request->get('_order'),
            ]);
        }

        return $result;
    }

    /**
     * @param Collection|Column[] $columns
     * @return Collection|LengthAwarePaginator
     */
    public function execute(Collection $columns)
    {
        if ($this->request->has('search')) {
            $this->search($this->request->get('search'), $columns);
        }

        $this->filter($columns);

        $this->order($columns);

        return $this->loadItems();
    }

    /**
     * @param $relationName
     */
    public function withRelation($relationName)
    {
        $this->query->with($relationName);
    }

    /**
     * @return QueryBuilder
     */
    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    /**
     * @return bool
     */
    public function isPaginated(): bool
    {
        return $this->paginated;
    }

    /**
     * @param bool $paginated
     */
    public function setPaginated(bool $paginated)
    {
        $this->paginated = $paginated;
    }

    /**
     * @return int|null
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * @param int $perPage
     */
    public function setPerPage(int $perPage)
    {
        $this->perPage = $perPage;
    }

    /**
     * @param FilterManager $filterManager
     * @return Filter
     */
    public function setFilterManager(FilterManager $filterManager): self
    {
        $this->filterManager = $filterManager;

        return $this;
    }

    /**
     * @return FilterManager|null
     */
    public function getFilterManager(): ?FilterManager
    {
        return $this->filterManager;
    }
}
