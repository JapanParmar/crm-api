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
    // ── Realistic Indian real-estate data pools ────────────────────────────
    private array $names = [
        'Rahul Sharma', 'Priya Patel', 'Amit Kumar', 'Sunita Verma', 'Vikash Singh',
        'Anjali Gupta', 'Rohit Mehta', 'Deepa Nair', 'Suresh Reddy', 'Kavita Joshi',
        'Manoj Tiwari', 'Rekha Bansal', 'Ajay Mishra', 'Pooja Agarwal', 'Sanjay Yadav',
        'Neha Chauhan', 'Rajesh Pandey', 'Meera Pillai', 'Vikas Khanna', 'Anita Dubey',
        'Sachin Kulkarni', 'Shreya Das', 'Kiran Rao', 'Arun Iyer', 'Swati Jain',
        'Nitin Bose', 'Divya Menon', 'Harish Chandra', 'Lalitha Ramesh', 'Sushil Thakur',
    ];

    private array $projects = [
        'Prestige Skyline', 'Brigade Utopia', 'Sobha Dream Acres', 'DLF Camellias',
        'Godrej Reserve', 'Purva Atmosphere', 'Mantri Espana', 'Total Environment',
        'Embassy Springs', 'RMZ Galleria', 'Salarpuria Sattva', 'Adarsh Palm Retreat',
    ];

    private array $locations = [
        'Whitefield, Bangalore', 'HSR Layout, Bangalore', 'Koramangala, Bangalore',
        'Electronic City, Bangalore', 'Sarjapur Road, Bangalore', 'Hebbal, Bangalore',
        'Yelahanka, Bangalore', 'Bannerghatta Road, Bangalore', 'Devanahalli, Bangalore',
        'Marathahalli, Bangalore', 'Bellandur, Bangalore', 'JP Nagar, Bangalore',
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
        return $arr[(int) ($this->rand() * count($arr))];
    }

    private function randInt(int $min, int $max): int
    {
        return $min + (int) ($this->rand() * ($max - $min + 1));
    }

    public function run(): void
    {
        // ── Create Employees ───────────────────────────────────────────────
        $employeeData = [
            ['name' => 'Arjun Rathore',  'email' => 'arjun.rathore@propcrm.in',  'phone' => '9876543210'],
            ['name' => 'Sneha Kapoor',   'email' => 'sneha.kapoor@propcrm.in',   'phone' => '9123456789'],
            ['name' => 'Dev Malhotra',   'email' => 'dev.malhotra@propcrm.in',   'phone' => '9345678901'],
            ['name' => 'Priti Saxena',   'email' => 'priti.saxena@propcrm.in',   'phone' => '9234567890'],
            ['name' => 'Ravi Shankar',   'email' => 'ravi.shankar@propcrm.in',   'phone' => '9456789012', 'is_active' => false],
        ];

        $employees = [];
        foreach ($employeeData as $data) {
            $emp = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'      => $data['name'],
                    'password'  => Hash::make('password'),
                    'phone'     => $data['phone'],
                    'is_active' => $data['is_active'] ?? true,
                ]
            );
            $emp->syncRoles(['employee']);
            $employees[] = $emp;
        }

        $activeEmployees = array_filter($employees, fn($e) => $e->is_active);
        $activeEmployees = array_values($activeEmployees);
        $admin           = User::where('email', 'admin@example.com')->first();

        // ── Seed Leads ─────────────────────────────────────────────────────
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
                'preferred_location'=> $this->pick($this->locations),
                'project_interest'  => $this->pick($this->projects),
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

            // Activity log: lead_created
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

        // ── Seed Follow-ups ───────────────────────────────────────────────
        $followUpStatuses = ['scheduled', 'completed', 'missed', 'cancelled'];
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

                // Activity log for follow-up
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

        // ── Seed Site Visits ───────────────────────────────────────────────
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
                    'project_name' => $lead->project_interest ?? $this->pick($this->projects),
                    'location'     => $lead->preferred_location ?? $this->pick($this->locations),
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

        // ── Seed Projects ──────────────────────────────────────────────────
        $projectSeeds = [
            [
                'name' => 'Prestige Skyline',
                'code' => 'PRJ-SKYL',
                'type' => 'residential',
                'status' => 'active',
                'location' => 'Whitefield',
                'city' => 'Bangalore',
                'developer' => 'Prestige Group',
                'budget' => 450000000.00,
                'total_units' => 250,
                'available_units' => 45,
                'sold_units' => 205,
                'price_min' => 7500000.00,
                'price_max' => 15000000.00,
                'launch_date' => '2024-01-15',
                'possession_date' => '2026-12-31',
                'description' => 'Luxury 2 & 3 BHK high-rise apartments with world-class amenities.',
                'amenities' => ['Clubhouse', 'Swimming Pool', 'Gym', '24/7 Security', 'Tennis Court', 'EV Charging'],
            ],
            [
                'name' => 'Brigade Utopia',
                'code' => 'PRJ-UTOP',
                'type' => 'residential',
                'status' => 'under_construction',
                'location' => 'Varthur',
                'city' => 'Bangalore',
                'developer' => 'Brigade Group',
                'budget' => 600000000.00,
                'total_units' => 400,
                'available_units' => 120,
                'sold_units' => 280,
                'price_min' => 5500000.00,
                'price_max' => 12000000.00,
                'launch_date' => '2024-06-01',
                'possession_date' => '2027-06-30',
                'description' => 'Integrated smart township featuring serene landscapes and modern architecture.',
                'amenities' => ['Shopping Complex', 'Sports Arena', 'Park', 'Co-working Space'],
            ],
            [
                'name' => 'Sobha Dream Acres',
                'code' => 'PRJ-SOBH',
                'type' => 'residential',
                'status' => 'completed',
                'location' => 'Panathur',
                'city' => 'Bangalore',
                'developer' => 'Sobha Developers',
                'budget' => 350000000.00,
                'total_units' => 300,
                'available_units' => 15,
                'sold_units' => 285,
                'price_min' => 6500000.00,
                'price_max' => 11000000.00,
                'launch_date' => '2022-03-10',
                'possession_date' => '2025-05-01',
                'description' => 'Premium ready-to-move-in residential community with German precast technology.',
                'amenities' => ['Squash Court', 'Clubhouse', 'Amphitheatre', 'Pet Park'],
            ],
            [
                'name' => 'DLF Camellias',
                'code' => 'PRJ-DLFC',
                'type' => 'commercial',
                'status' => 'active',
                'location' => 'Golf Course Road',
                'city' => 'Gurgaon',
                'developer' => 'DLF Limited',
                'budget' => 1200000000.00,
                'total_units' => 150,
                'available_units' => 30,
                'sold_units' => 120,
                'price_min' => 25000000.00,
                'price_max' => 85000000.00,
                'launch_date' => '2023-09-20',
                'possession_date' => '2026-09-01',
                'description' => 'Ultra-luxury commercial towers and executive suites overlooking the golf course.',
                'amenities' => ['Helipad', 'Concierge Service', 'Cigar Lounge', 'Infinity Pool'],
            ],
            [
                'name' => 'Godrej Reserve',
                'code' => 'PRJ-GODR',
                'type' => 'mixed_use',
                'status' => 'planning',
                'location' => 'Devanahalli',
                'city' => 'Bangalore',
                'developer' => 'Godrej Properties',
                'budget' => 800000000.00,
                'total_units' => 500,
                'available_units' => 500,
                'sold_units' => 0,
                'price_min' => 4000000.00,
                'price_max' => 9000000.00,
                'launch_date' => '2026-10-01',
                'possession_date' => '2029-12-31',
                'description' => 'Future-forward eco-friendly plotted development near International Airport.',
                'amenities' => ['Organic Farm', 'Solar Lighting', 'Forest Trails', 'Clubhouse'],
            ],
        ];

        foreach ($projectSeeds as $pData) {
            Project::firstOrCreate(
                ['code' => $pData['code']],
                array_merge($pData, ['created_by' => $admin?->id])
            );
        }

        // ── Seed HR Employees ──────────────────────────────────────────────
        $empHRSeeds = [
            [
                'employee_code' => 'EMP-101',
                'first_name' => 'Arjun',
                'last_name' => 'Rathore',
                'email' => 'arjun.rathore@propcrm.in',
                'phone' => '9876543210',
                'department' => 'Sales',
                'designation' => 'Senior Real Estate Advisor',
                'employment_type' => 'full_time',
                'status' => 'active',
                'joining_date' => '2023-01-15',
                'salary' => 75000.00,
                'user_id' => $employees[0]->id ?? null,
            ],
            [
                'employee_code' => 'EMP-102',
                'first_name' => 'Sneha',
                'last_name' => 'Kapoor',
                'email' => 'sneha.kapoor@propcrm.in',
                'phone' => '9123456789',
                'department' => 'Sales',
                'designation' => 'Sales Lead',
                'employment_type' => 'full_time',
                'status' => 'active',
                'joining_date' => '2022-08-01',
                'salary' => 95000.00,
                'user_id' => $employees[1]->id ?? null,
            ],
            [
                'employee_code' => 'EMP-103',
                'first_name' => 'Dev',
                'last_name' => 'Malhotra',
                'email' => 'dev.malhotra@propcrm.in',
                'phone' => '9345678901',
                'department' => 'Marketing',
                'designation' => 'Digital Marketing Lead',
                'employment_type' => 'full_time',
                'status' => 'active',
                'joining_date' => '2023-05-10',
                'salary' => 80000.00,
                'user_id' => $employees[2]->id ?? null,
            ],
            [
                'employee_code' => 'EMP-104',
                'first_name' => 'Priti',
                'last_name' => 'Saxena',
                'email' => 'priti.saxena@propcrm.in',
                'phone' => '9234567890',
                'department' => 'HR',
                'designation' => 'HR Manager',
                'employment_type' => 'full_time',
                'status' => 'active',
                'joining_date' => '2021-11-20',
                'salary' => 90000.00,
                'user_id' => $employees[3]->id ?? null,
            ],
            [
                'employee_code' => 'EMP-105',
                'first_name' => 'Ravi',
                'last_name' => 'Shankar',
                'email' => 'ravi.shankar@propcrm.in',
                'phone' => '9456789012',
                'department' => 'Operations',
                'designation' => 'Operations Officer',
                'employment_type' => 'full_time',
                'status' => 'on_leave',
                'joining_date' => '2024-02-01',
                'salary' => 60000.00,
                'user_id' => $employees[4]->id ?? null,
            ],
            [
                'employee_code' => 'EMP-106',
                'first_name' => 'Ananya',
                'last_name' => 'Sharma',
                'email' => 'ananya.sharma@propcrm.in',
                'phone' => '9567890123',
                'department' => 'Finance',
                'designation' => 'Senior Accountant',
                'employment_type' => 'full_time',
                'status' => 'active',
                'joining_date' => '2022-03-01',
                'salary' => 85000.00,
            ],
            [
                'employee_code' => 'EMP-107',
                'first_name' => 'Rajesh',
                'last_name' => 'Varma',
                'email' => 'rajesh.varma@propcrm.in',
                'phone' => '9678901234',
                'department' => 'Construction',
                'designation' => 'Site Architect',
                'employment_type' => 'contract',
                'status' => 'active',
                'joining_date' => '2023-09-15',
                'salary' => 110000.00,
            ],
        ];

        foreach ($empHRSeeds as $eData) {
            $emp = Employee::firstOrCreate(
                ['employee_code' => $eData['employee_code']],
                $eData
            );

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

        // Link Leads to Projects
        $createdProjects = \App\Models\Project::all();
        if ($createdProjects->count() > 0) {
            $leadsList = \App\Models\Lead::all();
            foreach ($leadsList as $index => $ld) {
                $proj = $createdProjects[$index % $createdProjects->count()];
                $ld->update([
                    'project_id' => $proj->id,
                    'project_interest' => $proj->name,
                ]);

                // Also update SiteVisits for this lead
                \App\Models\SiteVisit::where('lead_id', $ld->id)->update([
                    'project_id' => $proj->id,
                ]);
            }
        }
    }
}
