<?php

namespace App\Cms;

use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\PageBlock;
use App\Models\Site;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MediaService
{
    protected ImageManager $images;

    public function __construct(protected AuditLogger $audit)
    {
        $this->images = new ImageManager(new Driver);
    }

    public function upload(Site $site, ?User $user, UploadedFile $file, array $meta = []): array
    {
        $this->assertValidFile($file);

        $checksum = hash_file('sha256', $file->getRealPath());
        $existing = MediaAsset::forSite($site)->where('checksum', $checksum)->first();

        if ($existing) {
            return ['asset' => $existing, 'duplicate' => true];
        }

        $mime = $file->getMimeType();
        $extension = $this->extensionFor($mime);
        $originalName = $file->getClientOriginalName();
        $base = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'asset';
        $filename = $base.'-'.substr($checksum, 0, 12).'.'.$extension;
        $directory = 'media/'.$site->id.'/'.now()->format('Y/m');
        $disk = config('media.disk', 'public');
        $path = $file->storeAs($directory, $filename, $disk);

        [$width, $height] = $this->dimensions($file, $mime);
        $variants = $this->generateVariants($file, $site, $filename, $mime, $width, $disk);

        $asset = MediaAsset::create([
            'site_id' => $site->id,
            'folder_id' => $meta['folder_id'] ?? null,
            'uploaded_by' => $user?->id,
            'disk' => $disk,
            'path' => $path,
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => $mime,
            'extension' => $extension,
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'alt' => $this->localizedValue($meta['alt'] ?? null),
            'caption' => $this->localizedValue($meta['caption'] ?? null),
            'credit' => $meta['credit'] ?? null,
            'copyright' => $meta['copyright'] ?? null,
            'license' => $meta['license'] ?? null,
            'source_url' => $meta['source_url'] ?? null,
            'focal_x' => min(1, max(0, (float) ($meta['focal_x'] ?? 0.5))),
            'focal_y' => min(1, max(0, (float) ($meta['focal_y'] ?? 0.5))),
            'variants' => $variants,
            'checksum' => $checksum,
            'usage_count' => 0,
        ]);

        $this->audit->log('media.uploaded', "Uploaded media \"{$originalName}\"", [
            'subject_type' => 'media',
            'subject_id' => $asset->id,
            'user_id' => $user?->id,
            'meta' => ['mime' => $mime, 'size' => $file->getSize()],
        ]);

        return ['asset' => $asset, 'duplicate' => false];
    }

    public function update(MediaAsset $asset, User $user, array $data): MediaAsset
    {
        $asset->fill([
            'alt' => array_key_exists('alt', $data) ? $this->localizedValue($data['alt']) : $asset->alt,
            'caption' => array_key_exists('caption', $data) ? $this->localizedValue($data['caption']) : $asset->caption,
            'credit' => $data['credit'] ?? $asset->credit,
            'copyright' => $data['copyright'] ?? $asset->copyright,
            'license' => $data['license'] ?? $asset->license,
            'source_url' => $data['source_url'] ?? $asset->source_url,
            'focal_x' => isset($data['focal_x']) ? min(1, max(0, (float) $data['focal_x'])) : $asset->focal_x,
            'focal_y' => isset($data['focal_y']) ? min(1, max(0, (float) $data['focal_y'])) : $asset->focal_y,
            'folder_id' => $data['folder_id'] ?? $asset->folder_id,
        ])->save();

        $this->audit->log('media.updated', "Updated media \"{$asset->original_name}\"", [
            'subject_type' => 'media',
            'subject_id' => $asset->id,
        ]);

        return $asset->refresh();
    }

    public function delete(MediaAsset $asset, User $user): void
    {
        $paths = [$asset->path];

        foreach ($asset->variants ?? [] as $variant) {
            if (! empty($variant['path'])) {
                $paths[] = $variant['path'];
            }
        }

        Storage::disk($asset->disk)->delete(array_filter($paths));
        $asset->delete();

        $this->audit->log('media.deleted', "Deleted media \"{$asset->original_name}\"", [
            'subject_type' => 'media',
            'subject_id' => $asset->id,
        ]);
    }

    public function usageReport(MediaAsset $asset): array
    {
        $driver = DB::connection()->getDriverName();
        $needle = '%"media_id":'.$asset->id.'%';

        $blocks = PageBlock::query()
            ->when($driver === 'pgsql', fn ($q) => $q->whereRaw('props::text like ?', [$needle]))
            ->when($driver !== 'pgsql', fn ($q) => $q->where('props', 'like', $needle))
            ->with(['page.translations'])
            ->limit(50)
            ->get()
            ->map(fn (PageBlock $block) => [
                'type' => 'page',
                'id' => $block->page?->id,
                'title' => $block->page?->translations->firstWhere('locale', $block->locale)?->title
                    ?? $block->page?->translations->first()?->title
                    ?? 'Untitled page',
                'status' => $block->page?->status,
                'locale' => $block->locale,
                'url' => '/pages/'.$block->page?->id,
                'field' => $block->type,
            ]);

        $explicit = MediaUsage::where('media_asset_id', $asset->id)
            ->with('usable')
            ->limit(50)
            ->get()
            ->map(fn (MediaUsage $usage) => [
                'type' => class_basename($usage->usable_type),
                'id' => $usage->usable_id,
                'title' => 'Referenced by '.class_basename($usage->usable_type),
                'status' => 'unknown',
                'locale' => null,
                'url' => null,
                'field' => $usage->field,
            ]);

        return $blocks->merge($explicit)->unique(fn ($usage) => ($usage['type'] ?? '').'|'.($usage['id'] ?? '').'|'.($usage['field'] ?? ''))->values()->all();
    }

    public function refreshUsageCount(MediaAsset $asset): int
    {
        $count = count($this->usageReport($asset));
        $asset->update(['usage_count' => $count]);

        return $count;
    }

    protected function assertValidFile(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages(['file' => 'The upload did not complete. Please try again.']);
        }

        if ($file->getSize() > config('media.max_size')) {
            throw ValidationException::withMessages(['file' => 'The file exceeds the 10 MB upload limit.']);
        }

        $mime = $file->getMimeType();

        if (! in_array($mime, config('media.allowed_mimes'), true)) {
            throw ValidationException::withMessages(['file' => 'This file type is not allowed. Upload JPEG, PNG, WebP, GIF, or PDF.']);
        }

        if (in_array($mime, config('media.image_mimes'), true)) {
            $imageInfo = @getimagesize($file->getRealPath());

            if (! $imageInfo || $imageInfo[0] < 16 || $imageInfo[1] < 16) {
                throw ValidationException::withMessages(['file' => 'The image file is invalid or too small.']);
            }
        }

        if ($mime === 'application/pdf') {
            $header = file_get_contents($file->getRealPath(), false, null, 0, 5);

            if ($header !== '%PDF-') {
                throw ValidationException::withMessages(['file' => 'The PDF file is invalid.']);
            }
        }
    }

    protected function extensionFor(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }

    protected function dimensions(UploadedFile $file, string $mime): array
    {
        if (! in_array($mime, config('media.image_mimes'), true)) {
            return [null, null];
        }

        $info = @getimagesize($file->getRealPath());

        return $info ? [(int) $info[0], (int) $info[1]] : [null, null];
    }

    protected function generateVariants(UploadedFile $file, Site $site, string $filename, string $mime, ?int $width, string $disk): array
    {
        if (! in_array($mime, config('media.image_mimes'), true) || ! $width) {
            return [];
        }

        $variants = [];
        $base = pathinfo($filename, PATHINFO_FILENAME);

        foreach (config('media.variants') as $name => $spec) {
            if ($width <= $spec['width']) {
                continue;
            }

            $variantPath = 'media/'.$site->id.'/variants/'.$base.'-'.$name.'.webp';

            try {
                $image = $this->images->decodePath($file->getRealPath());
                $image->scale(width: $spec['width']);
                $encoded = $image->encodeUsingMediaType('image/webp', quality: (int) config('media.quality'));
                Storage::disk($disk)->put($variantPath, (string) $encoded);

                $variants[$name] = [
                    'path' => $variantPath,
                    'width' => $image->width(),
                    'height' => $image->height(),
                ];
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $variants;
    }

    public function localizedValue(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            return ['en' => $value, 'th' => $value, 'zh-CN' => $value];
        }

        if (is_array($value)) {
            return collect($value)
                ->only(['th', 'en', 'zh-CN'])
                ->map(fn ($item) => mb_substr(trim((string) $item), 0, 500))
                ->filter()
                ->all();
        }

        return null;
    }
}
