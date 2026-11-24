<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EthicsPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPolicyConfigController extends Controller
{
    /**
     * Publicly accessible (authenticated users) active ethics policies for login modal.
     */
    public function activePolicies(): JsonResponse
    {
        $policies = EthicsPolicy::orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'policies' => $policies,
        ]);
    }

    /**
     * List all policy rules and summary stats for Admin console.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->buildPayload());
    }

    /**
     * Create a new ethics policy rule.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:1000'],
            'point_deduction' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'severity' => ['nullable', 'string', 'in:low,medium,high'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $validated['point_deduction'] = max(1, (int) ($validated['point_deduction'] ?? 1));
        $validated['severity'] = $validated['severity'] ?? 'medium';
        $validated['is_active'] = true;
        $validated['sort_order'] = $validated['sort_order'] ?? ((int) EthicsPolicy::max('sort_order') + 1);

        $policy = EthicsPolicy::create($validated);

        return response()->json(array_merge([
            'message' => "Aturan pelanggaran \"{$policy->title}\" (Sanksi: -{$policy->point_deduction} Pts) berhasil ditambahkan.",
            'policy' => $policy,
        ], $this->buildPayload()), 201);
    }

    /**
     * Update a single ethics policy rule.
     */
    public function update(Request $request, EthicsPolicy $ethicsPolicy): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:1000'],
            'point_deduction' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'severity' => ['nullable', 'string', 'in:low,medium,high'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        if (array_key_exists('point_deduction', $validated)) {
            $validated['point_deduction'] = max(1, (int) $validated['point_deduction']);
        }
        if (array_key_exists('severity', $validated)) {
            $validated['severity'] = $validated['severity'] ?: 'medium';
        }
        $validated['is_active'] = true;
        $ethicsPolicy->update($validated);

        return response()->json(array_merge([
            'message' => "Aturan pelanggaran \"{$ethicsPolicy->title}\" (Sanksi: -{$ethicsPolicy->point_deduction} Pts) berhasil diperbarui.",
            'policy' => $ethicsPolicy->fresh(),
        ], $this->buildPayload()));
    }

    /**
     * Batch update multiple policy rules at once.
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'policies' => ['required', 'array', 'min:1'],
            'policies.*.id' => ['required', 'integer', 'exists:ethics_policies,id'],
            'policies.*.title' => ['required', 'string', 'max:160'],
            'policies.*.description' => ['required', 'string', 'max:1000'],
            'policies.*.point_deduction' => ['required', 'integer', 'min:1', 'max:10000'],
            'policies.*.severity' => ['nullable', 'string', 'in:low,medium,high'],
            'policies.*.sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['policies'] as $item) {
                EthicsPolicy::where('id', $item['id'])->update([
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'point_deduction' => max(1, (int) $item['point_deduction']),
                    'severity' => $item['severity'] ?? 'medium',
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json(array_merge([
            'message' => 'Semua konfigurasi Policy & Sanksi Poin berhasil disimpan.',
        ], $this->buildPayload()));
    }

    /**
     * Delete a single policy rule (ensuring at least 1 remains).
     */
    public function destroy(EthicsPolicy $ethicsPolicy): JsonResponse
    {
        if (EthicsPolicy::count() <= 1) {
            return response()->json([
                'message' => 'Minimal harus ada 1 aturan pelanggaran pada konfigurasi sistem.',
            ], 422);
        }

        $title = $ethicsPolicy->title;
        $ethicsPolicy->delete();

        return response()->json(array_merge([
            'message' => "Aturan \"{$title}\" berhasil dihapus.",
        ], $this->buildPayload()));
    }

    private function buildPayload(): array
    {
        $policies = EthicsPolicy::orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $minDeduction = (int) ($policies->min('point_deduction') ?: 1);
        $maxDeduction = (int) ($policies->max('point_deduction') ?: 1);
        $avgDeduction = round((float) ($policies->avg('point_deduction') ?: 1), 1);

        return [
            'policies' => $policies,
            'stats' => [
                'total_policies' => $policies->count(),
                'min_deduction' => $minDeduction,
                'max_deduction' => $maxDeduction,
                'avg_deduction' => $avgDeduction,
                'deduction_range' => $minDeduction === $maxDeduction
                    ? "-{$minDeduction} Pts"
                    : "-{$minDeduction} s/d -{$maxDeduction} Pts",
            ],
        ];
    }
}

