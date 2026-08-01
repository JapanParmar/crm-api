<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Project;
use App\Models\SiteVisit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CrmDataSeeder extends Seeder
{
    private array $names = [
        'Rahul Sharma', 'Priya Patel', 'Amit Kumar', 'Sunita Verma', 'Vikash Singh',
        'Anjali Gupta', 'Rohit Mehta', 'Deepa Nair', 'Suresh Reddy', 'Kavita Joshi',
        'Manoj Tiwari', 'Rekha Bansal', 'Ajay Mishra', 'Pooja Agarwal', 'Sanjay Yadav',
        'Neha Chauhan', 'Rajesh Pandey', 'Meera Pillai', 'Vikas Khanna', 'Anita Dubey',
        'Sachin Kulkarni', 'Shreya Das', 'Kiran Rao', 'Arun Iyer', 'Swati Jain',
        'Nitin Bose', 'Divya Menon', 'Harish Chandra', 'Lalitha Ramesh', 'Sushil Thakur',
    ];

    private array $sources = [
        'magicbricks', '99acres', 'housing', 'meta_ads', 'google_ads',
        'website', 'whatsapp', 'referral', 'walk_in', 'instagram',
    ];

    private array $statuses = [
        'new', 'contacted', 'qualified', 'site_visit', 'negotiation',
        'closed_won', 'closed_lost', 'on_hold',
    ];

    private array $priorities = ['low', 'medium', 'high', 'urgent'];

    private array $propertyTypes = ['apartment', 'villa', 'plot', 'commercial', 'penthouse'];

    private array $budgets = [
        [3000000, 5000000],
        [5000000, 8000000],
        [8000000, 12000000],
        [12000000, 20000000],
        [20000000, 50000000],
        [50000000, 100000000],
    ];

    private array $followUpTypes = ['call', 'whatsapp', 'email', 'meeting'];

    private array $siteVisitFeedbacks = [
        'Very interested in 3BHK corner unit. Concerned about parking allocation.',
        'Liked the amenities but found it too expensive. Suggested 2BHK options.',
        'Lead is comparing with another project. Will decide in 2 weeks.',
        'Highly interested. Asked for payment plan details and RERA number.',
        'Location suits them but wants an earlier possession date.',
        'Happy with the project. Wants to bring family for a second visit.',
        'Not interested. Budget constraints.',
    ];

    private array $followUpNotes = [
        'Discuss budget and payment plans.',
        'Send project brochure and floor plans.',
        'Follow up on site visit feedback.',
        'Share RERA details and possession timeline.',
        'Discuss financing options.',
        'Re-engage after previous missed call.',
        'Initial contact — introduce project portfolio.',
    ];

    private int $seed = 54321;

    private function rand(): float
    {
        $x = sin($this->seed++) * 10000;
        return $x - floor($x);
    }

    private function pick(array $arr): mixed
    {
        if (empty($arr)) {
            return null;
        }
        return $arr[(int) ($this->rand() * count($arr))];
    }

    private function randInt(int $min, int $max): int
    {
        return $min + (int) ($this->rand() * ($max - $min + 1));
    }

    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        // ── 1. Seed Employees & Users from Excel data ───────────────────────
        $employeesFilePath = database_path('seeders/data/employees.json');
        if (!file_exists($employeesFilePath)) {
            $this->command->error('Employees data file not found: ' . $employeesFilePath);
            return;
        }

        $employeesJson = json_decode(file_get_contents($employeesFilePath), true);
        $employees = [];
        $usedEmails = [];
        $usedCodes = [];

        foreach ($employeesJson as $data) {
            $fullName = trim($data['Employee Name'] ?? '');
            if (empty($fullName)) {
                continue;
            }

            // Split name into first and last name
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Clean email
            $email = trim($data['O. Email'] ?? '');
            if (empty($email)) {
                $email = trim($data['P. Email'] ?? '');
            }
            if (empty($email)) {
                $email = strtolower(str_replace(' ', '.', $fullName)) . '@brickroots.com';
            }

            // Clean phone
            $phone = trim($data['O. Mobile Number'] ?? '');
            if (empty($phone)) {
                $phone = trim($data['P. Mobile Number'] ?? '');
            }
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (empty($phone)) {
                $phone = '9' . $this->randInt(100000000, 999999999);
            }

            $isActive = (trim($data['Status'] ?? '') === 'Working');

            // Handle duplicate emails in source data
            $baseEmail = $email;
            $counter = 1;
            while (in_array(strtolower($email), $usedEmails)) {
                $emailParts = explode('@', $baseEmail);
                $email = $emailParts[0] . '+' . $counter . '@' . ($emailParts[1] ?? 'brickroots.com');
                $counter++;
            }
            $usedEmails[] = strtolower($email);

            // Handle duplicate employee codes in source data
            $employeeCode = trim($data['Employee ID'] ?? '');
            if (empty($employeeCode)) {
                $employeeCode = 'EMP-' . $this->randInt(1000, 9999);
            }
            $baseCode = $employeeCode;
            $codeCounter = 1;
            while (in_array(strtolower($employeeCode), $usedCodes)) {
                $employeeCode = $baseCode . '-' . $codeCounter;
                $codeCounter++;
            }
            $usedCodes[] = strtolower($employeeCode);

            // Create or update corresponding User
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'      => $fullName,
                    'password'  => Hash::make('password'),
                    'phone'     => $phone,
                    'is_active' => $isActive,
                ]
            );
            $user->syncRoles(['employee']);
            $employees[] = $user;

            // Normalize and Map Department
            $dept = trim($data['Department'] ?? 'Sales');
            $deptLower = strtolower($dept);
            if (str_contains($deptLower, 'sale')) {
                $dept = 'Sales';
            } elseif (str_contains($deptLower, 'market')) {
                $dept = 'Marketing';
            } elseif (str_contains($deptLower, 'hr') || str_contains($deptLower, 'human')) {
                $dept = 'HR';
            } elseif (str_contains($deptLower, 'it') || str_contains($deptLower, 'tech')) {
                $dept = 'IT';
            } elseif (str_contains($deptLower, 'financ') || str_contains($deptLower, 'account')) {
                $dept = 'Finance';
            } elseif (str_contains($deptLower, 'operation') || str_contains($deptLower, 'admin')) {
                $dept = 'Operations';
            } elseif (str_contains($deptLower, 'construct') || str_contains($deptLower, 'architect')) {
                $dept = 'Construction';
            } elseif (str_contains($deptLower, 'legal')) {
                $dept = 'Legal';
            } else {
                $dept = 'Sales';
            }

            // Map status
            $status = $isActive ? 'active' : 'terminated';

            // Clean joining date
            $joiningDate = $data['Joining date'] ?? null;
            if ($joiningDate) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $joiningDate)) {
                    $joiningDate = now()->toDateString();
                }
            } else {
                $joiningDate = now()->toDateString();
            }

            // Create or update Employee
            Employee::updateOrCreate(
                ['employee_code' => $employeeCode],
                [
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'department' => $dept,
                    'designation' => trim($data['Designation'] ?? 'Sales Executive'),
                    'employment_type' => 'full_time',
                    'status' => $status,
                    'joining_date' => $joiningDate,
                    'salary' => $this->randInt(25000, 150000),
                    'notes' => trim($data['Remarks'] ?? ''),

                    // Excel Columns
                    'sr_no' => trim($data['Sr. No.'] ?? ''),
                    'dob' => trim($data['DOB'] ?? ''),
                    'gender' => trim($data['Gender'] ?? ''),
                    'personal_phone' => trim($data['P. Mobile Number'] ?? ''),
                    'office_phone' => trim($data['O. Mobile Number'] ?? ''),
                    'personal_email' => trim($data['P. Email'] ?? ''),
                    'office_email' => trim($data['O. Email'] ?? ''),
                    'manager' => trim($data['Manager'] ?? ''),
                    'device_assigned' => trim($data['Device Assigned'] ?? ''),
                    'laptop_model' => trim($data['Laptop Model'] ?? ''),
                    'laptop_serial_number' => trim($data['Laptop Serial Number'] ?? ''),
                    'mobile_model' => trim($data['Mobile Model'] ?? ''),
                    'mobile_serial_number' => trim($data['Mobile Serial Number'] ?? ''),
                    'location' => trim($data['Location'] ?? ''),
                ]
            );
        }

        $activeEmployees = array_filter($employees, fn($e) => $e->is_active);
        $activeEmployees = array_values($activeEmployees);
        if (empty($activeEmployees)) {
            $activeEmployees = $employees;
        }

        // ── 2. Seed Projects from Excel data ───────────────────────────────
        $projectsFilePath = database_path('seeders/data/projects.json');
        if (!file_exists($projectsFilePath)) {
            $this->command->error('Projects data file not found: ' . $projectsFilePath);
            return;
        }

        $projectsJson = json_decode(file_get_contents($projectsFilePath), true);
        $seededProjectNames = [];

        foreach ($projectsJson as $pData) {
            $name = trim($pData['Project Name'] ?? '');
            $code = trim($pData['Project ID'] ?? '');
            if (empty($name) || empty($code)) {
                continue;
            }

            // Deduplicate projects by name to prevent multiple configuration entries as separate projects
            if (in_array(strtolower($name), $seededProjectNames)) {
                continue;
            }
            $seededProjectNames[] = strtolower($name);

            // Map Excel Status -> model type: residential, commercial, mixed_use
            $statusStr = strtolower(trim($pData['Project Status'] ?? ''));
            $type = 'residential';
            if (str_contains($statusStr, 'commercial')) {
                $type = 'commercial';
            } elseif (str_contains($statusStr, 'both') || str_contains($statusStr, 'mixed') || str_contains($statusStr, 'luxury')) {
                $type = 'mixed_use';
            }

            // Map Possession string -> model status: planning, active, under_construction, completed, on_hold
            $possessionStr = strtolower(trim($pData['Passession'] ?? ''));
            $status = 'active';
            if (str_contains($possessionStr, 'ready') || str_contains($possessionStr, 'move') || str_contains($possessionStr, 'completed') || str_contains($possessionStr, 'sold out')) {
                $status = 'completed';
            } elseif (str_contains($possessionStr, 'dec') || str_contains($possessionStr, '202') || str_contains($possessionStr, 'launch') || str_contains($possessionStr, 'possesion')) {
                $status = 'under_construction';
            }

            Project::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'status' => $status,
                    'location' => trim($pData['Location'] ?? ''),
                    'city' => trim($pData['City'] ?? 'Ahmedabad'),
                    'state' => trim($pData['State'] ?? 'Gujarat'),
                    'developer' => 'Brickroots Network',
                    'rera_number' => trim($pData['RERA Number'] ?? ''),
                    'budget' => 0.0,
                    'total_units' => 100,
                    'available_units' => 50,
                    'sold_units' => 50,
                    'price_min' => 0.0,
                    'price_max' => 0.0,
                    'description' => trim($pData['Remarks'] ?? ''),
                    'google_map_url' => trim($pData['Google Map Link'] ?? ''),
                    'created_by' => $admin?->id,

                    // Excel Columns
                    'sr_no' => trim($pData['Sr. No.'] ?? ''),
                    'project_type' => trim($pData['Project Type'] ?? ''),
                    'project_status' => trim($pData['Project Status'] ?? ''),
                    'passession' => trim($pData['Passession'] ?? ''),
                    'price' => trim($pData['Price'] ?? ''),
                    'size_sqft' => trim($pData['Size Sq. ft.'] ?? ''),
                    'contact_person' => trim($pData['Contact person'] ?? ''),
                    'contact_number' => trim($pData['Contact Number'] ?? ''),
                    'brochure_link' => trim($pData['Brochure Link'] ?? ''),
                    'remarks' => trim($pData['Remarks'] ?? ''),
                ]
            );
        }

        // Fetch dynamic lists for lead creation
        $dbProjects = Project::all();
        if ($dbProjects->isEmpty()) {
            $this->command->error('No projects seeded.');
            return;
        }

        $dbProjectNames = $dbProjects->pluck('name')->toArray();
        $dbProjectLocations = $dbProjects->pluck('location')->toArray();

        // ── 3. Seed Leads ──────────────────────────────────────────────────
        $leads = [];
        for ($i = 0; $i < 120; $i++) {
            $name     = $this->pick($this->names);
            $budgetPair = $this->pick($this->budgets);
            $status   = $this->pick($this->statuses);
            $emp      = $this->pick($activeEmployees);
            $daysAgo  = $this->randInt(1, 90);
            $createdAt = now()->subDays($daysAgo)->subHours($this->randInt(0, 23));

            $lastNum = Lead::withTrashed()->max('id') ?? 0;
            $leadNum = 'LID-' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);

            $lead = Lead::create([
                'lead_number'       => $leadNum,
                'name'              => $name,
                'phone'             => '9' . $this->randInt(100000000, 999999999),
                'email'             => strtolower(str_replace(' ', '.', $name)) . '@gmail.com',
                'source'            => $this->pick($this->sources),
                'status'            => $status,
                'priority'          => $this->pick($this->priorities),
                'property_type'     => $this->pick($this->propertyTypes),
                'budget_min'        => $budgetPair[0],
                'budget_max'        => $budgetPair[1],
                'preferred_location'=> $this->pick($dbProjectLocations),
                'project_interest'  => $this->pick($dbProjectNames),
                'bhk_preference'    => $this->pick(['1BHK', '2BHK', '3BHK', '4BHK', 'Villa']),
                'score'             => $this->randInt(10, 95),
                'assigned_to'       => $this->rand() > 0.1 ? $emp->id : null,
                'assigned_at'       => $this->rand() > 0.1 ? $createdAt->addHours(2) : null,
                'last_contacted_at' => $this->rand() > 0.3 ? now()->subDays($this->randInt(0, 14)) : null,
                'is_duplicate'      => false,
                'created_by'        => $admin?->id,
                'created_at'        => $createdAt,
                'updated_at'        => $createdAt->addDays($this->randInt(0, 5)),
            ]);

            ActivityLog::create([
                'lead_id'      => $lead->id,
                'performed_by' => $admin?->id,
                'type'         => 'lead_created',
                'description'  => "Lead {$lead->lead_number} created from {$lead->source}.",
                'metadata'     => ['source' => $lead->source],
                'created_at'   => $createdAt,
            ]);

            $leads[] = $lead;
        }

        // ── 4. Seed Follow-ups ─────────────────────────────────────────────
        foreach ($leads as $lead) {
            $count = $this->randInt(0, 5);
            $leadFollowUpCount = 0;
            for ($j = 0; $j < $count; $j++) {
                $emp = $activeEmployees[$lead->assigned_to
                    ? (array_search($lead->assigned_to, array_column($activeEmployees, 'id')) ?: 0)
                    : $this->randInt(0, count($activeEmployees) - 1)
                ];
                $isPast        = $this->rand() > 0.35;
                $scheduledAt   = $isPast
                    ? now()->subDays($this->randInt(1, 30))->subHours($this->randInt(0, 23))
                    : now()->addDays($this->randInt(0, 7))->addHours($this->randInt(0, 23));
                $fuStatus      = $isPast
                    ? $this->pick(['completed', 'missed', 'completed', 'completed'])
                    : 'scheduled';

                $fu = FollowUp::create([
                    'lead_id'      => $lead->id,
                    'assigned_to'  => $lead->assigned_to ?? $emp->id,
                    'type'         => $this->pick($this->followUpTypes),
                    'status'       => $fuStatus,
                    'scheduled_at' => $scheduledAt,
                    'completed_at' => $fuStatus === 'completed' ? $scheduledAt->addMinutes(30) : null,
                    'notes'        => $this->pick($this->followUpNotes),
                    'outcome'      => $fuStatus === 'completed' ? 'Lead responded positively. Scheduled next contact.' : null,
                    'created_by'   => $lead->assigned_to ?? $emp->id,
                    'created_at'   => $scheduledAt->subDay(),
                ]);

                $leadFollowUpCount++;

                ActivityLog::create([
                    'lead_id'      => $lead->id,
                    'performed_by' => $lead->assigned_to,
                    'type'         => $fuStatus === 'completed' ? 'follow_up_completed' : 'follow_up_scheduled',
                    'description'  => $fuStatus === 'completed'
                        ? "Follow-up ({$fu->type}) completed."
                        : "Follow-up ({$fu->type}) scheduled.",
                    'metadata'     => ['follow_up_id' => $fu->id],
                    'created_at'   => $scheduledAt,
                ]);
            }

            if ($leadFollowUpCount > 0) {
                $lead->update(['follow_up_count' => $leadFollowUpCount]);
            }
        }

        // ── 5. Seed Site Visits ────────────────────────────────────────────
        $visitsLeads = array_filter($leads, fn($l) => in_array($l->status, ['site_visit', 'negotiation', 'closed_won', 'closed_lost']));
        foreach ($visitsLeads as $lead) {
            $count = $this->randInt(1, 2);
            $leadVisitCount = 0;
            for ($j = 0; $j < $count; $j++) {
                $emp       = $activeEmployees[array_search($lead->assigned_to, array_column($activeEmployees, 'id')) ?: 0];
                $isPast    = $this->rand() > 0.3;
                $visitDate = $isPast
                    ? now()->subDays($this->randInt(1, 20))
                    : now()->addDays($this->randInt(1, 5));
                $vstStatus  = $isPast ? $this->pick(['completed', 'completed', 'no_show']) : 'scheduled';
                $interested = $vstStatus === 'completed' ? ($this->rand() > 0.4 ? true : false) : null;

                $visit = SiteVisit::create([
                    'lead_id'      => $lead->id,
                    'attended_by'  => $lead->assigned_to ?? $emp->id,
                    'project_name' => $lead->project_interest ?? $this->pick($dbProjectNames),
                    'location'     => $lead->preferred_location ?? $this->pick($dbProjectLocations),
                    'status'       => $vstStatus,
                    'scheduled_at' => $visitDate,
                    'completed_at' => $vstStatus === 'completed' ? $visitDate->addHours(2) : null,
                    'notes'        => 'Site visit arranged.',
                    'feedback'     => $vstStatus === 'completed' ? $this->pick($this->siteVisitFeedbacks) : null,
                    'interested'   => $interested,
                    'created_by'   => $lead->assigned_to ?? $emp->id,
                    'created_at'   => $visitDate->subDays(2),
                ]);

                $leadVisitCount++;

                ActivityLog::create([
                    'lead_id'      => $lead->id,
                    'performed_by' => $lead->assigned_to,
                    'type'         => $vstStatus === 'completed' ? 'site_visit_completed' : 'site_visit_scheduled',
                    'description'  => $vstStatus === 'completed'
                        ? "Site visit at {$visit->project_name} completed."
                        : "Site visit at {$visit->project_name} scheduled.",
                    'metadata'     => ['site_visit_id' => $visit->id, 'interested' => $interested],
                    'created_at'   => $visitDate,
                ]);
            }

            if ($leadVisitCount > 0) {
                $lead->update(['site_visit_count' => $leadVisitCount]);
            }
        }

        // ── 6. Seed Employee HR Records (Attendances, Leaves, Payrolls) ────
        $allEmployees = Employee::all();
        foreach ($allEmployees as $emp) {
            // Seed Attendances for past 5 working days
            for ($i = 0; $i < 5; $i++) {
                $attDate = \Carbon\Carbon::now()->subDays($i)->format('Y-m-d');
                \App\Models\Attendance::firstOrCreate(
                    ['employee_id' => $emp->id, 'date' => $attDate],
                    [
                        'clock_in' => '09:30:00',
                        'clock_out' => '18:30:00',
                        'work_hours' => 9.0,
                        'status' => $i === 1 ? 'late' : 'present',
                        'notes' => 'Regular work day',
                    ]
                );
            }

            // Seed Leave applications
            \App\Models\Leave::firstOrCreate(
                ['employee_id' => $emp->id, 'reason' => 'Annual family vacation'],
                [
                    'leave_type' => 'casual',
                    'start_date' => \Carbon\Carbon::now()->addDays(5)->format('Y-m-d'),
                    'end_date' => \Carbon\Carbon::now()->addDays(7)->format('Y-m-d'),
                    'days_count' => 3,
                    'status' => 'pending',
                ]
            );

            // Seed Payrolls
            \App\Models\Payroll::firstOrCreate(
                ['employee_id' => $emp->id, 'month' => 7, 'year' => 2026],
                [
                    'basic_salary' => $emp->salary,
                    'hra' => round($emp->salary * 0.20, 2),
                    'allowances' => round($emp->salary * 0.10, 2),
                    'deductions' => round($emp->salary * 0.05, 2),
                    'net_salary' => round($emp->salary * 1.25, 2),
                    'status' => 'paid',
                    'payment_date' => \Carbon\Carbon::now()->format('Y-m-d'),
                    'payment_method' => 'Bank Transfer',
                ]
            );
        }

        // ── 7. Link Leads to Projects ──────────────────────────────────────
        $createdProjects = Project::all();
        if ($createdProjects->count() > 0) {
            $leadsList = Lead::all();
            foreach ($leadsList as $index => $ld) {
                $proj = $createdProjects[$index % $createdProjects->count()];
                $ld->update([
                    'project_id' => $proj->id,
                    'project_interest' => $proj->name,
                ]);

                \App\Models\SiteVisit::where('lead_id', $ld->id)->update([
                    'project_id' => $proj->id,
                ]);
            }
        }
    }
}
