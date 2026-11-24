<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HelperContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminHelperContactController extends Controller
{
    /**
     * Tampilkan seluruh daftar kontak helper (aktif & nonaktif) serta statistik untuk Admin.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->buildPayload());
    }

    /**
     * Buat kontak helper baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role_or_title' => ['nullable', 'string', 'max:120'],
            'channel' => ['required', 'string', 'in:whatsapp,email,phone,telegram,other'],
            'contact_value' => ['required', 'string', 'max:160'],
            'available_hours' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? ((int) HelperContact::max('sort_order') + 1);

        $contact = HelperContact::create($validated);

        return response()->json(array_merge([
            'message' => "Kontak helper \"{$contact->name}\" berhasil ditambahkan.",
            'contact' => $contact,
        ], $this->buildPayload()), 201);
    }

    /**
     * Perbarui data kontak helper.
     */
    public function update(Request $request, HelperContact $helperContact): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role_or_title' => ['nullable', 'string', 'max:120'],
            'channel' => ['required', 'string', 'in:whatsapp,email,phone,telegram,other'],
            'contact_value' => ['required', 'string', 'max:160'],
            'available_hours' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $helperContact->update($validated);

        return response()->json(array_merge([
            'message' => "Kontak helper \"{$helperContact->name}\" berhasil diperbarui.",
            'contact' => $helperContact->fresh(),
        ], $this->buildPayload()));
    }

    /**
     * Toggle status aktif / non-aktif kontak helper.
     */
    public function toggle(HelperContact $helperContact): JsonResponse
    {
        $helperContact->is_active = ! $helperContact->is_active;
        $helperContact->save();

        $statusText = $helperContact->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json(array_merge([
            'message' => "Kontak helper \"{$helperContact->name}\" berhasil {$statusText}.",
            'contact' => $helperContact,
        ], $this->buildPayload()));
    }

    /**
     * Hapus kontak helper.
     */
    public function destroy(HelperContact $helperContact): JsonResponse
    {
        $name = $helperContact->name;
        $helperContact->delete();

        return response()->json(array_merge([
            'message' => "Kontak helper \"{$name}\" berhasil dihapus.",
        ], $this->buildPayload()));
    }

    /**
     * Kembalikan daftar kontak helper ke data default bawaan sistem.
     */
    public function resetDefaults(): JsonResponse
    {
        DB::transaction(function () {
            HelperContact::truncate();

            $now = now();
            $rows = array_map(
                fn (array $item) => array_merge($item, ['created_at' => $now, 'updated_at' => $now]),
                HelperContact::defaultContacts()
            );

            HelperContact::insert($rows);
        });

        return response()->json(array_merge([
            'message' => 'Daftar kontak helper berhasil direset ke pengaturan default sistem.',
        ], $this->buildPayload()));
    }

    private function buildPayload(): array
    {
        $contacts = HelperContact::orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return [
            'helpers' => $contacts,
            'stats' => [
                'total_helpers' => $contacts->count(),
                'active_helpers' => $contacts->where('is_active', true)->count(),
                'inactive_helpers' => $contacts->where('is_active', false)->count(),
            ],
        ];
    }
}
