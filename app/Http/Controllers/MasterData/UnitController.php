<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;

class UnitController extends LookupController
{
    protected string $modelClass = Unit::class;

    protected string $title = 'Satuan';

    protected string $routeName = 'units';

    protected string $permission = 'satuan';

    protected array $columns = [
        ['name' => 'nama', 'label' => 'Nama'],
        ['name' => 'singkatan', 'label' => 'Singkatan'],
    ];

    protected array $fields = [
        ['name' => 'nama', 'label' => 'Nama satuan', 'required' => true, 'maxlength' => 255],
        ['name' => 'singkatan', 'label' => 'Singkatan', 'required' => true, 'maxlength' => 20],
    ];

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        return $this->storeRecord(new Unit, $request->validated());
    }

    public function update(UpdateUnitRequest $request, string $unit): RedirectResponse
    {
        return $this->updateRecord($this->find($unit), $request->validated());
    }
}
