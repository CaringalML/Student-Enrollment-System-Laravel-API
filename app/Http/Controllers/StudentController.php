<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Document;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use Illuminate\Http\UploadedFile;
use Aws\Rekognition\RekognitionClient;


class StudentController extends Controller
{
    // Retrieve all students
    //config/cors.php that is were you find allowed origins or domain who can only access this Laravel API
    public function getStudents()
    {
        $students = Student::with('documents')->get();
        
        $cloudFrontDomain = config('services.cloudfront.domain');
        
        foreach ($students as $student) {
            if ($student->avatar_path) {
                // Remove any potential double https://
                $student->avatar_url = "https://{$cloudFrontDomain}/{$student->avatar_path}";
            }
            
            foreach ($student->documents as $document) {
                if ($document->file_path) {
                    // Remove any potential double https://
                    $document->document_url = "https://{$cloudFrontDomain}/{$document->file_path}";
                }
            }
        }
        
        return response()->json($students);
    }

    
    
    public function createStudent(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'age' => 'required|integer|min:1',
                'address' => 'required|string|max:255',
                'email' => 'required|email|unique:students,email',
                'course' => 'required|string|max:255',
                'student_files.*' => 'nullable|file|mimes:pdf,docx,xlsx,pptx,jpeg,png|max:2048',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ], [
                'name.required' => 'The name field is required.',
                'name.string' => 'The name must be a string.',
                'name.max' => 'The name cannot exceed 255 characters.',
                'age.required' => 'The age field is required.',
                'age.integer' => 'The age must be a number.',
                'age.min' => 'The age must be at least 1.',
                'address.required' => 'The address field is required.',
                'address.string' => 'The address must be a string.',
                'address.max' => 'The address cannot exceed 255 characters.',
                'email.required' => 'The email field is required.',
                'email.email' => 'Please provide a valid email address.',
                'email.unique' => 'This email is already registered.',
                'course.required' => 'The course field is required.',
                'course.string' => 'The course must be a string.',
                'course.max' => 'The course name cannot exceed 255 characters.',
                'student_files.*.mimes' => 'Only pdf, docx, xlsx, pptx, jpeg, and png files are allowed.',
                'student_files.*.max' => 'File size should not exceed 2MB.',
                'avatar.image' => 'The avatar must be an image.',
                'avatar.mimes' => 'Only jpeg, png, and jpg formats are allowed for avatars.',
                'avatar.max' => 'Avatar size should not exceed 2MB.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Create student record
            $student = Student::create([
                'name' => $request->name,
                'age' => $request->age,
                'address' => $request->address,
                'email' => $request->email,
                'course' => $request->course,
            ]);

            $avatarUrl = null;
            $qualityAssessment = null;

            // Handle avatar upload if present
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                
                // Get face validation and assessment
                $faceResult = $this->validateAndAssessFace($avatar);
                
                if (!$faceResult['isValid']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Image validation failed',
                        'errors' => [
                            'image_validation' => [
                                'message' => $faceResult['message'],
                                'quality_issues' => $faceResult['quality_issues'] ?? [],
                                'quality_assessment' => $faceResult['quality_assessment'] ?? null
                            ]
                        ]
                    ], 422);
                }

                $qualityAssessment = $faceResult['quality_assessment'];

                // Process and store avatar
                $originalFileName = pathinfo($avatar->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $avatar->getClientOriginalExtension();
                $uniqueSuffix = '_' . Str::random(4);
                $avatarFileName = $originalFileName . $uniqueSuffix . '.' . $extension;
                
                $avatarPath = $avatar->storeAs('avatar_images', $avatarFileName, 's3');
                $avatarUrl = Storage::disk('s3')->url($avatarPath);
                
                $student->avatar_path = $avatarPath;
                $student->save();
            }

            // Handle document uploads
            $uploadedFiles = [];
            $failedUploads = [];

            if ($request->hasFile('student_files')) {
                foreach ($request->file('student_files') as $file) {
                    try {
                        if (!$file->isValid()) {
                            throw new \Exception('Invalid file upload');
                        }

                        $originalFileName = $file->getClientOriginalName();
                        $extension = $file->getClientOriginalExtension();
                        $baseName = pathinfo($originalFileName, PATHINFO_FILENAME);
                        $uniqueSuffix = '_' . Str::random(4);
                        $newFileName = $baseName . $uniqueSuffix . '.' . $extension;
                        
                        $documentPath = $file->storeAs('student_files', $newFileName, 's3');

                        $document = Document::create([
                            'student_id' => $student->id,
                            'file_path' => $documentPath,
                            'original_filename' => $originalFileName,
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                        ]);

                        $uploadedFiles[] = [
                            'filename' => $originalFileName,
                            'path' => $documentPath,
                            'document_id' => $document->id,
                            'url' => Storage::disk('s3')->url($documentPath)
                        ];

                    } catch (\Exception $e) {
                        $failedUploads[] = [
                            'filename' => $originalFileName ?? 'Unknown file',
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            DB::commit();

            // Prepare success response
            $response = [
                'status' => 'success',
                'message' => 'Student record created successfully',
                'data' => [
                    'student' => $student,
                    'uploaded_files' => $uploadedFiles,
                    'avatar_url' => $avatarUrl,
                    'quality_assessment' => $qualityAssessment
                ]
            ];

            if (!empty($failedUploads)) {
                $response['failed_uploads'] = $failedUploads;
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            \Log::error('Student creation error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create student record!',
                'error' => $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    private function validateAndAssessFace(UploadedFile $image): array
    {
        try {
            $rekognition = new RekognitionClient([
                'version' => 'latest',
                'region'  => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY')
                ]
            ]);

            $imageContents = file_get_contents($image->getRealPath());

            $result = $rekognition->detectFaces([
                'Image' => [
                    'Bytes' => $imageContents
                ],
                'Attributes' => ['ALL']
            ]);

            if (empty($result['FaceDetails'])) {
                return [
                    'isValid' => false,
                    'message' => 'No human face detected in the image',
                    'quality_issues' => ['No face detected in the uploaded image'],
                    'quality_assessment' => null
                ];
            }

            $faceDetails = $result['FaceDetails'][0];
            $qualityIssues = [];
            $qualityWarnings = [];

            // Image Quality Checks
            $quality = $faceDetails['Quality'];

            // Brightness Check (0-100)
            if ($quality['Brightness'] < 20) {
                $qualityIssues[] = 'Image is extremely dark (brightness: ' . round($quality['Brightness']) . '%)';
            } elseif ($quality['Brightness'] < 30) {
                $qualityIssues[] = 'Image is too dark (brightness: ' . round($quality['Brightness']) . '%)';
            } elseif ($quality['Brightness'] < 40) {
                $qualityWarnings[] = 'Image brightness is low (brightness: ' . round($quality['Brightness']) . '%)';
            } elseif ($quality['Brightness'] > 90) {
                $qualityIssues[] = 'Image is too bright (brightness: ' . round($quality['Brightness']) . '%)';
            }

            // Sharpness Check (0-100)
            if ($quality['Sharpness'] < 20) {
                $qualityIssues[] = 'Image is extremely blurry (sharpness: ' . round($quality['Sharpness']) . '%)';
            } elseif ($quality['Sharpness'] < 30) {
                $qualityIssues[] = 'Image is too blurry (sharpness: ' . round($quality['Sharpness']) . '%)';
            } elseif ($quality['Sharpness'] < 40) {
                $qualityWarnings[] = 'Image could be clearer (sharpness: ' . round($quality['Sharpness']) . '%)';
            }

            // Generate quality assessment
            $qualityAssessment = [
                'brightness' => [
                    'value' => round($quality['Brightness'], 2),
                    'status' => $this->getQualityStatus($quality['Brightness']),
                    'recommendation' => $this->getBrightnessRecommendation($quality['Brightness'])
                ],
                'sharpness' => [
                    'value' => round($quality['Sharpness'], 2),
                    'status' => $this->getQualityStatus($quality['Sharpness']),
                    'recommendation' => $this->getSharpnessRecommendation($quality['Sharpness'])
                ],
                'overall_quality' => $this->assessOverallQuality($quality),
                'warnings' => $qualityWarnings
            ];

            $isValid = empty($qualityIssues);
            $message = $isValid 
                ? 'Image quality meets requirements' 
                : 'Image quality does not meet minimum requirements';

            return [
                'isValid' => $isValid,
                'message' => $message,
                'quality_issues' => $qualityIssues,
                'quality_assessment' => $qualityAssessment
            ];

        } catch (\Exception $e) {
            \Log::error('Face detection error: ' . $e->getMessage());
            return [
                'isValid' => false,
                'message' => 'Error processing image',
                'quality_issues' => ['Failed to process image: ' . $e->getMessage()],
                'quality_assessment' => null
            ];
        }
    }

    private function getQualityStatus(float $value): string
    {
        if ($value >= 80) return 'Excellent';
        if ($value >= 60) return 'Good';
        if ($value >= 40) return 'Fair';
        if ($value >= 30) return 'Poor';
        return 'Unacceptable';
    }

    private function getBrightnessRecommendation(float $brightness): string
    {
        if ($brightness < 20) {
            return 'Try taking the photo in a well-lit area or add more lighting';
        } elseif ($brightness < 30) {
            return 'Increase the lighting in your environment';
        } elseif ($brightness < 40) {
            return 'Consider using slightly better lighting';
        } elseif ($brightness > 90) {
            return 'Reduce the lighting or avoid direct bright light';
        }
        return 'Lighting is acceptable';
    }

    private function getSharpnessRecommendation(float $sharpness): string
    {
        if ($sharpness < 20) {
            return 'Image is too blurry. Keep the camera steady and ensure proper focus';
        } elseif ($sharpness < 30) {
            return 'Make sure the camera is focused on your face';
        } elseif ($sharpness < 40) {
            return 'Try to keep the camera more steady';
        }
        return 'Image clarity is acceptable';
    }

    private function assessOverallQuality(array $quality): string
    {
        $avgQuality = ($quality['Brightness'] + $quality['Sharpness']) / 2;
        
        if ($avgQuality >= 80) return 'Excellent';
        if ($avgQuality >= 60) return 'Good';
        if ($avgQuality >= 40) return 'Fair';
        if ($avgQuality >= 30) return 'Poor';
        return 'Unacceptable';
    }




//Update Student Data
public function updateStudent(Request $request, $id)
{
    // Find the student or return 404
    $student = Student::findOrFail($id);

    // Validate the request data
    $validatedData = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'age' => 'sometimes|required|integer|min:1',
        'address' => 'sometimes|required|string|max:255',
        'email' => 'sometimes|required|email|unique:students,email,' . $id,
        'course' => 'sometimes|required|string|max:255',
    ]);

    try {
        // Update the student record
        $student->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Student updated successfully',
            'data' => $student->fresh()
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update student',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function fileUpdate(Request $request, $id)
{
    // Validate incoming request data
    $validatedData = $request->validate([
        'student_file' => 'required|file|mimes:pdf,docx,xlsx,pptx,jpeg,png|max:2048',
    ]);

    // Find the document by ID
    $document = Document::find($id);

    if (!$document) {
        return response()->json(['message' => 'Document not found'], 404);
    }

    // Handle file upload
    if ($request->hasFile('student_file')) {
        $file = $request->file('student_file');

        // Get the original filename without extension
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalExtension = $file->getClientOriginalExtension();
        
        // Generate a 4-character unique string and append to the original filename
        $uniqueSuffix = Str::random(4);
        $uniqueFilename = "{$originalName}_{$uniqueSuffix}.{$originalExtension}";

        // Save the file to the 'student_files' directory on S3 with the unique name
        $path = Storage::disk('s3')->putFileAs('student_files', $file, $uniqueFilename);

        if ($path) {
            // Delete the old file from S3 if it exists
            if ($document->file_path && Storage::disk('s3')->exists($document->file_path)) {
                Storage::disk('s3')->delete($document->file_path);
            }

            // Update the document record with the new file path
            $document->file_path = $path;
            $document->save(); // Save the updated document record

            return response()->json([
                'message' => 'File uploaded successfully and document record updated.',
                'document' => $document,
            ], 200);
        } else {
            return response()->json([
                'message' => 'File upload failed. Please try again.',
            ], 500);
        }
    }

    return response()->json([
        'message' => 'No file provided in the request.',
    ], 400);
}






public function deleteStudent($id)
{
    // Begin transaction to ensure all operations complete or none do
    DB::beginTransaction();

    try {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Delete avatar image if exists
        if ($student->avatar_path) {
            $avatarPath = 'avatar_images/' . basename($student->avatar_path);
            if (Storage::disk('s3')->exists($avatarPath)) {
                Storage::disk('s3')->delete($avatarPath);
            }
        }

        // Delete associated documents
        foreach ($student->documents as $document) {
            if (Storage::disk('s3')->exists($document->file_path)) {
                Storage::disk('s3')->delete($document->file_path);
            }
            $document->delete();
        }

        // Force delete the student record (bypasses soft deletes if enabled)
        $student->forceDelete();

        // Explicitly delete from students table as a backup measure
        DB::table('students')->where('id', $id)->delete();

        // If everything is successful, commit the transaction
        DB::commit();

        return response()->json([
            'message' => 'Student and associated files deleted successfully'
        ], 200);

    } catch (\Exception $e) {
        // If any error occurs, rollback the transaction
        DB::rollback();
        
        \Log::error('Error deleting student: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'message' => 'Error occurred while deleting student',
            'error' => $e->getMessage()
        ], 500);
    }
}



// //Delete individual files
//     public function deleteFile($id)
// {
//     // Find the document by ID
//     $document = Document::find($id);

//     if (!$document) {
//         return response()->json(['message' => 'Document not found'], 404);
//     }

//     try {
//         // Check if the file exists in S3 and delete it
//         if ($document->file_path && Storage::disk('s3')->exists($document->file_path)) {
//             Storage::disk('s3')->delete($document->file_path);
//         }

//         // Delete the document record from the database
//         $document->delete();

//         return response()->json([
//             'message' => 'File and document record deleted successfully.',
//         ], 200);

//     } catch (\Exception $e) {
//         return response()->json([
//             'message' => 'Failed to delete file. Please try again.',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }








public function deleteFile($id)
{
    try {
        // Find the document by ID
        $document = Document::findOrFail($id);

        // Delete from S3 if exists
        if ($document->file_path && Storage::disk('s3')->exists($document->file_path)) {
            Storage::disk('s3')->delete($document->file_path);
        }

        // Delete the document record from the database
        $document->delete();

        // Get the updated student data
        $student = $document->student;
        $updatedStudentData = Student::with('documents')->find($student->id);

        return response()->json([
            'status' => 'success',
            'message' => 'File deleted successfully',
            'student' => $updatedStudentData
        ], 200);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Document not found'
        ], 404);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to delete file',
            'error' => $e->getMessage()
        ], 500);
    }
}








public function AddFile(Request $request, $studentId)
{
    try {
        // Validate the request data
        $validatedData = $request->validate([
            'student_files.*' => 'required|file|mimes:pdf,docx,xlsx,pptx,jpeg,png|max:2048',
        ], [
            'student_files.*.required' => 'Please select a file to upload.',
            'student_files.*.mimes' => 'Only pdf, docx, xlsx, pptx, jpeg, and png files are allowed.',
            'student_files.*.max' => 'File size should not exceed 2MB.',
        ]);

        // Start database transaction
        DB::beginTransaction();

        // Find the student record by ID
        $student = Student::findOrFail($studentId);
        
        // Get only this student's existing files and base names
        $studentExistingFiles = $student->documents()
            ->get(['original_filename', 'file_path'])
            ->map(function ($doc) {
                $fileName = pathinfo($doc->original_filename, PATHINFO_FILENAME);
                return preg_replace('/_[a-zA-Z0-9]{4}$/', '', $fileName);
            })
            ->toArray();

        $uploadedFiles = [];
        $failedUploads = [];
        $duplicateFiles = [];

        if ($request->hasFile('student_files')) {
            foreach ($request->file('student_files') as $file) {
                try {
                    // Validate individual file
                    if (!$file->isValid()) {
                        throw new \Exception('Invalid file upload');
                    }

                    $originalFileName = $file->getClientOriginalName();
                    $baseName = pathinfo($originalFileName, PATHINFO_FILENAME);

                    // Check for duplicates by comparing base names
                    if (in_array($baseName, $studentExistingFiles)) {
                        $duplicateFiles[] = [
                            'filename' => $originalFileName,
                            'message' => 'You already have uploaded this file'
                        ];
                        continue;
                    }

                    $extension = $file->getClientOriginalExtension();
                    $uniqueSuffix = '_' . Str::random(4);
                    $newFileName = $baseName . $uniqueSuffix . '.' . $extension;

                    // Store the file in S3
                    $documentPath = $file->storeAs('student_files', $newFileName, 's3');

                    // Create document record
                    $document = Document::create([
                        'student_id' => $student->id,
                        'file_path' => $documentPath,
                        'original_filename' => $originalFileName,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);

                    // Generate temporary URL
                    $temporaryUrl = Storage::disk('s3')->temporaryUrl(
                        $documentPath,
                        now()->addMinutes(5)
                    );

                    $uploadedFiles[] = [
                        'filename' => $originalFileName,
                        'path' => $documentPath,
                        'document_id' => $document->id,
                        'url' => $temporaryUrl,
                        'size' => $document->formatted_file_size, // Using the accessor from the model
                        'mime_type' => $document->mime_type
                    ];

                } catch (\Exception $e) {
                    \Log::error('File upload error for file ' . ($originalFileName ?? 'unknown') . ': ' . $e->getMessage());
                    $failedUploads[] = [
                        'filename' => $originalFileName ?? 'Unknown file',
                        'error' => $e->getMessage()
                    ];
                }
            }
        } else {
            throw new ValidationException(validator([], ['files' => 'required']), [
                'message' => 'No files were uploaded'
            ]);
        }

        // Commit the transaction
        DB::commit();

        // Prepare response
        $response = [
            'status' => 'success',
            'message' => 'Files processed successfully',
            'data' => [
                'uploaded_files' => $uploadedFiles,
                'total_uploaded' => count($uploadedFiles)
            ]
        ];

        if (!empty($duplicateFiles)) {
            $response['data']['duplicate_files'] = $duplicateFiles;
            $response['message'] = count($uploadedFiles) > 0 
                ? 'Some files were uploaded, some were duplicates' 
                : 'All files were duplicates';
        }

        if (!empty($failedUploads)) {
            $response['data']['failed_uploads'] = $failedUploads;
            $response['message'] = 'Some files failed to upload';
        }

        return response()->json($response, empty($uploadedFiles) ? 409 : 201);

    } catch (ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Student not found'
        ], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('File upload error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'An unexpected error occurred',
            'error' => $e->getMessage()
        ], 500);
    }
}










/**
 * Check if a file already exists for a student
 * 
 * @param int $studentId
 * @param string $filename
 * @return bool|array Returns false if no duplicate, or array with existing file info if duplicate found
 */
private function checkStudentFileExists($studentId, $filename)
{
    // Get base name without extension and potential existing suffix
    $newBaseName = pathinfo($filename, PATHINFO_FILENAME);
    $newBaseName = preg_replace('/_[a-zA-Z0-9]{4}$/', '', $newBaseName);

    // Get all student's existing files
    $existingFiles = Document::where('student_id', $studentId)
        ->get(['id', 'original_filename', 'file_path']);

    foreach ($existingFiles as $existingFile) {
        // Get base name of existing file without suffix
        $existingBaseName = pathinfo($existingFile->original_filename, PATHINFO_FILENAME);
        $existingBaseName = preg_replace('/_[a-zA-Z0-9]{4}$/', '', $existingBaseName);

        // Compare base names
        if (strtolower($newBaseName) === strtolower($existingBaseName)) {
            return [
                'exists' => true,
                'existing_file' => $existingFile->original_filename,
                'file_path' => $existingFile->file_path
            ];
        }
    }

    return false;
}










/**
 * Format file size to human readable format
 *
 * @param int $bytes
 * @return string
 */
private function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return $bytes . ' byte';
    } else {
        return '0 bytes';
    }
}




public function uploadAvatar(Request $request, Student $student)
{
    try {
        // Validate the uploaded file
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:' . config('app.max_avatar_size', 2048)]
        ]);

        // Validate face quality first using your existing method
        $file = $request->file('avatar');
        $faceValidation = $this->validateAndAssessFace($file);

        if (!$faceValidation['isValid']) {
            return response()->json([
                'message' => $faceValidation['message'],
                'quality_issues' => $faceValidation['quality_issues'],
                'quality_assessment' => $faceValidation['quality_assessment']
            ], 422);
        }

        // Delete old avatar if exists
        if ($student->avatar_path) {
            Storage::disk('s3')->delete($student->avatar_path);
        }
        
        // Get original filename without extension and clean it
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanName = Str::limit(Str::slug($originalName), 50, '');
        $extension = $file->getClientOriginalExtension();
        $randomSuffix = Str::random(4);
        
        // Create new filename: originalname_randomsuffix.extension
        $fileName = "{$cleanName}_{$randomSuffix}.{$extension}";
        
        // Store file in S3 with student ID in path for organization
        $path = $file->storeAs(
            "avatar_images/{$student->id}",
            $fileName,
            's3'
        );

        // Update student record within transaction
        DB::transaction(function () use ($student, $path) {
            $student->avatar_path = $path;
            $student->save();
        });

        // Generate CloudFront URL using your existing pattern
        $cloudFrontDomain = config('services.cloudfront.domain');
        $cloudFrontUrl = "https://{$cloudFrontDomain}/{$path}";

        return response()->json([
            'message' => 'Avatar uploaded successfully',
            'path' => $path,
            'avatar_url' => $cloudFrontUrl,
            'filename' => $fileName,
            'quality_assessment' => $faceValidation['quality_assessment']
        ], 200);

    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Invalid file upload',
            'errors' => $e->errors()
        ], 422);
    } catch (S3Exception $e) {
        return response()->json([
            'message' => 'Storage service error',
            'error' => $e->getMessage()
        ], 503);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error uploading avatar',
            'error' => $e->getMessage()
        ], 500);
    }
}






}
