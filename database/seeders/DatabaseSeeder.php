<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentMessage;
use App\Models\Availability;
use App\Models\BlockedDate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The administrator controls whether accounts receive priest privileges.
        $admin=User::where('role','admin')->first();
        if($admin) {
            $admin->forceFill(['name'=>'PriestAgenda Administrator','email'=>'admin@admin.com','email_verified_at'=>now(),'approval_status'=>'approved'])->save();
        } else {
            User::create(['name'=>'PriestAgenda Administrator','email'=>'admin@admin.com','password'=>Str::random(64),'role'=>'admin','approval_status'=>'approved','email_verified_at'=>now()]);
        }

        $priestData = [
            [
                'email' => 'father.demo@priestagenda.test', 'name' => 'Father Alexios (Demo)',
                'slug' => 'father-alexios-demo', 'parish' => 'Demo Orthodox Parish', 'city' => 'Nicosia',
                'is_confessor' => true, 'is_priest_verified' => true, 'is_confessor_verified' => true, 'approval_status'=>'approved',
                'bio' => 'This is a sample priest profile for exploring PriestAgenda. Replace it with a real parish profile.',
                'hours' => [
                    ['service_type' => 'christening', 'weekday' => 6, 'starts_at' => '10:00', 'ends_at' => '13:00', 'slot_minutes' => 60],
                    ['service_type' => 'marriage', 'weekday' => 6, 'starts_at' => '14:00', 'ends_at' => '17:00', 'slot_minutes' => 60],
                    ['service_type' => 'confession', 'weekday' => 3, 'starts_at' => '16:00', 'ends_at' => '18:00', 'slot_minutes' => 30],
                    ['service_type' => 'liturgy', 'weekday' => 0, 'starts_at' => '08:00', 'ends_at' => '11:00', 'slot_minutes' => 60],
                ],
            ],
            [
                'email' => 'father.georgios@priestagenda.test', 'name' => 'Father Georgios',
                'slug' => 'father-georgios', 'parish' => 'Saint Nicholas Parish', 'city' => 'Limassol',
                'is_confessor' => true, 'is_priest_verified' => true, 'is_confessor_verified' => true, 'approval_status'=>'approved',
                'bio' => 'Orthodox parish priest serving the Saint Nicholas community. Contact the parish to arrange a service.',
                'hours' => [
                    ['service_type' => 'christening', 'weekday' => 0, 'starts_at' => '11:30', 'ends_at' => '14:30', 'slot_minutes' => 60],
                    ['service_type' => 'marriage', 'weekday' => 6, 'starts_at' => '15:00', 'ends_at' => '18:00', 'slot_minutes' => 60],
                    ['service_type' => 'confession', 'weekday' => 4, 'starts_at' => '17:00', 'ends_at' => '19:00', 'slot_minutes' => 30],
                    ['service_type' => 'liturgy', 'weekday' => 0, 'starts_at' => '07:30', 'ends_at' => '10:30', 'slot_minutes' => 60],
                ],
            ],
            [
                'email' => 'father.demetrios@priestagenda.test', 'name' => 'Father Demetrios',
                'slug' => 'father-demetrios', 'parish' => 'Holy Cross Church', 'city' => 'Larnaca',
                'is_confessor' => true, 'is_priest_verified' => true, 'is_confessor_verified' => true, 'approval_status'=>'approved',
                'bio' => 'Serving the Holy Cross Orthodox community in Larnaca.',
                'hours' => [
                    ['service_type' => 'christening', 'weekday' => 6, 'starts_at' => '09:00', 'ends_at' => '12:00', 'slot_minutes' => 60],
                    ['service_type' => 'marriage', 'weekday' => 0, 'starts_at' => '12:00', 'ends_at' => '15:00', 'slot_minutes' => 60],
                    ['service_type' => 'confession', 'weekday' => 2, 'starts_at' => '15:30', 'ends_at' => '18:00', 'slot_minutes' => 30],
                    ['service_type' => 'liturgy', 'weekday' => 0, 'starts_at' => '08:00', 'ends_at' => '11:00', 'slot_minutes' => 60],
                ],
            ],
            [
                'email' => 'father.nikolaos@priestagenda.test', 'name' => 'Father Nikolaos',
                'slug' => 'father-nikolaos', 'parish' => 'Annunciation Parish', 'city' => 'Paphos',
                'is_confessor' => true, 'is_priest_verified' => true, 'is_confessor_verified' => true, 'approval_status'=>'approved',
                'bio' => 'Orthodox priest at the Annunciation Parish. Please send a request to discuss ceremony dates.',
                'hours' => [
                    ['service_type' => 'christening', 'weekday' => 0, 'starts_at' => '12:00', 'ends_at' => '15:00', 'slot_minutes' => 60],
                    ['service_type' => 'marriage', 'weekday' => 6, 'starts_at' => '10:00', 'ends_at' => '13:00', 'slot_minutes' => 60],
                    ['service_type' => 'confession', 'weekday' => 5, 'starts_at' => '16:00', 'ends_at' => '18:00', 'slot_minutes' => 30],
                    ['service_type' => 'liturgy', 'weekday' => 0, 'starts_at' => '08:00', 'ends_at' => '11:00', 'slot_minutes' => 60],
                ],
            ],
        ];

        $priests = [];
        foreach ($priestData as $data) {
            $hours = $data['hours'];
            unset($data['hours']);
            $priest = User::firstOrCreate(['email' => $data['email']], $data + [
                'password' => Str::random(64), 'role' => 'priest', 'email_verified_at' => now(),
            ]);
            $priests[] = ['user' => $priest, 'hours' => $hours];

            foreach ($hours as $rule) {
                Availability::firstOrCreate(['priest_id' => $priest->id] + $rule);
            }

            $nextFriday = now()->startOfDay()->next(Carbon::FRIDAY)->addWeeks(2);
            BlockedDate::firstOrCreate([
                'priest_id' => $priest->id,
                'date' => $nextFriday->toDateString(),
                'service_type' => 'all',
                'reason' => 'Parish retreat (sample)',
            ], ['end_date' => $nextFriday->copy()->addDay()->toDateString()]);
        }

        $requesters = [];
        $names = [
            'Maria Andreou', 'Andreas Christou', 'Eleni Georgiou', 'Nicolas Michael',
            'Sophia Ioannou', 'Christos Demetriou', 'Anna Stylianou', 'George Constantinou',
            'Katerina Nicolaou', 'Petros Savva', 'Irene Panayiotou', 'Markos Antoniou',
        ];
        foreach ($names as $index => $name) {
            $email = 'parishioner'.($index + 1).'@priestagenda.test';
            $requesters[] = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'phone' => '+3579900'.str_pad((string)($index + 1), 4, '0', STR_PAD_LEFT),
                'password' => Str::random(64),
                'role' => 'user',
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]);
        }

        $services = ['confession', 'christening', 'marriage', 'liturgy', 'confession', 'marriage'];
        $statuses = ['pending', 'confirmed', 'confirmed', 'pending', 'declined', 'confirmed'];
        $sampleMessages = [
            'I would like to arrange a time to speak with you.',
            'We would like to discuss a christening for our child.',
            'We are asking about arranging our Orthodox wedding.',
            'Could you please let us know if this time is suitable?',
            'We would be grateful to discuss the next available date.',
            'Please let us know what details you need from us.',
        ];

        foreach ($priests as $priestIndex => $entry) {
            $priest = $entry['user'];
            foreach ($services as $index => $service) {
                $rule = collect($entry['hours'])->firstWhere('service_type', $service);
                $weekday = (int)$rule['weekday'];
                $daysUntil = ($weekday - now()->dayOfWeek + 7) % 7;
                $daysUntil = $daysUntil === 0 ? 7 : $daysUntil;
                $date = now()->startOfDay()->addDays($daysUntil)->addWeeks(1 + intdiv($index, 4));
                $startsAt = Carbon::parse($date->toDateString().' '.$rule['starts_at']);
                $duration = $service === 'confession' ? (int)$rule['slot_minutes'] : 60;
                $requester = $requesters[($priestIndex * 3 + $index) % count($requesters)];
                $status = $statuses[($index + $priestIndex) % count($statuses)];

                $appointment = Appointment::firstOrCreate([
                    'priest_id' => $priest->id,
                    'requester_id' => $requester->id,
                    'service_type' => $service,
                    'starts_at' => $startsAt,
                ], [
                    'duration_minutes' => $duration,
                    'status' => $status,
                    'message' => $sampleMessages[$index],
                    'priest_response' => $status === 'declined' ? 'I am not available at that time. Please use the conversation to arrange another date.' : null,
                ]);

                if ($index < 2) {
                    AppointmentMessage::firstOrCreate([
                        'appointment_id' => $appointment->id,
                        'sender_id' => $requester->id,
                        'body' => 'Thank you, Father. Please let us know if you need any more information.',
                    ]);
                    AppointmentMessage::firstOrCreate([
                        'appointment_id' => $appointment->id,
                        'sender_id' => $priest->id,
                        'body' => 'Thank you for your message. I will review the request and reply here.',
                    ]);
                }
            }
        }
    }
}
