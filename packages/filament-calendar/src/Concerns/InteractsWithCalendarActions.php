<?php

namespace OpenFm\FilamentCalendar\Concerns;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

trait InteractsWithCalendarActions
{
    /**
     * @var array<Action|ActionGroup>
     */
    protected array $cachedCalendarHeaderActions = [];

    public function cacheInteractsWithCalendarActions(): void
    {
        foreach ($this->getHeaderActions() as $action) {
            if ($action instanceof ActionGroup) {
                $action->livewire($this);

                /** @var array<string, Action> $flatActions */
                $flatActions = $action->getFlatActions();

                $this->mergeCachedActions($flatActions);
                $this->cachedCalendarHeaderActions[] = $action;

                continue;
            }

            $this->cacheAction($action);
            $this->cachedCalendarHeaderActions[] = $action;
        }
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getCachedCalendarHeaderActions(): array
    {
        return $this->cachedCalendarHeaderActions;
    }
}
