<?php

namespace CubeSystems\Leaf\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AbstractModelRepository
 * @package CubeSystems\Leaf\Repositories
 */
abstract class AbstractModelsRepository
{
    /**
     * @var string
     */
    protected $modelClass;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * GenericRepository constructor.
     */
    public function __construct()
    {
        $this->makeModel( $this->modelClass );
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function all( array $columns = [ '*' ] )
    {
        return $this->newQuery()->get( $columns );
    }

    /**
     * @param int $perPage
     * @param array $columns
     * @return mixed
     */
    public function paginate( $perPage = 15, array $columns = [ '*' ] )
    {
        return $this->newQuery()->paginate( $perPage, $columns );
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function create( array $data )
    {
        return $this->getModel()->create( $data );
    }

    /**
     * @param array $data
     * @param $itemId
     * @param string $attribute
     * @return mixed
     */
    public function update( array $data, $itemId, $attribute = 'id' )
    {
        return $this->newQuery()->where( $attribute, '=', $itemId )->update( $data );
    }

    /**
     * @param $leafFileId
     * @return mixed
     */
    public function delete( $leafFileId )
    {
        return $this->getModel()->destroy( $leafFileId );
    }

    /**
     * @param $itemId
     * @param array $columns
     * @return mixed
     */
    public function find( $itemId, array $columns = [ '*' ] )
    {
        return $this->newQuery()->find( $itemId, $columns );
    }

    /**
     * @param $attribute
     * @param $value
     * @param array $columns
     * @return mixed
     */
    public function findBy( $attribute, $value, array $columns = [ '*' ] )
    {
        return $this->newQuery()->where( $attribute, '=', $value )->first( $columns );
    }

    /**
     * @param $itemId
     * @param array $columns
     * @return Model
     */
    public function findOrNew( $itemId, array $columns = [ '*' ] )
    {
        return $this->newQuery()->findOrNew( $itemId, $columns );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        return $this->getModel()->newQuery();
    }

    /**
     * @param $class
     */
    protected function makeModel( $class )
    {
        $this->model = new $class;
    }
}