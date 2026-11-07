<?php

use EvanSchleret\LaravelUuidable\Enums\UuidGeneration;
use Illuminate\Support\Str;
use Tests\Models\CustomColumnPost;
use Tests\Models\Post;
use Tests\Models\UuidPrimaryKeyUser;
use Tests\Models\UuidV4Post;

it('generates a UUIDv7 in the configured column by default', function (): void {
    $post = Post::query()->create(['title' => 'First post']);

    expect(Str::isUuid($post->uuid, 7))->toBeTrue();
});

it('preserves a UUID explicitly assigned before creation', function (): void {
    $uuid = (string) Str::uuid7();
    $post = Post::query()->create(['uuid' => $uuid]);

    expect($post->uuid)->toBe($uuid);
});

it('uses the configured UUIDv4 override for a model', function (): void {
    $post = UuidV4Post::query()->create();

    expect(Str::isUuid($post->uuid, 4))->toBeTrue();
});

it('uses a custom UUID column defined by the model', function (): void {
    $post = CustomColumnPost::query()->create();

    expect(Str::isUuid($post->public_id, 7))->toBeTrue()
        ->and($post->uuid)->toBeNull()
        ->and(CustomColumnPost::query()->whereUuid($post->public_id)->exists())->toBeTrue();
});

it('uses each UUID generation mode configured globally', function (UuidGeneration $generation, int $version): void {
    config(['uuidable.generation' => $generation->value]);

    $post = Post::query()->create();

    expect(Str::isUuid($post->uuid, $version))->toBeTrue();
})->with([
    'UUIDv1' => [UuidGeneration::V1, 1],
    'UUIDv4' => [UuidGeneration::V4, 4],
    'UUIDv6' => [UuidGeneration::V6, 6],
    'UUIDv7' => [UuidGeneration::V7, 7],
    'ordered UUID' => [UuidGeneration::ORDERED, 4],
]);

it('generates UUIDs for a model primary key', function (): void {
    $user = UuidPrimaryKeyUser::query()->create(['name' => 'Evan']);

    expect(Str::isUuid($user->getKey(), 7))->toBeTrue()
        ->and($user->getKeyType())->toBe('string')
        ->and($user->getIncrementing())->toBeFalse()
        ->and(UuidPrimaryKeyUser::query()->whereUuid($user->getKey())->exists())->toBeTrue();
});

it('preserves a primary key explicitly assigned before creation', function (): void {
    $uuid = (string) Str::uuid7();
    $user = UuidPrimaryKeyUser::query()->create(['id' => $uuid]);

    expect($user->id)->toBe($uuid);
});

it('queries UUIDs with the model scopes', function (): void {
    $first = Post::query()->create();
    $second = Post::query()->create();

    expect(Post::query()->whereUuid([$first->uuid, $second->uuid])->count())->toBe(2)
        ->and(Post::query()->whereNotUuid($first->uuid)->pluck('uuid')->all())->toBe([$second->uuid]);
});
