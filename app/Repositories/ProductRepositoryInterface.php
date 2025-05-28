<?php

namespace App\Repositories;

interface ProductRepositoryInterface
{
    public function getAll();
    public function findById($id);
    public function findByName($name);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}

