<?php

namespace Database\Seeders;

use App\Cms\PermissionRegistry;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::where('slug', 'meridian')->firstOrFail();
        $energy = Site::where('slug', 'meridian-energy')->firstOrFail();

        foreach (PermissionRegistry::permissions() as $permission) {
            Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        }

        foreach (PermissionRegistry::rolePresets() as $slug => $preset) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $preset['name'],
                    'description' => $preset['description'],
                    'level' => $preset['level'],
                    'is_system' => true,
                ]
            );

            $role->permissions()->sync(
                Permission::whereIn('slug', PermissionRegistry::forRole($slug))->pluck('id')
            );
        }

        $password = 'Meridian!2026';

        $users = [
            ['name' => 'Anan Srisuk', 'email' => 'admin@meridian.example', 'job_title' => 'Platform Administrator', 'role' => 'super_admin', 'site' => null],
            ['name' => 'Pimchanok Rattanaporn', 'email' => 'manager@meridian.example', 'job_title' => 'Marketing Director', 'role' => 'marketing_manager', 'site' => $site],
            ['name' => 'Nattaya Boonsong', 'email' => 'editor@meridian.example', 'job_title' => 'Senior Content Editor', 'role' => 'content_editor', 'site' => $site],
            ['name' => 'Daniel Lim', 'email' => 'reviewer@meridian.example', 'job_title' => 'Head of Communications', 'role' => 'content_reviewer', 'site' => $site],
            ['name' => 'Siriporn Khamdee', 'email' => 'seo@meridian.example', 'job_title' => 'SEO Lead', 'role' => 'seo_specialist', 'site' => $site],
            ['name' => 'Marcus Chen', 'email' => 'analyst@meridian.example', 'job_title' => 'Performance Analyst', 'role' => 'analyst', 'site' => $site],
            ['name' => 'Kanya Wattana', 'email' => 'careers@meridian.example', 'job_title' => 'People Operations Manager', 'role' => 'careers_manager', 'site' => $site],
            ['name' => 'Energy Site Admin', 'email' => 'admin@meridian-energy.example', 'job_title' => 'Site Administrator', 'role' => 'site_admin', 'site' => $energy],
        ];

        foreach ($users as $entry) {
            $user = User::updateOrCreate(
                ['email' => $entry['email']],
                [
                    'name' => $entry['name'],
                    'job_title' => $entry['job_title'],
                    'password' => $password,
                    'locale' => 'en',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $role = Role::where('slug', $entry['role'])->firstOrFail();

            // Rebuild site assignments so re-seeding is idempotent.
            DB::table('role_user')->where('user_id', $user->id)->delete();
            DB::table('role_user')->insert([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'site_id' => $entry['site']?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
