<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
    }

    public function test_sales_invoice_and_80mm_receipt_download_as_pdf_and_detail_links_are_available(): void
    {
        $sale = $this->createSale();

        $this->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee(route('sales.pdf', $sale))
            ->assertSee(route('sales.receipt', $sale))
            ->assertSee('Cetak nota 80 mm');

        $invoice = $this->get(route('sales.pdf', $sale))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $invoice->getContent());
        $this->assertStringContainsString('invoice-'.$sale->nomor.'.pdf', $invoice->headers->get('content-disposition'));

        $receipt = $this->get(route('sales.receipt', $sale))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $receipt->getContent());
        $this->assertStringContainsString('nota-'.$sale->nomor.'.pdf', $receipt->headers->get('content-disposition'));
        $this->assertMatchesRegularExpression('/\/MediaBox \[0\.000 0\.000 226\.770 /', $receipt->getContent());

        $html = view('pdf.sales.invoice', [
            'sale' => $sale->load(['items.product', 'customer', 'user']),
            'store' => ['name' => 'Toko Uji', 'address' => 'Jalan Merdeka', 'logo' => null],
            'isCancelled' => true,
        ])->render();
        $this->assertStringContainsString('Toko Uji', $html);
        $this->assertStringContainsString('BATAL', $html);
        $this->assertStringContainsString('Rp 1.250.000', $html);
    }

    public function test_purchase_document_downloads_as_pdf_and_shows_cancelled_watermark(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['harga_beli' => 1250000]);
        $purchase = Purchase::query()->create([
            'nomor' => 'PB-'.now()->format('Ymd').'-9001',
            'supplier_id' => $supplier->id,
            'user_id' => $this->admin->id,
            'tanggal' => now()->toDateString(),
            'subtotal' => 1250000,
            'diskon' => 0,
            'total' => 1250000,
            'metode_pembayaran' => 'transfer',
            'status' => Purchase::STATUS_CANCELLED,
        ]);
        $purchase->items()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'harga_beli' => 1250000,
            'subtotal' => 1250000,
        ]);

        $this->get(route('purchases.show', $purchase))
            ->assertOk()
            ->assertSee(route('purchases.pdf', $purchase))
            ->assertSee('Cetak/Unduh PDF');

        $response = $this->get(route('purchases.pdf', $purchase))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('bukti-pembelian-'.$purchase->nomor.'.pdf', $response->headers->get('content-disposition'));

        $html = view('pdf.purchases.receipt', [
            'purchase' => $purchase->load(['items.product', 'supplier', 'user']),
            'store' => ['name' => 'Toko Uji', 'address' => '', 'logo' => null],
            'isCancelled' => true,
        ])->render();
        $this->assertStringContainsString('BATAL', $html);
        $this->assertStringContainsString('Rp 1.250.000', $html);
    }

    public function test_rupiah_helper_formats_integer_amounts_without_decimals(): void
    {
        $this->assertSame('Rp 1.250.000', rupiah(1250000));
        $this->assertSame('Rp 0', rupiah(0));
    }

    private function createSale(): Sale
    {
        $product = Product::factory()->create(['harga_jual' => 1250000]);
        $sale = Sale::query()->create([
            'nomor' => 'PJ-'.now()->format('Ymd').'-9001',
            'user_id' => $this->admin->id,
            'tanggal' => now()->toDateString(),
            'subtotal' => 1250000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 1250000,
            'bayar' => 1500000,
            'kembalian' => 250000,
            'metode_pembayaran' => Sale::PAYMENT_CASH,
            'status' => Sale::STATUS_COMPLETED,
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'harga_jual' => 1250000,
            'diskon' => 0,
            'subtotal' => 1250000,
        ]);

        return $sale;
    }
}
