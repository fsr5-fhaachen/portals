<?php

namespace App\Http\Controllers;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Aws\Credentials\CredentialsInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemAdapter;



class FileS3Controller extends Controller
{
  public function uploadFile(Request $request)
  {

    if ($request->hasFile('file')) {
      $data = $request->file('file');
      $filename = $data->getClientOriginalName();
      $data->storeAs( '', $filename, 's3');
    }

    return view('uploadFile');
  }

  public function showImage(Request $request, $filename)
  {
    $client = Storage::disk('s3')->getClient();
    $bucket = Config::get('filesystems.disks.s3.bucket');

    $command = $client->getCommand('GetObject', [
      'Bucket' => $bucket,
      'Key' => 'downtest.png'  // file name in s3 bucket which you want to access
    ]);

    $request = $client->createPresignedRequest($command, '+200 minutes');

    $url = (string)$request->getUri();

    return view('downloadFile', ['url' => $url]);
  }

}

