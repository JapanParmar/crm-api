<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\FollowUp;
use App\Models\Lead;
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
    }
}
