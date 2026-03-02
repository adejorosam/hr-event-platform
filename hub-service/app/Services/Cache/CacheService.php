<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    private const EMPLOYEE_LIST_TTL = 3600;      // 1 hour
    private const EMPLOYEE_ITEM_TTL = 3600;      // 1 hour
    private const CHECKLIST_TTL = 1800;           // 30 minutes

    // --- Employee Cache ---

    public function getEmployeeList(string $country, int $page = 1, int $perPage = 15): ?array
    {
        return Cache::get($this->employeeListKey($country, $page, $perPage));
    }

    public function setEmployeeList(string $country, int $page, int $perPage, array $data): void
    {
        Cache::put(
            $this->employeeListKey($country, $page, $perPage),
            $data,
            self::EMPLOYEE_LIST_TTL
        );
    }

    public function getEmployee(string $country, int $employeeId): ?array
    {
        return Cache::get($this->employeeKey($country, $employeeId));
    }

    public function setEmployee(string $country, int $employeeId, array $data): void
    {
        Cache::put(
            $this->employeeKey($country, $employeeId),
            $data,
            self::EMPLOYEE_ITEM_TTL
        );
    }

    public function removeEmployee(string $country, int $employeeId): void
    {
        Cache::forget($this->employeeKey($country, $employeeId));
    }

    // --- Checklist Cache ---

    public function getChecklist(string $country): ?array
    {
        return Cache::get($this->checklistKey($country));
    }

    public function setChecklist(string $country, array $data): void
    {
        Cache::put(
            $this->checklistKey($country),
            $data,
            self::CHECKLIST_TTL
        );
    }

    // --- Invalidation ---

    public function invalidateEmployeeCache(string $country): void
    {
        // Invalidate all paginated lists for this country
        $this->invalidatePattern("employees:{$country}:list:*");

        Log::info("Invalidated employee list cache for {$country}");
    }

    public function invalidateChecklistCache(string $country): void
    {
        Cache::forget($this->checklistKey($country));

        Log::info("Invalidated checklist cache for {$country}");
    }

    public function invalidateAllForCountry(string $country): void
    {
        $this->invalidateEmployeeCache($country);
        $this->invalidateChecklistCache($country);
    }

    // --- Key Builders ---

    private function employeeListKey(string $country, int $page, int $perPage): string
    {
        return "employees:{$country}:list:{$page}:{$perPage}";
    }

    private function employeeKey(string $country, int $employeeId): string
    {
        return "employees:{$country}:{$employeeId}";
    }

    private function checklistKey(string $country): string
    {
        return "checklists:{$country}";
    }

    private function invalidatePattern(string $pattern): void
    {
        try {
            $store = Cache::getStore();
            if (!$store instanceof \Illuminate\Cache\RedisStore) {
                Log::warning("Cache driver is not Redis, cannot invalidate pattern: {$pattern}");
                return;
            }
            $redis = $store->getRedis()->connection();
            $prefix = config('cache.prefix', 'laravel_cache') . ':';
            $keys = $redis->keys($prefix . $pattern);

            if (!empty($keys)) {
                foreach ($keys as $key) {
                    // Remove the Redis prefix to get the cache key
                    $cacheKey = str_replace($prefix, '', $key);
                    Cache::forget($cacheKey);
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to invalidate cache pattern: {$pattern}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
