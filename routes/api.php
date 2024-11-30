<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// GET request to retrieve all students
Route::get('students', [StudentController::class, 'getStudents'])->name('students.index');

//create new student record along with its files
Route::post('/students', [StudentController::class, 'createStudent']);

// PUT request to update an existing student data
Route::put('students/{id}', [StudentController::class, 'updateStudent'])->name('students.update');

//file individual update request
Route::post('/documents/{id}', [StudentController::class, 'fileUpdate']);

// DELETE request to delete a student along with accociated files
Route::delete('students/{id}', [StudentController::class, 'deleteStudent'])->name('students.delete');

//delete individual files
Route::delete('/document/{id}', [StudentController::class, 'deleteFile']);

//add new file to student
Route::post('/students/{studentId}/add-files', [StudentController::class, 'AddFile']);

//upload/update new student avatar
Route::post('students/{student}/avatar', [StudentController::class, 'uploadAvatar']);




Route::get('/hello-world', function () {
    return response()->json(['message' => 'Hello Caringal!']);
});
