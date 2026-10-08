<?php
declare(strict_types=1);

/**
 * QAFlow — Attachment model.
 *
 * Wraps a row from the attachments table. Provides a MIME-based icon hint
 * and a human-readable file size for the UI. The stored path is never
 * exposed to the client; downloads go through the attachment controller.
 */

namespace QAFlow\Models;

final class Attachment extends BaseModel
{
    /** @var array<int,string> */
    protected array $hidden = ['stored_path'];

    /** @var array<string,string> */
    protected array $casts = [
        'id'          => 'int',
        'entity_id'   => 'int',
        'size_bytes'  => 'int',
        'uploaded_by' => 'int',
    ];

    public function originalName(): string
    {
        return (string) $this->get('original_name', '');
    }

    public function mimeType(): string
    {
        return (string) $this->get('mime_type', '');
    }

    public function sizeBytes(): int
    {
        return (int) $this->get('size_bytes', 0);
    }

    public function uploadedByName(): string
    {
        return (string) $this->get('uploaded_by_name', '');
    }

    /**
     * Human-readable size, e.g. "1.2 MB".
     */
    public function sizeLabel(): string
    {
        $bytes = $this->sizeBytes();

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $kb = $bytes / 1024;
        if ($kb < 1024) {
            return number_format($kb, 1) . ' KB';
        }

        $mb = $kb / 1024;
        if ($mb < 1024) {
            return number_format($mb, 1) . ' MB';
        }

        return number_format($mb / 1024, 2) . ' GB';
    }

    /**
     * Coarse kind used by the UI to pick an icon.
     */
    public function kind(): string
    {
        $mime = $this->mimeType();

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if ($mime === 'application/pdf') {
            return 'pdf';
        }
        if (str_starts_with($mime, 'text/')) {
            return 'text';
        }
        if ($mime === 'application/zip') {
            return 'archive';
        }

        return 'file';
    }

    public function isImage(): bool
    {
        return $this->kind() === 'image';
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['size_label'] = $this->sizeLabel();
        $out['kind']       = $this->kind();
        $out['is_image']   = $this->isImage();
        return $out;
    }
}