<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toHaveValidationError', function (string $field) {
    return $this->toHaveKey("errors.{$field}");
});

expect()->extend('toBeSuccessfulResponse', function () {
    return $this->toHaveStatus(200);
});

expect()->extend('toBeValidJson', function () {
    json_decode($this->value);
    return $this->and(json_last_error())->toBe(JSON_ERROR_NONE);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create an authenticated user for testing
 */
function createAuthenticatedUser(array $attributes = []): \App\Models\User
{
    $user = \App\Models\User::factory()->create($attributes);
    test()->actingAs($user);
    return $user;
}

/**
 * Create a test file upload
 */
function createTestFile(string $name = 'test.txt', string $content = 'test content'): \Illuminate\Http\Testing\File
{
    return \Illuminate\Http\Testing\File::fake()->createWithContent($name, $content);
}

/**
 * Assert database has record with attributes
 */
function assertDatabaseHasRecord(string $table, array $attributes): void
{
    test()->assertDatabaseHas($table, $attributes);
}

/**
 * Mock external API calls
 */
function mockExternalApi(string $url, array $response = [], int $status = 200): void
{
    \Illuminate\Support\Facades\Http::fake([
        $url => \Illuminate\Support\Facades\Http::response($response, $status)
    ]);
}
