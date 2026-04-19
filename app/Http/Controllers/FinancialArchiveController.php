<?php

namespace App\Http\Controllers;

use App\Models\FinancialArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class FinancialArchiveController extends Controller
{
    public function index(Request $request)
    {
        \Log::info("FinancialArchive index called");
        try {
            $query = \App\Models\FinancialArchive::query();

            if ($request->has('category') && $request->category !== 'all') {
                $query->where('category', $request->category);
            }

            if ($request->has('search')) {
                $query->where('document_name', 'like', '%' . $request->search . '%')
                    ->orWhere('related_entity', 'like', '%' . $request->search . '%');
            }

            return response()->json($query->orderBy('created_at', 'desc')->get());
        } catch (\Exception $e) {
            \Log::error("FinancialArchive error: " . $e->getMessage());
            return response()->json([
                [
                    'id' => 1,
                    'document_name' => 'خطأ: ' . $e->getMessage(),
                    'category' => 'إيصالات قبض',
                    'file_path' => '#',
                    'file_type' => 'PDF',
                    'file_size' => '0 KB',
                    'uploaded_by' => 'System',
                    'related_entity' => 'SQL Error',
                    'created_at' => now()->toISOString()
                ]
            ]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_name' => 'required|string',
            'category' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'related_entity' => 'nullable|string'
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('financial_archive', 'public');
            $size = $request->file('file')->getSize();
            $type = $request->file('file')->getClientOriginalExtension();

            $archive = FinancialArchive::create([
                'document_name' => $request->document_name,
                'category' => $request->category,
                'file_path' => $path,
                'file_size' => $this->formatBytes($size),
                'file_type' => strtoupper($type),
                'uploaded_by' => auth()->user()->name ?? 'System',
                'related_entity' => $request->related_entity,
                'status' => 'active'
            ]);

            return response()->json($archive, 201);
        }

        return response()->json(['message' => 'File not found'], 400);
    }

    public function destroy($id)
    {
        $archive = FinancialArchive::findOrFail($id);
        if ($archive->file_path && Storage::disk('public')->exists($archive->file_path)) {
            Storage::disk('public')->delete($archive->file_path);
        }
        $archive->delete();
        return response()->json(['message' => 'Document deleted']);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
