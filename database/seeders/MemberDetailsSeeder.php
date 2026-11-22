<?php

namespace Database\Seeders;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\MembershipDocument;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * One fictional user for each step of the membership flow, to click through
 * in the admin. Run it by hand, it's deliberately not in DatabaseSeeder:
 *
 *   php artisan db:seed --class=MemberDetailsSeeder
 *
 * Safe to run again: it resets these users and touches nobody else. The
 * .invalid addresses can't receive mail and the accounts can't log in.
 */
class MemberDetailsSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, phone: ?string, birth_date: ?string, tag: ?string, status: ?MembershipStatus, accepted: bool}>
     */
    private const SCENARIOS = [
        'resident-no-phone' => ['name' => 'Test Bewoner Zonder Telefoon', 'phone' => null, 'birth_date' => null, 'tag' => null, 'status' => null, 'accepted' => false],
        'resident' => ['name' => 'Test Bewoner', 'phone' => '+31612345601', 'birth_date' => null, 'tag' => null, 'status' => null, 'accepted' => false],
        'member-empty' => ['name' => 'Test Lid Nog Niets Ingevuld', 'phone' => null, 'birth_date' => null, 'tag' => null, 'status' => MembershipStatus::PENDING, 'accepted' => false],
        'member-not-accepted' => ['name' => 'Test Lid Nog Niet Akkoord', 'phone' => '+31612345602', 'birth_date' => '2001-02-14', 'tag' => null, 'status' => MembershipStatus::PENDING, 'accepted' => false],
        'member-needs-id-check' => ['name' => 'Test Aanmelding Wacht Op ID-check', 'phone' => '+31612345603', 'birth_date' => '1999-04-12', 'tag' => null, 'status' => MembershipStatus::PENDING, 'accepted' => true],
        'member-active' => ['name' => 'Test Lid Actief Met Tag', 'phone' => '+31612345605', 'birth_date' => '1997-01-30', 'tag' => 'SEED0001', 'status' => MembershipStatus::ACTIVE, 'accepted' => true],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->error('MemberDetailsSeeder only runs in local and testing.');

            return;
        }

        // No status mails or audit entries for fake data.
        Model::withoutEvents(function () {
            $type = MemberType::firstOrCreate(['name' => 'Testlid'], ['has_voting_rights' => true]);
            $statutes = MembershipDocument::current(MembershipDocument::STATUTES)
                ?? MembershipDocument::publish(MembershipDocument::STATUTES, '<h2>Test-statuten</h2><p>Voorbeeldtekst, alleen voor lokaal testen.</p>');
            $rules = MembershipDocument::current(MembershipDocument::RULES)
                ?? MembershipDocument::publish(MembershipDocument::RULES, '<h2>Test-huishoudelijk reglement</h2><p>Voorbeeldtekst, alleen voor lokaal testen.</p>');
            $checker = $this->seedUser('board-checker', 'Test Bestuurslid', '+31612345600', '1990-06-15', null);

            // Scenarios from an earlier version of this seeder.
            $stale = User::whereIn('keycloak_id', ['seed-member-ready', 'seed-member-legacy', 'seed-member-no-birth-date'])->pluck('id');
            Membership::whereIn('user_id', $stale)->delete();
            User::whereKey($stale)->delete();

            foreach (self::SCENARIOS as $key => $scenario) {
                $user = $this->seedUser($key, $scenario['name'], $scenario['phone'], $scenario['birth_date'], $scenario['tag']);

                Membership::where('user_id', $user->id)->delete();

                if ($scenario['status'] === null) {
                    continue;
                }

                $isActive = $scenario['status'] === MembershipStatus::ACTIVE;

                $membership = Membership::create([
                    'user_id' => $user->id,
                    // The board picks the type when approving.
                    'member_type_id' => $isActive ? $type->id : null,
                    'status' => $scenario['status'],
                    'joined_at' => $isActive ? now()->subMonth()->toDateString() : null,
                ]);

                if ($scenario['accepted']) {
                    $membership->forceFill([
                        'statutes_document_id' => $statutes->id,
                        'rules_document_id' => $rules->id,
                        'documents_accepted_at' => now()->subDay(),
                    ])->save();
                }

                if ($isActive) {
                    $membership->markIdentityVerified($checker);
                }
            }

            $honorary = $this->seedUser('honorary', 'Test Erelid', '+31612345608', '1975-03-09', null);
            Membership::where('user_id', $honorary->id)->delete();
            Membership::create([
                'user_id' => $honorary->id,
                'member_type_id' => MemberType::where('is_honorary', true)->firstOrFail()->id,
                'status' => MembershipStatus::ACTIVE,
                'has_voting_rights' => false,
                'joined_at' => now()->subYear()->toDateString(),
            ])->markIdentityVerified($checker);

            $beneficiaryType = MemberType::where('is_beneficiary', true)->firstOrFail();

            foreach ([
                'beneficiary-application' => ['Test Oud-bewoner Wil Begunstiger Worden', MembershipStatus::PENDING, '+31612345606'],
                'beneficiary-active' => ['Test Begunstiger', MembershipStatus::ACTIVE, '+31612345607'],
            ] as $key => [$name, $status, $phone]) {
                $user = $this->seedUser($key, $name, $phone, '1988-11-03', null);
                $user->update([
                    'activated_at' => null,
                    'moved_out_at' => now()->subDays(10),
                    'postal_address' => ['line' => 'Voorbeeldstraat 1', 'postal_code' => '1234 AB', 'city' => 'Voorbeeldstad', 'country' => 'NL'],
                ]);

                Membership::where('user_id', $user->id)->delete();

                $membership = Membership::create([
                    'user_id' => $user->id,
                    'member_type_id' => $beneficiaryType->id,
                    'status' => $status,
                    'has_voting_rights' => false,
                    'joined_at' => $status === MembershipStatus::ACTIVE ? now()->subDays(5)->toDateString() : null,
                ]);
                $membership->forceFill([
                    'statutes_document_id' => $statutes->id,
                    'rules_document_id' => $rules->id,
                    'documents_accepted_at' => now()->subDays(6),
                ])->save();

                if ($status === MembershipStatus::ACTIVE) {
                    $membership->markIdentityVerified($checker);
                }
            }
        });
    }

    private function seedUser(string $key, string $name, ?string $phone, ?string $birthDate, ?string $tag): User
    {
        return User::updateOrCreate(['keycloak_id' => "seed-{$key}"], [
            'name' => $name,
            'email' => "{$key}@seed.invalid",
            'phone' => $phone,
            'birth_date' => $birthDate,
            'access_tag_hash' => $tag ? User::hashAccessTag($tag) : null,
            'activated_at' => now()->subMonth(),
            'privacy_policy_accepted_at' => now(),
        ]);
    }
}
