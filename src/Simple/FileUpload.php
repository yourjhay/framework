<?php

namespace Simple;

use Simple\Storage\Storage;

class FileUpload
{
    private array $file;
    private string $originalName;
    private string $tempName;
    private int $size;
    private string $extension;
    private string $mimeType;
    private int $error;
    private array $allowedTypes = [];
    private array $allowedMimes = [];
    private ?int $maxSize = null;

    public function __construct(string $key)
    {
        if (!isset($_FILES[$key])) {
            throw new \RuntimeException("Upload field [$key] not found.");
        }
        $this->file = $_FILES[$key];
        $this->originalName = $this->file['name'];
        $this->tempName = $this->file['tmp_name'];
        $this->size = $this->file['size'];
        $this->extension = strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $this->mimeType = finfo_file($finfo, $this->tempName);
        finfo_close($finfo);
        $this->error = $this->file['error'];
    }

    public function validateTypes(array $types): static
    {
        $this->allowedTypes = array_map('strtolower', $types);
        return $this;
    }

    public function validateMimes(array $mimes): static
    {
        $this->allowedMimes = array_map('strtolower', $mimes);
        return $this;
    }

    public function validateMaxSize(int $maxKb): static
    {
        $this->maxSize = $maxKb * 1024;
        return $this;
    }

    public function store(string $directory = '', ?string $disk = null): string
    {
        $directory = $directory ? trim($directory, '/') . '/' : '';
        return $this->storeAs($directory, $this->hashName(), $disk);
    }

    public function storeAs(string $path, string $name, ?string $disk = null): string
    {
        $this->validate();

        $path = rtrim($path, '/') . '/' . $this->sanitizeName($name);

        $stream = fopen($this->tempName, 'rb');
        Storage::disk($disk)->writeStream($path, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $path;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getClientExtension(): string
    {
        return $this->extension;
    }

    public function getClientMimeType(): string
    {
        return $this->mimeType;
    }

    public function getHash(): string
    {
        return sha1_file($this->tempName);
    }

    public function getError(): int
    {
        return $this->error;
    }

    private function hashName(): string
    {
        return $this->getHash() . '.' . $this->extension;
    }

    private function sanitizeName(string $name): string
    {
        $name = preg_replace('/[^\w.\-]/', '_', $name);
        return preg_replace('/_{2,}/', '_', $name);
    }

    private function validate(): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(match ($this->error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeded size limit.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                default => 'Unknown upload error.',
            });
        }

        if (!is_uploaded_file($this->tempName)) {
            throw new \RuntimeException('File is not a valid upload.');
        }

        if ($this->maxSize !== null && $this->size > $this->maxSize) {
            throw new \RuntimeException('File exceeds the maximum allowed size.');
        }

        if (!empty($this->allowedTypes) && !in_array($this->extension, $this->allowedTypes, true)) {
            throw new \RuntimeException("File type [{$this->extension}] is not allowed.");
        }

        if (!empty($this->allowedMimes) && !in_array($this->mimeType, $this->allowedMimes, true)) {
            throw new \RuntimeException("File MIME type [{$this->mimeType}] is not allowed.");
        }
    }
}
