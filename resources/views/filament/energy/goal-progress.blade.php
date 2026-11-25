@php
    $goal = $getRecord();
    $result = \App\Filament\Energy\Resources\EnergyGoalResource::evaluation($goal);
@endphp
@include('filament.energy.goal-bar', ['result' => $result])
