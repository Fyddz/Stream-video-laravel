Route::get('/video/{video_id}', function($video_id){
    
    $video = Video::find($video_id);

    # Verify file exists
    abort_if(
        !Storage::disk('files')->exists($video->file_path),
        404,
        "The file doesn't exist. Check the path."
    );

    $contentType = "video/mp4";

    # FILE_PATH MUST BE A REALLY SAFE PATH OR MAY OCCUR

    $path = '../storage/app/files/'.$video->file_path;
    $fullsize = filesize($path);
    $size = $fullsize;
    $stream = fopen($path, "r");
    $response_code = 200;
    $headers = array("Content-type" => $contentType);

    // Check for request for part of the stream
    $range = Request::header('Range');
    if($range != null) {
        $eqPos = strpos($range, "=");
        $toPos = strpos($range, "-");
        $unit = substr($range, 0, $eqPos);
        $start = intval(substr($range, $eqPos+1, $toPos));
        $success = fseek($stream, $start);
        if($success == 0) {
            $size = $fullsize - $start;
            $response_code = 206;
            $headers["Accept-Ranges"] = $unit;
            $headers["Content-Range"] = $unit . " " . $start . "-" . ($fullsize-1) . "/" . $fullsize;
        }
    }

    $headers["Content-Length"] = $size;

    return Response::stream(function () use ($stream) {
        fpassthru($stream);
    }, $response_code, $headers);
})->name('viewFileVideo');
