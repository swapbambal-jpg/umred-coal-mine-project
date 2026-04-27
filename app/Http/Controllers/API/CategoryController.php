<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request, $rest_type = 1)
    {
        $categories = Category::where('rest_type', $rest_type)->get();
        return response()->json($categories, 200);
    }


    
    public function getRestCat($restId)
    {
        // Assuming 'restaurant_id' is the foreign key in your categories table
    //$categories = Category::where('rest_type', $restId)->get();
    $categories = Category::all();

    return response()->json($categories, 200);
    }
    
    

    public function store(Request $request){

    try {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name'
        ]);

        $category = Category::create($request->all());

        return response()->json([
            'status' => 200,
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation error',
            'status' => 500,
            'errors' => $e->errors()["name"][0]
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Something went wrong',
            'error' => $e->getMessage()
        ], 500);
    }
}


    public function show(Category $category)
    {
        return response()->json($category, 200);
    }

    public function update(Request $request, Category $category)
{

    try {

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id
        ], [
            'name.unique' => 'The category name already exists. Please choose a different name.'
        ]);

        $category->update($request->all());

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ], 200);


    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation error',
            'status' => 500,
            'errors' => $e->errors()["name"][0]
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Something went wrong',
            'error' => $e->getMessage()
        ], 500);
    }


}


    public function destroy($id)
    {
        try {
            // Check if the category exists
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Category not found or already deleted'
                ], 404);
            }

            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully',
                'category' => $category
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'status' => 422,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
