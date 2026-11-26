<?php

namespace Database\Seeders;

use App\Models\MediaAsset;
use App\Models\MediaCollection;
use App\Models\MediaFolder;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::where('slug', 'meridian')->firstOrFail();
        $manifestPath = database_path('seed-data/media.json');
        $assets = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $folder = MediaFolder::firstOrCreate(
            ['site_id' => $site->id, 'path' => 'brand-and-editorial'],
            ['name' => 'Brand & editorial', 'parent_id' => null]
        );

        $collection = MediaCollection::firstOrCreate(
            ['site_id' => $site->id, 'slug' => 'editorial'],
            ['name' => 'Editorial', 'description' => 'Photography used across the corporate website.']
        );

        foreach ($assets as $item) {
            $source = database_path('seed-assets/'.$item['path']);
            $target = 'media/seed/'.basename($item['path']);

            if (File::exists($source) && ! Storage::disk('public')->exists($target)) {
                Storage::disk('public')->put($target, File::get($source));
            }

            foreach ($item['variants'] as $variant) {
                $variantSource = database_path('seed-assets/'.$variant['path']);
                $variantTarget = 'media/seed/variants/'.basename($variant['path']);

                if (File::exists($variantSource) && ! Storage::disk('public')->exists($variantTarget)) {
                    Storage::disk('public')->put($variantTarget, File::get($variantSource));
                }
            }

            $checksum = File::exists($source) ? hash_file('sha256', $source) : hash('sha256', $item['name']);
            $originalName = $item['name'].'.webp';

            $asset = MediaAsset::updateOrCreate(
                ['site_id' => $site->id, 'checksum' => $checksum],
                [
                    'folder_id' => $folder->id,
                    'uploaded_by' => null,
                    'disk' => 'public',
                    'path' => $item['path'],
                    'filename' => $originalName,
                    'original_name' => $originalName,
                    'mime_type' => 'image/webp',
                    'extension' => 'webp',
                    'size' => $item['bytes'],
                    'width' => $item['width'],
                    'height' => $item['height'],
                    'alt' => $item['alt'],
                    'caption' => [
                        'en' => $item['name'] === 'site-plan' ? 'Illustrative engineering site plan.' : null,
                        'th' => $item['name'] === 'site-plan' ? 'แบบแปลนวิศวกรรมเพื่อการสาธิต' : null,
                    ],
                    'credit' => $item['credit'],
                    'license' => $item['license'],
                    'source_url' => $item['source'],
                    'focal_x' => 0.5,
                    'focal_y' => 0.5,
                    'variants' => $item['variants'],
                    'usage_count' => 0,
                ]
            );

            $collection->assets()->syncWithoutDetaching([$asset->id]);
        }

        $ogSource = database_path('seed-assets/media/og-default.jpg');
        if (File::exists($ogSource) && ! Storage::disk('public')->exists('media/og-default.jpg')) {
            Storage::disk('public')->put('media/og-default.jpg', File::get($ogSource));
        }

        $documentsFolder = MediaFolder::firstOrCreate(
            ['site_id' => $site->id, 'path' => 'corporate-documents'],
            ['name' => 'Corporate documents', 'parent_id' => null]
        );

        foreach (['meridian-company-profile.pdf', 'meridian-annual-review-2025.pdf', 'meridian-governance-charter.pdf', 'meridian-data-protection-summary.pdf'] as $file) {
            $source = database_path('seed-assets/documents/'.$file);
            $target = 'media/seed/documents/'.$file;

            if (File::exists($source) && ! Storage::disk('public')->exists($target)) {
                Storage::disk('public')->put($target, File::get($source));
            }

            if (! File::exists($source)) {
                continue;
            }

            MediaAsset::updateOrCreate(
                ['site_id' => $site->id, 'checksum' => hash_file('sha256', $source)],
                [
                    'folder_id' => $documentsFolder->id,
                    'disk' => 'public',
                    'path' => $target,
                    'filename' => $file,
                    'original_name' => $file,
                    'mime_type' => 'application/pdf',
                    'extension' => 'pdf',
                    'size' => filesize($source),
                    'alt' => ['en' => 'Illustrative corporate PDF document', 'th' => 'เอกสาร PDF องค์กรเพื่อการสาธิต'],
                    'caption' => ['en' => 'Illustrative document. Requires review before production use.'],
                    'credit' => 'Meridian sample document',
                    'license' => 'Illustrative content',
                    'focal_x' => 0.5,
                    'focal_y' => 0.5,
                    'variants' => [],
                    'usage_count' => 0,
                ]
            );
        }
    }
}
