<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;

class CategoryController extends LookupController
{
    protected string $modelClass = Category::class;

    protected string $title = 'Kategori';

    protected string $routeName = 'categories';

    protected string $permission = 'kategori';

    protected array $columns = [
        ['name' => 'nama', 'label' => 'Nama'],
    ];

    protected array $fields = [
        ['name' => 'nama', 'label' => 'Nama kategori', 'required' => true, 'maxlength' => 255],
    ];

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        return $this->storeRecord(new Category, $request->validated());
    }

    public function update(UpdateCategoryRequest $request, string $category): RedirectResponse
    {
        return $this->updateRecord($this->find($category), $request->validated());
    }
}
