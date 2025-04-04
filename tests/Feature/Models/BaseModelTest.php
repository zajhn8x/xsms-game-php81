<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BaseModelTest extends TestCase
{
    use RefreshDatabase;

    // Hoặc DatabaseTransactions nếu không muốn reset toàn bộ

    protected function setUp(): void
    {
        parent::setUp();

        // Setup chung cho toàn bộ test
        $this->artisan('migrate'); // Chạy migration trước mỗi test nếu cần
        $this->seed(); // Nếu cần seed dữ liệu cho test
    }
}
