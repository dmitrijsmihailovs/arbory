<?php

namespace CubeSystems\Leaf\Generator;

use CubeSystems\Leaf\Admin\Form\Fields\Hidden;
use CubeSystems\Leaf\Generator\Extras\Field;
use CubeSystems\Leaf\Generator\Extras\Relation;
use CubeSystems\Leaf\Generator\Extras\Structure;
use Illuminate\Support\Collection;

class Schema
{
    /**
     * @var string
     */
    protected $nameSingular;

    /**
     * @var string
     */
    protected $namePlural;

    /**
     * @var Collection|Field
     */
    protected $fields;

    /**
     * @var Collection|Field
     */
    protected $relations;

    /**
     * @var bool
     */
    protected $useTimestamps;

    /**
     * @var bool
     */
    protected $useId;

    public function __construct()
    {
        $this->fields = new Collection();
        $this->relations = new Collection();
    }

    /**
     * @return Collection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param Field $field
     * @return void
     */
    public function addField( Field $field )
    {
        $this->fields->push( $field );
    }

    /**
     * @return Field|Collection
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @param Relation $relation
     * @return void
     */
    public function addRelation( Relation $relation )
    {
        $this->relations->push( $relation );
    }

    /**
     * @return string
     */
    public function getNameSingular(): string
    {
        return $this->nameSingular;
    }

    /**
     * @param string $nameSingular
     */
    public function setNameSingular( string $nameSingular )
    {
        $this->nameSingular = $nameSingular;
    }

    /**
     * @return string
     */
    public function getNamePlural(): string
    {
        return $this->namePlural;
    }

    /**
     * @param string $namePlural
     */
    public function setNamePlural( string $namePlural )
    {
        $this->namePlural = $namePlural;
    }

    /**
     * @return bool
     */
    public function usesTimestamps(): bool
    {
        return $this->useTimestamps;
    }

    /**
     * @param bool $useTimestamps
     */
    public function useTimestamps( bool $useTimestamps = true )
    {
        $this->useTimestamps = $useTimestamps;
    }

    /**
     * @return bool
     */
    public function usesId(): bool
    {
        return $this->useId;
    }

    /**
     * @param bool $useId
     */
    public function useId( bool $useId = true )
    {
        $this->useId = $useId;
    }
}