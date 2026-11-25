<?php

namespace App\Providers;

use App\Filament\Integrations\CustomFields\CustomFieldSectionPolicy;
use App\Filament\Integrations\FormBuilder\FormPolicy;
use App\Filament\Integrations\MediaManager\MediaManagerPage;
use App\Models\ApiToken;
use App\Models\Asset;
use App\Models\MaintenancePlanTemplate;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Observers\AssetObserver;
use App\Observers\MaintenancePlanTemplateObserver;
use App\Observers\TicketObserver;
use App\Observers\TicketReplyObserver;
use App\Policies\FlowRecordPolicy;
use App\Policies\MediaFilePolicy;
use App\Policies\MediaFolderPolicy;
use App\Services\AppSettings;
use App\Services\EntityFolders;
use App\Support\MemoryLimit;
use App\Support\Plugins\PluginManager;
use App\Support\Plugins\Plugins;
use App\Support\QrCodeTargets;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BladeUI\Icons\Factory as IconFactory;
use Cafm\CustomFields\Models\CustomFieldSection;
use Cafm\FormBuilder\Models\Form as FormBuilderForm;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Filament\Actions\View\ActionsIconAlias;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Pages\BasePage as Page;
use Filament\Resources\Resource;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\Widget;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Packstub\Flow\Models\Secret;
use Packstub\Flow\Models\Workflow;
use Packstub\Flow\Models\WorkflowRun;
use Packstub\Flow\Models\WorkflowStep;
use Packstub\Flow\Models\WorkflowTrigger;
use Packstub\Flow\Models\WorkflowVersion;
use Packstub\Flow\Models\WorkflowWait;
use Slimani\MediaManager\MediaManagerPlugin;
use Slimani\MediaManager\Models\File as MediaFile;
use Slimani\MediaManager\Models\Folder as MediaFolder;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class);

        $this->callAfterResolving(IconFactory::class, function (IconFactory $factory): void {
            $factory->add('open-fm', ['path' => resource_path('svg/icons'), 'prefix' => 'openfm']);
        });
    }

    public function boot(): void
    {
        FilamentIcon::register([ActionsIconAlias::ACTION_GROUP => Heroicon::OutlinedBars3]);

        Sanctum::usePersonalAccessTokenModel(ApiToken::class);

        FilamentShield::buildPermissionKeyUsing(
            function (string $entity, string $affix, string $subject, string $case, string $separator): string {
                return match (true) {
                    is_subclass_of($entity, Resource::class) => Str::of($affix)
                        ->snake()
                        ->append('_')
                        ->append(
                            Str::of($entity)
                                ->afterLast('\\')
                                ->beforeLast('Resource')
                                ->replace('\\', '')
                                ->snake()
                                ->replace('_', '::')
                        )
                        ->toString(),
                    is_subclass_of($entity, Page::class) => Str::of('page_')
                        ->append(class_basename($entity))
                        ->toString(),
                    is_subclass_of($entity, Widget::class) => Str::of('widget_')
                        ->append(class_basename($entity))
                        ->toString(),
                };
            }
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            fn (): View => view('filament.language-switcher'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::FOOTER,
            fn (): View => view('filament.footer'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): View => view('filament.widget-grid-gate'),
        );

        AppSettings::apply();
        EntityFolders::registerModelHooks();

        if (Plugins::installed('qr-code')) {
            QrCodeTargets::register();
        }

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute((int) config('api.rate_limit_per_minute', 120))
            ->by($request->user()?->currentAccessToken()?->getKey() ?? $request->user()?->getAuthIdentifier() ?? $request->ip()));

        Gate::policy(MediaFile::class, MediaFilePolicy::class);
        Gate::policy(MediaFolder::class, MediaFolderPolicy::class);

        foreach ([Workflow::class, Secret::class, WorkflowRun::class, WorkflowStep::class, WorkflowTrigger::class, WorkflowVersion::class, WorkflowWait::class] as $flowModel) {
            Gate::policy($flowModel, FlowRecordPolicy::class);
        }

        if (Plugins::installed('form-builder')) {
            Gate::policy(FormBuilderForm::class, FormPolicy::class);
        }

        if (Plugins::installed('custom-fields')) {
            Gate::policy(CustomFieldSection::class, CustomFieldSectionPolicy::class);
        }

        $this->app->resolving(MediaManagerPlugin::class, fn (MediaManagerPlugin $plugin): MediaManagerPlugin => $plugin->mediaManagerPage(MediaManagerPage::class));

        Gate::define('viewApiDocs', fn (User $user): bool => $user->organizations()->exists());

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(SecurityScheme::http('bearer'));
            });

        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if (str_starts_with((string) $event->command, 'scramble:')) {
                MemoryLimit::raiseTo((string) config('api.docs_memory_limit', '512M'));
            }
        });
        Ticket::observe(TicketObserver::class);
        TicketReply::observe(TicketReplyObserver::class);
        Asset::observe(AssetObserver::class);
        MaintenancePlanTemplate::observe(MaintenancePlanTemplateObserver::class);

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->view('auth.verification', [
                    'url' => $url,
                    'user' => $notifiable,
                ]);
        });
    }
}
