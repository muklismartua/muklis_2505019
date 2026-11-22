<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;

class CustomerController extends LookupController
{
    protected string $modelClass = Customer::class;

    protected string $title = 'Pelanggan';

    protected string $routeName = 'customers';

    protected string $permission = 'pelanggan';

    protected array $searchFields = ['nama', 'telepon', 'email', 'alamat'];

    protected array $columns = [
        ['name' => 'nama', 'label' => 'Nama'],
        ['name' => 'telepon', 'label' => 'Telepon'],
        ['name' => 'email', 'label' => 'Email'],
    ];

    protected array $fields = [
        ['name' => 'nama', 'label' => 'Nama pelanggan', 'required' => true, 'maxlength' => 255],
        ['name' => 'telepon', 'label' => 'Telepon', 'maxlength' => 50],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'maxlength' => 255],
        ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'maxlength' => 2000],
    ];

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        return $this->storeRecord(new Customer, $request->validated());
    }

    public function update(UpdateCustomerRequest $request, string $customer): RedirectResponse
    {
        return $this->updateRecord($this->find($customer), $request->validated());
    }
}
