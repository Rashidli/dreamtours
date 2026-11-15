<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\CustomerImport;
use App\Models\CustomerStatus;
use App\Models\CustomerTag;
use App\Models\User;
use App\Models\VisaEnquiry;
use App\Support\CustomerMerge;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM §4 / §25 — customer import with preview; §3 / §4 — reviewed merging of duplicates. */
class CustomerImportMergeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(CmsPermissionSeeder::class);
        $user = User::factory()->create(['name' => 'Jenn Manager']);
        $user->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
        $this->actingAs($user);
    }

    private function upload(string $name, string $content): CustomerImport
    {
        $this->post(route('customer-imports.store'), ['file' => UploadedFile::fake()->createWithContent($name, $content)])->assertRedirect();

        return CustomerImport::latest('id')->firstOrFail();
    }

    private const CSV = "Name,E-mail,Mobile,Birthday,Tags,Newsletter,City\n"
        ."Ana Silva,ana@example.com,416-555-0001,1980-05-17,\"Seniors, Facebook\",yes,Toronto\n"
        ."Maria Lopez,MARIA@example.com,416-555-0101,,,no,\n"          // already a customer (same email)
        ."Ana S.,ana@example.com,,,,,\n"                                // repeats line 2 of the file
        ."Bad Email,not-an-email,416-555-0002,,,,\n"
        ."No Contact,,,,,,\n"
        ."Ben Ross,ben@example.com,416-555-0003,03/04/1990,,,\n"       // ambiguous date
        ."Chen Wu,,(647) 555-0004,22/07/1986,,,Markham\n";

    public function test_import_maps_columns_previews_without_saving_then_imports(): void
    {
        $maria = Customer::create(['first_name' => 'Maria', 'last_name' => 'Lopez', 'email' => 'maria@example.com']);
        $import = $this->upload('clients.csv', self::CSV);

        // Step 2: columns guessed from the header names.
        $this->assertSame([0 => 'full_name', 1 => 'email', 2 => 'phone', 3 => 'date_of_birth', 4 => 'tags', 5 => 'marketing_opt_in', 6 => 'city'], $import->mapping);
        $this->get(route('customer-imports.edit', $import))->assertOk()->assertSee('Ana Silva')->assertSee('7 rows');
        $this->put(route('customer-imports.update', $import), ['mapping' => $import->mapping, 'duplicates' => 'skip', 'status_id' => CustomerStatus::where('name', 'New')->value('id')])->assertRedirect(route('customer-imports.show', $import));

        // Step 3: every row checked, nothing saved.
        $this->get(route('customer-imports.show', $import))->assertOk()
            ->assertSeeInOrder(['New customers', '2', 'Already exist', '2', 'Problems', '3'])
            ->assertSee('Already a customer: Maria Lopez')->assertSee('Repeats line 2 of this file.')
            ->assertSee('is not a valid email')->assertSee('Needs an email or a phone number.')->assertSee('is ambiguous');
        $this->assertSame(1, Customer::count());
        $problems = $this->get(route('customer-imports.problems', $import))->assertOk()->streamedContent();
        $this->assertStringContainsString('not-an-email', $problems);
        $this->assertStringNotContainsString('Chen', $problems);

        // Step 4: import.
        $this->post(route('customer-imports.commit', $import))->assertRedirect();
        $ana = Customer::where('email', 'ana@example.com')->firstOrFail();
        $chen = Customer::where('phone_digits', '6475550004')->firstOrFail();
        $this->assertSame(['Ana', 'Silva', '1980-05-17', 'Toronto', 'import', 'New'], [$ana->first_name, $ana->last_name, $ana->date_of_birth->format('Y-m-d'), $ana->city, $ana->source, $ana->status->name]);
        $this->assertSame('Import clients.csv by Jenn Manager', $ana->marketing_opt_in_source);
        $this->assertEqualsCanonicalizing(['Seniors', 'Facebook'], $ana->tags->pluck('name')->all());
        $this->assertSame(['Wu', '1986-07-22', false], [$chen->last_name, $chen->date_of_birth->format('Y-m-d'), $chen->marketing_opt_in]);
        $this->assertNull($maria->fresh()->city, 'skip mode leaves existing customers alone');
        $this->assertSame(3, Customer::count());
        $this->assertEquals(['created' => 2, 'updated' => 0, 'skipped' => 2, 'failed' => 3], $import->fresh()->summary); // MySQL JSON reorders keys
        Storage::disk('local')->assertMissing($import->path);
        $this->assertTrue(ActivityLog::where('action', 'customers_imported')->exists());
        $this->get(route('customer-imports.show', $import))->assertOk()->assertSee('was imported');
        $this->post(route('customer-imports.commit', $import))->assertStatus(409);
    }

    public function test_update_mode_fills_only_empty_fields_and_adds_tags(): void
    {
        $maria = Customer::create(['first_name' => 'Maria', 'last_name' => 'Lopez', 'email' => 'maria@example.com', 'phone' => '111-222-3333']);
        $batch = CustomerTag::create(['name' => 'Fall 2026 list', 'color' => 'info']);
        $import = $this->upload('update.csv', "First name,Last name,Email,Phone,City\nMaria,Garcia,maria@example.com,416-555-0101,Ottawa\n");
        $this->put(route('customer-imports.update', $import), ['mapping' => $import->mapping, 'duplicates' => 'update', 'tag_id' => $batch->id]);
        $this->post(route('customer-imports.commit', $import))->assertRedirect();

        $maria->refresh();
        $this->assertSame(['Lopez', '111-222-3333', 'Ottawa'], [$maria->last_name, $maria->phone, $maria->city]);
        $this->assertTrue($maria->tags->contains($batch));
        $this->assertSame(1, $import->fresh()->summary['updated']);
    }

    public function test_excel_and_semicolon_windows_csv_files_are_read(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        $writer = new XlsxWriter();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['First name', 'Email', 'Date of birth']));
        $writer->addRow(Row::fromValues(['Olga', 'olga@example.com', new \DateTimeImmutable('1975-11-02')]));
        $writer->close();
        $xlsx = $this->upload('list.xlsx', file_get_contents($path));
        unlink($path);
        $this->assertSame(['first_name', 'email', 'date_of_birth'], array_values($xlsx->mapping));
        $this->post(route('customer-imports.commit', $xlsx))->assertRedirect();
        $this->assertSame('1975-11-02', Customer::where('email', 'olga@example.com')->firstOrFail()->date_of_birth->format('Y-m-d'));

        $csv = $this->upload('excel-fr.csv', mb_convert_encoding("Prénom;Email;Ville\nRené;rene@example.com;Montréal\n", 'Windows-1252', 'UTF-8'));
        $this->put(route('customer-imports.update', $csv), ['mapping' => [0 => 'first_name', 1 => 'email', 2 => 'city'], 'duplicates' => 'skip']);
        $this->post(route('customer-imports.commit', $csv))->assertRedirect();
        $this->assertSame(['first_name' => 'René', 'city' => 'Montréal'], Customer::where('email', 'rene@example.com')->firstOrFail()->only(['first_name', 'city']));
    }

    public function test_mapping_needs_a_name_and_a_contact_column_and_files_are_checked(): void
    {
        $import = $this->upload('x.csv', "Name,Email\nA,a@example.com\n");
        $this->put(route('customer-imports.update', $import), ['mapping' => [1 => 'email'], 'duplicates' => 'skip'])->assertSessionHasErrors('mapping');
        $this->put(route('customer-imports.update', $import), ['mapping' => [0 => 'email', 1 => 'email'], 'duplicates' => 'skip'])->assertSessionHasErrors('mapping');

        $this->post(route('customer-imports.store'), ['file' => UploadedFile::fake()->createWithContent('only-header.csv', "Name,Email\n")])->assertSessionHasErrors('file');
        $this->post(route('customer-imports.store'), ['file' => UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg')])->assertSessionHasErrors('file');

        $this->actingAs(tap(User::factory()->create())->givePermissionTo('list-customers'))->get(route('customer-imports.create'))->assertForbidden();
    }

    private function customer(string $first, string $last, ?string $email, ?string $phone, array $extra = []): Customer
    {
        return Customer::create(['first_name' => $first, 'last_name' => $last, 'email' => $email, 'phone' => $phone] + $extra);
    }

    public function test_duplicate_suggestions_and_not_duplicate(): void
    {
        $a = $this->customer('Maria', 'Lopez', 'maria@example.com', '416-555-0101');
        $b = $this->customer('Juan', 'Lopez', 'juan@example.com', '+1 416 555 0101');
        $c = $this->customer('maria', 'lopez', 'm.lopez@work.com', null);
        $this->customer('Sam', 'Lee', 'sam@example.com', '647-555-0000');

        $pairs = CustomerMerge::suggestions()->map(fn ($p) => [$p[0]->id, $p[1]->id, $p['reasons']])->all();
        $this->assertEqualsCanonicalizing([[$a->id, $b->id, ['Same phone']], [$a->id, $c->id, ['Same name']]], $pairs);

        $this->get(route('customer-merge.index'))->assertOk()->assertSee('Same phone')->assertSee('Same name');
        $this->post(route('customer-merge.dismiss'), ['a' => $b->id, 'b' => $a->id])->assertRedirect();
        $this->assertCount(1, CustomerMerge::suggestions());
        $this->assertTrue(ActivityLog::where('action', 'duplicate_dismissed')->exists());
    }

    public function test_merge_moves_everything_and_keeps_the_archived_record(): void
    {
        $dnc = CustomerStatus::where('name', 'Do Not Contact')->firstOrFail();
        $vip = CustomerTag::create(['name' => 'VIP', 'color' => 'warning']);
        $seniors = CustomerTag::create(['name' => 'Seniors', 'color' => 'info']);
        $keep = $this->customer('Maria', 'Lopez', 'maria@example.com', '416-555-0101', ['notes' => 'Prefers front seat.', 'city' => null]);
        $other = $this->customer('Maria', 'Lopez-Garcia', 'm.lopez@work.com', '416-555-0199', ['city' => 'Toronto', 'date_of_birth' => '1986-07-22', 'customer_status_id' => $dnc->id, 'notes' => 'Allergic to nuts.']);
        $other->forceFill(['created_at' => now()->subYear()])->save();
        $keep->tags()->attach($vip);
        $other->tags()->attach($seniors);
        $booking = Booking::create(['customer_id' => $other->id, 'type' => 'tour', 'status' => 'new', 'first_name' => 'Maria', 'last_name' => 'Lopez-Garcia', 'phone' => '416-555-0199', 'email' => 'm.lopez@work.com', 'participant_count' => 1]);
        $visa = VisaEnquiry::create(['customer_id' => $other->id, 'full_name' => 'Maria Lopez', 'email' => 'm.lopez@work.com', 'phone' => '4165550199', 'country' => 'USA', 'assistance' => ['visa'], 'applicants' => 1]);

        // Do Not Contact is preselected from either side; empty fields default to the filled one.
        $this->get(route('customer-merge.compare', ['keep' => $keep->id, 'other' => $other->id]))->assertOk()
            ->assertSee('preselected so promotions stay blocked');
        $this->post(route('customer-merge.merge'), ['keep' => $keep->id, 'other' => $other->id, 'choice' => ['last_name' => 'other']])->assertSessionHasErrors('confirm');

        $this->post(route('customer-merge.merge'), ['keep' => $keep->id, 'other' => $other->id, 'choice' => ['last_name' => 'other', 'email' => 'keep', 'phone' => 'keep', 'customer_status_id' => 'other'], 'confirm' => 1])
            ->assertRedirect(route('customers.show', $keep));

        $keep->refresh();
        $this->assertSame(['Lopez-Garcia', 'maria@example.com', 'Toronto', '1986-07-22', $dnc->id], [$keep->last_name, $keep->email, $keep->city, $keep->date_of_birth->format('Y-m-d'), $keep->customer_status_id]);
        $this->assertStringContainsString('Prefers front seat.', $keep->notes);
        $this->assertStringContainsString('Allergic to nuts.', $keep->notes);
        $this->assertStringContainsString('m.lopez@work.com', $keep->notes);
        $this->assertStringContainsString('416-555-0199', $keep->notes);
        $this->assertTrue($keep->created_at->lt(now()->subMonths(11)), 'customer since the first contact');
        $this->assertEqualsCanonicalizing(['VIP', 'Seniors'], $keep->tags->pluck('name')->all());
        $this->assertSame($keep->id, $booking->fresh()->customer_id);
        $this->assertSame($keep->id, $visa->fresh()->customer_id);

        $archived = Customer::withTrashed()->findOrFail($other->id);
        $this->assertTrue($archived->trashed());
        $this->assertSame([$keep->id, 'm.lopez@work.com'], [$archived->merged_into_id, $archived->email]);
        $this->get(route('customers.show', $other->id))->assertOk()->assertSee('was merged into');
        $this->assertTrue(ActivityLog::where(['subject_id' => $keep->id, 'action' => 'customer_merged'])->where('changes->bookings moved', $booking->reference)->exists());
        $this->assertTrue(ActivityLog::where(['subject_id' => $other->id, 'action' => 'merged_into'])->exists());

        // A merged record can't be merged again.
        $this->post(route('customer-merge.merge'), ['keep' => $keep->id, 'other' => $other->id, 'confirm' => 1])->assertSessionHasErrors('other');
    }
}
