<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Tests\Models\RoutablePost;

it('binds an implicit route parameter using the UUID column', function (): void {
    $post = RoutablePost::query()->create();

    Route::middleware(SubstituteBindings::class)->get('/posts/{post}', function (RoutablePost $post): string {
        return $post->uuid;
    })->name('posts.show');

    $url = route('posts.show', $post);

    expect(parse_url($url, PHP_URL_PATH))->toBe('/posts/'.$post->uuid);

    $this->get($url)->assertOk()->assertSeeText($post->uuid);
});

it('binds an explicit UUID route key', function (): void {
    $post = RoutablePost::query()->create();

    Route::middleware(SubstituteBindings::class)->get('/posts/by-uuid/{post:uuid}', function (RoutablePost $post): string {
        return $post->uuid;
    });

    $this->get('/posts/by-uuid/'.$post->uuid)->assertOk()->assertSeeText($post->uuid);
});

it('uses Laravel route binding for an explicit non-UUID route key', function (): void {
    $post = RoutablePost::query()->create();

    Route::middleware(SubstituteBindings::class)->get('/posts/by-id/{post:id}', function (RoutablePost $post): string {
        return (string) $post->id;
    });

    $this->get('/posts/by-id/'.$post->id)->assertOk()->assertSeeText((string) $post->id);
});
