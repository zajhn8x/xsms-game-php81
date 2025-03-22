
<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StatisticsPageTest extends DuskTestCase
{
    public function test_statistics_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/statistics')
                    ->assertSee('Tổng số lần đặt')
                    ->assertSee('Tổng tiền đã đặt')
                    ->assertSee('Số lần trúng')
                    ->assertSee('Tổng tiền thắng');
        });
    }

    public function test_statistics_components_are_visible()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/statistics')
                    ->assertPresent('#total-bets')
                    ->assertPresent('#total-amount')
                    ->assertPresent('#total-wins')
                    ->assertPresent('#total-winnings');
        });
    }
}
