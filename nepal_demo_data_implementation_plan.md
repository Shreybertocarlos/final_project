# Nepal-Specific Demo Data Implementation Plan

## Executive Summary

This document outlines the comprehensive plan to populate the job matching platform with realistic Nepal-specific demo data while preserving all existing records. The implementation will add 15-20 companies, 50-80 candidates, 30-50 jobs, and realistic application patterns.

## Current Database State Analysis

### Existing Data (TO BE PRESERVED):
- **Users**: 5 users (IDs: 1, 2, 14, and others)
- **Companies**: 1 company (Google - ID: 1, User: 2)
- **Candidates**: 2 candidates (shrey - ID: 2, User: 1; Smith - ID: 3, User: 14)
- **Jobs**: 3 jobs (Data Analyst, Frontend Developer, AWS DevOps Engineer)
- **Applied Jobs**: 1 application
- **Geographic Data**: Complete Nepal structure (7 provinces, 77 districts)
- **Lookup Tables**: 42 job categories, 806 skills, industry types, organization types

### Data Gaps to Fill:
1. **Companies**: Need 15-20 Nepal-specific companies
2. **Candidates**: Need 50-80 diverse Nepalese candidates  
3. **Jobs**: Need 30-50 realistic job postings
4. **Applications**: Need realistic application patterns

## Phase 2: Nepal-Specific Demo Data Requirements

### 2.1 Companies (Target: 15-20 companies)

#### Real Nepalese Companies to Include:
1. **Technology Sector**:
   - Leapfrog Technology
   - Verisk Nepal
   - Deerwalk Inc
   - F1Soft International
   - Yomari Inc
   - CloudFactory

2. **Banking/Finance**:
   - Nepal Investment Bank Limited (NIBL)
   - Nabil Bank Limited
   - Standard Chartered Bank Nepal
   - Kumari Bank Limited

3. **Telecommunications**:
   - Nepal Telecom
   - Ncell Axiata Limited

4. **Manufacturing/FMCG**:
   - Chaudhary Group (CG Corp Global)
   - Golchha Organization
   - Dabur Nepal

5. **NGO/Development**:
   - Mercy Corps Nepal
   - World Vision Nepal

#### Company Data Structure:
```php
// Each company will include:
- name: Real/realistic Nepalese company names
- industry_type_id: Appropriate industry mapping
- organization_type_id: Private/Public/NGO/Government
- team_size_id: Realistic team sizes
- establishment_date: Realistic dates
- bio/vision: Nepal-focused descriptions
- address: Real Nepal addresses
- city/state/country: Nepal locations (country_id: 1)
- phone: +977 format
- email: Company-specific emails
- website: Realistic websites
```

### 2.2 Candidates (Target: 50-80 candidates)

#### Demographic Distribution:
- **Geographic**: Distributed across major Nepal cities (Kathmandu, Pokhara, Chitwan, Biratnagar, etc.)
- **Experience Levels**: 
  - Fresher: 20-25 candidates
  - 1-3 Years: 20-25 candidates
  - 3-8 Years: 15-20 candidates
  - 8+ Years: 10-15 candidates
- **Skill Categories**:
  - IT/Software: 25-30 candidates
  - Finance/Banking: 10-12 candidates
  - Marketing/Sales: 8-10 candidates
  - Operations/Management: 8-10 candidates
  - Others: 5-8 candidates

#### Nepalese Names Database:
```php
// Male Names
$maleNames = [
    'Rajesh', 'Suresh', 'Ramesh', 'Dinesh', 'Mahesh', 'Naresh', 'Bikash', 'Prakash',
    'Anil', 'Sunil', 'Sanjay', 'Ajay', 'Vijay', 'Binod', 'Manoj', 'Santosh',
    'Deepak', 'Dipesh', 'Roshan', 'Kiran', 'Rajan', 'Sajan', 'Gagan', 'Niran',
    'Arjun', 'Krishna', 'Shyam', 'Ram', 'Hari', 'Gopal', 'Mohan', 'Sohan'
];

// Female Names  
$femaleNames = [
    'Sita', 'Gita', 'Rita', 'Sunita', 'Anita', 'Mamta', 'Kamala', 'Sharmila',
    'Radha', 'Meera', 'Geeta', 'Seema', 'Reema', 'Prema', 'Shanti', 'Laxmi',
    'Saraswati', 'Durga', 'Parvati', 'Kalpana', 'Sapana', 'Rachana', 'Archana',
    'Bina', 'Mina', 'Tina', 'Dina', 'Renu', 'Menu', 'Sanu', 'Manu'
];

// Surnames
$surnames = [
    'Sharma', 'Shrestha', 'Tamang', 'Gurung', 'Magar', 'Rai', 'Limbu', 'Sherpa',
    'Thapa', 'Adhikari', 'Khadka', 'Karki', 'Poudel', 'Acharya', 'Bhattarai',
    'Koirala', 'Dahal', 'Oli', 'Pandey', 'Joshi', 'Regmi', 'Ghimire', 'Basnet'
];
```

#### Candidate Profile Structure:
```php
// Each candidate will include:
- full_name: Realistic Nepalese names
- title: Job-relevant titles
- experience_id: Distributed experience levels
- profession_id: Matching profession categories
- bio: Nepal-context professional summaries
- skills: Relevant skill combinations
- education: Realistic Nepal education backgrounds
- experience_records: Professional experience in Nepal companies
- city/state/country: Nepal locations
- phone: +977 format
- email: Professional email addresses
```

### 2.3 Jobs (Target: 30-50 job postings)

#### Job Distribution by Industry:
1. **Technology (15-20 jobs)**:
   - Software Developer (Laravel, React, Node.js)
   - Data Analyst/Scientist
   - DevOps Engineer
   - UI/UX Designer
   - Mobile App Developer
   - QA Engineer

2. **Banking/Finance (8-10 jobs)**:
   - Credit Officer
   - Branch Manager
   - Financial Analyst
   - Loan Officer
   - Accountant

3. **Marketing/Sales (5-8 jobs)**:
   - Digital Marketing Specialist
   - Sales Executive
   - Content Creator
   - Social Media Manager

4. **Operations/Management (5-8 jobs)**:
   - Project Manager
   - Operations Manager
   - HR Manager
   - Administrative Officer

#### Job Posting Timeline:
- **Recently Posted** (1-7 days ago): 10-15 jobs
- **Active** (1-4 weeks ago): 15-20 jobs  
- **Near Deadline** (deadline in 1-7 days): 8-10 jobs
- **Expired** (past deadline): 5-8 jobs

#### Salary Ranges (NPR):
```php
$salaryRanges = [
    'fresher' => ['min' => 25000, 'max' => 40000],
    'junior' => ['min' => 40000, 'max' => 70000],
    'mid' => ['min' => 70000, 'max' => 120000],
    'senior' => ['min' => 120000, 'max' => 200000],
    'lead' => ['min' => 200000, 'max' => 350000]
];
```

### 2.4 Job Applications Pattern

#### Application Distribution:
- **Popular Jobs** (15+ applications): 5-8 jobs
- **Moderate Interest** (8-15 applications): 10-15 jobs
- **Low Interest** (3-8 applications): 10-15 jobs
- **New Postings** (0-3 applications): 5-10 jobs

#### Application Logic:
- Match candidate skills with job requirements
- Consider experience level compatibility
- Geographic preferences (local candidates more likely to apply)
- Industry experience alignment

## Phase 3: Implementation Strategy

### 3.1 Seeder Creation Plan

#### Step 1: Create Base Seeders
```bash
php artisan make:seeder NepalDemoCompanySeeder
php artisan make:seeder NepalDemoCandidateSeeder  
php artisan make:seeder NepalDemoJobSeeder
php artisan make:seeder NepalDemoApplicationSeeder
```

#### Step 2: Seeder Dependencies
1. **NepalDemoCompanySeeder**: Creates users + companies
2. **NepalDemoCandidateSeeder**: Creates users + candidates + profiles
3. **NepalDemoJobSeeder**: Creates jobs (depends on companies)
4. **NepalDemoApplicationSeeder**: Creates applications (depends on jobs + candidates)

#### Step 3: Data Integrity Measures
- Use database transactions
- Check for existing data before insertion
- Maintain foreign key relationships
- Use realistic timestamps
- Implement proper error handling

### 3.2 Backup Strategy

#### Pre-Implementation Backup:
```bash
# Create database backup
php artisan db:backup --filename=pre_demo_data_backup.sql

# Export current data
mysqldump -u username -p database_name > current_data_backup.sql
```

#### Rollback Plan:
```bash
# If issues occur, restore from backup
mysql -u username -p database_name < current_data_backup.sql
```

### 3.3 Implementation Sequence

#### Phase 3A: Company Data (Day 1)
1. Create NepalDemoCompanySeeder
2. Generate 15-20 company users
3. Create company profiles with Nepal-specific data
4. Test data integrity

#### Phase 3B: Candidate Data (Day 2)
1. Create NepalDemoCandidateSeeder
2. Generate 50-80 candidate users
3. Create candidate profiles with skills/education
4. Test profile completeness

#### Phase 3C: Job Data (Day 3)
1. Create NepalDemoJobSeeder
2. Generate 30-50 job postings
3. Assign realistic deadlines and salaries
4. Test job-company relationships

#### Phase 3D: Application Data (Day 4)
1. Create NepalDemoApplicationSeeder
2. Generate realistic application patterns
3. Test candidate-job matching logic
4. Verify application timestamps

#### Phase 3E: Testing & Validation (Day 5)
1. Comprehensive data validation
2. UI testing with new data
3. Performance testing with larger dataset
4. BM25 ranking preparation testing

### 3.4 Quality Assurance

#### Data Validation Checklist:
- [ ] All foreign keys properly linked
- [ ] No duplicate email addresses
- [ ] Realistic salary ranges in NPR
- [ ] Proper Nepal geographic distribution
- [ ] Appropriate skill-job matching
- [ ] Realistic application patterns
- [ ] Proper timestamp sequences
- [ ] Complete profile information

#### Performance Considerations:
- Use batch inserts for large datasets
- Implement progress bars for long-running seeders
- Optimize database queries
- Monitor memory usage during seeding

### 3.5 Seeder Integration

#### Update DatabaseSeeder.php:
```php
public function run(): void
{
    // Existing seeders
    $this->call([
        JobExperienceSeeder::class,
        // ... other existing seeders
    ]);
    
    // New demo data seeders
    if (app()->environment(['local', 'staging'])) {
        $this->call([
            NepalDemoCompanySeeder::class,
            NepalDemoCandidateSeeder::class,
            NepalDemoJobSeeder::class,
            NepalDemoApplicationSeeder::class,
        ]);
    }
}
```

## Expected Outcomes

### Post-Implementation Database State:
- **Users**: 5 (existing) + 65-100 (new) = 70-105 total
- **Companies**: 1 (existing) + 15-20 (new) = 16-21 total
- **Candidates**: 2 (existing) + 50-80 (new) = 52-82 total
- **Jobs**: 3 (existing) + 30-50 (new) = 33-53 total
- **Applied Jobs**: 1 (existing) + 200-400 (new) = 201-401 total

### Demo Presentation Benefits:
1. **Realistic Data**: Professional Nepal-specific content
2. **Diverse Scenarios**: Various industries, experience levels, locations
3. **BM25 Testing**: Sufficient data for ranking algorithm demonstration
4. **User Experience**: Rich, meaningful demo interactions
5. **Scalability Testing**: Performance with realistic data volumes

---

**Implementation Timeline**: 5 days
**Risk Level**: Low (preserves existing data)
**Rollback Time**: < 30 minutes
**Testing Required**: Comprehensive validation before production use
