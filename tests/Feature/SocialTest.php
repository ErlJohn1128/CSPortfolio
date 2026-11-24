<?php

use App\Models\User;
use Illuminate\Database\QueryException;

test('a user can own multiple social links', function () {
    $user = User::factory()->create();

    $github = $user->socials()->create([
        'platform' => 'github',
        'url' => 'https://github.com/example',
    ]);

    $linkedin = $user->socials()->create([
        'platform' => 'linkedin',
        'url' => 'https://linkedin.com/in/example',
    ]);

    expect($user->socials)->toHaveCount(2)
        ->and($github->user->is($user))->toBeTrue()
        ->and($linkedin->user->is($user))->toBeTrue();

    $this->assertModelExists($github);
    $this->assertModelExists($linkedin);
});

test('a user cannot add the same social platform twice', function () {
    $user = User::factory()->create();

    $user->socials()->create([
        'platform' => 'github',
        'url' => 'https://github.com/example',
    ]);

    expect(fn () => $user->socials()->create([
        'platform' => 'github',
        'url' => 'https://github.com/another-example',
    ]))->toThrow(QueryException::class);
});
