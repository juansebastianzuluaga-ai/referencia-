<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ExternalClinicDocument extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'external_clinic_id',
        'document_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    public function externalClinic(): BelongsTo
    {
        return $this->belongsTo(ExternalClinic::class);
    }

    public function getFullPathAttribute(): string
    {
        return Storage::path($this->file_path);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    public function delete(): ?bool
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }

        return parent::delete();
    }

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }
}
