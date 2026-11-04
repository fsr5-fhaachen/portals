<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    /**
     * Set path to the file with students data.
     *
     * @var string
     */
    private const STUDENTS_CSV_PATH = __DIR__.'/students.csv';

    /**
     * Set path to the folder with student images.
     *
     * @var string
     */
    private const STUDENT_IMAGES_PATH = __DIR__.'/images';

    /**
     * Set possible file extensions for the student images.
     *
     * @var array
     */
    private const STUDENT_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    /**
     * Run the tutor seeds.
     */
    public function run(): void
    {
        // check if the file exists
        if (! file_exists(self::STUDENTS_CSV_PATH)) {
            return;
        }

        // get course
        $course = Course::all();

        // map course by abbreviation
        $courseByKey = $course->mapWithKeys(function ($item) {
            return [$item->abbreviation => $item];
        });

        $this->command->info('Student images are loaded from '.self::STUDENT_IMAGES_PATH.'.');
        $this->command->info('Name them like the student in the students.csv in lowercase: firstname_lastname.'.implode(', .', self::STUDENT_IMAGE_EXTENSIONS).' (e.g. max_mustermann.jpg)');

        // images that belong to a student and students without an image
        $usedImagePaths = [];
        $missingImages = [];

        // read the students.csv file
        $students = array_map('str_getcsv', file(self::STUDENTS_CSV_PATH));

        // remove the header row
        array_shift($students);

        // loop through the students
        foreach ($students as $studentRaw) {
            // get the student
            $student = explode(';', $studentRaw[0]);

            // check if student exists
            $user = User::where('email', strtolower($student[3]))->first();
            if ($user && ! $courseByKey[$student[2]]) {
                continue;
            }

            // create a new user
            $user = new User;
            $user->lastname = $student[0];
            $user->firstname = $student[1];
            $user->course_id = $courseByKey[$student[2]]->id;
            $user->email = strtolower($student[3]);

            // check if an image with the name of the user in one of the possible extensions exists
            $imageBaseName = $this->imageBaseName($user->firstname, $user->lastname);
            $imageBasePath = self::STUDENT_IMAGES_PATH.'/'.$imageBaseName;
            $imageExtension = null;
            $imagePath = null;
            foreach (self::STUDENT_IMAGE_EXTENSIONS as $extension) {
                if (file_exists($imageBasePath.'.'.$extension)) {
                    $imageExtension = $extension;
                    $imagePath = $imageBasePath.'.'.$extension;
                    break;
                }
            }

            if ($imagePath) {
                $usedImagePaths[] = $imagePath;

                // generate presigned url
                $uuid = Str::uuid()->toString();
                $path = 'avatars/'.$uuid.'.'.$imageExtension;
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
            } else {
                $missingImages[] = "{$user->firstname} {$user->lastname} (expected {$imageBaseName}.".implode(', .', self::STUDENT_IMAGE_EXTENSIONS).')';
            }

            // save the user
            $user->save();
        }

        $this->reportImages($usedImagePaths, $missingImages);
    }

    /**
     * Get the file name (without extension) of the image of a student.
     */
    private function imageBaseName(string $firstname, string $lastname): string
    {
        return mb_strtolower($firstname).'_'.mb_strtolower($lastname);
    }

    /**
     * Report the students without an image and the images that do not belong to a student, e.g. because of a typo.
     *
     * @param  list<string>  $usedImagePaths
     * @param  list<string>  $missingImages
     */
    private function reportImages(array $usedImagePaths, array $missingImages): void
    {
        foreach ($missingImages as $missingImage) {
            $this->command->warn("No image found for {$missingImage}");
        }

        $imagePaths = glob(self::STUDENT_IMAGES_PATH.'/*') ?: [];
        $unusedImagePaths = array_diff(array_filter($imagePaths, 'is_file'), $usedImagePaths);

        foreach ($unusedImagePaths as $unusedImagePath) {
            $this->command->warn('Image '.basename($unusedImagePath).' does not belong to a student. Check the name (firstname_lastname in lowercase) and the extension ('.implode(', ', self::STUDENT_IMAGE_EXTENSIONS).').');
        }
    }
}
