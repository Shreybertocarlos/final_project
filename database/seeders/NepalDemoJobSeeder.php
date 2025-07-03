<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\Company;
use App\Models\JobSkills;
use App\Models\JobTag;
use App\Models\JobBenefits;
use App\Models\Benefits;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NepalDemoJobSeeder extends Seeder
{
    private $jobTemplates = [
        [
            'title' => 'Laravel Developer',
            'category_id' => 20, // Information Technology
            'role_id' => 22, // Software Engineer
            'experience_id' => 12, // 2 Year
            'education_id' => 3, // Bachelor Degree
            'type_id' => 3, // Full-time
            'salary_type_id' => 2, // Monthly
            'min_salary' => 60000,
            'max_salary' => 100000,
            'skills' => [1, 2, 3, 12, 20], // Communication, Critical Thinking, Problem Solving, Project Management, Data Analysis
            'description' => '<h3>Job Description</h3><p>We are looking for a skilled Laravel Developer to join our development team. The ideal candidate will have experience in building web applications using Laravel framework and modern web technologies.</p><h3>Key Responsibilities</h3><ul><li>Develop and maintain web applications using Laravel framework</li><li>Write clean, maintainable, and efficient code</li><li>Collaborate with cross-functional teams to define and implement new features</li><li>Participate in code reviews and maintain coding standards</li><li>Troubleshoot and debug applications</li></ul><h3>Requirements</h3><ul><li>2+ years of experience with Laravel framework</li><li>Strong knowledge of PHP, MySQL, HTML, CSS, and JavaScript</li><li>Experience with Git version control</li><li>Understanding of RESTful APIs</li><li>Good communication skills in English and Nepali</li></ul>',
        ],
        [
            'title' => 'React Frontend Developer',
            'category_id' => 20, // Information Technology
            'role_id' => 23, // Frontend Developer
            'experience_id' => 11, // 1 Year
            'education_id' => 3, // Bachelor Degree
            'type_id' => 3, // Full-time
            'salary_type_id' => 2, // Monthly
            'min_salary' => 50000,
            'max_salary' => 85000,
            'skills' => [1, 2, 4, 12, 17], // Communication, Critical Thinking, Creativity, Project Management, Presentation Skills
            'description' => '<h3>Job Description</h3><p>Join our frontend team as a React Developer and help us build amazing user interfaces for our web applications. We are looking for someone passionate about creating exceptional user experiences.</p><h3>Key Responsibilities</h3><ul><li>Develop user interfaces using React.js and modern JavaScript</li><li>Implement responsive designs and ensure cross-browser compatibility</li><li>Collaborate with designers and backend developers</li><li>Optimize applications for maximum speed and scalability</li><li>Write unit tests and maintain code quality</li></ul><h3>Requirements</h3><ul><li>1+ years of experience with React.js</li><li>Strong knowledge of JavaScript, HTML5, and CSS3</li><li>Experience with state management libraries (Redux, Context API)</li><li>Familiarity with modern build tools (Webpack, Babel)</li><li>Understanding of responsive design principles</li></ul>',
        ],
        [
            'title' => 'Data Scientist',
            'category_id' => 11, // Data Science/Analytics
            'role_id' => 3, // Data Scientist
            'experience_id' => 13, // 3+ Year
            'education_id' => 3, // Bachelor Degree (changed from Master as ID 2 is intermediate)
            'type_id' => 3, // Full-time
            'salary_type_id' => 2, // Monthly
            'min_salary' => 80000,
            'max_salary' => 140000,
            'skills' => [1, 2, 11, 19, 20], // Communication, Critical Thinking, Analytical Skills, Research, Data Analysis
            'description' => '<h3>Job Description</h3><p>We are seeking a talented Data Scientist to join our analytics team. You will work with large datasets to extract insights and build predictive models that drive business decisions.</p><h3>Key Responsibilities</h3><ul><li>Analyze complex datasets to identify trends and patterns</li><li>Build and deploy machine learning models</li><li>Create data visualizations and reports for stakeholders</li><li>Collaborate with business teams to understand requirements</li><li>Implement data pipelines and automation processes</li></ul><h3>Requirements</h3><ul><li>3+ years of experience in data science or analytics</li><li>Strong knowledge of Python, R, or similar languages</li><li>Experience with machine learning libraries (scikit-learn, TensorFlow, PyTorch)</li><li>Proficiency in SQL and database management</li><li>Master\'s degree in Computer Science, Statistics, or related field</li></ul>',
        ],
        [
            'title' => 'Digital Marketing Manager',
            'category_id' => 2, // Advertising/Marketing
            'role_id' => 4, // Marketing Manager
            'experience_id' => 14, // 5+ Year
            'education_id' => 3, // Bachelor Degree
            'type_id' => 3, // Full-time
            'salary_type_id' => 2, // Monthly
            'min_salary' => 70000,
            'max_salary' => 120000,
            'skills' => [1, 4, 12, 16, 17], // Communication, Creativity, Project Management, Networking, Presentation Skills
            'description' => '<h3>Job Description</h3><p>Lead our digital marketing efforts and drive online growth for our brand. We are looking for an experienced Digital Marketing Manager who can develop and execute comprehensive digital marketing strategies.</p><h3>Key Responsibilities</h3><ul><li>Develop and implement digital marketing strategies</li><li>Manage social media campaigns and content creation</li><li>Analyze campaign performance and optimize for ROI</li><li>Lead SEO/SEM initiatives and website optimization</li><li>Collaborate with design and content teams</li></ul><h3>Requirements</h3><ul><li>5+ years of experience in digital marketing</li><li>Proven track record of successful campaigns</li><li>Experience with Google Analytics, AdWords, and social media platforms</li><li>Strong analytical and project management skills</li><li>Bachelor\'s degree in Marketing, Business, or related field</li></ul>',
        ],
        [
            'title' => 'DevOps Engineer',
            'category_id' => 9, // Cloud Computing/Infrastructure
            'role_id' => 24, // DevOps Engineer
            'experience_id' => 13, // 3+ Year
            'education_id' => 3, // Bachelor Degree
            'type_id' => 3, // Full-time
            'salary_type_id' => 2, // Monthly
            'min_salary' => 90000,
            'max_salary' => 150000,
            'skills' => [1, 2, 3, 12, 18], // Communication, Critical Thinking, Problem Solving, Project Management, Strategic Planning
            'description' => '<h3>Job Description</h3><p>Join our infrastructure team as a DevOps Engineer and help us build scalable, reliable systems. You will work on automating deployment processes and maintaining our cloud infrastructure.</p><h3>Key Responsibilities</h3><ul><li>Design and implement CI/CD pipelines</li><li>Manage cloud infrastructure on AWS/Azure</li><li>Automate deployment and monitoring processes</li><li>Ensure system security and performance optimization</li><li>Collaborate with development teams on infrastructure needs</li></ul><h3>Requirements</h3><ul><li>3+ years of experience in DevOps or system administration</li><li>Strong knowledge of Docker, Kubernetes, and containerization</li><li>Experience with cloud platforms (AWS, Azure, GCP)</li><li>Proficiency in scripting languages (Python, Bash)</li><li>Understanding of infrastructure as code (Terraform, Ansible)</li></ul>',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all demo companies (excluding the existing Google company)
        $companies = Company::where('id', '>', 1)->get();

        if ($companies->isEmpty()) {
            $this->command->error('No demo companies found. Please run NepalDemoCompanySeeder first.');
            return;
        }

        $jobCount = 0;
        $targetJobs = 40;

        // Create jobs for each company
        foreach ($companies as $company) {
            $jobsPerCompany = rand(2, 5); // Each company gets 2-5 jobs

            for ($i = 0; $i < $jobsPerCompany && $jobCount < $targetJobs; $i++) {
                $this->createJob($company, $jobCount);
                $jobCount++;
            }
        }

        $this->command->info("Created {$jobCount} demo jobs successfully!");
    }

    private function createJob($company, $index)
    {
        // Select job template
        $template = $this->jobTemplates[array_rand($this->jobTemplates)];

        // Modify title to make it unique
        $title = $template['title'];
        if ($index > 0 && rand(0, 1)) {
            $modifiers = ['Senior', 'Junior', 'Lead', 'Associate'];
            $title = $modifiers[array_rand($modifiers)] . ' ' . $title;
        }

        // Set deadline based on job age
        $deadline = $this->getJobDeadline($index);
        $createdAt = $deadline->copy()->subDays(rand(7, 45));

        // Adjust salary based on company and experience
        $salaryMultiplier = $this->getSalaryMultiplier($company->name);
        $minSalary = (int)($template['min_salary'] * $salaryMultiplier);
        $maxSalary = (int)($template['max_salary'] * $salaryMultiplier);

        // Create job
        $job = Job::create([
            'company_id' => $company->id,
            'job_category_id' => $template['category_id'],
            'job_role_id' => $template['role_id'],
            'job_experience_id' => $template['experience_id'],
            'education_id' => $template['education_id'],
            'job_type_id' => $template['type_id'],
            'salary_type_id' => $template['salary_type_id'],
            'title' => $title,
            'slug' => Str::slug($title . '-' . $company->name . '-' . rand(100, 999)),
            'vacancies' => rand(1, 5),
            'min_salary' => $minSalary,
            'max_salary' => $maxSalary,
            'salary_mode' => 'range',
            'deadline' => $deadline,
            'description' => $template['description'],
            'status' => $deadline->isPast() ? 'expired' : 'active',
            'apply_on' => 'app',
            'featured' => rand(0, 1) ? 1 : 0,
            'highlight' => rand(0, 1) ? 1 : 0,
            'city_id' => $company->city,
            'state_id' => $company->state,
            'country_id' => $company->country,
            'address' => $company->address,
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(rand(1, 5)),
        ]);

        // Add skills
        foreach ($template['skills'] as $skillId) {
            JobSkills::create([
                'job_id' => $job->id,
                'skill_id' => $skillId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add benefits
        $this->addJobBenefits($job);

        // Add tags
        $this->addJobTags($job, $template['title']);

        $this->command->info("Created job: {$title} at {$company->name}");
    }

    private function getJobDeadline($index)
    {
        // Distribute deadlines to create realistic scenarios
        if ($index < 8) {
            // Recently posted (deadline in 2-4 weeks)
            return now()->addDays(rand(14, 28));
        } elseif ($index < 20) {
            // Active jobs (deadline in 1-2 weeks)
            return now()->addDays(rand(7, 14));
        } elseif ($index < 28) {
            // Near deadline (deadline in 1-7 days)
            return now()->addDays(rand(1, 7));
        } else {
            // Expired jobs (deadline 1-30 days ago)
            return now()->subDays(rand(1, 30));
        }
    }

    private function getSalaryMultiplier($companyName)
    {
        // Adjust salary based on company type
        $premiumCompanies = ['Verisk Nepal', 'F1Soft International', 'Leapfrog Technology'];
        $bankingCompanies = ['Nepal Investment Bank Limited', 'Nabil Bank Limited'];

        if (in_array($companyName, $premiumCompanies)) {
            return 1.2; // 20% higher
        } elseif (in_array($companyName, $bankingCompanies)) {
            return 1.1; // 10% higher
        }

        return 1.0; // Standard salary
    }

    private function addJobBenefits($job)
    {
        $benefits = [
            'Health Insurance', 'Paid Time Off', 'Professional Development',
            'Flexible Working Hours', 'Remote Work Options', 'Performance Bonus',
            'Festival Bonus', 'Transportation Allowance', 'Meal Allowance'
        ];

        $selectedBenefits = array_rand(array_flip($benefits), rand(3, 6));

        foreach ($selectedBenefits as $benefitName) {
            // Create benefit if not exists
            $benefit = Benefits::firstOrCreate([
                'company_id' => $job->company_id,
                'name' => $benefitName,
            ]);

            // Link to job
            JobBenefits::create([
                'job_id' => $job->id,
                'benefit_id' => $benefit->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function addJobTags($job, $jobTitle)
    {
        $tagMap = [
            'Laravel Developer' => [1, 2, 3], // Assuming tag IDs for PHP, Laravel, Web Development
            'React Frontend Developer' => [4, 5, 6], // JavaScript, React, Frontend
            'Data Scientist' => [7, 8, 9], // Python, Machine Learning, Analytics
            'Digital Marketing Manager' => [10, 11, 12], // Marketing, Digital, Social Media
            'DevOps Engineer' => [13, 14, 15], // DevOps, Cloud, Infrastructure
        ];

        $tags = $tagMap[$jobTitle] ?? [1, 2, 3]; // Default tags

        foreach ($tags as $tagId) {
            JobTag::create([
                'job_id' => $job->id,
                'tag_id' => $tagId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
