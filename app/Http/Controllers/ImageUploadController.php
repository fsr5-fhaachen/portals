<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class ImageUploadController extends Controller
{

  public function imageUpload(){
    return view('imageUpload');
  }

  public function imageUploadPost(Request $request) {
    $request->validate([
      'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    if ($request->hasFile('image')) {
      $imageName = time() . '.' . $request->image->getClientOriginalExtension();
      $path = $request->file('image')->storeAs('images', $imageName, 's3');
      $url = Storage::disk('s3')->url($path);

      return back()
        ->with('success', 'Image uploaded successfully.')
        ->with('image', $url);
    }

    return back()
      ->with('error', 'Failed to upload image.');
  }


}
