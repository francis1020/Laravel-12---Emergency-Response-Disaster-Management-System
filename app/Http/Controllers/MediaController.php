<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Models\EmergencyReportMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaController extends Controller
{
    public function store(Request $request, $reportId)
    {
        try {
            $report = EmergencyReport::findOrFail($reportId);
            
            // Allow all authenticated users (user, responder, admin, super admin) to upload
            // No additional permission check needed as route is already protected by auth middleware

            // Validate files with custom error messages - Only images and videos allowed
            $validated = $request->validate([
                'files.*' => 'required|file|mimes:jpeg,jpg,png,gif,mp4,avi,mov|max:10240', // 10MB max
            ], [
                'files.*.required' => 'File is required',
                'files.*.file' => 'Invalid file',
                'files.*.mimes' => 'Invalid file type. Only images (jpeg, jpg, png, gif) and videos (mp4, avi, mov) are allowed.',
                'files.*.max' => 'File size exceeds maximum limit of 10MB',
            ]);

            // Additional validation: Check if files array exists and is not empty
            if (!$request->hasFile('files') || count($request->file('files')) === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No files provided',
                    'errors' => ['files' => ['Please select at least one file to upload.']]
                ], 422);
            }

            $uploadedFiles = [];
            $invalidFiles = [];

            foreach ($request->file('files', []) as $index => $file) {
                // Additional file type validation - Only images and videos allowed
                $allowedMimes = [
                    // Images
                    'image/jpeg', 
                    'image/jpg', 
                    'image/png', 
                    'image/gif',
                    // Videos
                    'video/mp4', 
                    'video/avi', 
                    'video/quicktime',
                    'video/x-msvideo' // AVI alternative MIME type
                ];
                
                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    $invalidFiles[] = [
                        'file' => $file->getClientOriginalName(),
                        'type' => $file->getMimeType(),
                        'message' => 'Invalid file type. Only pictures (images) and videos are allowed.'
                    ];
                    continue;
                }
                $fileType = $this->getFileType($file->getMimeType());
                $storagePath = "emergency-reports/{$reportId}";
                $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                
                $filePath = $file->storeAs($storagePath, $fileName, 'b2');
                $thumbnailPath = null;

                // Generate thumbnail for images
                if (in_array($fileType, ['image'])) {
                    try {
                        $thumbnailPath = $this->generateThumbnail($file, $storagePath);
                    } catch (\Exception $e) {
                        // Continue without thumbnail if generation fails
                    }
                }

                $media = EmergencyReportMedia::create([
                    'emergency_report_id' => $reportId,
                    'user_id' => Auth::id(), // Track who uploaded the file
                    'file_path' => $filePath,
                    'file_type' => $fileType,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'thumbnail_path' => $thumbnailPath,
                ]);

                $uploadedFiles[] = [
                    'id' => $media->id,
                    'url' => $media->url,
                    'thumbnail_url' => $media->thumbnail_url,
                    'file_type' => $fileType,
                ];
            }

            // If there are invalid files, return error
            if (count($invalidFiles) > 0) {
                $errorMessages = array_map(function($item) {
                    return $item['file'] . ': ' . $item['message'];
                }, $invalidFiles);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Some files are invalid',
                    'errors' => [
                        'files' => $errorMessages
                    ],
                    'invalid_files' => $invalidFiles
                ], 422);
            }

            // If no files were uploaded successfully
            if (count($uploadedFiles) === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid files were uploaded',
                    'errors' => ['files' => ['Please upload valid files only.']]
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Files uploaded successfully',
                'media' => $uploadedFiles,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error uploading files: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index($reportId)
    {
        $report = EmergencyReport::findOrFail($reportId);
        
        // Allow all authenticated users to view media
        // No additional permission check needed as route is already protected by auth middleware
        $media = $report->media()->get()->map(function ($item) {

            $disk = Storage::disk('b2');

            $b2FilePathUrl = $item->url;
            $b2ThumbnailUrl = $item->thumbnail_url ? $item->thumbnail_url : $item->url;

            // if b2FilePathUrl exist {
            if ($disk->exists($item->file_path)) {
                $b2FilePathUrl = $disk->temporaryUrl(
                    $item->file_path,
                    now()->addMinutes(10) // valid for 10 minutes
                );

                $b2ThumbnailUrl = $b2FilePathUrl; 
            }

            return [
                'id' => $item->id,
                'url' => $b2FilePathUrl,
                'thumbnail_url' => $b2FilePathUrl,
                'file_type' => $item->file_type,
                'mime_type' => $item->mime_type,
                'description' => $item->description,
                'user_id' => $item->user_id, // Include uploader's user_id
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
        
        // Allow the user who uploaded the file OR admins/super admins to delete it
        $isUploader = $media->user_id === Auth::id();
        $isAdmin = Auth::user()->isAdmin();
        
        if (!$isUploader && !$isAdmin) {
            return response()->json([
                'status' => 'error', 
                'message' => 'You can only delete files that you uploaded.'
            ], 403);
        }

        // Delete files
        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }
        if ($media->thumbnail_path && Storage::disk('public')->exists($media->thumbnail_path)) {
            Storage::disk('public')->delete($media->thumbnail_path);
        }

        $media->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Media deleted successfully',
        ]);
    }

    private function getFileType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } else {
            return 'document';
        }
    }

    private function generateThumbnail($file, $storagePath)
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);
            $image->scale(width: 300, height: 300);
            
            $thumbnailName = 'thumb_' . uniqid() . '_' . time() . '.jpg';
            $thumbnailPath = $storagePath . '/' . $thumbnailName;
            
            $encoded = $image->toJpeg(80);
            Storage::disk('public')->put($thumbnailPath, $encoded);
            
            return $thumbnailPath;
        } catch (\Exception $e) {
            // Return null if thumbnail generation fails
            return null;
        }
    }
}

