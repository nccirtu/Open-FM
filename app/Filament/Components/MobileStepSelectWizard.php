<?php

namespace App\Filament\Components;

use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Js;

class MobileStepSelectWizard extends Wizard
{
    public function toEmbeddedHtml(): string
    {
        $html = parent::toEmbeddedHtml();

        $visibleSteps = array_values(array_filter(
            $this->getChildSchema()->getComponents(),
            static fn ($component): bool => $component instanceof Step && $component->isVisible(),
        ));

        if ($this->isHeaderHidden() || count($visibleSteps) < 2) {
            return $html;
        }

        $anchor = strpos($html, 'x-ref="stepsData"');

        if ($anchor === false) {
            return $html;
        }

        $insertAt = strpos($html, '/>', $anchor);

        if ($insertAt === false) {
            return $html;
        }

        return substr_replace($html, $this->buildMobileStepSelectHtml($visibleSteps), $insertAt + 2, 0);
    }

    /**
     * @param  list<Step>  $steps
     */
    protected function buildMobileStepSelectHtml(array $steps): string
    {
        $selectId = ($this->getId() ?? 'wizard').'-mobile-step-select';

        $options = '';

        foreach ($steps as $index => $step) {
            $escapedKey = e($step->getKey());
            $jsKey = Js::from($step->getKey());
            $label = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).' — '.e($step->getLabel());

            $options .= <<<HTML
                <option
                    value="{$escapedKey}"
                    x-bind:disabled="step !== {$jsKey} && ! isStepAccessible({$jsKey})"
                >{$label}</option>
            HTML;
        }

        $escapedSelectId = e($selectId);

        return <<<HTML
            <style>
                .fi-sc-wizard > .fi-sc-wizard-mobile-select-ctn { display: none; }
                @media (max-width: 768px) {
                    .fi-sc-wizard > .fi-sc-wizard-header { display: none; }
                    .fi-sc-wizard > .fi-sc-wizard-mobile-select-ctn { display: block; padding: 1rem 1rem 0; }
                }
            </style>

            <div class="fi-sc-wizard-mobile-select-ctn" x-cloak>
                <label class="fi-sr-only" for="{$escapedSelectId}">
                    {$this->getMobileStepSelectLabel()}
                </label>

                <div class="fi-input-wrp">
                    <select
                        id="{$escapedSelectId}"
                        class="fi-select-input"
                        x-bind:value="step"
                        x-on:change="goToStep(\$event.target.value); if (step !== \$event.target.value) \$event.target.value = step"
                    >{$options}</select>
                </div>
            </div>
        HTML;
    }

    protected function getMobileStepSelectLabel(): string
    {
        $label = $this->getLabel();

        return e(filled($label) ? $label : __('Schritt wählen'));
    }
}
