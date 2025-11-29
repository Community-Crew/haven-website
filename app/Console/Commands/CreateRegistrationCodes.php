<?php

namespace App\Console\Commands;

use App\Models\RegistrationCode;
use App\Models\Unit;
use Illuminate\Console\Command;

class CreateRegistrationCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-registration-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create registration codes for units that dont have users registered';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subparUnits = 0;
        Unit::all()->each(function (Unit $unit) use (&$subparUnits) {
            $registrationCodes = $unit->registrationCodes();
            $activeCodes = $registrationCodes->where('is_used', false);
            $registeredUsers = $unit->users()->count();
            $unregisteredUsersPerUnit = $unit['max_residents']- $registeredUsers;

            if($unregisteredUsersPerUnit > 0 && $activeCodes->count() < $unregisteredUsersPerUnit) {
                for ($i = 0; $i < $unregisteredUsersPerUnit; $i++) {
                    $unit->registrationCodes()->create();
                }
            }

        });
    }
}
