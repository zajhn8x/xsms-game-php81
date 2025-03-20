<?php /***


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LotteryCombinationGenerator;
use App\Services\LotteryResultVerifier;
use App\Services\CombinationProcessor;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateAndVerifyLotteryCombinations extends Command
{
    protected $signature = 'lottery:generate-combinations {count=1000} {--verify} {--start-date=} {--end-date=}';
    protected $description = 'Tạo và kiểm tra các cầu lô tự động';

    protected $generator;
    protected $verifier;

    public function __construct(LotteryCombinationGenerator $generator, LotteryResultVerifier $verifier)
    {
        parent::__construct();
        $this->generator = $generator;
        $this->verifier = $verifier;
    }

    public function handle()
    {
        $count = $this->argument('count');
        $shouldVerify = $this->option('verify');

        $this->info("Bắt đầu tạo $count cầu lô...");

        // Tạo job để theo dõi tiến trình
        $jobId = DB::table('lottery_processing_jobs')->insertGetId([
            'job_type' => 'generate_combinations',
            'status' => 'processing',
            'progress_percentage' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        try {
            // Tạo các cầu lô
            $combinations = $this->generator->generateRandomCombinations($count);

            // Cập nhật tiến trình
            DB::table('lottery_processing_jobs')
                ->where('id', $jobId)
                ->update([
                    'progress_percentage' => 50,
                    'updated_at' => Carbon::now(),
                ]);

            // Lưu vào database
            $metaIds = $this->generator->saveCombinationsToDatabase($combinations);

            // Tạo các bản ghi trong bảng lottery_cau
            $cauData = [];
            foreach ($metaIds as $index => $metaId) {
                $cauData[] = [
                    'formula_meta_id' => $metaId,
                    'combination_type' => $combinations[$index]['combination_type'],
                    'is_verified' => false,
                    'last_date_verified' => null,
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            // Chèn theo batch để tối ưu hiệu suất
            $chunks = array_chunk($cauData, 100);
            foreach ($chunks as $chunk) {
                DB::table('lottery_cau')->insert($chunk);
            }

            // Cập nhật hoàn thành tạo cầu
            DB::table('lottery_processing_jobs')
                ->where('id', $jobId)
                ->update([
                    'progress_percentage' => 100,
                    'status' => 'completed',
                    'stats' => json_encode([
                        'combinations_generated' => $count,
                        'single_count' => count(array_filter($combinations, function($c) { return $c['combination_type'] === 'single'; })),
                        'pair_count' => count(array_filter($combinations, function($c) { return $c['combination_type'] === 'pair'; })),
                        'multi_count' => count(array_filter($combinations, function($c) { return $c['combination_type'] === 'multi'; })),
                        'dynamic_count' => count(array_filter($combinations, function($c) { return $c['combination_type'] === 'dynamic'; })),
                    ]),
                    'updated_at' => Carbon::now(),
                ]);

            $this->info("Đã tạo thành công $count cầu lô!");

            // Kiểm tra nếu yêu cầu xác minh
            if ($shouldVerify) {
                $this->verifyGeneratedCombinations();
            }

            return 0;

        } catch (\Exception $e) {
            // Cập nhật trạng thái lỗi
            DB::table('lottery_processing_jobs')
                ->where('id', $jobId)
                ->update([
                    'status' => 'failed',
                    'stats' => json_encode(['error' => $e->getMessage()]),
                    'updated_at' => Carbon::now(),
                ]);

            $this->error("Lỗi trong quá trình tạo cầu: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Xác minh các cầu đã tạo với dữ liệu xổ số
     */ /**
    protected function verifyGeneratedCombinations()
    {
        $startDate = $this->option('start-date') ?: Carbon::now()->subDays(30)->format('Y-m-d');
        $endDate = $this->option('end-date') ?: Carbon::now()->format('Y-m-d');

        $this->info("Bắt đầu kiểm tra cầu với dữ liệu từ $startDate đến $endDate...");

        // Tạo job mới để theo dõi tiến trình
        $jobId = DB::table('lottery_processing_jobs')->insertGetId([
            'job_type' => 'verify_combinations',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'processing',
            'progress_percentage' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        try {
            // Thiết lập batch size lớn hơn để tăng tốc
            $this->verifier->setBatchSize(200);

            // Thực hiện xác minh hàng loạt
            $stats = $this->verifier->batchVerifyResults($startDate, $endDate);

            // Cập nhật hoàn thành
            DB::table('lottery_processing_jobs')
                ->where('id', $jobId)
                ->update([
                    'progress_percentage' => 100,
                    'status' => 'completed',
                    'stats' => json_encode($stats),
                    'updated_at' => Carbon::now(),
                ]);

            $this->info("Đã hoàn thành kiểm tra cầu!");
            $this->table(
                ['Ngày xử lý', 'Tổng số cầu', 'Trúng', 'Trượt', 'Tỷ lệ trúng (%)'],
                array_map(function($date, $stat) {
                    $hitRate = $stat['total_processed'] > 0
                        ? round(($stat['hits'] / $stat['total_processed']) * 100, 2)
                        : 0;
                    return [
                        $date,
                        $stat['total_processed'],
                        $stat['hits'],
                        $stat['misses'],
                        $hitRate
                    ];
                }, array_keys($stats['daily_stats']), array_values($stats['daily_stats']))
            );

        } catch (\Exception $e) {
            // Cập nhật trạng thái lỗi
            DB::table('lottery_processing_jobs')
                ->where('id', $jobId)
                ->update([
                    'status' => 'failed',
                    'stats' => json_encode(['error' => $e->getMessage()]),
                    'updated_at' => Carbon::now(),
                ]);

            $this->error("Lỗi trong quá trình kiểm tra cầu: " . $e->getMessage());
        }
    }
}
 *
 * /**  */


