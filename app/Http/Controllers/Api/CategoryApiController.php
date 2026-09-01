<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryApiController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        // Search by category name or slug.
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filter by active/inactive status.
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query
            ->withCount('products')
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json($categories);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => $category->load('creator', 'updater'),
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'category' => $category->load('creator', 'updater')
                ->loadCount('products'),
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->ignore($category->id),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $nameChanged = $validated['name'] !== $category->name;

        $category->name = $validated['name'];
        $category->description = $validated['description'] ?? null;

        if (array_key_exists('is_active', $validated)) {
            $category->is_active = $validated['is_active'];
        }

        // Regenerate slug only when the category name changes.
        if ($nameChanged) {
            $category->slug = $this->generateUniqueSlug(
                $validated['name'],
                $category->id
            );
        }

        $category->updated_by = $request->user()->id;
        $category->save();

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => $category->load('creator', 'updater'),
        ]);
    }

    /**
     * Soft delete the specified category.
     */
    public function destroy(
        Request $request,
        Category $category
    ): JsonResponse {
        // Don't allow deleting a category that still contains products.
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that has products assigned to it.',
            ], 422);
        }

        $category->updated_by = $request->user()->id;
        $category->save();

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }

    /**
     * Generate a unique slug.
     */
    private function generateUniqueSlug(
        string $name,
        ?int $ignoreId = null
    ): string {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Category::withTrashed()
                ->where('slug', $slug)
                ->when(
                    $ignoreId,
                    fn ($query) => $query->where('id', '!=', $ignoreId)
                )
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
