<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CilSubsidiaryCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companies = \App\Models\CilSubsidiaryCompany::all();
        return response()->json([
            'success' => true,
            'data' => $companies
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:cil_subsidiary_companies,name',
            'status' => 'sometimes|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $company = \App\Models\CilSubsidiaryCompany::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Company created successfully',
            'data' => $company
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $company = \App\Models\CilSubsidiaryCompany::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $company
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $company = \App\Models\CilSubsidiaryCompany::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:cil_subsidiary_companies,name,' . $id,
            'status' => 'sometimes|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $company->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully',
            'data' => $company
        ]);
    }

    /**
     * Get companies list for dropdown (id and name only)
     */
    public function getCompanyList(Request $request)
    {
        try {
            $query = \App\Models\CilSubsidiaryCompany::select('id', 'name');
            
            // Search by company name if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            $companies = $query->orderBy('name', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $companies,
                'message' => 'Company list retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('CIL subsidiary company list error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve company list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $company = \App\Models\CilSubsidiaryCompany::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        }

        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Company deleted successfully'
        ]);
    }
}
