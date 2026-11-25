<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Support\Plugins\Plugins;
use Cafm\FormBuilder\Models\Form;
use Cafm\FormBuilder\Support\FormTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportFormTemplate extends Command
{
    protected $signature = 'forms:import-template
        {file : Vorlage (.form.json) — Dateiname im Vorlagenverzeichnis database/templates oder absoluter Pfad}
        {--organization=* : IDs oder Slugs der Mandanten, denen das Formular zugeordnet wird (leer = alle)}
        {--publish : Formular nach dem Import veröffentlichen}
        {--replace : Bestehendes Formular mit gleichem Kürzel aktualisieren statt Kopie anlegen}';

    protected $description = 'Importiert eine Form-Builder-Vorlage (JSON) als Formular.';

    public function handle(): int
    {
        if (! Plugins::installed('form-builder')) {
            $this->error(__('plugins.required.not_installed', ['plugin' => Plugins::definition('form-builder')->name()]));

            return self::FAILURE;
        }

        $path = File::exists($this->argument('file')) ? $this->argument('file') : database_path('templates/'.$this->argument('file'));

        if (! File::exists($path)) {
            $this->error("Vorlage {$path} nicht gefunden.");

            return self::FAILURE;
        }

        $template = json_decode(File::get($path), true);

        if (! is_array($template)) {
            $this->error('Die Datei enthält kein gültiges JSON.');

            return self::FAILURE;
        }

        $identifiers = array_values(array_filter(array_map('strval', (array) $this->option('organization'))));
        $organizations = Organization::query()
            ->whereKey($identifiers)
            ->orWhereIn('slug', $identifiers)
            ->get();
        $missing = array_diff($identifiers, $organizations->pluck('id')->all(), $organizations->pluck('slug')->all());

        if ($missing !== []) {
            $this->error('Mandanten nicht gefunden: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $organizationIds = $organizations->pluck('id')->all();

        $form = self::import($template, $organizationIds, (bool) $this->option('publish'), (bool) $this->option('replace'));

        $this->info(sprintf(
            'Formular „%s“ (%s, %s) importiert%s.',
            $form->name,
            $form->getKey(),
            $form->slug,
            $form->isPublished() ? ' und als Version '.$form->publishedVersion?->version.' veröffentlicht' : '',
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $template
     * @param  array<int, string>  $organizationIds
     */
    public static function import(array $template, array $organizationIds = [], bool $publish = false, bool $replace = false): Form
    {
        $form = $replace
            ? Form::query()->withoutGlobalScope(Form::TENANT_SCOPE)->where('slug', $template['slug'] ?? null)->first()
            : null;

        if ($form !== null) {
            $form->forceFill(collect($template)->except(['form_builder_template', 'slug'])->all())->save();
        } else {
            $form = FormTemplate::import($template);
        }

        $form->tenants()->sync($organizationIds);

        if ($publish) {
            $form->publish();
        }

        return $form->fresh(['publishedVersion', 'tenants']);
    }
}
