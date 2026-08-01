<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectConfiguration;
use App\Models\Tower;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InventoryDataSeeder extends Seeder
{
    private array $facings = ['North', 'South', 'East', 'West', 'NE', 'NW', 'SE', 'SW'];

    public function run(): void
    {
        $projects = Project::all();
        if ($projects->isEmpty()) {
            $this->command->warn('No projects found to seed inventory. Run CrmDataSeeder first.');
            return;
        }

        $employees = User::role('employee')->get();
        $admin = User::where('email', 'admin@example.com')->first();
        $adminId = $admin ? $admin->id : 1;

        foreach ($projects as $project) {
            // 1. Create project configurations
            $configs = [];
            $bhkTypes = ['2BHK', '3BHK'];
            if ($project->type === 'commercial') {
                $bhkTypes = ['Commercial', 'Plot'];
            }

            foreach ($bhkTypes as $index => $bhk) {
                $configs[] = ProjectConfiguration::create([
                    'project_id'      => $project->id,
                    'bhk_type'        => $bhk,
                    'carpet_area_min' => $bhk === '2BHK' ? 800 : ($bhk === '3BHK' ? 1100 : 1400),
                    'carpet_area_max' => $bhk === '2BHK' ? 900 : ($bhk === '3BHK' ? 1300 : 1600),
                    'price_from'      => $bhk === '2BHK' ? 6000000 : ($bhk === '3BHK' ? 9000000 : 11000000),
                    'price_to'        => $bhk === '2BHK' ? 7000000 : ($bhk === '3BHK' ? 10000000 : 13000000),
                ]);
            }

            // 2. Create Towers
            $towerCount = $project->type === 'commercial' ? 1 : 2;
            for ($t = 1; $t <= $towerCount; $t++) {
                $towerName = 'Tower ' . chr(64 + $t); // Tower A, Tower B
                $tower = Tower::create([
                    'project_id'      => $project->id,
                    'tower_name'      => $towerName,
                    'total_floors'    => $project->type === 'commercial' ? 5 : 8,
                    'units_per_floor' => 4,
                    'has_lift'        => true,
                    'parking_details' => 'Basement and Ground level stilt parking available.',
                ]);

                // 3. Create Units for each floor & position
                for ($floor = 1; $floor <= $tower->total_floors; $floor++) {
                    for ($pos = 1; $pos <= $tower->units_per_floor; $pos++) {
                        // Generate unit number: A-101, A-102, B-804, etc.
                        $unitNumber = chr(64 + $t) . '-' . ($floor * 100 + $pos);

                        // Randomly choose configuration
                        $config = $configs[array_rand($configs)];

                        // Calculate pricing & areas
                        $carpetArea = rand((int) $config->carpet_area_min, (int) $config->carpet_area_max);
                        $builtUpArea = round($carpetArea * 1.15, 2);
                        $basePrice = rand((int) ($config->price_from / 100000), (int) ($config->price_to / 100000)) * 100000;
                        $sqftPrice = round($basePrice / $builtUpArea, 2);
                        
                        // Add some premium for higher floors
                        $floorRise = $floor > 3 ? ($floor - 3) * 15000 : 0;
                        $plc = $pos === 1 || $pos === 4 ? 50000 : 0; // Corner units plc
                        $parking = 250000;
                        $clubhouse = 100000;
                        
                        $totalPrice = $basePrice + $floorRise + $plc + $parking + $clubhouse;
                        $gstAmount = round($totalPrice * 0.05, 2); // 5% GST
                        $totalWithGst = $totalPrice + $gstAmount;

                        // Determine status
                        // For Project 5 (Godrej Reserve), it is in planning so all units are available
                        // For others, mix status
                        $status = 'available';
                        if ($project->code !== 'PRJ-GODR') {
                            $randVal = rand(1, 10);
                            if ($randVal <= 3) {
                                $status = 'available';
                            } elseif ($randVal <= 6) {
                                $status = 'sold';
                            } elseif ($randVal <= 8) {
                                $status = 'booked';
                            } else {
                                $status = 'hold';
                            }
                        }

                        $unit = Unit::create([
                            'tower_id'            => $tower->id,
                            'project_id'          => $project->id,
                            'unit_number'         => $unitNumber,
                            'floor_number'        => $floor,
                            'bhk_type'            => $config->bhk_type,
                            'carpet_area'         => $carpetArea,
                            'built_up_area'       => $builtUpArea,
                            'super_built_up_area' => $builtUpArea * 1.25,
                            'facing'              => $this->facings[array_rand($this->facings)],
                            'base_price'          => $basePrice,
                            'price_per_sqft'      => $sqftPrice,
                            'floor_rise_charges'  => $floorRise,
                            'plc_charges'         => $plc,
                            'parking_charges'     => $parking,
                            'club_house_charges'  => $clubhouse,
                            'gst_amount'          => $gstAmount,
                            'total_price'         => $totalWithGst,
                            'status'              => $status,
                        ]);

                        // 4. Create Bookings & Payments for booked/sold units
                        if (in_array($status, ['booked', 'sold'])) {
                            // Find a lead in closed_won or site_visit for this project
                            $lead = Lead::where('project_id', $project->id)
                                ->whereIn('status', ['closed_won', 'negotiation', 'site_visit'])
                                ->whereNotExists(function ($q) {
                                    $q->select(\DB::raw(1))
                                      ->from('bookings')
                                      ->whereColumn('bookings.lead_id', 'leads.id');
                                })
                                ->first();

                            // Fallback if no matching leads
                            $custName = $lead ? $lead->name : 'Customer for ' . $unitNumber;
                            $custPhone = $lead ? $lead->phone : '9' . rand(100000000, 999999999);
                            $custEmail = $lead ? $lead->email : strtolower(str_replace(' ', '.', $custName)) . '@example.com';
                            $assignedTo = $lead ? $lead->assigned_to : ($employees->isNotEmpty() ? $employees->random()->id : $adminId);

                            $bookingDate = Carbon::now()->subDays(rand(10, 60));
                            
                            $booking = Booking::create([
                                'unit_id'          => $unit->id,
                                'lead_id'          => $lead ? $lead->id : null,
                                'customer_name'    => $custName,
                                'customer_phone'   => $custPhone,
                                'customer_email'   => $custEmail,
                                'assigned_to'      => $assignedTo,
                                'booking_date'     => $bookingDate->toDateString(),
                                'booking_amount'   => round($totalWithGst * 0.1, 2), // 10% booking amount
                                'agreement_status' => $status === 'sold' ? 'registered' : (rand(0, 1) ? 'signed' : 'draft'),
                                'notes'            => 'Initial booking seed.',
                            ]);

                            // If we matched a lead, set status to closed_won
                            if ($lead) {
                                $lead->update(['status' => 'closed_won']);
                            }

                            // 5. Create Payment Schedules
                            // Payment 1: Booking Amount (Paid)
                            Payment::create([
                                'booking_id'            => $booking->id,
                                'payment_type'          => 'booking',
                                'amount'                => $booking->booking_amount,
                                'due_date'              => $bookingDate->toDateString(),
                                'paid_date'             => $bookingDate->toDateString(),
                                'payment_status'        => 'paid',
                                'notes'                 => 'Booking token payment received.',
                            ]);

                            // Payment 2: Installment 1 (Paid for Sold, Paid or Overdue/Pending for Booked)
                            $inst1DueDate = $bookingDate->copy()->addDays(30);
                            $inst1Status = $status === 'sold' ? 'paid' : (rand(0, 1) ? 'paid' : (Carbon::now()->gt($inst1DueDate) ? 'overdue' : 'pending'));
                            Payment::create([
                                'booking_id'            => $booking->id,
                                'payment_type'          => 'installment',
                                'amount'                => round($totalWithGst * 0.4, 2), // 40% installment
                                'due_date'              => $inst1DueDate->toDateString(),
                                'paid_date'             => $inst1Status === 'paid' ? $inst1DueDate->copy()->subDays(2)->toDateString() : null,
                                'payment_status'        => $inst1Status,
                                'notes'                 => 'Plinth level construction milestone.',
                            ]);

                            // Payment 3: Installment 2 (Pending)
                            $inst2DueDate = $bookingDate->copy()->addDays(90);
                            Payment::create([
                                'booking_id'            => $booking->id,
                                'payment_type'          => 'installment',
                                'amount'                => round($totalWithGst * 0.3, 2), // 30% installment
                                'due_date'              => $inst2DueDate->toDateString(),
                                'payment_status'        => $inst2DueDate->lt(Carbon::now()) ? 'overdue' : 'pending',
                                'notes'                 => 'Superstructure slab completion milestone.',
                            ]);

                            // Payment 4: Final Possession (Pending)
                            $finalDueDate = Carbon::parse($project->possession_date);
                            Payment::create([
                                'booking_id'     => $booking->id,
                                'payment_type'   => 'final',
                                'amount'         => round($totalWithGst * 0.2, 2), // 20% remaining
                                'due_date'       => $finalDueDate->toDateString(),
                                'payment_status' => 'pending',
                                'notes'          => 'Possession and keys handover.',
                            ]);
                        }
                    }
                }
            }
        }
    }
}
