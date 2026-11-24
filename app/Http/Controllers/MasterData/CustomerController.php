<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Requests\CustomerSearchRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\StoreQuickCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Services\PiutangService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends LookupController
{
    protected string $modelClass = Customer::class;

    protected string $title = 'Pelanggan';

    protected string $routeName = 'customers';

    protected string $permission = 'pelanggan';

    protected array $searchFields = ['kode', 'nama', 'telepon', 'email', 'alamat'];

    protected array $columns = [
        ['name' => 'kode', 'label' => 'Kode'],
        ['name' => 'nama', 'label' => 'Nama'],
        ['name' => 'telepon', 'label' => 'Telepon'],
        ['name' => 'batas_hutang', 'label' => 'Batas hutang'],
        ['name' => 'aktif', 'label' => 'Status'],
        ['name' => 'email', 'label' => 'Email'],
    ];

    protected array $fields = [
        ['name' => 'nama', 'label' => 'Nama pelanggan', 'required' => true, 'maxlength' => 255],
        ['name' => 'telepon', 'label' => 'Telepon', 'maxlength' => 50],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'maxlength' => 255],
        ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'maxlength' => 2000],
        ['name' => 'batas_hutang', 'label' => 'Batas hutang (Rp; 0 = tanpa batas)', 'type' => 'number', 'min' => 0, 'step' => 1, 'default' => 0],
        ['name' => 'aktif', 'label' => 'Pelanggan aktif', 'type' => 'checkbox', 'default' => true],
    ];

    public function search(CustomerSearchRequest $request, CustomerService $customerService): JsonResponse
    {
        $customers = $customerService->cari($request->validated('q') ?? '');

        return response()->json([
            'data' => $customers->map(fn (Customer $customer): array => $this->customerData($customer))->values(),
        ]);
    }

    public function quickStore(
        StoreQuickCustomerRequest $request,
        CustomerService $customerService,
    ): JsonResponse {
        $result = $customerService->buatCepat($request->validated());
        $customer = $this->customerData($customerService->denganRingkasan($result['customer']));

        if ($result['duplicate']) {
            return response()->json([
                'duplicate' => true,
                'message' => 'Pelanggan dengan nama dan nomor telepon tersebut sudah ada.',
                'customer' => $customer,
            ], 409);
        }

        return response()->json([
            'message' => 'Pelanggan berhasil ditambahkan.',
            'customer' => $customer,
        ], 201);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        return $this->storeRecord(new Customer, $request->validated());
    }

    public function update(UpdateCustomerRequest $request, string $customer): RedirectResponse
    {
        return $this->updateRecord($this->find($customer), $request->validated());
    }

    public function show(Request $request, string $record): View
    {
        $this->authorizePermission($request, 'lihat');

        $customer = Customer::query()->findOrFail($record);
        $canViewPiutang = $request->user()->canAny(['piutang.lihat', 'piutang.kelola']);
        $piutangs = $canViewPiutang
            ? $customer->piutangs()->with('sale')->latest('id')->paginate(10)
            : collect();
        $totalHutang = $canViewPiutang
            ? app(PiutangService::class)->totalHutangPelanggan($customer)
            : 0;

        return view('master-data.customers.show', compact(
            'customer',
            'piutangs',
            'totalHutang',
            'canViewPiutang',
        ));
    }

    /**
     * @return array{id: int, kode: string, nama: string, telepon: ?string, batas_hutang: int, total_hutang: int, sisa_batas_hutang: ?int, ada_piutang_terlambat: bool}
     */
    private function customerData(Customer $customer): array
    {
        $limit = (int) $customer->batas_hutang;
        $debt = (int) ($customer->total_hutang ?? 0);

        return [
            'id' => $customer->id,
            'kode' => $customer->kode,
            'nama' => $customer->nama,
            'telepon' => $customer->telepon,
            'batas_hutang' => $limit,
            'total_hutang' => $debt,
            'sisa_batas_hutang' => $limit > 0 ? max($limit - $debt, 0) : null,
            'ada_piutang_terlambat' => (bool) ($customer->ada_piutang_terlambat ?? false),
        ];
    }
}
