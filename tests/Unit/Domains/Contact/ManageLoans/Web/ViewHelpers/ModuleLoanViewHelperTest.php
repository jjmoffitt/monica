<?php

namespace Tests\Unit\Domains\Contact\ManageLoans\Web\ViewHelpers;

use function env;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Loan;
use App\Models\User;
use App\Models\Contact;
use App\Models\Currency;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Contact\ManageLoans\Web\ViewHelpers\ModuleLoanViewHelper;

class ModuleLoanViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $loan = Loan::factory()->create([
            'currency_id' => Currency::factory()->create(),
        ]);
        $array = ModuleLoanViewHelper::data($contact, $user);

        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('loans', $array);
        $this->assertArrayHasKey('current_date', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            '2018-01-01',
            $array['current_date']
        );

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/loans',
                'currencies' => env('APP_URL').'/currencies',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $contact = Contact::factory()->create();
        $otherContact = Contact::factory()->create();
        $user = User::factory()->create();
        $loan = Loan::factory()->create([
            'currency_id' => Currency::factory()->create(),
            'loaned_at' => '2019-02-02',
        ]);
        $contact->loansAsLoaner()->syncWithoutDetaching([$loan->id => ['loanee_id' => $otherContact->id]]);

        $array = ModuleLoanViewHelper::dtoLoan($loan, $contact, $user);

        $this->assertEquals(
            14,
            count($array)
        );

        $this->assertEquals(
            $loan->id,
            $array['id']
        );
        $this->assertEquals(
            $loan->type,
            $array['type']
        );
        $this->assertEquals(
            $loan->name,
            $array['name']
        );
        $this->assertEquals(
            $loan->description,
            $array['description']
        );
        $this->assertEquals(
            $loan->amount_lent / 100,
            $array['amount_lent']
        );
        $this->assertEquals(
            $loan->currency_id,
            $array['currency_id']
        );
        $this->assertEquals(
            '2019-02-02',
            $array['loaned_at']
        );
        $this->assertEquals(
            'Feb 02, 2019',
            $array['loaned_at_human_format']
        );
        $this->assertEquals(
            false,
            $array['settled']
        );
        $this->assertEquals(
            null,
            $array['settled_at_human_format']
        );
        $this->assertEquals(
            $loan->currency->code,
            $array['currency_name']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $contact->id,
                    'name' => $contact->getName($user),
                ],
            ],
            $array['loaners']->toArray()
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $otherContact->id,
                    'name' => $otherContact->getName($user),
                ],
            ],
            $array['loanees']->toArray()
        );
        $this->assertEquals(
            [
                'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/loans/'.$loan->id,
                'toggle' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/loans/'.$loan->id.'/toggle',
                'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/loans/'.$loan->id,
            ],
            $array['url']
        );
    }
}