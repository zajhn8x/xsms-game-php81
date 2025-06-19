# Đặt Cược Thử Nghiệm với Dữ Liệu Quá Khứ

## Mục tiêu
Xây dựng hệ thống cho phép người dùng thử nghiệm các chiến lược đặt cược với dữ liệu xổ số trong quá khứ để đánh giá hiệu quả trước khi áp dụng với tiền thật.

## Prerequisites
- Hệ thống campaign đã được thiết lập
- Dữ liệu xổ số lịch sử đã được import vào database
- User authentication đã hoạt động

## Các Components Cần Xây Dựng

### 1. Historical Campaign Model
```php
// Migration: create_historical_campaigns_table
Schema::create('historical_campaigns', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('name');
    $table->text('description')->nullable();
    $table->date('test_start_date'); // Ngày bắt đầu test
    $table->date('test_end_date');   // Ngày kết thúc test
    $table->date('data_start_date'); // Ngày bắt đầu dữ liệu
    $table->date('data_end_date');   // Ngày kết thúc dữ liệu
    $table->decimal('initial_balance', 15, 2);
    $table->decimal('final_balance', 15, 2)->default(0);
    $table->string('betting_strategy'); // manual, auto_heatmap, auto_streak
    $table->json('strategy_config')->nullable();
    $table->enum('status', ['pending', 'running', 'completed', 'failed']);
    $table->timestamps();
});
```

### 2. Historical Testing Service
```php
// app/Services/HistoricalTestingService.php
class HistoricalTestingService
{
    public function createTestCampaign($userId, $config);
    public function runHistoricalTest($campaignId);
    public function simulateDailyBetting($campaign, $date);
    public function calculateResults($campaign);
}
```

### 3. Time Travel Betting Engine
```php
// app/Services/TimeTravelBettingEngine.php
class TimeTravelBettingEngine
{
    public function processDay($campaign, $currentDate);
    public function applyBettingStrategy($campaign, $availableData);
    public function checkResults($bets, $lotteryResults);
}
```

## Các Bước Thực Hiện

### Bước 1: Tạo Database Schema
```bash
# Tạo migration
php artisan make:migration create_historical_campaigns_table
php artisan make:migration create_historical_bets_table

# Chạy migration
php artisan migrate
```

### Bước 2: Tạo Models và Relationships
```php
// app/Models/HistoricalCampaign.php
class HistoricalCampaign extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description',
        'test_start_date', 'test_end_date',
        'data_start_date', 'data_end_date',
        'initial_balance', 'final_balance',
        'betting_strategy', 'strategy_config', 'status'
    ];

    protected $casts = [
        'test_start_date' => 'date',
        'test_end_date' => 'date',
        'data_start_date' => 'date',
        'data_end_date' => 'date',
        'strategy_config' => 'array'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function bets() {
        return $this->hasMany(HistoricalBet::class);
    }
}
```

### Bước 3: Tạo Controller cho Historical Testing
```php
// app/Http/Controllers/HistoricalTestingController.php
class HistoricalTestingController extends Controller
{
    public function index()
    {
        $campaigns = auth()->user()->historicalCampaigns()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('historical-testing.index', compact('campaigns'));
    }

    public function create()
    {
        return view('historical-testing.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'test_start_date' => 'required|date',
            'test_end_date' => 'required|date|after:test_start_date',
            'initial_balance' => 'required|numeric|min:100000',
            'betting_strategy' => 'required|in:manual,auto_heatmap,auto_streak',
            'strategy_config' => 'nullable|array'
        ]);

        $campaign = $this->historicalTestingService
            ->createTestCampaign(auth()->id(), $validated);

        return redirect()
            ->route('historical-testing.show', $campaign)
            ->with('success', 'Tạo chiến dịch test thành công!');
    }

    public function run(HistoricalCampaign $campaign)
    {
        if ($campaign->status !== 'pending') {
            return back()->with('error', 'Chiến dịch không thể chạy');
        }

        // Dispatch job để chạy test
        RunHistoricalTestJob::dispatch($campaign->id);

        return back()->with('success', 'Bắt đầu chạy test...');
    }
}
```

### Bước 4: Tạo Job xử lý Historical Testing
```php
// app/Jobs/RunHistoricalTestJob.php
class RunHistoricalTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $campaignId
    ) {}

    public function handle(HistoricalTestingService $service)
    {
        $service->runHistoricalTest($this->campaignId);
    }
}
```

### Bước 5: Implement Historical Testing Service
```php
// app/Services/HistoricalTestingService.php
class HistoricalTestingService
{
    public function runHistoricalTest($campaignId)
    {
        $campaign = HistoricalCampaign::findOrFail($campaignId);
        $campaign->update(['status' => 'running']);

        try {
            $currentDate = $campaign->test_start_date;
            $currentBalance = $campaign->initial_balance;

            while ($currentDate <= $campaign->test_end_date) {
                // Bỏ qua ngày không có kết quả xổ số
                if (!$this->hasLotteryResult($currentDate)) {
                    $currentDate = $currentDate->addDay();
                    continue;
                }

                // Simulate betting cho ngày này
                $dayResults = $this->simulateDailyBetting($campaign, $currentDate);
                $currentBalance += $dayResults['profit'];

                // Kiểm tra điều kiện dừng (hết tiền, đạt target)
                if ($currentBalance <= 0) {
                    break;
                }

                $currentDate = $currentDate->addDay();
            }

            $campaign->update([
                'final_balance' => $currentBalance,
                'status' => 'completed'
            ]);

        } catch (\Exception $e) {
            $campaign->update(['status' => 'failed']);
            throw $e;
        }
    }

    private function simulateDailyBetting($campaign, $date)
    {
        $engine = app(TimeTravelBettingEngine::class);
        return $engine->processDay($campaign, $date);
    }

    private function hasLotteryResult($date)
    {
        return LotteryResult::whereDate('date', $date)->exists();
    }
}
```

### Bước 6: Tạo Views
```blade
{{-- resources/views/historical-testing/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Kiểm Thử Lịch Sử</h1>
        <a href="{{ route('historical-testing.create') }}" class="btn btn-primary">
            Tạo Test Mới
        </a>
    </div>

    <div class="row">
        @foreach($campaigns as $campaign)
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>{{ $campaign->name }}</h5>
                    <span class="badge badge-{{ $campaign->status === 'completed' ? 'success' : 'warning' }}">
                        {{ ucfirst($campaign->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <p><strong>Thời gian test:</strong> 
                        {{ $campaign->test_start_date->format('d/m/Y') }} - 
                        {{ $campaign->test_end_date->format('d/m/Y') }}
                    </p>
                    <p><strong>Số dư ban đầu:</strong> {{ number_format($campaign->initial_balance) }}đ</p>
                    <p><strong>Số dư cuối:</strong> {{ number_format($campaign->final_balance) }}đ</p>
                    <p><strong>Lợi nhuận:</strong> 
                        <span class="text-{{ $campaign->final_balance >= $campaign->initial_balance ? 'success' : 'danger' }}">
                            {{ number_format($campaign->final_balance - $campaign->initial_balance) }}đ
                        </span>
                    </p>
                    
                    <div class="btn-group">
                        <a href="{{ route('historical-testing.show', $campaign) }}" class="btn btn-info btn-sm">Xem Chi Tiết</a>
                        @if($campaign->status === 'pending')
                            <form action="{{ route('historical-testing.run', $campaign) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">Chạy Test</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{ $campaigns->links() }}
</div>
@endsection
```

### Bước 7: Tạo Routes
```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::resource('historical-testing', HistoricalTestingController::class);
    Route::post('historical-testing/{campaign}/run', [HistoricalTestingController::class, 'run'])
        ->name('historical-testing.run');
});
```

## Testing & Validation

### Unit Tests
```php
// tests/Unit/HistoricalTestingServiceTest.php
class HistoricalTestingServiceTest extends TestCase
{
    public function test_can_create_historical_campaign()
    {
        $user = User::factory()->create();
        $config = [
            'name' => 'Test Campaign',
            'test_start_date' => '2023-01-01',
            'test_end_date' => '2023-01-31',
            'initial_balance' => 1000000,
            'betting_strategy' => 'manual'
        ];

        $campaign = $this->service->createTestCampaign($user->id, $config);
        
        $this->assertInstanceOf(HistoricalCampaign::class, $campaign);
        $this->assertEquals('pending', $campaign->status);
    }
}
```

### Feature Tests
```php
// tests/Feature/HistoricalTestingTest.php
class HistoricalTestingTest extends TestCase
{
    public function test_user_can_create_historical_test()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->post('/historical-testing', [
                'name' => 'My Test',
                'test_start_date' => '2023-01-01',
                'test_end_date' => '2023-01-31',
                'initial_balance' => 1000000,
                'betting_strategy' => 'manual'
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('historical_campaigns', [
            'name' => 'My Test',
            'user_id' => $user->id
        ]);
    }
}
```

## Performance Considerations

1. **Chunked Processing**: Xử lý từng ngày một để tránh timeout
2. **Queue Jobs**: Sử dụng queue để chạy test ở background
3. **Caching**: Cache kết quả xổ số để tăng tốc độ
4. **Database Indexing**: Index các cột date và user_id

## Security

1. **Authorization**: Chỉ cho phép user xem/chỉnh sửa test của mình
2. **Input Validation**: Validate tất cả input từ user
3. **Rate Limiting**: Giới hạn số test có thể tạo mỗi ngày

## Monitoring

1. **Job Monitoring**: Theo dõi queue jobs success/failure
2. **Performance Metrics**: Đo thời gian chạy test
3. **Error Logging**: Log chi tiết các lỗi xảy ra

## Troubleshooting

### Lỗi thường gặp:
1. **Job timeout**: Tăng timeout cho queue job
2. **Memory limit**: Sử dụng chunked processing
3. **Missing lottery data**: Kiểm tra data integrity trước khi chạy test

### Debug commands:
```bash
# Kiểm tra queue jobs
php artisan queue:work --verbose

# Kiểm tra logs
tail -f storage/logs/laravel.log

# Test một phần nhỏ dữ liệu
php artisan historical:test-single --campaign=1 --date=2023-01-01
``` 
