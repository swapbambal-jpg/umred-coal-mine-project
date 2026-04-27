<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CompaniesController extends Controller
{
    /**
     * Display a listing of the companies.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = Company::query();
            
            // Filter by status if provided
            if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
                $query->where('status', $request->status);
            }
            
            // Search by company name if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('company_name', 'like', '%' . $request->search . '%');
            }
            
            // Pagination parameters - handle both query params and JSON body
            $requestData = $request->all();
            $page = isset($requestData['page']) ? $requestData['page'] : $request->get('page', 1);
            $offset = isset($requestData['offset']) ? $requestData['offset'] : $request->get('offset', 0);
            $limit = isset($requestData['limit']) ? $requestData['limit'] : $request->get('limit', 10);
            
            // Calculate offset based on page if offset is not explicitly provided
            if ($offset == 0 && $page > 1) {
                $offset = ($page - 1) * $limit;
            }
            
            // Get total count for pagination info
            $total = $query->count();
            
            // Apply pagination
            $companies = $query->orderBy('company_name', 'asc')
                              ->offset($offset)
                              ->limit($limit)
                              ->get();
            
            // Calculate current page based on offset for response
            $currentPage = floor($offset / $limit) + 1;
            
            return response()->json([
                'success' => true,
                'data' => $companies,
                'pagination' => [
                    'total' => $total,
                    'page' => $currentPage,
                    'offset' => $offset,
                    'limit' => $limit,
                    'has_more' => ($offset + $limit) < $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'message' => 'Companies retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Company list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve companies',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Store a newly created company in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_name' => 'required|string|max:255',
                'gst_number' => 'required|string|max:50|unique:companies,gst_number',
                'address' => 'required|string',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $company = Company::create([
                'company_name' => $request->company_name,
                'gst_number' => $request->gst_number,
                'address' => $request->address,
                'status' => $request->status ?? 'active',
            ]);

            return response()->json([
                'success' => true,
                'data' => $company,
                'message' => 'Company created successfully'
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Company creation error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create company',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Display the specified company.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $company = Company::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $company,
                'message' => 'Company retrieved successfully'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
                'error' => 'Company with ID ' . $id . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Company show error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve company',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Update the specified company in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'company_name' => 'sometimes|string|max:255',
                'gst_number' => 'sometimes|string|max:50|unique:companies,gst_number,' . $id,
                'address' => 'sometimes|string',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $company->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $company,
                'message' => 'Company updated successfully'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
                'error' => 'Company with ID ' . $id . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Company update error', [
                'error' => $e->getMessage(),
                'id' => $id,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Remove the specified company from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $company = Company::findOrFail($id);
            $company->delete();

            return response()->json([
                'success' => true,
                'message' => 'Company deleted successfully'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
                'error' => 'Company with ID ' . $id . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Company deletion error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete company',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Get companies list for dropdown (id and name only)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCompanyList(Request $request)
    {
        try {
            $query = Company::select('id', 'company_name');
            
            // Filter by status if provided
            if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
                $query->where('status', $request->status);
            }
            
            // Search by company name if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('company_name', 'like', '%' . $request->search . '%');
            }
            
            $companies = $query->orderBy('company_name', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $companies,
                'message' => 'Company list retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Company dropdown list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve company list',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Get only active companies
     *
     * @return \Illuminate\Http\Response
     */
    public function activeCompanies()
    {
        try {
            $companies = Company::active()->get();

            return response()->json([
                'success' => true,
                'data' => $companies,
                'message' => 'Active companies retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active companies',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
