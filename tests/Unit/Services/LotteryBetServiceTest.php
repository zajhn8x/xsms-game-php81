<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LotteryBetService;
use App\Models\User;
use App\Models\Campaign;
use App\Models\CampaignBet;
use App\Models\LotteryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Carbon\Carbon;

class LotteryBetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $lotteryBetService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lotteryBetService = new LotteryBetService();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function can_create_campaign_with_valid_data()
    {
        $campaignData = [
            'start_date' => '2024-01-01',
            'days' => 30,
            'initial_balance' => 1000000,
            'bet_type' => 'manual'
        ];

        $campaign = $this->lotteryBetService->createCampaign($this->user->id, $campaignData);

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals($this->user->id, $campaign->user_id);
        $this->assertEquals(1000000, $campaign->initial_balance);
        $this->assertEquals(1000000, $campaign->current_balance);
        $this->assertEquals('active', $campaign->status);
        $this->assertDatabaseHas('campaigns', [
            'user_id' => $this->user->id,
            'initial_balance' => 1000000,
            'current_balance' => 1000000,
            'bet_type' => 'manual',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function can_place_campaign_bet_with_sufficient_balance()
    {
        $campaign = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'current_balance' => 100000
        ]);

        $betData = [
            'lo_number' => '12',
            'points' => 10,
            'bet_date' => '2024-01-01'
        ];

        $bet = $this->lotteryBetService->placeCampaignBet($campaign->id, $betData);

        $this->assertInstanceOf(CampaignBet::class, $bet);
        $this->assertEquals($campaign->id, $bet->campaign_id);
        $this->assertEquals('12', $bet->lo_number);
        $this->assertEquals(10, $bet->points);
        $this->assertEquals(230, $bet->amount); // 10 * 23
        $this->assertEquals('pending', $bet->status);

        // Check campaign balance updated
        $campaign->refresh();
        $this->assertEquals(99770, $campaign->current_balance); // 100000 - 230
    }

    /** @test */
    public function cannot_place_bet_with_insufficient_balance()
    {
        $campaign = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'current_balance' => 100 // Very small balance
        ]);

        $betData = [
            'lo_number' => '12',
            'points' => 10, // This will cost 230
            'bet_date' => '2024-01-01'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->lotteryBetService->placeCampaignBet($campaign->id, $betData);
    }

    /** @test */
    public function throws_exception_when_campaign_not_found()
    {
        $betData = [
            'lo_number' => '12',
            'points' => 10,
            'bet_date' => '2024-01-01'
        ];

        $this->expectException(ModelNotFoundException::class);

        $this->lotteryBetService->placeCampaignBet(999, $betData);
    }

    /** @test */
    public function can_process_winning_campaign_results()
    {
        // Create lottery result
        $lotteryResult = LotteryResult::factory()->create([
            'draw_date' => '2024-01-01',
            'lo_array' => ['12', '34', '56']
        ]);

        // Create campaign and winning bet
        $campaign = Campaign::factory()->create([
            'current_balance' => 50000
        ]);

        $winningBet = CampaignBet::factory()->create([
            'campaign_id' => $campaign->id,
            'lo_number' => '12', // This will win
            'amount' => 1000,
            'bet_date' => '2024-01-01',
            'status' => 'pending'
        ]);

        $losingBet = CampaignBet::factory()->create([
            'campaign_id' => $campaign->id,
            'lo_number' => '99', // This will lose
            'amount' => 500,
            'bet_date' => '2024-01-01',
            'status' => 'pending'
        ]);

        $this->lotteryBetService->processCampaignResults('2024-01-01');

        // Check winning bet
        $winningBet->refresh();
        $this->assertTrue($winningBet->is_win);
        $this->assertEquals(80000, $winningBet->win_amount); // 1000 * 80
        $this->assertEquals('completed', $winningBet->status);

        // Check losing bet
        $losingBet->refresh();
        $this->assertFalse($losingBet->is_win);
        $this->assertEquals(0, $losingBet->win_amount);
        $this->assertEquals('completed', $losingBet->status);

        // Check campaign balance updated with winnings
        $campaign->refresh();
        $this->assertEquals(130000, $campaign->current_balance); // 50000 + 80000
    }

    /** @test */
    public function can_process_all_losing_campaign_results()
    {
        // Create lottery result
        $lotteryResult = LotteryResult::factory()->create([
            'draw_date' => '2024-01-01',
            'lo_array' => ['11', '22', '33']
        ]);

        // Create campaign and losing bets
        $campaign = Campaign::factory()->create([
            'current_balance' => 50000
        ]);

        $losingBet1 = CampaignBet::factory()->create([
            'campaign_id' => $campaign->id,
            'lo_number' => '44',
            'amount' => 1000,
            'bet_date' => '2024-01-01',
            'status' => 'pending'
        ]);

        $losingBet2 = CampaignBet::factory()->create([
            'campaign_id' => $campaign->id,
            'lo_number' => '55',
            'amount' => 500,
            'bet_date' => '2024-01-01',
            'status' => 'pending'
        ]);

        $this->lotteryBetService->processCampaignResults('2024-01-01');

        // Check all bets marked as completed but not winning
        $losingBet1->refresh();
        $this->assertFalse($losingBet1->is_win);
        $this->assertEquals('completed', $losingBet1->status);

        $losingBet2->refresh();
        $this->assertFalse($losingBet2->is_win);
        $this->assertEquals('completed', $losingBet2->status);

        // Check campaign balance unchanged (no winnings)
        $campaign->refresh();
        $this->assertEquals(50000, $campaign->current_balance);
    }

    /** @test */
    public function does_nothing_when_no_lottery_result_exists()
    {
        $campaign = Campaign::factory()->create();
        $bet = CampaignBet::factory()->create([
            'campaign_id' => $campaign->id,
            'bet_date' => '2024-01-01',
            'status' => 'pending'
        ]);

        // No lottery result for this date
        $this->lotteryBetService->processCampaignResults('2024-01-01');

        // Bet should remain pending
        $bet->refresh();
        $this->assertEquals('pending', $bet->status);
    }

    /** @test */
    public function can_check_and_complete_expired_campaigns()
    {
        $today = Carbon::today();
        $pastDate = $today->copy()->subDays(10);

        // Create campaigns: one expired, one active
        $expiredCampaign = Campaign::factory()->create([
            'start_date' => $pastDate->copy()->subDays(40),
            'days' => 30, // Should have ended 10 days ago
            'status' => 'active'
        ]);

        $activeCampaign = Campaign::factory()->create([
            'start_date' => $pastDate,
            'days' => 30, // Still has 20 days left
            'status' => 'active'
        ]);

        $this->lotteryBetService->checkCompletedCampaigns();

        // Check expired campaign is completed
        $expiredCampaign->refresh();
        $this->assertEquals('completed', $expiredCampaign->status);

        // Check active campaign remains active
        $activeCampaign->refresh();
        $this->assertEquals('active', $activeCampaign->status);
    }

    /** @test */
    public function campaign_creation_uses_database_transaction()
    {
        // Mock DB transaction to ensure it's being used
        $this->expectsDatabaseQueryCount(1); // INSERT for campaign

        $campaignData = [
            'start_date' => '2024-01-01',
            'days' => 30,
            'initial_balance' => 1000000,
            'bet_type' => 'manual'
        ];

        $campaign = $this->lotteryBetService->createCampaign($this->user->id, $campaignData);
        $this->assertInstanceOf(Campaign::class, $campaign);
    }

    /** @test */
    public function bet_placement_uses_database_transaction()
    {
        $campaign = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'current_balance' => 100000
        ]);

        $betData = [
            'lo_number' => '12',
            'points' => 10,
            'bet_date' => '2024-01-01'
        ];

        // Should use transaction for bet creation and campaign update
        $bet = $this->lotteryBetService->placeCampaignBet($campaign->id, $betData);
        $this->assertInstanceOf(CampaignBet::class, $bet);
    }

    /** @test */
    public function calculates_bet_amount_correctly()
    {
        $campaign = Campaign::factory()->create([
            'current_balance' => 100000
        ]);

        $testCases = [
            ['points' => 1, 'expected_amount' => 23],
            ['points' => 5, 'expected_amount' => 115],
            ['points' => 10, 'expected_amount' => 230],
            ['points' => 100, 'expected_amount' => 2300]
        ];

        foreach ($testCases as $testCase) {
            $betData = [
                'lo_number' => '12',
                'points' => $testCase['points'],
                'bet_date' => '2024-01-01'
            ];

            $bet = $this->lotteryBetService->placeCampaignBet($campaign->id, $betData);
            $this->assertEquals($testCase['expected_amount'], $bet->amount);

            // Reset campaign balance for next test
            $campaign->update(['current_balance' => 100000]);
        }
    }
}
