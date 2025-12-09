<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class VideoController extends Controller
{
    // ✅ ADD $unit parameter
    public function stream($date, $unit, $filename)
    {
        try {
            // Get base path from config
            $basePath = config('filesystems.disks.videos.root');
            
            // ✅ Build path with unit folder: E:\video_dsm\20251029\HD7273\file.mp4
            $videoPath = "{$basePath}\\{$date}\\{$unit}\\{$filename}";
            
            // Check if video exists
            if (!File::exists($videoPath)) {
                abort(404, 'Video not found at: ' . $videoPath);
            }

            $stream = fopen($videoPath, 'r');
            $size = File::size($videoPath);
            $mimeType = File::mimeType($videoPath) ?: 'video/mp4';

            $start = 0;
            $end = $size - 1;
            $length = $size;

            // Handle range requests for video seeking
            if (isset($_SERVER['HTTP_RANGE'])) {
                $c_start = $start;
                $c_end = $end;

                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                if (strpos($range, ',') !== false) {
                    return response('Requested Range Not Satisfiable', 416)
                        ->header('Content-Range', "bytes $start-$end/$size");
                }

                if ($range == '-') {
                    $c_start = $size - substr($range, 1);
                } else {
                    $range = explode('-', $range);
                    $c_start = $range[0];
                    $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
                }

                $c_end = ($c_end > $end) ? $end : $c_end;
                if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                    return response('Requested Range Not Satisfiable', 416)
                        ->header('Content-Range', "bytes $start-$end/$size");
                }

                $start = $c_start;
                $end = $c_end;
                $length = $end - $start + 1;
                fseek($stream, $start);

                return response()->stream(function() use ($stream, $length) {
                    $buffer = 1024 * 8;
                    $read = 0;
                    while (!feof($stream) && $read < $length) {
                        $readSize = min($buffer, $length - $read);
                        echo fread($stream, $readSize);
                        $read += $readSize;
                        flush();
                    }
                    fclose($stream);
                }, 206, [
                    'Content-Type' => $mimeType,
                    'Content-Length' => $length,
                    'Content-Range' => "bytes $start-$end/$size",
                    'Accept-Ranges' => 'bytes',
                ]);
            }

            return response()->stream(function() use ($stream) {
                fpassthru($stream);
                fclose($stream);
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => $size,
                'Accept-Ranges' => 'bytes',
            ]);

        } catch (\Exception $e) {
            abort(500, 'Error streaming video: ' . $e->getMessage());
        }
    }

    public function checkVideo($eventId)
    {
        $event = Event::findOrFail($eventId);
        
        $hasVideo = $event->hasVideo();
        $videoExists = false;
        $videoPath = null;
        
        if ($hasVideo) {
            $videoPath = $event->getVideoPath();
            $videoExists = File::exists($videoPath);
        }
        
        return response()->json([
            'has_video' => $hasVideo,
            'video_exists' => $videoExists,
            'video_url' => $videoExists ? $event->getVideoUrl() : null,
            'video_path' => $videoPath,
            'filename' => $event->VideoFileName,
            'is_video_sent' => $event->IsVideoSent,
        ]);
    }
}