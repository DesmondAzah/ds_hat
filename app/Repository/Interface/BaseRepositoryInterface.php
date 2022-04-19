<?php
namespace App\Repository\Interface;

/**
* Interface BaseRepositoryInterface
* @package App\Repositories
*/
interface BaseRepositoryInterface
{
   /**
    * Create a new instance of the model
    * @param array $attributes
    * @return Model
    */
   public function create(array $attributes);

   /**
    * Find a record by ID
    * @param $id
    * @return Model
    */
   public function find($id);

   /**
    * Get all records by column name and column value
    * @param string $column The name of the column to search for
    * @param mixed $value The value to search for
    * @return Collection
    */
   public function getByColumn($column, $value);

   /**
    * Get the first record by column name and column value
    * @param string $column The name of the column to search for
    * @param mixed $value The value to search for
    * @return Model
    */
   public function getFirstByColumn($column, $value);

   /**
    * Update the model
    * @param array $attribute The array of attributes to update on the model
    * @return Model
    */
    public function update($id, array $attributes);
}
