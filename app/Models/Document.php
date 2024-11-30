<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'file_path',
        'original_filename',
        'file_size',
        'mime_type'
    ];

    /**
     * Get the formatted file size
     *
     * @return string
     */
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        
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

    /**
     * Get the base name of the file without unique suffix
     *
     * @return string
     */
    public function getBaseNameAttribute()
    {
        $filename = pathinfo($this->original_filename, PATHINFO_FILENAME);
        return preg_replace('/_[a-zA-Z0-9]{4}$/', '', $filename);
    }

    /**
     * Get the file extension
     *
     * @return string
     */
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->original_filename, PATHINFO_EXTENSION);
    }

    /**
     * Define an inverse relationship with the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}