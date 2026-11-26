<?php

it('exposes a stable contact key with localized navigation labels and urls', function (string $locale, string $label, string $url) {
    $response = $this->getJson("/api/v1/public/bootstrap?locale={$locale}", ['X-Site' => 'meridian']);

    $response->assertOk();

    $main = collect($response->json('data.navigation.main'));
    $contact = $main->firstWhere('key', 'contact');

    expect($contact)->not->toBeNull()
        ->and($contact['label'])->toBe($label)
        ->and($contact['url'])->toBe($url);
})->with([
    'Thai' => ['th', 'ติดต่อ', '/th/ติดต่อเรา'],
    'English' => ['en', 'Contact', '/en/contact'],
    'Chinese' => ['zh-CN', '联系', '/zh-cn/contact'],
]);
