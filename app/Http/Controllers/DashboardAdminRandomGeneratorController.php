<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request as Req;

class DashboardAdminRandomGeneratorController extends Controller
{
    /**
     * Display the dashboard admin random generator page
     */
    public function index(): Response
    {
        // get all users except tutors and admins
        $users = User::doesntHave('roles')->orderBy('firstname')->get();

        // get all courses
        $courses = Course::all();

        return Inertia::render('Dashboard/Admin/RandomGenerator/Index', [
            'users' => $users,
            'courses' => $courses,
        ]);
    }

    /**
     * Execute the dashboard admin random generator submit action
     */
    public function indexExecuteSubmit(): JsonResponse
    {
        // validate the request
        $request = Request::validate([
            'state' => ['required', 'string', 'in:setup,idle,running,stopped'],
            'user_id' => ['integer', 'exists:users,id'],
        ]);

        // get the user
        $user = null;
        if ($request['state'] == 'stopped') {
            $user = User::select('id', 'firstname', 'lastname')->where('id', $request['user_id'])->get();

            // check if no user was found
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        // check if a state exists or we need to create a new one
        $state = State::where('key', 'randomGenerator')->first();
        if (! $state) {
            $state = new State();
            $state->key = 'randomGenerator';
        }

        // update the state
        $state->value = json_encode([
            'state' => $request['state'],
            'user' => $user,
        ]);

        // save the state
        $state->save();

        // return success
        return response()->json([
            'success' => true,
        ]);
    }
  //TODO: function must be implemented. See issue #224
  public function showImage(Req $request, $filename)
  {
    $client = Storage::disk('s3')->getClient();
    $bucket = Config::get('filesystems.disks.s3.bucket');

    $command = $client->getCommand('GetObject', [
      'Bucket' => $bucket,
      'Key' => $request->post('uuid') //read uuid from Server depends on Vue implementation
    ]);
    //Time is Link expiry, but link doesnt seem to expiry not sure why
    $request = $client->createPresignedRequest($command, '+20 minutes');

    $url = (string)$request->getUri();

    return view('downloadFile', ['url' => $url]);
  }
}
