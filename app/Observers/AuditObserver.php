<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model)
    {
        $user = auth('user_api')->user() ?? auth()->user();
        if ($user) {
            AuditLog::logCreate(
                $user,
                class_basename($model),
                $model->id,
                $model->toArray()
            );
        }
    }

    public function updated(Model $model)
    {
        $user = auth('user_api')->user() ?? auth()->user();
        if ($user) {
            $old = [];
            $new = [];
            foreach ($model->getDirty() as $key => $value) {
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $value;
            }
            if (!empty($new)) {
                AuditLog::logUpdate(
                    $user,
                    class_basename($model),
                    $model->id,
                    $old,
                    $new
                );
            }
        }
    }

    public function deleted(Model $model)
    {
        $user = auth('user_api')->user() ?? auth()->user();
        if ($user) {
            AuditLog::logDelete(
                $user,
                class_basename($model),
                $model->id,
                $model->toArray()
            );
        }
    }
}
