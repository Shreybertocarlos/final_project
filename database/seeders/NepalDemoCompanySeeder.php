<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NepalDemoCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Leapfrog Technology',
                'industry_type_id' => 11, // Technology
                'organization_type_id' => 11, // Private
                'team_size_id' => 6, // 50-100 Members
                'establishment_date' => '2010-03-15',
                'website' => 'https://www.lftechnology.com',
                'phone' => '+977-1-4444004',
                'bio' => '<p>Leapfrog Technology is a leading software development company in Nepal, specializing in custom software development, mobile applications, and digital transformation solutions. We serve clients globally while maintaining our roots in Nepal.</p><p>Our team of experienced developers and designers work on cutting-edge technologies including React, Node.js, Python, and cloud platforms. We are committed to delivering high-quality solutions that drive business growth.</p>',
                'vision' => 'To be the leading technology partner for businesses seeking digital transformation and innovation.',
                'address' => 'Dillibazar, Kathmandu',
                'city' => 27, // Kathmandu
                'state' => 3, // Bagmati Province
                'country' => 1, // Nepal
            ],
            [
                'name' => 'Verisk Nepal',
                'industry_type_id' => 11, // Technology
                'organization_type_id' => 11, // Private
                'team_size_id' => 7, // 100-200 Members
                'establishment_date' => '2012-08-20',
                'website' => 'https://www.verisk.com',
                'phone' => '+977-1-4445678',
                'bio' => '<p>Verisk Nepal is the Nepal office of Verisk Analytics, a leading data analytics provider serving customers in insurance, energy, and specialized markets. We leverage advanced analytics and technology to help our clients make better decisions.</p><p>Our Nepal team contributes to global projects in data science, software engineering, and analytics, working with cutting-edge technologies and methodologies.</p>',
                'vision' => 'Empowering better decisions through data analytics and technology innovation.',
                'address' => 'Pulchowk, Lalitpur',
                'city' => 29, // Lalitpur
                'state' => 3, // Bagmati Province
                'country' => 1, // Nepal
            ],
            [
                'name' => 'F1Soft International',
                'industry_type_id' => 11, // Technology
                'organization_type_id' => 11, // Private
                'team_size_id' => 8, // 200-300 Members
                'establishment_date' => '2004-01-10',
                'website' => 'https://www.f1soft.com',
                'phone' => '+977-1-4423456',
                'bio' => '<p>F1Soft International is a leading fintech company in Nepal, providing innovative digital payment solutions, mobile banking, and financial technology services. We are the creators of eSewa, Nepal\'s most popular digital wallet.</p><p>Our solutions serve millions of users across Nepal, enabling digital financial inclusion and transforming how people manage their finances.</p>',
                'vision' => 'To digitize Nepal\'s financial ecosystem and promote financial inclusion through innovative technology.',
                'address' => 'Baluwatar, Kathmandu',
                'city' => 27, // Kathmandu
                'state' => 3, // Bagmati Province
                'country' => 1, // Nepal
            ],
            [
                'name' => 'Nepal Investment Bank Limited',
                'industry_type_id' => 13, // Financial Services
                'organization_type_id' => 11, // Private
                'team_size_id' => 10, // 500+ Members
                'establishment_date' => '1986-02-27',
                'website' => 'https://www.nibl.com.np',
                'phone' => '+977-1-4228229',
                'bio' => '<p>Nepal Investment Bank Limited (NIBL) is one of the leading commercial banks in Nepal, providing comprehensive banking and financial services to individuals, businesses, and institutions across the country.</p><p>With a strong network of branches and ATMs, NIBL offers modern banking solutions including digital banking, corporate finance, trade finance, and investment services.</p>',
                'vision' => 'To be the most preferred bank in Nepal, delivering exceptional value to all stakeholders.',
                'address' => 'Durbar Marg, Kathmandu',
                'city' => 27, // Kathmandu
                'state' => 3, // Bagmati Province
                'country' => 1, // Nepal
            ],
            [
                'name' => 'Nabil Bank Limited',
                'industry_type_id' => 13, // Financial Services
                'organization_type_id' => 11, // Private
                'team_size_id' => 10, // 500+ Members
                'establishment_date' => '1984-07-16',
                'website' => 'https://www.nabilbank.com',
                'phone' => '+977-1-4015555',
                'bio' => '<p>Nabil Bank Limited is the first foreign joint venture bank in Nepal, established in partnership with Dubai Bank Limited. We provide a full range of commercial banking services with a focus on innovation and customer satisfaction.</p><p>Our services include retail banking, corporate banking, trade finance, remittance, and digital banking solutions.</p>',
                'vision' => 'To be the bank of choice for all Nepalis, providing world-class banking services.',
                'address' => 'Kamaladi, Kathmandu',
                'city' => 27, // Kathmandu
                'state' => 3, // Bagmati Province
                'country' => 1, // Nepal
            ],
            [
                'name' => 'Nepal Telecom',
                'industry_type_id' => 16, // Telecommunications
                'organization_type_id' => 8, // Government
                'team_size_id' => 10, // 500+ Members
                'establishment_date' => '1975-04-13',
                'website' => 'https://www.ntc.net.np',
                'phone' => '+977-1-4101010',
                'bio' => '<p>Nepal Telecom is the largest telecommunications service provider in Nepal, offering a comprehensive range of telecom services including mobile, fixed-line, internet, and data services across the country.</p><p>As the national telecom operator, we are committed to connecting Nepal and bridging the digital divide through innovative telecommunications solutions.</p>',
                'vision' => 'To be the leading telecommunications service provider in Nepal, connecting every corner of the nation.',
                'address' => 'Bhadrakali, Kathmandu',
                'city' => 27, // Kathmandu
                'state' => 3, // Bagmati Province
                'country' => 1, // Nepal
            ],
            [
                'name' => 'Chaudhary Group',
                'industry_type_id' => 10, // Manufacturing
                'organization_type_id' => 11, // Private
                'team_size_id' => 10, // 500+ Members
                'establishment_date' => '1960-01-01',
                'website' => 'https://www.chaudharygroup.com',
                'phone' => '+977-1-4270001',
                'bio' => '<p>Chaudhary Group (CG Corp Global) is one of Nepal\'s largest and most diversified business conglomerates, with operations spanning FMCG, consumer electronics, real estate, hospitality, and financial services.</p><p>With a presence across South Asia and beyond, CG Corp Global is committed to improving lives through innovative products and services.</p>',
                'vision' => 'To be a leading multinational corporation improving lives through innovative products and services.',
                'address' => 'Teku, Kathmandu',
                'city' => 27, // Kathmandu
                'state' => 3, // Bagmati Province
                'country' => 1, // Nepal
            ],
            [
                'name' => 'Mercy Corps Nepal',
                'industry_type_id' => 23, // Education
                'organization_type_id' => 14, // INGGO
                'team_size_id' => 5, // 20-50 Members
                'establishment_date' => '2006-09-15',
                'website' => 'https://www.mercycorps.org.np',
                'phone' => '+977-1-4004848',
                'bio' => '<p>Mercy Corps Nepal works to alleviate suffering, poverty, and oppression by helping people build secure, productive, and just communities. We implement programs in disaster risk reduction, economic development, and youth empowerment.</p><p>Our work focuses on building resilient communities and creating opportunities for sustainable development across Nepal.</p>',
                'vision' => 'A world where all people have the opportunity to build better lives.',
                'address' => 'Kupondole, Lalitpur',
                'city' => 29, // Lalitpur
                'state' => 3, // Bagmati Province
                'country' => 1, // Nepal
            ],
            [
                'name' => 'Deerwalk Inc',
                'industry_type_id' => 11, // Technology
                'organization_type_id' => 11, // Private
                'team_size_id' => 6, // 50-100 Members
                'establishment_date' => '2010-05-20',
                'website' => 'https://www.deerwalk.com',
                'phone' => '+977-1-4004000',
                'bio' => '<p>Deerwalk Inc is a healthcare technology company that provides data analytics and population health management solutions. We help healthcare organizations improve patient outcomes while reducing costs through innovative technology.</p><p>Our Nepal office contributes to global healthcare technology solutions, working with advanced analytics and machine learning.</p>',
                'vision' => 'To transform healthcare through innovative data analytics and technology solutions.',
                'address' => 'Sifal, Kathmandu',
                'city' => 27, // Kathmandu
                'state' => 3, // Bagmati Province
                'country' => 1, // Nepal
            ],
            [
                'name' => 'CloudFactory',
                'industry_type_id' => 11, // Technology
                'organization_type_id' => 11, // Private
                'team_size_id' => 7, // 100-200 Members
                'establishment_date' => '2010-11-01',
                'website' => 'https://www.cloudfactory.com',
                'phone' => '+977-1-4004567',
                'bio' => '<p>CloudFactory is a distributed workforce platform that provides on-demand access to a managed workforce for data entry, content moderation, and other digital tasks. We combine human intelligence with technology to deliver scalable solutions.</p><p>Our Nepal operations serve as a key hub for our global distributed workforce, providing opportunities for thousands of workers across the country.</p>',
                'vision' => 'To create economic opportunities for people everywhere through distributed work.',
                'address' => 'Lazimpat, Kathmandu',
                'city' => 27, // Kathmandu
                'state' => 3, // Bagmati Province
                'country' => 1, // Nepal
            ],
        ];

        foreach ($companies as $index => $companyData) {
            // Generate email
            $email = strtolower(str_replace([' ', '.'], ['', ''], $companyData['name'])) . '@company.com';

            // Check if user already exists
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                $this->command->info("Skipping existing company: {$companyData['name']}");
                continue;
            }

            // Create user for company
            $user = User::create([
                'name' => $companyData['name'],
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'company',
                'email_verified_at' => now(),
            ]);

            // Role is already set in the user creation above

            // Create company profile
            Company::create([
                'user_id' => $user->id,
                'name' => $companyData['name'],
                'slug' => Str::slug($companyData['name']),
                'industry_type_id' => $companyData['industry_type_id'],
                'organization_type_id' => $companyData['organization_type_id'],
                'team_size_id' => $companyData['team_size_id'],
                'logo' => '/default-uploads/avatar.png',
                'banner' => '/default-uploads/avatar.png',
                'establishment_date' => $companyData['establishment_date'],
                'website' => $companyData['website'],
                'phone' => $companyData['phone'],
                'email' => $user->email,
                'bio' => $companyData['bio'],
                'vision' => $companyData['vision'],
                'address' => $companyData['address'],
                'city' => $companyData['city'],
                'state' => $companyData['state'],
                'country' => $companyData['country'],
                'is_profile_verified' => true,
                'profile_completion' => 1,
                'visibility' => 1,
                'created_at' => now()->subDays(rand(30, 365)),
                'updated_at' => now()->subDays(rand(1, 30)),
            ]);

            $this->command->info("Created company: {$companyData['name']}");
        }

        $this->command->info('Nepal demo companies seeded successfully!');
    }
}
