<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Candidate;
use App\Models\CandidateSkill;
use App\Models\CandidateEducation;
use App\Models\CandidateExperience;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NepalDemoCandidateSeeder extends Seeder
{
    private $maleNames = [
        'Rajesh', 'Suresh', 'Ramesh', 'Dinesh', 'Mahesh', 'Naresh', 'Bikash', 'Prakash',
        'Anil', 'Sunil', 'Sanjay', 'Ajay', 'Vijay', 'Binod', 'Manoj', 'Santosh',
        'Deepak', 'Dipesh', 'Roshan', 'Kiran', 'Rajan', 'Sajan', 'Gagan', 'Niran',
        'Arjun', 'Krishna', 'Shyam', 'Ram', 'Hari', 'Gopal', 'Mohan', 'Sohan'
    ];

    private $femaleNames = [
        'Sita', 'Gita', 'Rita', 'Sunita', 'Anita', 'Mamta', 'Kamala', 'Sharmila',
        'Radha', 'Meera', 'Geeta', 'Seema', 'Reema', 'Prema', 'Shanti', 'Laxmi',
        'Saraswati', 'Durga', 'Parvati', 'Kalpana', 'Sapana', 'Rachana', 'Archana',
        'Bina', 'Mina', 'Tina', 'Dina', 'Renu', 'Menu', 'Sanu', 'Manu'
    ];

    private $surnames = [
        'Sharma', 'Shrestha', 'Tamang', 'Gurung', 'Magar', 'Rai', 'Limbu', 'Sherpa',
        'Thapa', 'Adhikari', 'Khadka', 'Karki', 'Poudel', 'Acharya', 'Bhattarai',
        'Koirala', 'Dahal', 'Oli', 'Pandey', 'Joshi', 'Regmi', 'Ghimire', 'Basnet'
    ];

    private $jobTitles = [
        'Software Developer', 'Web Developer', 'Mobile App Developer', 'Data Analyst',
        'UI/UX Designer', 'DevOps Engineer', 'QA Engineer', 'Business Analyst',
        'Project Manager', 'Digital Marketing Specialist', 'Content Creator',
        'Graphic Designer', 'Network Administrator', 'Database Administrator',
        'Financial Analyst', 'Accountant', 'HR Specialist', 'Sales Executive',
        'Marketing Manager', 'Operations Manager'
    ];

    private $majorCities = [
        ['id' => 27, 'name' => 'Kathmandu', 'state' => 3],
        ['id' => 29, 'name' => 'Lalitpur', 'state' => 3],
        ['id' => 23, 'name' => 'Bhaktapur', 'state' => 3],
        ['id' => 38, 'name' => 'Kaski', 'state' => 4], // Pokhara
        ['id' => 24, 'name' => 'Chitwan', 'state' => 3],
        ['id' => 6, 'name' => 'Morang', 'state' => 1], // Biratnagar
        ['id' => 58, 'name' => 'Rupandehi', 'state' => 5], // Butwal
        ['id' => 48, 'name' => 'Banke', 'state' => 5], // Nepalgunj
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $candidateCount = 60; // Target: 60 candidates

        for ($i = 0; $i < $candidateCount; $i++) {
            $this->createCandidate($i);
        }

        $this->command->info('Nepal demo candidates seeded successfully!');
    }

    private function createCandidate($index)
    {
        // Generate random name
        $gender = rand(0, 1) ? 'male' : 'female';
        $firstName = $gender === 'male'
            ? $this->maleNames[array_rand($this->maleNames)]
            : $this->femaleNames[array_rand($this->femaleNames)];
        $lastName = $this->surnames[array_rand($this->surnames)];
        $fullName = $firstName . ' ' . $lastName;

        // Generate email
        $email = strtolower($firstName . '.' . $lastName . rand(1, 999) . '@gmail.com');

        // Create user
        $user = User::create([
            'name' => $fullName,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'candidate',
            'email_verified_at' => now(),
        ]);

        // Role is already set in the user creation above

        // Select random city
        $city = $this->majorCities[array_rand($this->majorCities)];

        // Select experience level
        $experienceLevel = $this->getExperienceLevel($index);

        // Select profession and title
        $title = $this->jobTitles[array_rand($this->jobTitles)];
        $professionId = $this->getProfessionId($title);

        // Create candidate profile
        $candidate = Candidate::create([
            'user_id' => $user->id,
            'experience_id' => $experienceLevel['id'],
            'profession_id' => $professionId,
            'title' => $title,
            'image' => '/default-uploads/avatar.png',
            'full_name' => $fullName,
            'slug' => Str::slug($fullName . '-' . rand(100, 999)),
            'gender' => $gender,
            'phone_one' => '+977-98' . rand(10000000, 99999999),
            'email' => $email,
            'bio' => $this->generateBio($title, $experienceLevel['name']),
            'city' => $city['id'],
            'state' => $city['state'],
            'country' => 1, // Nepal
            'address' => $city['name'] . ', Nepal',
            'profile_complete' => 1, // Make candidates visible on frontend
            'visibility' => 1, // Make candidates visible on frontend
            'created_at' => now()->subDays(rand(1, 180)),
            'updated_at' => now()->subDays(rand(1, 30)),
        ]);

        // Add skills
        $this->addCandidateSkills($candidate, $title);

        // Add education
        $this->addCandidateEducation($candidate);

        // Add experience if not fresher
        if ($experienceLevel['id'] > 10) { // Not fresher
            $this->addCandidateExperience($candidate, $experienceLevel, $title);
        }

        $this->command->info("Created candidate: {$fullName} - {$title}");
    }

    private function getExperienceLevel($index)
    {
        // Distribute experience levels using correct Experience table IDs
        if ($index < 15) return ['id' => 1, 'name' => 'Fresher']; // Fresher
        if ($index < 30) return ['id' => 2, 'name' => '1 Year']; // 1 Year
        if ($index < 42) return ['id' => 3, 'name' => '2 Year']; // 2 Year
        if ($index < 50) return ['id' => 4, 'name' => '3+ Year']; // 3+ Year
        if ($index < 56) return ['id' => 5, 'name' => '5+ Year']; // 5+ Year
        return ['id' => 6, 'name' => '8+ Year']; // 8+ Year
    }

    private function getProfessionId($title)
    {
        $professionMap = [
            'Software Developer' => 2,
            'Web Developer' => 2,
            'Mobile App Developer' => 2,
            'Data Analyst' => 4,
            'UI/UX Designer' => 6,
            'DevOps Engineer' => 2,
            'QA Engineer' => 2,
            'Business Analyst' => 4,
            'Project Manager' => 9,
            'Digital Marketing Specialist' => 5,
            'Content Creator' => 8,
            'Graphic Designer' => 10,
            'Network Administrator' => 7,
            'Database Administrator' => 11,
            'Financial Analyst' => 4,
            'Accountant' => 4,
            'HR Specialist' => 9,
            'Sales Executive' => 5,
            'Marketing Manager' => 5,
            'Operations Manager' => 9,
        ];

        return $professionMap[$title] ?? 2; // Default to Software Developer
    }

    private function generateBio($title, $experience)
    {
        $bios = [
            "Experienced {$title} with {$experience} of experience in the field. Passionate about technology and innovation, with a strong background in delivering high-quality solutions. Based in Nepal and looking for opportunities to contribute to meaningful projects.",
            "Dedicated {$title} with {$experience} of professional experience. Skilled in modern technologies and methodologies, with a proven track record of successful project delivery. Committed to continuous learning and professional growth.",
            "Results-driven {$title} with {$experience} of experience in the industry. Strong problem-solving skills and ability to work effectively in team environments. Passionate about leveraging technology to solve real-world problems.",
        ];

        return $bios[array_rand($bios)];
    }

    private function addCandidateSkills($candidate, $title)
    {
        $skillSets = [
            'Software Developer' => [1, 2, 3, 12, 20, 50, 51, 52], // Communication, Critical Thinking, Problem Solving, Project Management, Data Analysis, Programming skills
            'Web Developer' => [1, 2, 3, 12, 20, 53, 54, 55],
            'Mobile App Developer' => [1, 2, 3, 12, 20, 56, 57, 58],
            'Data Analyst' => [1, 2, 3, 11, 19, 20, 59, 60],
            'UI/UX Designer' => [1, 4, 12, 17, 61, 62, 63],
            'DevOps Engineer' => [1, 2, 3, 12, 20, 64, 65, 66],
            'QA Engineer' => [1, 2, 3, 11, 12, 67, 68, 69],
            'Digital Marketing Specialist' => [1, 4, 12, 16, 17, 70, 71, 72],
            'Project Manager' => [1, 6, 12, 13, 14, 18, 73, 74],
        ];

        $skills = $skillSets[$title] ?? [1, 2, 3, 12]; // Default skills

        foreach ($skills as $skillId) {
            CandidateSkill::create([
                'candidate_id' => $candidate->id,
                'skill_id' => $skillId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function addCandidateEducation($candidate)
    {
        $educations = [
            ['degree' => 'Bachelor in Computer Science', 'institution' => 'Tribhuvan University'],
            ['degree' => 'Bachelor in Information Technology', 'institution' => 'Kathmandu University'],
            ['degree' => 'Bachelor in Business Administration', 'institution' => 'Pokhara University'],
            ['degree' => 'Master in Computer Applications', 'institution' => 'Tribhuvan University'],
            ['degree' => 'Bachelor in Engineering', 'institution' => 'Pulchowk Campus'],
        ];

        $education = $educations[array_rand($educations)];

        CandidateEducation::create([
            'candidate_id' => $candidate->id,
            'level' => 'Bachelor',
            'degree' => $education['degree'],
            'year' => rand(2015, 2023),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addCandidateExperience($candidate, $experienceLevel, $title)
    {
        $companies = [
            'Tech Solutions Nepal', 'Digital Innovation Pvt. Ltd.', 'Nepal Software Company',
            'Himalayan Tech', 'Everest Solutions', 'Kathmandu IT Services',
            'Pokhara Technologies', 'Nepal Digital Agency', 'Mountain View Tech'
        ];

        $company = $companies[array_rand($companies)];
        $years = (int) filter_var($experienceLevel['name'], FILTER_SANITIZE_NUMBER_INT) ?: 1;

        CandidateExperience::create([
            'candidate_id' => $candidate->id,
            'company' => $company,
            'department' => 'Technology',
            'designation' => $title,
            'start' => now()->subYears($years + 1)->format('Y-m-d'),
            'end' => now()->subMonths(rand(1, 12))->format('Y-m-d'),
            'responsibilities' => "Worked as {$title} at {$company}, responsible for developing and maintaining software solutions, collaborating with cross-functional teams, and delivering high-quality projects on time.",
            'currently_working' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
