<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;

class SupplierController extends LookupController
{
    protected string $modelClass = Supplier::class;

    protected string $title = 'Supplier';

    protected string $routeName = 'suppliers';

    protected string $permission = 'pemasok';

    protected array $searchFields = ['nama', 'telepon', 'email', 'alamat'];

    protected array $columns = [
        ['name' => 'nama', 'label' => 'Nama'],
        ['name' => 'telepon', 'label' => 'Telepon'],
        ['name' => 'email', 'label' => 'Email'],
    ];

    protected array $fields = [
        ['name' => 'nama', 'label' => 'Nama supplier', 'required' => true, 'maxlength' => 255],
        ['name' => 'telepon', 'label' => 'Telepon', 'maxlength' => 50],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'maxlength' => 255],
        ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'maxlength' => 2000],
    ];

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        return $this->storeRecord(new Supplier, $request->validated());
    }

    public function update(UpdateSupplierRequest $request, string $supplier): RedirectResponse
    {
        return $this->updateRecord($this->find($supplier), $request->validated());
    }
}
