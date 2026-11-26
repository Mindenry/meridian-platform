<?php

namespace Tests;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    public Site $site;

    public User $admin;

    public User $editor;

    public User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->site = Site::where('slug', 'meridian')->firstOrFail();
        $this->admin = User::where('email', 'admin@meridian.example')->firstOrFail();
        $this->editor = User::where('email', 'editor@meridian.example')->firstOrFail();
        $this->reviewer = User::where('email', 'reviewer@meridian.example')->firstOrFail();
    }
}
