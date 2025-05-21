<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HeatmapCacheService
{
    private const CACHE_PREFIX = 'heatmap_';
    private const DEFAULT_TTL = 86400; // 24 giờ

    /**
     * Lấy dữ liệu heatmap từ cache
     */
    public function getHeatmapData(int $days = 30): array
    {
        $cacheKey = $this->getCacheKey($days);
        return Cache::get($cacheKey, []);
    }

    /**
     * Lưu dữ liệu heatmap vào cache
     */
    public function setHeatmapData(array $data, int $days = 30, ?int $ttl = null): bool
    {
        try {
            $cacheKey = $this->getCacheKey($days);
            $ttl = $ttl ?? self::DEFAULT_TTL;

            return Cache::put($cacheKey, $data, $ttl);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lưu cache heatmap: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Xóa cache heatmap
     */
    public function clearHeatmapCache(int $days = 30): bool
    {
        try {
            $cacheKey = $this->getCacheKey($days);
            return Cache::forget($cacheKey);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa cache heatmap: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật dữ liệu cho một ngày cụ thể
     */
    public function updateDayData(string $date, array $dayData, int $days = 30): bool
    {
        try {
            $cacheKey = $this->getCacheKey($days);
            $data = Cache::get($cacheKey, []);
            
            $data[$date] = $dayData;
            
            return $this->setHeatmapData($data, $days);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật cache heatmap: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy dữ liệu cho một ngày cụ thể
     */
    public function getDayData(string $date, int $days = 30): ?array
    {
        $data = $this->getHeatmapData($days);
        return $data[$date] ?? null;
    }

    /**
     * Kiểm tra xem cache có tồn tại không
     */
    public function hasHeatmapCache(int $days = 30): bool
    {
        $cacheKey = $this->getCacheKey($days);
        return Cache::has($cacheKey);
    }

    /**
     * Lấy thời gian hết hạn của cache
     */
    public function getCacheExpiration(int $days = 30): ?int
    {
        $cacheKey = $this->getCacheKey($days);
        return Cache::getTimeToLive($cacheKey);
    }

    /**
     * Tạo cache key
     */
    private function getCacheKey(int $days): string
    {
        return self::CACHE_PREFIX . "last_{$days}_days";
    }
} 