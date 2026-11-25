@php
    use App\Support\OpenFm;
@endphp

<footer class="fi-footer w-full pt-6 pb-10 text-center text-xs text-gray-500 dark:text-gray-400">
    {{ OpenFm::attribution() }} · v{{ OpenFm::VERSION }} · {{ now()->year }} ·
    <a href="{{ OpenFm::LICENSE_URL }}" target="_blank" rel="noopener" class="hover:text-gray-700 dark:hover:text-gray-200">{{ OpenFm::LICENSE }}</a>
</footer>
