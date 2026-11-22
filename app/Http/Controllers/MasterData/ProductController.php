<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission($request, 'lihat');

        $products = Product::query()
            ->with(['category', 'unit'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->trim()->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->input('category_id')))
            ->when(in_array($request->input('status'), ['aktif', 'nonaktif'], true), fn ($query) => $query->where('aktif', $request->input('status') === 'aktif'))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('master-data.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('nama')->pluck('nama', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizePermission($request, 'kelola');

        return $this->formView(new Product);
    }

    public function store(StoreProductRequest $request, ProductService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->file('foto'));

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Request $request, Product $product): View
    {
        $this->authorizePermission($request, 'lihat');
        $product->load(['category', 'unit']);

        return view('master-data.products.show', compact('product'));
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorizePermission($request, 'kelola');

        return $this->formView($product);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        ProductService $service,
    ): RedirectResponse {
        $service->update($product, $request->validated(), $request->file('foto'));

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Request $request, Product $product, ProductService $service): RedirectResponse
    {
        $this->authorizePermission($request, 'kelola');
        $service->delete($product);

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function formView(Product $product): View
    {
        return view('master-data.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('nama')->pluck('nama', 'id'),
            'units' => Unit::query()->orderBy('nama')->pluck('nama', 'id'),
        ]);
    }

    private function authorizePermission(Request $request, string $action): void
    {
        abort_unless($request->user()->can('produk.'.$action), 403);
    }
}
