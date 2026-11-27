<?php

namespace App\Console\Commands;

use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\SupportMessage;
use App\Models\User;
use Database\Seeders\ChildProfileSeeder;
use Database\Seeders\EmergencySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Console\Command;

class CleanProductionData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-production-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sanitize test records and seed baseline Parañaque CSN roles, users, and incident data.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting database sanitization...');

        $preserveEmails = [
            'superadmin@csn.gov.ph',
            'admin@csn.gov.ph',
            'teacher@csn.gov.ph',
            'responder@csn.gov.ph',
            'guardian@csn.gov.ph',
            'brgy.sanantonio@paranaque.gov.ph',
            'brgy.donbosco@paranaque.gov.ph',
            'brgy.sandionisio@paranaque.gov.ph',
            'brgy.sanisidro@paranaque.gov.ph',
            'brgy.baclaran@paranaque.gov.ph',
            'brgy.sunvalley@paranaque.gov.ph',
        ];

        $deletedUsers = User::whereNotIn('email', $preserveEmails)
            ->where(function ($q) {
                $q->where('email', 'like', '%@test.com')
                    ->orWhere('email', 'like', '%@example.com')
                    ->orWhere('email', 'like', '%@csn.mobile')
                    ->orWhere('email', 'test@gmail.com')
                    ->orWhere('name', 'like', 'test%');
            })->delete();

        $this->info("Removed {$deletedUsers} synthetic test users.");

        $deletedChildren = ChildProfile::where(function ($q) {
            $q->where('name', 'like', 'Test%')
                ->orWhere('name', 'like', '%test%')
                ->orWhere('condition', 'like', 'test%');
        })->delete();

        $this->info("Removed {$deletedChildren} synthetic test child profiles.");

        $deletedEmergencies = Emergency::whereNull('child_id')
            ->orWhereNotIn('child_id', ChildProfile::pluck('id'))
            ->delete();

        $this->info("Cleaned {$deletedEmergencies} orphaned emergency alerts.");

        SupportMessage::whereNotIn('guardian_id', User::pluck('id'))->delete();

        $this->info('Seeding baseline roles, users, child profiles, and historical incidents...');
        (new RoleSeeder)->run();
        (new UserSeeder)->run();
        (new ChildProfileSeeder)->run();
        (new EmergencySeeder)->run();

        Emergency::whereIn('status', ['pending', 'responding'])->update(['status' => 'resolved', 'resolved_at' => now()]);

        $this->info('Database sanitization completed successfully.');
        $this->line('Remaining Users: '.User::count());
        $this->line('Clean Child Profiles: '.ChildProfile::count());
        $this->line('Active Emergencies (Pending/Responding): '.Emergency::whereIn('status', ['pending', 'responding'])->count());
        $this->line('Historical Resolved Incidents: '.Emergency::where('status', 'resolved')->count());

        return Command::SUCCESS;
    }
}
