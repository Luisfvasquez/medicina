<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasPublicUuid
{
    /**
     * Boot the trait to generate UUID on creation.
     */
    protected static function bootHasPublicUuid()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
