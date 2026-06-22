<?php

namespace App\Http\Controllers\Api\V1\Phase3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PrescriptionTemplate\StorePrescriptionTemplateRequest;
use App\Http\Requests\Api\V1\PrescriptionTemplate\UpdatePrescriptionTemplateRequest;
use App\Models\PrescriptionTemplate;
use App\Models\TemplateItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PrescriptionTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = PrescriptionTemplate::with(['user', 'items'])
            ->where('user_id', auth('user_api')->id())
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $templates]);
    }

    public function store(StorePrescriptionTemplateRequest $request): JsonResponse
    {
        $template = DB::transaction(function () use ($request) {
            $template = PrescriptionTemplate::create([
                'user_id' => auth('user_api')->id(),
                'title' => $request->input('title'),
            ]);

            if ($request->has('items')) {
                foreach ($request->input('items') as $item) {
                    $item['template_id'] = $template->id;
                    TemplateItem::create($item);
                }
            }

            return $template;
        });

        return response()->json(['data' => $template->load(['user', 'items'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $template = PrescriptionTemplate::with(['user', 'items'])->findOrFail($id);

        if ($template->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $template]);
    }

    public function update(UpdatePrescriptionTemplateRequest $request, string $id): JsonResponse
    {
        $template = PrescriptionTemplate::findOrFail($id);

        if ($template->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        DB::transaction(function () use ($template, $request) {
            if ($request->has('title')) {
                $template->update(['title' => $request->input('title')]);
            }

            if ($request->has('items')) {
                $template->items()->delete();
                foreach ($request->input('items') as $item) {
                    $item['template_id'] = $template->id;
                    TemplateItem::create($item);
                }
            }
        });

        return response()->json(['data' => $template->load(['user', 'items'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $template = PrescriptionTemplate::findOrFail($id);

        if ($template->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $template->delete();

        return response()->json(null, 204);
    }
}
