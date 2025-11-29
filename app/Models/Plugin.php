<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $version
 * @property string $type
 * @property boolean $is_enabled
 * @property string $config
 * @property string $installed_at
 * @property string $created_at
 * @property string $updated_at
 */
class Plugin extends Model
{
    protected $table = 'v2_plugins';

    const TYPE_FEATURE = 'feature';
    const TYPE_PAYMENT = 'payment';

    // Default protected plugins list (cannot be deleted)
    const PROTECTED_PLUGINS = [];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function isFeaturePlugin(): bool
    {
        return $this->type === self::TYPE_FEATURE;
    }

    public function isPaymentPlugin(): bool
    {
        return $this->type === self::TYPE_PAYMENT;
    }

    public function isProtected(): bool
    {
        return in_array($this->code, self::PROTECTED_PLUGINS);
    }

    public function canBeDeleted(): bool
    {
        return !$this->isProtected();
    }
}
