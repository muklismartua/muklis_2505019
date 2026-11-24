<?php

namespace App\Console\Commands;

use App\Models\Piutang;
use App\Services\ActivityLogService;
use Illuminate\Console\Command;

class CheckOverduePiutangs extends Command
{
    protected $signature = 'piutang:cek-jatuh-tempo';

    protected $description = 'Catat piutang belum lunas yang telah melewati tanggal jatuh tempo.';

    public function handle(ActivityLogService $activityLogService): int
    {
        $logged = 0;

        Piutang::query()
            ->with(['customer', 'sale'])
            ->where('status', Piutang::STATUS_UNPAID)
            ->whereNotNull('jatuh_tempo')
            ->whereDate('jatuh_tempo', '<', today())
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('activity_logs')
                    ->where('aksi', 'piutang.jatuh_tempo')
                    ->where('subject_type', Piutang::class)
                    ->whereColumn('subject_id', 'piutangs.id');
            })
            ->chunkById(100, function ($piutangs) use ($activityLogService, &$logged): void {
                foreach ($piutangs as $piutang) {
                    $customerName = $piutang->customer?->nama ?? 'Pelanggan dihapus';
                    $saleNumber = $piutang->sale?->nomor ?? '—';
                    $activityLogService->catat(
                        'piutang.jatuh_tempo',
                        "Piutang {$saleNumber} milik {$customerName} telah melewati jatuh tempo dengan sisa Rp "
                            .number_format($piutang->sisa, 0, ',', '.').'.',
                        $piutang,
                    );
                    $logged++;
                }
            });

        $this->info("{$logged} log piutang jatuh tempo dibuat.");

        return self::SUCCESS;
    }
}
