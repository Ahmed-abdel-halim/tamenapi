<?php

namespace App\Http\Controllers;

use App\Models\ExternalEntity;
use Illuminate\Http\Request;

class ExternalEntityController extends Controller
{
    public function index()
    {
        return response()->json(ExternalEntity::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_number' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'default_messenger_name' => 'nullable|string|max:255',
            'default_messenger_phone' => 'nullable|string|max:50',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo_path'] = $path;
        }

        $entity = ExternalEntity::create($validated);
        return response()->json($entity, 201);
    }

    public function show(ExternalEntity $externalEntity)
    {
        return response()->json($externalEntity);
    }

    public function update(Request $request, ExternalEntity $externalEntity)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_number' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'default_messenger_name' => 'nullable|string|max:255',
            'default_messenger_phone' => 'nullable|string|max:50',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($externalEntity->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($externalEntity->logo_path);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo_path'] = $path;
        }

        $externalEntity->update($validated);
        return response()->json($externalEntity);
    }

    public function destroy(ExternalEntity $externalEntity)
    {
        $externalEntity->delete();
        return response()->json(['message' => 'Entity deleted successfully']);
    }
}
