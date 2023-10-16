<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class FileS3Controller extends Controller
{
  public function uploadFile(Request $request)
  {

    if ($request->hasFile('file')) {
      $data = $request->file('file');
      $filename = $data->getClientOriginalName();
      $data->storeAs( '/Bilder', $filename, 's3');
    }

    return view('uploadFile');
  }

  public function downloadFile($filename)
  {
      $path =  $filename;
      $url = Storage::disk('s3')->get($path);
      return view('downloadFile', ['url' => $url]);


  }

}

