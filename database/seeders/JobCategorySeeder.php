<?php

namespace Database\Seeders;

use App\Models\JobCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        

        $top_job_categories = array(
            "Accounting/Finance",
            "Advertising/Marketing",
            "Aerospace/Defense",
            "Artificial Intelligence/Machine Learning",
            "Automotive Technology",
            "Banking/Financial Services",
            "Biotechnology/Bioinformatics",
            "Blockchain/Cryptocurrency",
            "Cloud Computing/Infrastructure",
            "Cybersecurity",
            "Data Science/Analytics",
            "Digital Media/Content Creation",
            "E-commerce/Online Retail",
            "EdTech/Online Education",
            "Energy/Smart Grid Technology",
            "Entertainment/Gaming",
            "Financial Technology (FinTech)",
            "Government Technology/GovTech",
            "Healthcare Technology/HealthTech",
            "Information Technology",
            "Insurance Technology (InsurTech)",
            "Internet of Things (IoT)",
            "Legal Technology (LegalTech)",
            "Logistics/Supply Chain Technology",
            "Manufacturing/Industry 4.0",
            "Mobile App Development",
            "Network Infrastructure",
            "Product Management (Tech)",
            "Real Estate Technology (PropTech)",
            "Research & Development (Tech)",
            "Robotics/Automation",
            "Software Development",
            "Smart Cities/Urban Technology",
            "Social Media/Social Networking",
            "Streaming/Digital Entertainment",
            "Telecommunications",
            "Transportation Technology",
            "Travel Technology/TravelTech",
            "Video Games/Interactive Media",
            "Virtual/Augmented Reality",
            "Web Development/Design",
            "Other Technology"
        );

        foreach ($top_job_categories as $item) {
            $create = new JobCategory();
            $create->icon = 'fas fa-dot-circle';
            $create->name = $item;
            $create->save();
        }
    }
}
