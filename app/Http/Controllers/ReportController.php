<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Http\Requests\ReportFilterRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function home(): RedirectResponse
    {
        return redirect()->route('reports.show', 'penjualan-tanggal');
    }

    public function index(ReportFilterRequest $request, string $report, ReportService $reportService): View
    {
        return $this->renderReport($request, $report, $reportService);
    }

    public function excel(
        ReportFilterRequest $request,
        string $report,
        ReportService $reportService,
    ): BinaryFileResponse {
        $definition = $reportService->definition($report);
        $filters = $reportService->normalizeFilters($request->validated());
        $rows = $reportService->rows($report, $filters, paginate: false);
        $headings = array_column($definition['columns'], 'label');
        $values = $rows->map(fn (array $row) => array_values($row))->all();
        if ($report === 'stok-saat-ini') {
            $values[] = ['', '', '', '', 'TOTAL', $reportService->stockValueTotal()];
        }

        return Excel::download(
            new ReportExport($headings, $values),
            "laporan-{$report}.xlsx",
        );
    }

    public function pdf(
        ReportFilterRequest $request,
        string $report,
        ReportService $reportService,
    ): Response {
        $definition = $reportService->definition($report);
        $filters = $reportService->normalizeFilters($request->validated());
        $rows = $reportService->rows($report, $filters, paginate: false);
        $stockValueTotal = $report === 'stok-saat-ini' ? $reportService->stockValueTotal() : null;

        return Pdf::loadView('reports.pdf', compact('definition', 'filters', 'rows', 'stockValueTotal'))
            ->setPaper('a4', 'landscape')
            ->download("laporan-{$report}.pdf");
    }

    private function renderReport(
        ReportFilterRequest $request,
        string $report,
        ReportService $reportService,
    ): View {
        $definition = $reportService->definition($report);
        $filters = $reportService->normalizeFilters($request->validated());
        $rows = $reportService->rows($report, $filters);
        $products = ($definition['filters']['produk'] ?? false)
            ? Product::query()->orderBy('nama')->pluck('nama', 'id')
            : collect();
        $customers = ($definition['filters']['pelanggan'] ?? false)
            ? Customer::query()->orderBy('nama')->pluck('nama', 'id')
            : collect();
        $suppliers = ($definition['filters']['supplier'] ?? false)
            ? Supplier::query()->orderBy('nama')->pluck('nama', 'id')
            : collect();
        $stockValueTotal = $report === 'stok-saat-ini' ? $reportService->stockValueTotal() : null;
        $reports = $reportService->definitions();
        $statuses = [
            'belum_lunas' => 'Belum lunas',
            'lunas' => 'Lunas',
            'batal' => 'Batal',
        ];

        return view('reports.index', compact(
            'definition',
            'filters',
            'rows',
            'products',
            'customers',
            'suppliers',
            'stockValueTotal',
            'reports',
            'report',
            'statuses',
        ));
    }
}
