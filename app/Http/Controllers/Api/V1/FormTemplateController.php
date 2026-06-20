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
            ->paginate(20);

        return response()->json(['data' => $templates]);
    }

    public function store(StoreFormTemplateRequest $request): JsonResponse
    {
        $template = FormTemplate::create($request->validated());

        return response()->json(['data' => $template->load('user')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $template = FormTemplate::with('user')->findOrFail($id);

        if ($template->user_id && $template->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $template]);
    }

    public function update(UpdateFormTemplateRequest $request, string $id): JsonResponse
    {
        $template = FormTemplate::findOrFail($id);

        if ($template->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $template->update($request->validated());

        return response()->json(['data' => $template->load('user')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $template = FormTemplate::findOrFail($id);

        if ($template->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $template->delete();

        return response()->json(null, 204);
    }
}
