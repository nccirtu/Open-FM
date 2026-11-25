<?php

namespace App\Filament\Admin\Widgets;

use App\Support\Plugins\Plugins;
use Composer\InstalledVersions;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Failed\CountableFailedJobProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Number;
use Throwable;

class SystemHealthWidget extends StatsOverviewWidget
{
    public static int $gridW = 12;

    public static int $gridH = 4;

    public static function canView(): bool
    {
        return Plugins::canManage();
    }

    public function getHeading(): ?string
    {
        return __('admin_dashboard.system.heading');
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $pending = $this->pendingJobs();
        $failed = $this->failedJobs();
        $disk = $this->disk();
        $debugInProduction = app()->isProduction() && config('app.debug') === true;

        return [
            Stat::make(__('admin_dashboard.system.pending_jobs'), $pending ?? '—')
                ->description(__('admin_dashboard.system.queue_connection', ['connection' => config('queue.default')]))
                ->color('gray'),
            Stat::make(__('admin_dashboard.system.failed_jobs'), $failed ?? '—')
                ->description($failed === null || $failed === 0 ? __('admin_dashboard.system.failed_jobs_none') : __('admin_dashboard.system.failed_jobs_some'))
                ->color($failed === null || $failed === 0 ? 'success' : 'danger'),
            Stat::make(__('admin_dashboard.system.disk_free'), $disk === null ? '—' : Number::fileSize($disk['free'], precision: 1))
                ->description($disk === null ? null : __('admin_dashboard.system.disk_description', ['percent' => $disk['percent'], 'total' => Number::fileSize($disk['total'], precision: 0)]))
                ->color($disk === null || $disk['percent'] >= 10 ? 'success' : 'danger'),
            Stat::make(__('admin_dashboard.system.environment'), app()->environment())
                ->description($debugInProduction
                    ? __('admin_dashboard.system.debug_warning')
                    : __('admin_dashboard.system.environment_description', [
                        'php' => PHP_VERSION,
                        'laravel' => app()->version(),
                        'filament' => InstalledVersions::getPrettyVersion('filament/filament') ?? '—',
                    ]))
                ->color($debugInProduction ? 'danger' : 'gray'),
        ];
    }

    protected function pendingJobs(): ?int
    {
        if (config('queue.default') === 'sync') {
            return null;
        }

        try {
            /** @var QueueContract $connection */
            $connection = Queue::connection();

            return $connection->size();
        } catch (Throwable) {
            return null;
        }
    }

    protected function failedJobs(): ?int
    {
        try {
            $failer = app('queue.failer');

            return $failer instanceof CountableFailedJobProvider ? $failer->count() : count($failer->all());
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{free: float, total: float, percent: int}|null
     */
    protected function disk(): ?array
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());

        if ($free === false || $total === false || $total <= 0) {
            return null;
        }

        return ['free' => $free, 'total' => $total, 'percent' => (int) round($free / $total * 100)];
    }
}
