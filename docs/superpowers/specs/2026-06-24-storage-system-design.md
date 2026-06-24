# Storage System & FileUpload Rewrite

## Overview

Replace the hard-coded `FileUpload` class with a disk-based storage system powered by `league/flysystem`. The `Storage` class provides the low-level filesystem API; `FileUpload` becomes a thin upload helper that wraps it.

## Architecture

```
Simple\Storage\Storage         — static facade, proxies to current disk
Simple\Storage\StorageManager  — manages disk instances, config resolution
Simple\Storage\Disk            — interface for individual disks
Simple\Storage\LocalDisk       — local filesystem adapter (wraps Flysystem)
Simple\Storage\S3Disk          — S3 adapter (wraps Flysystem)

Simple\FileUpload               — rewritten: handles $_FILES, delegates to Storage
```

**Dependency:** `league/flysystem-local` + `league/flysystem-adapter` (for S3 when needed).

## Configuration

Users define disks in `app/Config/Storage.php`:

```php
define('STORAGE_DISKS', serialize([
    'local' => [
        'driver' => 'local',
        'root' => getcwd() . '/public/storage',
        'url' => '/storage',
    ],
    'private' => [
        'driver' => 'local',
        'root' => getcwd() . '/storage/app/private',
        'url' => null, // no public URL
    ],
    's3' => [
        'driver' => 's3',
        'key' => $_ENV['AWS_ACCESS_KEY_ID'] ?? '',
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'] ?? '',
        'region' => $_ENV['AWS_DEFAULT_REGION'] ?? 'us-east-1',
        'bucket' => $_ENV['AWS_BUCKET'] ?? '',
        'url' => $_ENV['AWS_URL'] ?? null,
    ],
]));

define('STORAGE_DEFAULT', 'local');
```

## Storage API

```php
// Write
Storage::put('path/file.jpg', $contents, 'public');       // string contents
Storage::putStream('path/file.mov', fopen(...), 'public'); // stream

// Read
$contents = Storage::get('path/file.jpg');
$stream   = Storage::readStream('path/file.mov');

// Meta
Storage::exists('path/file.jpg');          // bool
Storage::size('path/file.jpg');            // bytes
Storage::mimeType('path/file.jpg');        // MIME string
Storage::lastModified('path/file.jpg');    // unix timestamp

// Delete
Storage::delete('path/file.jpg');

// Copy / Move
Storage::copy('from.jpg', 'to.jpg');
Storage::move('from.jpg', 'to.jpg');

// Visibility
Storage::setVisibility('path/file.jpg', 'public');
Storage::visibility('path/file.jpg');  // 'public' | 'private'

// URLs
Storage::url('path/file.jpg');                             // public URL
Storage::temporaryUrl('path/doc.pdf', 3600);               // signed URL (S3)

// Disk selection
Storage::disk('s3')->put('path/file.jpg', $data);
Storage::disk('private')->put('doc.pdf', $data, 'private');
```

## FileUpload API (rewritten)

```php
// From $_FILES
$upload = new FileUpload('avatar');

// Store with auto-generated hash name
$path = $upload->store('avatars');
// → 'avatars/abc123def.jpg', saved to default disk

// Store with custom name
$path = $upload->storeAs('avatars', 'my-avatar.jpg');

// Specify disk
$path = $upload->store('avatars', 's3');

// Keep original filename (sanitized)
$path = $upload->storeAs('uploads', $upload->getOriginalName());

// Validate before storing
$upload->validateTypes(['jpg', 'png']);
$upload->validateMaxSize(2048); // KB

// Info methods (carried over from current)
$upload->getOriginalName();      // 'photo.jpg'
$upload->getClientExtension();   // 'jpg'
$upload->getClientMimeType();    // 'image/jpeg'
$upload->getSize();              // 123456
$upload->getHash();              // sha1 hash of contents
```

## File naming

- `store()`: renamed to `{sha1_of_content}.{ext}` (prevents duplicates, safe)
- `storeAs()`: uses the provided name (sanitized — strip path traversal, replace unsafe chars)
- If no custom name: original filename is sanitized and used

## Private files

Private files use a separate disk (`private`). The `public/storage/` disk is served directly by the web server. The `storage/app/private/` disk requires a download controller that checks auth before serving.

Example download controller:

```php
Route::get('/download/{path}', function ($path) {
    $disk = Storage::disk('private');
    if (!$disk->exists($path)) abort(404);
    return response()->stream(function () use ($disk, $path) {
        echo $disk->get($path);
    }, 200, ['Content-Type' => $disk->mimeType($path)]);
});
```

## Documentation Update

The existing `components/fileUpload.view.html` page will be updated to document both the new `Storage` class and the rewritten `FileUpload` class:

- **Storage class section** — disk configuration, disk() selection, all operations (put, get, delete, copy, move, exists, url, temporaryUrl, visibility), example with local and S3
- **FileUpload section** — creating from `$_FILES`, store/storeAs, custom naming, disk selection, validation (type, size), methods reference
- **Private files section** — how to serve private files via a controller, link to the download controller pattern
- **Sidebar nav** — updated with links to all sections

The `components.view.html` components listing card for File Upload will be updated to reflect the new capabilities.

## Implementation Plan

1. Add `league/flysystem-local` to composer.json
2. Create `Simple\Storage\StorageManager` (config parsing, disk resolution)
3. Create `Simple\Storage\Disk` interface
4. Create `Simple\Storage\LocalDisk` implementation
5. Create `Simple\Storage\S3Disk` implementation
6. Create `Simple\Storage\Storage` facade
7. Rewrite `Simple\FileUpload` to use the Storage facade
8. Write tests
9. Update docs page
10. Tag release
