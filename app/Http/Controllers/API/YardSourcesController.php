<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\YardSource;

class YardSourcesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $offset = $request->get('offset', 10);
        
        $yardSources = YardSource::paginate($offset, ['*'], 'page', $page)->toArray();
        return response()->json([
            'success' => true,
            'data' => $yardSources["data"]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:yard_sources,name',
            'status' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $yardSource = YardSource::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Yard Source created successfully',
            'data' => $yardSource
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $yardSource = YardSource::find($id);

        if (!$yardSource) {
            return response()->json([
                'success' => false,
                'message' => 'Yard Source not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $yardSource
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $yardSource = YardSource::find($id);

        if (!$yardSource) {
            return response()->json([
                'success' => false,
                'message' => 'Yard Source not found'
            ], 404);
        }

        $validator = \Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:yard_sources,name,' . $id,
            'status' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $yardSource->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Yard Source updated successfully',
            'data' => $yardSource
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $yardSource = YardSource::find($id);

        if (!$yardSource) {
            return response()->json([
                'success' => false,
                'message' => 'Yard Source not found'
            ], 404);
        }

        $yardSource->delete();

        return response()->json([
            'success' => true,
            'message' => 'Yard Source deleted successfully'
        ]);
    }

    /**
     * Get list of yard sources (id and name only)
     */
    public function getYardSourcesList()
    {
        $yardSources = YardSource::where('status', true)
            ->select('id', 'name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $yardSources
        ]);
    }
}
