<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\DoGrade;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DoGradesController extends BaseController
{
    /**
     * Display a listing of the do grades.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DoGrade::query();
            
            // Filter by status if provided
            if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
                $query->where('status', $request->status);
            }
            
            // Search by name if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            $doGrades = $query->orderBy('name', 'asc')->get();
            
            return $this->sendResponse($doGrades, 'DO grades retrieved successfully.');
            
        } catch (\Exception $e) {
            Log::error('DO grades list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to retrieve DO grades.', ['error' => 'Database error']);
        }
    }

    /**
     * Store a newly created do grade in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:do_grades,name',
            'status' => 'sometimes|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        try {
            $doGrade = DoGrade::create([
                'name' => $request->name,
                'status' => $request->status ?? 'active'
            ]);
            
            return $this->sendResponse($doGrade, 'DO grade created successfully.');
            
        } catch (\Exception $e) {
            Log::error('DO grade creation error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to create DO grade.', ['error' => 'Database error']);
        }
    }

    /**
     * Display the specified do grade.
     */
    public function show($id): JsonResponse
    {
        try {
            $doGrade = DoGrade::find($id);
            
            if (!$doGrade) {
                return $this->sendError('DO grade not found.', ['error' => 'DO grade not found']);
            }
            
            return $this->sendResponse($doGrade, 'DO grade retrieved successfully.');
            
        } catch (\Exception $e) {
            Log::error('DO grade show error', [
                'error' => $e->getMessage(),
                'grade_id' => $id
            ]);
            
            return $this->sendError('Failed to retrieve DO grade.', ['error' => 'Database error']);
        }
    }

    /**
     * Update the specified do grade in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:do_grades,name,' . $id,
            'status' => 'sometimes|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        try {
            $doGrade = DoGrade::find($id);
            
            if (!$doGrade) {
                return $this->sendError('DO grade not found.', ['error' => 'DO grade not found']);
            }
            
            $doGrade->update($request->all());
            
            return $this->sendResponse($doGrade, 'DO grade updated successfully.');
            
        } catch (\Exception $e) {
            Log::error('DO grade update error', [
                'error' => $e->getMessage(),
                'grade_id' => $id,
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to update DO grade.', ['error' => 'Database error']);
        }
    }

    /**
     * Remove the specified do grade from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $doGrade = DoGrade::find($id);
            
            if (!$doGrade) {
                return $this->sendError('DO grade not found.', ['error' => 'DO grade not found']);
            }
            
            $doGrade->delete();
            
            return $this->sendResponse([], 'DO grade deleted successfully.');
            
        } catch (\Exception $e) {
            Log::error('DO grade deletion error', [
                'error' => $e->getMessage(),
                'grade_id' => $id
            ]);
            
            return $this->sendError('Failed to delete DO grade.', ['error' => 'Database error']);
        }
    }

    /**
     * Get DO grades list for dropdown (id and name only)
     */
    public function getDoGradesList(Request $request): JsonResponse
    {
        try {
            $query = DoGrade::select('id', 'name');
            
            // By default, only show active grades
            if (!$request->has('status')) {
                $query->where('status', 'active');
            } else if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }
            
            // Search by name if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            $doGrades = $query->orderBy('name', 'asc')->get();
            
            return $this->sendResponse($doGrades, 'DO grades list retrieved successfully.');
            
        } catch (\Exception $e) {
            Log::error('DO grades dropdown list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to retrieve DO grades list.', ['error' => 'Database error']);
        }
    }
}
