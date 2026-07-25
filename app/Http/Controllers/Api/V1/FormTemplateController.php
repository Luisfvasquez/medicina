<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FormTemplate\StoreFormTemplateRequest;
use App\Http\Requests\Api\V1\FormTemplate\UpdateFormTemplateRequest;
use App\Models\FormTemplate;
use Illuminate\Http\JsonResponse;

class FormTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = FormTemplate::with('user')
            ->whereNull('user_id')
            ->orWhere('user_id', auth('user_api')->id())
            ->latest()
            ->get();

        $mapped = $templates->map(function ($template) {
            return array_merge($template->schema_json ?? [], [
                'id' => $template->uuid,
                'specialty' => $template->specialty,
                'documentCategory' => $template->document_category ?? 'historia-clinica',
                'userId' => $template->user?->uuid,
            ]);
        });

        return response()->json(['schemas' => $mapped]);
    }

    public function store(StoreFormTemplateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $now = now()->toIso8601String();
        $existing = FormTemplate::where('uuid', $validated['id'])->first();
        
        $schemaJson = $request->all();
        if ($existing) {
            $oldSchema = $existing->schema_json;
            $oldVersion = (float) ($oldSchema['version'] ?? '1.0');
            $schemaJson['version'] = (string) ($oldVersion + 0.1);
            $schemaJson['createdAt'] = $oldSchema['createdAt'] ?? $now;
        } else {
            $schemaJson['version'] = '1.0.0';
            $schemaJson['createdAt'] = $now;
        }
        $schemaJson['updatedAt'] = $now;

        $template = FormTemplate::updateOrCreate(
            ['uuid' => $validated['id']],
            [
                'user_id' => auth('user_api')->id(),
                'specialty' => $validated['specialty'] ?? null,
                'document_category' => $validated['document_category'] ?? 'historia-clinica',
                'schema_json' => $schemaJson,
            ]
        );

        $schema = array_merge($template->schema_json ?? [], [
            'id' => $template->uuid,
            'specialty' => $template->specialty,
            'documentCategory' => $template->document_category ?? 'historia-clinica',
            'userId' => $template->user?->uuid,
        ]);

        return response()->json(['schema' => $schema], $existing ? 200 : 201);
    }

    public function show(string $id): JsonResponse
    {
        $template = FormTemplate::with('user')->where('uuid', $id)->firstOrFail();

        if ($template->user_id && $template->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $schema = array_merge($template->schema_json ?? [], [
            'id' => $template->uuid,
            'specialty' => $template->specialty,
            'documentCategory' => $template->document_category ?? 'historia-clinica',
            'userId' => $template->user?->uuid,
        ]);

        return response()->json(['schema' => $schema]);
    }

    public function update(UpdateFormTemplateRequest $request, string $id): JsonResponse
    {
        $template = FormTemplate::where('uuid', $id)->firstOrFail();

        if ($template->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();
        
        $schemaJson = array_merge($template->schema_json ?? [], $request->all());
        $schemaJson['updatedAt'] = now()->toIso8601String();

        $template->update([
            'specialty' => $validated['specialty'] ?? $template->specialty,
            'document_category' => $validated['document_category'] ?? $template->document_category,
            'schema_json' => $schemaJson,
        ]);

        $schema = array_merge($template->schema_json ?? [], [
            'id' => $template->uuid,
            'specialty' => $template->specialty,
            'documentCategory' => $template->document_category ?? 'historia-clinica',
            'userId' => $template->user?->uuid,
        ]);

        return response()->json(['schema' => $schema]);
    }

    public function destroy(string $id): JsonResponse
    {
        $template = FormTemplate::where('uuid', $id)->firstOrFail();

        if ($template->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $template->delete();

        return response()->json(['deleted' => $id]);
    }
}
