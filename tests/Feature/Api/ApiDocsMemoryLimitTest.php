<?php

use App\Support\MemoryLimit;
use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

beforeEach(function (): void {
    $this->originalMemoryLimit = ini_get('memory_limit');
    $this->lowMemoryLimit = (int) ceil(memory_get_usage(true) / 1024 ** 2 + 16).'M';
});

afterEach(function (): void {
    ini_set('memory_limit', $this->originalMemoryLimit);
});

it('raises the memory limit for the scramble commands only', function () {
    ini_set('memory_limit', $this->lowMemoryLimit);

    event(new CommandStarting('about', new ArrayInput([]), new NullOutput));
    expect(ini_get('memory_limit'))->toBe($this->lowMemoryLimit);

    event(new CommandStarting('scramble:analyze', new ArrayInput([]), new NullOutput));
    expect(ini_get('memory_limit'))->toBe('512M');
});

it('raises the memory limit for the generated API documentation', function () {
    loginAsAdmin();
    ini_set('memory_limit', $this->lowMemoryLimit);

    $this->get('/docs/api.json')->assertOk();

    expect(ini_get('memory_limit'))->toBe('512M');
});

it('never lowers a higher or unlimited memory limit', function (string $current) {
    ini_set('memory_limit', $current);

    MemoryLimit::raiseTo('512M');

    expect(ini_get('memory_limit'))->toBe($current);
})->with(['1G', '-1']);
