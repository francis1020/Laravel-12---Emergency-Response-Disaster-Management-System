<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Models\EmergencyReportMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaController extends Controller
{
    public function store(Request $request, $reportId)
    {
        try {
            $report = EmergencyReport::findOrFail($reportId);

            $validated = $request->validate([
                'files.*' => 'required|file|mimes:jpeg,jpg,png,gif,mp4,avi,mov|max:10240',
            ], [
                'files.*.required' => 'File is required',
                'files.*.file' => 'Invalid file',
                'files.*.mimes' => 'Invalid file type. Only images and videos are allowed.',
                'files.*.max' => 'File size exceeds maximum limit of 10MB',
            ]);

            if (!$request->hasFile('files') || count($request->file('files')) === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No files provided',
                    'errors' => ['files' => ['Please select at least one file to upload.']]
                ], 422);
            }

            $uploadedFiles = [];
            $invalidFiles = [];

            foreach ($request->file('files', []) as $file) {

                $allowedMimes = [
                    'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                    'video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo'
                ];

                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    $invalidFiles[] = [
                        'file' => $file->getClientOriginalName(),
                        'type' => $file->getMimeType(),
                        'message' => 'Invalid file type.'
                    ];
                    continue;
                }

                $fileType = $this->getFileType($file->getMimeType());
                $storagePath = "emergency-reports";
                $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();

                // ✅ SAVE DIRECTLY TO PUBLIC FOLDER
                $destinationPath = public_path('storage/' . $storagePath);

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $file->move($destinationPath, $fileName);

                // Save relative path
                $filePath = 'storage/' . $storagePath . '/' . $fileName;

                $thumbnailPath = null;

                if ($fileType === 'image') {
                    $thumbnailPath = $this->generateThumbnail($filePath, $storagePath);
                }

                $media = EmergencyReportMedia::create([
                    'emergency_report_id' => $reportId,
                    'user_id' => Auth::id(),
                    'file_path' => $filePath,
                    'file_type' => $fileType,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'thumbnail_path' => $thumbnailPath,
                ]);

                $uploadedFiles[] = [
                    'id' => $media->id,
                    'url' => asset($filePath),
                    'thumbnail_url' => $thumbnailPath ? asset($thumbnailPath) : null,
                    'file_type' => $fileType,
                ];
            }

            if (count($invalidFiles) > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Some files are invalid',
                    'errors' => ['files' => array_column($invalidFiles, 'message')],
                    'invalid_files' => $invalidFiles
                ], 422);
            }

            if (count($uploadedFiles) === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid files uploaded',
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Files uploaded successfully',
                'media' => $uploadedFiles,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index($reportId)
    {
        $report = EmergencyReport::findOrFail($reportId);

        $media = $report->media()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'url' => asset($item->file_path),
                'thumbnail_url' => $item->thumbnail_path ? asset($item->thumbnail_path) : null,
                'file_type' => $item->file_type,
                'mime_type' => $item->mime_type,
                'description' => $item->description,
                'user_id' => $item->user_id,
            ];
        });

        return response()->json([
            'status' => 'success',
            'media' => $media,
        ]);
    }

    public function destroy($mediaId)
    {
        $media = EmergencyReportMedia::findOrFail($mediaId);

        $isUploader = $media->user_id === Auth::id();
        $isAdmin = Auth::user()->isAdmin();

        if (!$isUploader && !$isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        // ✅ DELETE FILE
        $fileFullPath = public_path($media->file_path);
        if (file_exists($fileFullPath)) {
            unlink($fileFullPath);
        }

        // ✅ DELETE THUMBNAIL
        if ($media->thumbnail_path) {
            $thumbFullPath = public_path($media->thumbnail_path);
            if (file_exists($thumbFullPath)) {
                unlink($thumbFullPath);
            }
        }

        $media->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Deleted successfully',
        ]);
    }

    private function getFileType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) return 'image';
        if (str_starts_with($mimeType, 'video/')) return 'video';
        return 'document';
    }

    private function generateThumbnail($filePath, $storagePath)
    {
        try {
            $manager = new ImageManager(new Driver());

            $fullPath = public_path($filePath);
            $image = $manager->read($fullPath);
            $image->scale(width: 300, height: 300);

            $thumbnailName = 'thumb_' . uniqid() . '.jpg';
            $destinationPath = public_path('storage/' . $storagePath);

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            $thumbnailFullPath = $destinationPath . '/' . $thumbnailName;
            $image->toJpeg(80)->save($thumbnailFullPath);

            return 'storage/' . $storagePath . '/' . $thumbnailName;

        } catch (\Exception $e) {
            return null;
        }
    }
}
