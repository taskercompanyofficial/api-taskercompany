<?php

namespace App\Http\Controllers\Crm\Authenticated;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get filters and pagination inputs
        $from = $request->input('from');
        $to = $request->input('to');
        $perPage = $request->input('per_page', 50);
        $q = $request->input('q');
        $status = $request->input('status');

        // Query with filters
        $servicesQuery = Services::query()
            ->when($q, fn($query) => $query->where('name', 'like', "%$q%"))
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($from && $to, fn($query) => $query->whereBetween('created_at', [$from, $to]))
            ->orderByDesc('created_at');

        // Paginate directly
        $services = $servicesQuery->paginate($perPage);

        // Return JSON response with built-in pagination
        return response()->json([
            'data' => $services->items(),
            'pagination' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
                'next_page' => $services->nextPageUrl(),
                'prev_page' => $services->previousPageUrl(),
            ],
        ]);
    }
    public function app_index(Request $request)
    {
        // Get all services
        $services = Services::orderByDesc('created_at')->get();

        // Return JSON response with all services
        return response()->json([
            'data' => $services
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'required|string|max:1000',
            'status' => 'required|in:active,inactive',
            'icon' => 'required|string|max:255',
            'color' => 'required|string|max:255'
        ]);

        try {
            $service = new Services();
            $service->name = $validatedData['name'];
            $service->slug = Str::slug($validatedData['name'], '-');
            $service->description = $validatedData['description'];
            $service->status = $validatedData['status'];
            $service->icon = $validatedData['icon'];
            $service->color = $validatedData['color'];
            $service->save();

            return response()->json([
                "status" => "success",
                "message" => "Service has been created successfully",
                "data" => $service,
            ], 201);
        } catch (\Exception $err) {
            // Log the error and return a response
            Log::error("Error creating Service: " . $err->getMessage(), [
                'stack' => $err->getTraceAsString(),
            ]);

            return response()->json([
                "status" => "error",
                "message" => "Error creating Service: " . $err->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $service = Services::where('slug', $slug)->first();
        return $service;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $slug)
    {
        // Find the service first
        $service = Services::where('slug', $slug)->firstOrFail();

        // Validate the incoming request
        $validatedData = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255|unique:services,name,' . $service->id,
            'description' => 'required|string|max:250',
            'keywords' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp',
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp',
        ]);

        try {
            // Store the current paths (to avoid overwriting if no new image is uploaded)
            $imagePath = $service->image; // Default current image path
            $heroImagePath = $service->hero_image; // Default current hero image path

            // Handle image upload if it exists
            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
                // Store the new image and update the image path
                $imagePath = $request->file('image')->store('images/services', 'public');
            }

            // Handle hero image upload if it exists
            if ($request->hasFile('hero_image')) {
                // Delete the old hero image if it exists
                if ($heroImagePath) {
                    Storage::disk('public')->delete($heroImagePath);
                }
                // Store the new hero image and update the hero image path
                $heroImagePath = $request->file('hero_image')->store('images/services', 'public');
            }

            // Update service data
            $service->category_id = $validatedData['category_id'];
            $service->name = $validatedData['name'];
            $service->slug = Str::slug($validatedData['name'], '-');
            $service->description = $validatedData['description'];
            $service->keywords = $validatedData['keywords'];
            $service->status = $validatedData['status'];
            $service->image = $imagePath; // Only updated if a new image is uploaded
            $service->hero_image = $heroImagePath; // Only updated if a new hero image is uploaded
            $service->save();

            return response()->json([
                "status" => "success",
                "message" => "Service has been updated",
                "data" => $service,
            ], 200);
        } catch (\Exception $err) {
            // Log the error and return a response
            Log::error("Error updating Service: " . $err->getMessage(), [
                'stack' => $err->getTraceAsString(),
            ]);
            return response()->json([
                "status" => "error",
                "message" => "Error updating Service: " . $err->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $delete = Services::destroy($id);
        return response()->json([
            "status" => "success",
            "message" => "Service has been deleted",
        ], 200);
    }
}
