<?php 

namespace App\Repository;

use App\Repository\Interface\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class BaseRepository implements BaseRepositoryInterface
{

    /** 
     * @var Model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        
    }

    /**
     * Create a new instance of the model
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes)
    {
        return $this->model->create($attributes);
    }

    /**
     * Find all instances of model
     * @return Collection
     */
    public function findAll(){
        return $this->model->all();
    }
    /**
     * Find a record by ID
     * @param $id
     * @return Model
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    /**
     * Get all records by column name and column value
     * @param string $column The name of the column to search for
     * @param mixed $value The value to search for
     * @return Collection
     */
    public function getByColumn($column, $value)
    {
        return $this->model->where($column, $value)->get();
    }

    /**
     * Get the first record by column name and column value
     * @param string $column The name of the column to search for
     * @param mixed $value The value to search for
     * @return Model
     */
    public function getFirstByColumn($column, $value)
    {
        return $this->model->where($column, $value)->first();
    }

    /**
     * Update the model
     * @param array $attribute The array of attributes to update on the model
     * @return Model
     */
     public function update($id, array $attributes)
     {
         return $this->model->find($id)->update($attributes);
     }
}