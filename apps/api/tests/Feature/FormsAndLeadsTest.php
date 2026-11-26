<?php

use App\Models\Lead;
use Laravel\Sanctum\Sanctum;

it('stores a public contact submission and creates a lead', function () {
    $response = $this->postJson('/api/v1/public/forms/contact/submissions', [
        'name' => 'Pest Tester',
        'email' => 'pest@example.com',
        'company' => 'Example Test Co',
        'department' => 'project',
        'message' => 'We would like to discuss an automation project.',
        'consent' => true,
        'elapsed_ms' => 5000,
    ], ['X-Site' => 'meridian']);

    $response->assertCreated()->assertJsonStructure(['message', 'reference']);

    $lead = Lead::where('email', 'pest@example.com')->first();
    expect($lead)->not->toBeNull();
    expect($lead->priority)->toBe('normal');
    expect($lead->source)->toBe('contact');
});

it('rejects a submission without consent or required fields', function () {
    $this->postJson('/api/v1/public/forms/contact/submissions', [
        'email' => 'not-an-email',
        'message' => '',
        'consent' => false,
    ], ['X-Site' => 'meridian'])->assertStatus(422);
});

it('lets an authorised team member update and export a lead', function () {
    Sanctum::actingAs($this->admin);

    $lead = Lead::forSite($this->site)->firstOrFail();

    $this->withHeaders(adminHeaders($this))
        ->patchJson("/api/v1/admin/leads/{$lead->id}", [
            'status' => 'qualified',
            'priority' => 'high',
            'owner_id' => $this->admin->id,
            'notes' => 'Qualified in a Pest test.',
        ])->assertOk();

    expect($lead->fresh()->status)->toBe('qualified');

    $export = $this->withHeaders(adminHeaders($this))->get('/api/v1/admin/leads/export');
    $export->assertOk();
    expect($export->headers->get('content-type'))->toContain('text/csv');
});
