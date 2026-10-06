<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BackupFile extends Model
{
    protected $table = 'backup_files';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->id ??= (string) Str::uuid();
        });
    }

    protected $fillable = [
        'company_id', 'user_id', 'type', 'original_name', 'disk_path',
        'size', 'sha256', 'schema_hash', 'status',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }
}
