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
    protected $description = 'Sanitizes the database by removing test clutter and seeding clean, authentic Parañaque CSN production data.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting database sanitization for production / defense presentation...');

        // 1. Preserve real accounts
        $preserveEmails = [
            'superadmin@csn.gov.ph',
            'admin@csn.gov.ph',
            'responder@csn.gov.ph',
            'guardian@csn.gov.ph',
            'guardian.parent@gmail.com',
            'brgy.sanantonio@paranaque.gov.ph',
            'brgy.donbosco@paranaque.gov.ph',
            'brgy.sandionisio@paranaque.gov.ph',
            'brgy.sanisidro@paranaque.gov.ph',
            'brgy.baclaran@paranaque.gov.ph',
            'brgy.sunvalley@paranaque.gov.ph',
            'jackie.aludo2@gmail.com',
            'jackie.aludo02@gmail.com',
            'cristian.ogena09@gmail.com',
            'ogena009@gmail.com',
            'crisone43@gmail.com',
        ];

        // Delete test clutter users
        $deletedUsers = User::whereNotIn('email', $preserveEmails)
            ->where(function ($q) {
                $q->where('email', 'like', '%@test.com')
                    ->orWhere('email', 'like', '%@example.com')
                    ->orWhere('email', 'like', '%@tyess.com')
                    ->orWhere('email', 'like', '%@csn.mobile')
                    ->orWhere('email', 'test@gmail.com')
                    ->orWhere('email', 'testios@gmail.com')
                    ->orWhere('email', 'sj@gmail.con')
                    ->orWhere('name', 'like', 'test%')
                    ->orWhere('name', 'asd%')
                    ->orWhere('name', 'ahhaf%')
                    ->orWhere('name', 'djaka%');
            })->delete();

        $this->info("Removed {$deletedUsers} dummy test users.");

        // Delete junk child profiles
        $deletedChildren = ChildProfile::where(function ($q) {
            $q->where('name', 'like', 'asd%')
                ->orWhere('name', 'like', 'Test%')
                ->orWhere('name', 'like', '%test%')
                ->orWhere('name', 'ZXasdas')
                ->orWhere('condition', 'asd')
                ->orWhere('condition', 'testt')
                ->orWhere('condition', 'test PWD');
        })->delete();

        $this->info("Removed {$deletedChildren} dummy test child profiles.");

        // Clean orphaned emergencies and support messages
        $deletedEmergencies = Emergency::whereNull('child_id')
            ->orWhereNotIn('child_id', ChildProfile::pluck('id'))
            ->delete();

        $this->info("Cleaned {$deletedEmergencies} orphaned emergency alerts.");

        // Clean orphaned support messages
        SupportMessage::whereNotIn('guardian_id', User::pluck('id'))->delete();

        // 2. Re-seed official and realistic data
        $this->info('Seeding official roles, users, child profiles, and realistic historical incidents...');
        (new RoleSeeder)->run();
        (new UserSeeder)->run();
        (new ChildProfileSeeder)->run();
        (new EmergencySeeder)->run();

        // Reset any accidental active emergency so Emergency Center starts in clean Standby Mode
        Emergency::whereIn('status', ['pending', 'responding'])->update(['status' => 'resolved', 'resolved_at' => now()]);

        $this->info('Database sanitization completed successfully!');
        $this->line('Remaining Users: '.User::count());
        $this->line('Clean Child Profiles: '.ChildProfile::count());
        $this->line('Active Emergencies (Pending/Responding): '.Emergency::whereIn('status', ['pending', 'responding'])->count());
        $this->line('Historical Resolved Incidents: '.Emergency::where('status', 'resolved')->count());

        return Command::SUCCESS;
    }
}
