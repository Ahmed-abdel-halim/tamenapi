<?php

namespace App\Http\Controllers;

use App\Models\CompanyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyDocumentController extends Controller
{
    public function index()
    {
        $documents = CompanyDocument::orderBy('created_at', 'desc')->get();
        return response()->json($documents);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'document_number' => 'required|string|max:255',

            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'attachments.*' => 'nullable|file', // Can be image, pdf, etc.
        ]);

        $paths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('company_documents', 'public');
                $paths[] = $path;
            }
        }

        $document = CompanyDocument::create([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? null,
            'document_number' => $validated['document_number'],

            'issue_date' => $validated['issue_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'attachments' => $paths,
        ]);

        return response()->json($document, 201);
    }

    public function update(Request $request, $id)
    {
        $document = CompanyDocument::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'document_number' => 'required|string|max:255',

            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'attachments.*' => 'nullable|file',
            'existing_attachments' => 'nullable|array',
            'existing_attachments.*' => 'string'
        ]);

        // Keep existing attachments that were not removed
        $paths = $validated['existing_attachments'] ?? [];

        // Add new attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('company_documents', 'public');
                $paths[] = $path;
            }
        }

        $document->update([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? null,
            'document_number' => $validated['document_number'],

            'issue_date' => $validated['issue_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'attachments' => $paths,
        ]);

        return response()->json($document);
    }

    public function destroy($id)
    {
        $document = CompanyDocument::findOrFail($id);
        
        // Optional: Delete files from storage
        if ($document->attachments) {
            foreach ($document->attachments as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }
}
