<?php

use App\Models\User;

function completeStudentProfile(): array
{
    return [
        'block' => 'BSCS-2A',
        'bio' => 'Student bio',
        'profile_photo_path' => 'profile-pictures/student.png',
        'portfolio_repository_url' => 'https://github.com/student/portfolio',
        'portfolio_site_url' => 'https://student.example.com',
        'preview_images' => [
            'desktop_1' => 'portfolio-previews/desktop-1.png',
            'desktop_2' => 'portfolio-previews/desktop-2.png',
            'desktop_3' => 'portfolio-previews/desktop-3.png',
            'mobile_1' => 'portfolio-previews/mobile-1.png',
            'mobile_2' => 'portfolio-previews/mobile-2.png',
            'mobile_3' => 'portfolio-previews/mobile-3.png',
        ],
    ];
}

test('student cards open the profile page', function () {
    $student = User::factory()->create([
        ...completeStudentProfile(),
        'name' => 'Student With Bio',
        'block' => 'BSCS-2A',
        'bio' => 'I make things.',
    ]);

    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('Student With Bio')
        ->assertSee('BSCS-2A')
        ->assertSee('I make things.')
        ->assertSee(route('profile.student', $student));

    $this->get(route('profile.student', $student))
        ->assertOk()
        ->assertSee('Student With Bio')
        ->assertSee('I make things.')
        ->assertSee('https://github.com/student/portfolio')
        ->assertSee('https://student.example.com')
        ->assertDontSee('Allen Bien Carlo T. Tatel');
});

test('the home page excludes students without a bio', function () {
    User::factory()->create([...completeStudentProfile(), 'name' => 'Without Bio', 'bio' => null]);
    User::factory()->create([...completeStudentProfile(), 'name' => 'Blank Bio', 'bio' => '   ']);
    User::factory()->create([...completeStudentProfile(), 'name' => 'Whitespace Bio', 'bio' => " \n\t "]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Without Bio')
        ->assertDontSee('Blank Bio')
        ->assertDontSee('Whitespace Bio')
        ->assertSee('No complete profiles in this class yet.');
});

test('the home page excludes a profile without a mobile preview', function () {
    User::factory()->create([
        ...completeStudentProfile(),
        'preview_images' => ['desktop_2' => 'portfolio-previews/desktop-2.png'],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee(asset('storage/portfolio-previews/desktop-2.png'))
        ->assertSee('No complete profiles in this class yet.');
});

test('the home page requires every profile detail and preview', function (string $missing) {
    $profile = completeStudentProfile();
    if (str_starts_with($missing, 'desktop_') || str_starts_with($missing, 'mobile_')) {
        unset($profile['preview_images'][$missing]);
    } else {
        $profile[$missing] = ' ';
    }

    User::factory()->create([...$profile, 'name' => 'Incomplete Profile']);
    $this->get(route('home'))->assertDontSee('Incomplete Profile');
})->with([
    'bio', 'profile_photo_path', 'portfolio_repository_url', 'portfolio_site_url',
    'desktop_1', 'desktop_2', 'desktop_3', 'mobile_1', 'mobile_2', 'mobile_3',
]);

test('search filters complete profiles by name, block or bio regardless of case', function () {
    User::factory()->create([...completeStudentProfile(), 'name' => 'Alice Example', 'block' => 'BSCS-2A', 'bio' => 'Backend developer']);
    User::factory()->create([...completeStudentProfile(), 'name' => 'Bob Example', 'block' => 'BSCS-2B', 'bio' => 'Designer']);
    User::factory()->create(['name' => 'Alice Incomplete', 'bio' => 'Backend developer']);

    $this->get(route('home', ['search' => 'ALICE']))
        ->assertSee('Alice Example')->assertDontSee('Bob Example')->assertDontSee('Alice Incomplete');
    $this->get(route('home', ['search' => 'backend']))
        ->assertSee('Alice Example')->assertDontSee('Bob Example');
    $this->get(route('home', ['block' => '', 'search' => '2B']))
        ->assertSee('Bob Example')->assertDontSee('Alice Example');
    $this->get(route('home', ['search' => 'no match']))->assertSee('No matching profiles found.');
});

test('the class filter can be combined with search and cleared', function () {
    User::factory()->create([...completeStudentProfile(), 'name' => 'Alice Example', 'block' => 'BSCS-2A']);
    User::factory()->create([...completeStudentProfile(), 'name' => 'Bob Example', 'block' => 'BSCS-2B']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Alice Example')
        ->assertDontSee('Bob Example')
        ->assertSee('aria-pressed="true"', false);

    $this->get(route('home', ['block' => 'BSCS-2A', 'search' => 'Example']))
        ->assertOk()
        ->assertSee('Alice Example')
        ->assertDontSee('Bob Example')
        ->assertSee('name="block" value="BSCS-2A"', false)
        ->assertSee('name="search" value="Example"', false)
        ->assertSee('aria-pressed="true"', false);

    $this->get(route('home', ['block' => '', 'search' => 'Example']))
        ->assertOk()
        ->assertSee('Alice Example')
        ->assertSee('Bob Example')
        ->assertSee('name="block" value=""', false)
        ->assertSee('aria-pressed="false"', false);
});

test('an empty class filter has a class-specific message', function () {
    User::factory()->create([...completeStudentProfile(), 'name' => 'Bob Example', 'block' => 'BSCS-2B']);

    $this->get(route('home', ['block' => 'BSCS-2A']))
        ->assertOk()
        ->assertDontSee('Bob Example')
        ->assertSee('No complete profiles in this class yet.');
});

test('unsupported class filters are rejected', function () {
    $this->getJson(route('home', ['block' => 'BSCS-2B']))
        ->assertUnprocessable()
        ->assertInvalid(['block']);
});

test('social links appear in the preferred order regardless of when they were added', function () {
    $student = User::factory()->create(completeStudentProfile());
    $student->socials()->createMany([
        ['platform' => 'email', 'url' => 'student@example.com'],
        ['platform' => 'instagram', 'url' => 'https://instagram.com/student'],
        ['platform' => 'github', 'url' => 'https://github.com/student'],
        ['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/student'],
        ['platform' => 'facebook', 'url' => 'https://facebook.com/student'],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder([
            'https://facebook.com/student',
            'https://linkedin.com/in/student',
            'https://github.com/student',
            'https://instagram.com/student',
            'mailto:student@example.com',
        ]);
});

test('profile page shows the portfolio preview and profile details', function () {
    $response = $this->get(route('profile.show'));

    $response
        ->assertOk()
        ->assertSee('Allen Bien Carlo T. Tatel')
        ->assertSee('Portfolio preview')
        ->assertSee(asset('images/test-white-bg.png'))
        ->assertDontSee('Age')
        ->assertDontSee('Contact')
        ->assertSee('Bio')
        ->assertSee('Email address')
        ->assertSee('Socials')
        ->assertSee('Facebook')
        ->assertSee('LinkedIn')
        ->assertSee('Deployed portfolio')
        ->assertSee('Repository')
        ->assertSee('Not provided');
});

test('a profile shows the public email in its details', function () {
    $student = User::factory()->create(['bio' => 'Student bio']);
    $student->socials()->create(['platform' => 'email', 'url' => 'public@example.com']);

    $this->get(route('profile.student', $student))
        ->assertOk()
        ->assertSee('mailto:public@example.com')
        ->assertSee('public@example.com')
        ->assertDontSee('Age')
        ->assertDontSee('Contact');
});

test('a profile without a public email does not expose its account email', function () {
    $student = User::factory()->create(['bio' => 'Student bio', 'email' => 'private@example.com']);

    $this->get(route('profile.student', $student))
        ->assertOk()
        ->assertSee('Email address')
        ->assertSee('Not provided')
        ->assertDontSee('private@example.com');
});
