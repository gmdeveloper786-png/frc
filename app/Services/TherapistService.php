<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\TherapistRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class TherapistService
{
    public function __construct(
        private readonly TherapistRepositoryInterface $repository,
        private readonly SecureFileStorageService $secureFiles,
    ) {}

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->repository->getAll($filters, $perPage);
        return $paginator->withQueryString();
    }

    /**
     * Active therapists at a branch; optionally filtered to those linked to every given published service.
     *
     * @param  array<int>  $serviceIds
     */
    public function getByBranch(int $branchId, array $serviceIds = [], string $serviceMatch = 'all'): Collection
    {
        return $this->repository->getByBranch($branchId, $serviceIds, $serviceMatch);
    }

    public function findById(int $id): User
    {
        return $this->repository->findById($id) ?? abort(404, 'Therapist not found.');
    }

    /**
     * Therapist belongs to branch (active profile) and covers each requested service ID (when $serviceIds non-empty).
     *
     * @param  array<int>  $serviceIds
     */
    /**
     * @param  array<int>  $serviceIds
     * @param  'all'|'any'  $serviceMatch  Assessment scheduling uses any overlap; enrollment uses all.
     */
    public function therapistQualifiesForFilters(User $therapist, int $branchId, array $serviceIds, string $serviceMatch = 'all'): bool
    {
        $therapist->loadMissing(['therapistProfile', 'therapistServices']);

        if (! $therapist->isTherapist() || $therapist->status !== 'active') {
            return false;
        }

        $profile = $therapist->therapistProfile;
        if (! $profile || $profile->status !== 'active' || (int) $profile->branch_id !== $branchId) {
            return false;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));
        if ($ids === []) {
            return true;
        }

        $serviceMatch = $serviceMatch === 'any' ? 'any' : 'all';

        if ($serviceMatch === 'any') {
            foreach ($ids as $sid) {
                if ($therapist->therapistServices->contains('id', $sid)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($ids as $sid) {
            if (! $therapist->therapistServices->contains('id', $sid)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, UploadedFile>  $documentFiles
     */
    public function create(array $data, array $documentFiles = []): User
    {
        $therapistRole = Role::where('name', Role::THERAPIST)->firstOrFail();

        $serviceIds = array_values(array_unique(array_map('intval', $data['service_ids'] ?? [])));

        $userData = [
            'full_name'       => $data['full_name'],
            'father_name'     => $data['father_name'] ?? null,
            'email'           => $data['email'],
            'password'        => Hash::make($data['password']),
            'role_id'         => $therapistRole->id,
            'gender'          => $data['gender'] ?? null,
            'date_of_birth'   => $data['date_of_birth'] ?? null,
            'phone_number'    => $data['phone_number'] ?? null,
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'address'         => $data['address'] ?? null,
            'status'          => $data['status'] ?? 'active',
        ];

        $slots = $this->generateTimeSlots(
            $data['slot_start'],
            $data['slot_end'],
            $data['break_start'] ?? null,
            $data['break_end'] ?? null,
        );

        $profileData = [
            'branch_id'            => $data['branch_id'],
            'cnic_number'          => $data['cnic_number'] ?? null,
            'qualification'        => $data['qualification'] ?? null,
            'working_days'         => $data['working_days'] ?? [],
            'available_time_slots' => $slots,
            'break_time'           => isset($data['break_start'], $data['break_end'])
                ? $data['break_start'] . ' - ' . $data['break_end']
                : null,
            'documents'            => $this->storeDocumentPaths($documentFiles),
            'status'               => $data['profile_status'] ?? 'active',
        ];

        return $this->repository->create($userData, $profileData, $serviceIds);
    }

    /**
     * @param  array<int, UploadedFile>  $documentFiles
     */
    public function update(User $therapist, array $data, array $documentFiles = []): User
    {
        $serviceIds = array_values(array_unique(array_map('intval', $data['service_ids'] ?? [])));

        $userData = array_filter([
            'full_name'       => $data['full_name'] ?? null,
            'father_name'     => $data['father_name'] ?? null,
            'gender'          => $data['gender'] ?? null,
            'date_of_birth'   => $data['date_of_birth'] ?? null,
            'phone_number'    => $data['phone_number'] ?? null,
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'address'         => $data['address'] ?? null,
            'status'          => $data['status'] ?? null,
        ], fn($v) => ! is_null($v));

        if (! empty($data['password'])) {
            $userData['password'] = Hash::make($data['password']);
        }

        if (isset($data['email'])) {
            $userData['email'] = $data['email'];
        }

        $slots = $this->generateTimeSlots(
            $data['slot_start'] ?? null,
            $data['slot_end'] ?? null,
            $data['break_start'] ?? null,
            $data['break_end'] ?? null,
        );

        $profileData = array_filter([
            'branch_id'            => $data['branch_id'] ?? null,
            'cnic_number'          => $data['cnic_number'] ?? null,
            'qualification'        => $data['qualification'] ?? null,
            'working_days'         => $data['working_days'] ?? null,
            'available_time_slots' => ! empty($slots) ? $slots : null,
            'break_time'           => isset($data['break_start'], $data['break_end'])
                ? $data['break_start'] . ' - ' . $data['break_end']
                : null,
            'status'               => $data['profile_status'] ?? null,
        ], fn($v) => ! is_null($v));

        $newDocumentPaths = $this->storeDocumentPaths($documentFiles);
        if ($newDocumentPaths !== null) {
            $existing = is_array($therapist->therapistProfile?->documents)
                ? $therapist->therapistProfile->documents
                : [];
            $profileData['documents'] = array_values(array_merge($existing, $newDocumentPaths));
        }

        return $this->repository->update($therapist, $userData, $profileData, $serviceIds);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return list<string>|null
     */
    private function storeDocumentPaths(array $files): ?array
    {
        if ($files === []) {
            return null;
        }

        return $this->secureFiles->storeMany($files, 'therapists/documents');
    }

    public function delete(User $therapist): bool
    {
        return DB::transaction(function () use ($therapist) {
            $therapist->loadMissing('therapistProfile');

            $documents = is_array($therapist->therapistProfile?->documents)
                ? $therapist->therapistProfile->documents
                : [];

            foreach ($documents as $path) {
                if (is_string($path) && $path !== '') {
                    $this->secureFiles->delete($path);
                }
            }

            $therapist->tokens()->delete();

            return $this->repository->delete($therapist);
        });
    }

    /**
     * Generate 30-minute time slots between start and end time,
     * marking slots within break time as disabled.
     */
    public function generateTimeSlots(
        ?string $start,
        ?string $end,
        ?string $breakStart = null,
        ?string $breakEnd = null,
        int $intervalMinutes = 30
    ): array {
        if (! $start || ! $end) {
            return [];
        }

        $slots        = [];
        $current      = strtotime($start);
        $endTime      = strtotime($end);
        $breakStartTs = $breakStart ? strtotime($breakStart) : null;
        $breakEndTs   = $breakEnd ? strtotime($breakEnd) : null;

        while ($current < $endTime) {
            $slotEnd      = $current + ($intervalMinutes * 60);
            $slotLabel    = date('g:iA', $current) . ' - ' . date('g:iA', $slotEnd);
            $isInBreak    = $breakStartTs && $breakEndTs && $current >= $breakStartTs && $current < $breakEndTs;

            $slots[] = [
                'slot'     => $slotLabel,
                'start'    => date('H:i', $current),
                'end'      => date('H:i', $slotEnd),
                'disabled' => $isInBreak,
            ];

            $current = $slotEnd;
        }

        return $slots;
    }

    public function getAvailableDays(User $therapist): array
    {
        return $therapist->therapistProfile?->working_days ?? [];
    }

    /**
     * All generated slots for the therapist profile, including break slots (disabled: true).
     * Enrollment UI shows break slots as disabled options so users cannot book them.
     */
    public function getAvailableSlots(User $therapist): array
    {
        $profile  = $therapist->therapistProfile;
        $breakWin = $this->breakWindowFromProfile($profile);

        return collect($profile?->available_time_slots ?? [])
            ->map(function ($slot) use ($breakWin) {
                if (is_string($slot)) {
                    return ['slot' => $slot, 'disabled' => false];
                }

                $disabled = filter_var($slot['disabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $start    = $slot['start'] ?? null;
                $end      = $slot['end'] ?? null;
                if ($breakWin && $start && $end) {
                    $disabled = $disabled || $this->slotRangeOverlapsBreak($start, $end, $breakWin);
                }

                return [
                    'slot'     => $slot['slot'] ?? '',
                    'disabled' => $disabled,
                ];
            })
            ->filter(fn($s) => ($s['slot'] ?? '') !== '')
            ->values()
            ->toArray();
    }

    /**
     * Parse profile.break_time e.g. "12:00 - 13:00" or "12:00 PM - 1:00 PM".
     *
     * @return array{start:string,end:string}|null  Times as H:i (24h)
     */
    private function breakWindowFromProfile(?\App\Models\TherapistProfile $profile): ?array
    {
        if (! $profile || empty($profile->break_time)) {
            return null;
        }

        $parts = preg_split('/\s*-\s*/', trim($profile->break_time), 2);
        if (count($parts) !== 2) {
            return null;
        }

        try {
            return [
                'start' => \Carbon\Carbon::parse(trim($parts[0]))->format('H:i'),
                'end'   => \Carbon\Carbon::parse(trim($parts[1]))->format('H:i'),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Half-open style overlap: slot [start,end) vs break [bStart,bEnd) using H:i string compare.
     */
    private function slotRangeOverlapsBreak(string $slotStart, string $slotEnd, array $break): bool
    {
        return $slotStart < $break['end'] && $slotEnd > $break['start'];
    }

    /** Slots that can actually be booked (excludes break). */
    public function getBookableSlots(User $therapist): array
    {
        return collect($this->getAvailableSlots($therapist))
            ->filter(fn($s) => ! ($s['disabled'] ?? false))
            ->values()
            ->toArray();
    }
}
