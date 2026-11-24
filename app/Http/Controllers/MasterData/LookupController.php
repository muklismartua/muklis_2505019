<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\MasterDataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

abstract class LookupController extends Controller
{
    protected string $modelClass;

    protected string $title;

    protected string $routeName;

    protected string $permission;

    /** @var list<string> */
    protected array $searchFields = ['nama'];

    /** @var list<array{name: string, label: string}> */
    protected array $columns = [];

    /** @var list<array{name: string, label: string, type?: string, required?: bool, maxlength?: int}> */
    protected array $fields = [];

    public function index(Request $request): View
    {
        $this->authorizePermission($request, 'lihat');
        $query = $this->modelClass::query();

        if ($request->filled('q')) {
            $search = $request->string('q')->trim()->toString();
            $query->where(function ($query) use ($search): void {
                foreach ($this->searchFields as $index => $field) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}($field, 'like', "%{$search}%");
                }
            });
        }

        $records = $query->orderBy('nama')->paginate(15)->withQueryString();

        return view('master-data.lookup.index', [
            'records' => $records,
            'title' => $this->title,
            'routeName' => $this->routeName,
            'columns' => $this->columns,
            'canManage' => $request->user()->can($this->permission.'.kelola'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizePermission($request, 'kelola');

        return $this->formView(null);
    }

    public function show(Request $request, string $record): View
    {
        $this->authorizePermission($request, 'lihat');

        return view('master-data.lookup.show', [
            'record' => $this->find($record),
            'title' => $this->title,
            'routeName' => $this->routeName,
            'fields' => $this->fields,
        ]);
    }

    public function edit(Request $request, string $record): View
    {
        $this->authorizePermission($request, 'kelola');

        return $this->formView($this->find($record));
    }

    public function destroy(Request $request, string $record, MasterDataService $service): RedirectResponse
    {
        $this->authorizePermission($request, 'kelola');
        $service->deleteLookup($this->find($record));

        return redirect()->route($this->routeName.'.index')->with('success', "{$this->title} berhasil dihapus.");
    }

    protected function storeRecord(Model $record, array $data): RedirectResponse
    {
        $record::query()->create($data);

        return redirect()->route($this->routeName.'.index')->with('success', "{$this->title} berhasil ditambahkan.");
    }

    protected function updateRecord(Model $record, array $data): RedirectResponse
    {
        $record->update($data);

        return redirect()->route($this->routeName.'.index')->with('success', "{$this->title} berhasil diperbarui.");
    }

    protected function find(string $id): Model
    {
        return $this->modelClass::query()->findOrFail($id);
    }

    protected function formView(?Model $record): View
    {
        return view('master-data.lookup.form', [
            'record' => $record,
            'title' => $this->title,
            'routeName' => $this->routeName,
            'fields' => $this->fields,
        ]);
    }

    protected function authorizePermission(Request $request, string $action): void
    {
        abort_unless($request->user()->can($this->permission.'.'.$action), 403);
    }
}
