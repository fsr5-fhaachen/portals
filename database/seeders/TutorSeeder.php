<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TutorSeeder extends Seeder
{
    /**
     * Set path to the file with tutors data.
     *
     * @var string
     */
    private const TUTORS_CSV_PATH = __DIR__ . '/tutors.csv';

    /**
     * Set path to the folder with tutor images.
     * 
     * @var string
     */
    private const TUTOR_IMAGES_PATH = __DIR__ . '/images';

    /**
     * Set possible file extensions for the tutor images.
     * 
     * @var array
     */
    private const TUTOR_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    /**
     * Run the tutor seeds.
     */
    public function run(): void
    {
        // check if the file exists
        if (! file_exists(self::TUTORS_CSV_PATH)) {
            return;
        }

        // get course
        $course = Course::all();

        // map course by abbreviation
        $courseByKey = $course->mapWithKeys(function ($item) {
            return [$item->abbreviation => $item];
        });

        // read the tutors.csv file
        $tutors = array_map('str_getcsv', file(self::TUTORS_CSV_PATH));

        // remove the header row
        array_shift($tutors);

        // loop through the tutors
        foreach ($tutors as $tutorRaw) {
            // get the tutor
            $tutor = explode(';', $tutorRaw[0]);

            // check if tutor exists
            $user = User::where('email', strtolower($tutor[3]))->first();
            if ($user) {
                continue;
            }

            // create a new user
            echo 'Creating tutor ' . $tutor[0] . ' ' . $tutor[1] . ' (' . $tutor[3] . ')' . PHP_EOL;
            $user = new User;
            $user->lastname = $tutor[0];
            $user->firstname = $tutor[1];
            $user->course_id = $courseByKey[$tutor[2]]->id;
            $user->email = strtolower($tutor[3]);

            //set user to disabled
            if (array_key_exists(5, $tutor) && $tutor[5] == '1') {
                echo 'Setting tutor to disabled for ' . $tutor[0] . ' ' . $tutor[1] . ' (' . $tutor[3] . ')' . PHP_EOL;
                $user->is_disabled = true;
            }

            // check if an image with the name of the user in one of the possible extensions exists
            $imageBasePath = self::TUTOR_IMAGES_PATH . '/' . strtolower($user->firstname) . '_' . strtolower($user->lastname);
            $imageExtension = null;
            $imagePath = null;
            foreach (self::TUTOR_IMAGE_EXTENSIONS as $extension) {
                if (file_exists($imageBasePath . '.' . $extension)) {
                    $imageExtension = $extension;
                    $imagePath = $imageBasePath . '.' . $extension;
                    break;
                }
            }

            if ($imagePath) {
                // generate presigned url
                $uuid = Str::uuid()->toString();
                $path = 'avatars/' . $uuid . '.' . $imageExtension;
                $presignedUrl = Storage::disk('s3')->temporaryUploadUrl(
                    $path,
                    now()->addMinutes(5)
                );

                // extract relevant data from presigned url
                $uploadUrl = $presignedUrl['url'] ?? null;
                $uploadHeaders = $presignedUrl['headers'] ?? [];

                // get mime type of image
                $mime = mime_content_type($imagePath);

                // upload avatar using presigned url
                $req = Http::withBody(file_get_contents($imagePath), $mime);
                if (! empty($uploadHeaders)) {
                    $req = $req->withHeaders($uploadHeaders);
                }
                $response = $req->put($uploadUrl);

                if ($response->successful()) {
                    // save avatar path to user
                    $user->avatar = $path;

                    $this->command->info("Found and uploaded avatar for user {$user->firstname} {$user->lastname}");
                } else {
                    $this->command->warn("Failed to upload avatar for user {$user->firstname} {$user->lastname}");
                }
            }

            // save the user
            $user->save();

            // assigne user role
            echo 'Assigning role tutor to ' . $tutor[0] . ' ' . $tutor[1] . ' (' . $tutor[3] . ')' . PHP_EOL;
            $user->assignRole('tutor');

            // check if the user has additional roles
            if (array_key_exists(4, $tutor)) {
                // split roles by comma
                $roles = explode('|', $tutor[4]);

                // loop through the roles
                foreach ($roles as $role) {
                    // skip empty strings because of ";;" in the csv file
                    if (empty($role)) {
                        continue;
                    }
                    echo 'Assigning role ' . $role . ' to ' . $tutor[0] . ' ' . $tutor[1] . ' (' . $tutor[3] . ')' . PHP_EOL;
                    $user->assignRole($role);
                }
            }
        }
    }
}
