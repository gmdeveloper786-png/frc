<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjaxChildController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /** Approved children for enrollment / assessment pickers (typeahead). */
    public function searchApproved(Request $request): JsonResponse
    {
        $request->validate([
            'q'     => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $term = trim((string) $request->query('q', ''));
        if (strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $limit = (int) $request->query('limit', 40);
        $children = $this->userRepository->searchApprovedChildren($term, $limit, $request->user());

        return response()->json([
            'data' => $children->map(static function ($child): array {
                return [
                    'id'           => $child->id,
                    'full_name'    => $child->full_name,
                    'gr_number'    => $child->gr_number,
                    'age'          => $child->age,
                    'gender'       => $child->gender,
                    'phone_number' => $child->phone_number,
                ];
            })->values(),
        ]);
    }
}
