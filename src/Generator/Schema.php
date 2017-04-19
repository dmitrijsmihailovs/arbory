<?php

namespace CubeSystems\Leaf\Generator;

use CubeSystems\Leaf\Admin\Form\Fields\Hidden;
use CubeSystems\Leaf\Generator\Extras\Field;
use CubeSystems\Leaf\Generator\Extras\Structure;
use Illuminate\Support\Collection;

class Schema
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Collection|Field
     */
    protected $fields;

    /**
     * @var bool
     */
    protected $timestamps;


    public function __construct()
    {
        $this->fields = new Collection();
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName( string $name )
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function usesTimestamps(): bool
    {
        return $this->timestamps;
    }

    /**
     * @param bool $timestamps
     */
    public function setTimestamps( bool $timestamps )
    {
        $this->timestamps = $timestamps;
    }
}