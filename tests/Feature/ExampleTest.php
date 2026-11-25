<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('landing page links the favicon that exists on disk', function () {
    $this->get(route('home'))->assertSee(asset('favicon.svg'), false);

    expect(public_path('favicon.svg'))->toBeFile();
});

test('login page links the favicon', function () {
    $this->get(route('login'))->assertSee(asset('favicon.svg'), false);
});
