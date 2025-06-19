# Multi-Currency Support

## Tổng quan
Triển khai hệ thống hỗ trợ đa tiền tệ cho wallet system, cho phép người dùng giao dịch với nhiều loại tiền tệ khác nhau và tự động chuyển đổi tỷ giá.

## Mục tiêu
- Hỗ trợ multiple currencies (VND, USD, EUR, BTC, ETH)
- Real-time exchange rate updates
- Automatic currency conversion
- Multi-currency wallet management
- Currency-specific betting limits
- Comprehensive financial reporting

## Phân tích kỹ thuật

### Database Schema

#### Bảng currencies
```sql
CREATE TABLE currencies (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(3) NOT NULL UNIQUE, -- VND, USD, EUR, BTC, ETH
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    decimal_places TINYINT DEFAULT 2,
    is_crypto BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_currencies_active (is_active),
    INDEX idx_currencies_crypto (is_crypto)
);
```

#### Bảng exchange_rates
```sql
CREATE TABLE exchange_rates (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    from_currency VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    rate DECIMAL(20, 8) NOT NULL,
    source VARCHAR(50) NOT NULL, -- coinbase, binance, vietcombank, etc.
    is_active BOOLEAN DEFAULT true,
    fetched_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_currency_pair_source (from_currency, to_currency, source),
    INDEX idx_exchange_rates_pair (from_currency, to_currency),
    INDEX idx_exchange_rates_active (is_active),
    INDEX idx_exchange_rates_fetched (fetched_at),
    
    FOREIGN KEY (from_currency) REFERENCES currencies(code),
    FOREIGN KEY (to_currency) REFERENCES currencies(code)
);
```

#### Cập nhật bảng wallets
```sql
ALTER TABLE wallets ADD COLUMN currency VARCHAR(3) DEFAULT 'VND' AFTER user_id;
ALTER TABLE wallets ADD COLUMN is_primary BOOLEAN DEFAULT false AFTER currency;
ALTER TABLE wallets ADD INDEX idx_wallets_currency (currency);
ALTER TABLE wallets ADD INDEX idx_wallets_primary (user_id, is_primary);
ALTER TABLE wallets ADD FOREIGN KEY (currency) REFERENCES currencies(code);
```

#### Cập nhật bảng wallet_transactions
```sql
ALTER TABLE wallet_transactions ADD COLUMN currency VARCHAR(3) DEFAULT 'VND' AFTER amount;
ALTER TABLE wallet_transactions ADD COLUMN exchange_rate DECIMAL(20, 8) NULL AFTER currency;
ALTER TABLE wallet_transactions ADD COLUMN original_amount DECIMAL(15, 2) NULL AFTER exchange_rate;
ALTER TABLE wallet_transactions ADD COLUMN original_currency VARCHAR(3) NULL AFTER original_amount;
ALTER TABLE wallet_transactions ADD INDEX idx_transactions_currency (currency);
```

### Bước 1: Tạo Models

```php
// app/Models/Currency.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code', 'name', 'symbol', 'decimal_places', 
        'is_crypto', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'is_crypto' => 'boolean',
        'is_active' => 'boolean',
        'decimal_places' => 'integer',
        'sort_order' => 'integer'
    ];

    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;

    // Relationships
    public function wallets()
    {
        return $this->hasMany(Wallet::class, 'currency', 'code');
    }

    public function fromExchangeRates()
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency', 'code');
    }

    public function toExchangeRates()
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency', 'code');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFiat($query)
    {
        return $query->where('is_crypto', false);
    }

    public function scopeCrypto($query)
    {
        return $query->where('is_crypto', true);
    }

    public function scopeOrderBySort($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Methods
    public function formatAmount($amount)
    {
        return number_format($amount, $this->decimal_places) . ' ' . $this->symbol;
    }

    public function getDisplayNameAttribute()
    {
        return "{$this->name} ({$this->code})";
    }

    public function getTypeAttribute()
    {
        return $this->is_crypto ? 'Cryptocurrency' : 'Fiat Currency';
    }
}
```

```php
// app/Models/ExchangeRate.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency', 'to_currency', 'rate', 
        'source', 'is_active', 'fetched_at'
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'is_active' => 'boolean',
        'fetched_at' => 'datetime'
    ];

    // Relationships
    public function fromCurrency()
    {
        return $this->belongsTo(Currency::class, 'from_currency', 'code');
    }

    public function toCurrency()
    {
        return $this->belongsTo(Currency::class, 'to_currency', 'code');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRecent($query, $hours = 1)
    {
        return $query->where('fetched_at', '>=', Carbon::now()->subHours($hours));
    }

    public function scopeForPair($query, $from, $to)
    {
        return $query->where('from_currency', $from)->where('to_currency', $to);
    }

    public function scopeBestRate($query)
    {
        return $query->orderBy('rate', 'desc');
    }

    // Methods
    public function isStale($maxAgeHours = 1)
    {
        return $this->fetched_at->lt(Carbon::now()->subHours($maxAgeHours));
    }

    public function getPairNameAttribute()
    {
        return "{$this->from_currency}/{$this->to_currency}";
    }

    public function getFormattedRateAttribute()
    {
        return number_format($this->rate, 8);
    }

    public function convert($amount)
    {
        return $amount * $this->rate;
    }
}
```

### Bước 2: Currency Service

```php
// app/Services/CurrencyService.php
<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CurrencyService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const MAX_RATE_AGE_HOURS = 1;

    public function getAllCurrencies($activeOnly = true)
    {
        $query = Currency::orderBySort();
        
        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    public function getExchangeRate($fromCurrency, $toCurrency, $source = null)
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}" . ($source ? "_{$source}" : '');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($fromCurrency, $toCurrency, $source) {
            $query = ExchangeRate::forPair($fromCurrency, $toCurrency)
                ->active()
                ->recent(self::MAX_RATE_AGE_HOURS);

            if ($source) {
                $query->where('source', $source);
            }

            $rate = $query->orderBy('fetched_at', 'desc')->first();

            if (!$rate) {
                // Try to fetch fresh rate
                $this->fetchExchangeRate($fromCurrency, $toCurrency);
                
                // Try again
                $rate = ExchangeRate::forPair($fromCurrency, $toCurrency)
                    ->active()
                    ->orderBy('fetched_at', 'desc')
                    ->first();
            }

            return $rate ? $rate->rate : null;
        });
    }

    public function convertAmount($amount, $fromCurrency, $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        
        if (!$rate) {
            throw new \Exception("Exchange rate not available for {$fromCurrency} to {$toCurrency}");
        }

        return $amount * $rate;
    }

    public function convertAmountWithDetails($amount, $fromCurrency, $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return [
                'original_amount' => $amount,
                'converted_amount' => $amount,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'exchange_rate' => 1.0,
                'conversion_fee' => 0,
                'net_amount' => $amount
            ];
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        
        if (!$rate) {
            throw new \Exception("Exchange rate not available for {$fromCurrency} to {$toCurrency}");
        }

        $convertedAmount = $amount * $rate;
        $conversionFee = $this->calculateConversionFee($amount, $fromCurrency, $toCurrency);
        $netAmount = $convertedAmount - $conversionFee;

        return [
            'original_amount' => $amount,
            'converted_amount' => $convertedAmount,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'exchange_rate' => $rate,
            'conversion_fee' => $conversionFee,
            'net_amount' => $netAmount
        ];
    }

    public function fetchExchangeRate($fromCurrency, $toCurrency)
    {
        try {
            if ($this->isFiatPair($fromCurrency, $toCurrency)) {
                return $this->fetchFiatExchangeRate($fromCurrency, $toCurrency);
            }

            if ($this->isCryptoPair($fromCurrency, $toCurrency)) {
                return $this->fetchCryptoExchangeRate($fromCurrency, $toCurrency);
            }

            // Mixed fiat/crypto pair
            return $this->fetchMixedExchangeRate($fromCurrency, $toCurrency);
        } catch (\Exception $e) {
            Log::error("Failed to fetch exchange rate {$fromCurrency}/{$toCurrency}: " . $e->getMessage());
            return null;
        }
    }

    private function fetchFiatExchangeRate($fromCurrency, $toCurrency)
    {
        // Use VietComBank API for VND rates
        if ($fromCurrency === 'VND' || $toCurrency === 'VND') {
            return $this->fetchVietComBankRate($fromCurrency, $toCurrency);
        }

        // Use ExchangeRate-API for other fiat currencies
        return $this->fetchExchangeRateAPI($fromCurrency, $toCurrency);
    }

    private function fetchCryptoExchangeRate($fromCurrency, $toCurrency)
    {
        // Use CoinGecko API for crypto rates
        return $this->fetchCoinGeckoRate($fromCurrency, $toCurrency);
    }

    private function fetchMixedExchangeRate($fromCurrency, $toCurrency)
    {
        // Convert through USD as intermediate currency
        if ($fromCurrency !== 'USD' && $toCurrency !== 'USD') {
            $fromToUSD = $this->getExchangeRate($fromCurrency, 'USD');
            $USDtoTo = $this->getExchangeRate('USD', $toCurrency);
            
            if ($fromToUSD && $USDtoTo) {
                $rate = $fromToUSD * $USDtoTo;
                
                ExchangeRate::updateOrCreate([
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'source' => 'calculated'
                ], [
                    'rate' => $rate,
                    'is_active' => true,
                    'fetched_at' => now()
                ]);

                return $rate;
            }
        }

        return null;
    }

    private function fetchVietComBankRate($fromCurrency, $toCurrency)
    {
        // VietComBank exchange rate API implementation
        $response = Http::timeout(10)->get('https://portal.vietcombank.com.vn/Usercontrols/TVPortal.TyGia/pXML.aspx');
        
        if ($response->successful()) {
            $xml = simplexml_load_string($response->body());
            
            foreach ($xml->Exrate as $rate) {
                $currency = (string) $rate['CurrencyCode'];
                $sellRate = (float) str_replace(',', '', $rate['Sell']);
                $buyRate = (float) str_replace(',', '', $rate['Buy']);
                
                if ($fromCurrency === 'VND' && $toCurrency === $currency) {
                    $exchangeRate = 1 / $sellRate;
                } elseif ($fromCurrency === $currency && $toCurrency === 'VND') {
                    $exchangeRate = $buyRate;
                } else {
                    continue;
                }

                ExchangeRate::updateOrCreate([
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'source' => 'vietcombank'
                ], [
                    'rate' => $exchangeRate,
                    'is_active' => true,
                    'fetched_at' => now()
                ]);

                return $exchangeRate;
            }
        }

        return null;
    }

    private function fetchExchangeRateAPI($fromCurrency, $toCurrency)
    {
        $apiKey = config('services.exchangerate.api_key');
        $response = Http::timeout(10)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$fromCurrency}/{$toCurrency}");
        
        if ($response->successful()) {
            $data = $response->json();
            
            if ($data['result'] === 'success') {
                $rate = $data['conversion_rate'];
                
                ExchangeRate::updateOrCreate([
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'source' => 'exchangerate-api'
                ], [
                    'rate' => $rate,
                    'is_active' => true,
                    'fetched_at' => now()
                ]);

                return $rate;
            }
        }

        return null;
    }

    private function fetchCoinGeckoRate($fromCurrency, $toCurrency)
    {
        $fromId = $this->getCoinGeckoId($fromCurrency);
        $toId = $this->getCoinGeckoId($toCurrency);
        
        if (!$fromId || !$toId) {
            return null;
        }

        $response = Http::timeout(10)->get("https://api.coingecko.com/api/v3/simple/price", [
            'ids' => $fromId,
            'vs_currencies' => $toId
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data[$fromId][$toId])) {
                $rate = $data[$fromId][$toId];
                
                ExchangeRate::updateOrCreate([
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'source' => 'coingecko'
                ], [
                    'rate' => $rate,
                    'is_active' => true,
                    'fetched_at' => now()
                ]);

                return $rate;
            }
        }

        return null;
    }

    private function getCoinGeckoId($currency)
    {
        $mapping = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'USDT' => 'tether',
            'USDC' => 'usd-coin'
        ];

        return $mapping[$currency] ?? null;
    }

    public function calculateConversionFee($amount, $fromCurrency, $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return 0;
        }

        // Different fee structures based on currency types
        $fromCrypto = Currency::where('code', $fromCurrency)->value('is_crypto');
        $toCrypto = Currency::where('code', $toCurrency)->value('is_crypto');

        if ($fromCrypto && $toCrypto) {
            // Crypto to crypto: 0.1%
            return $amount * 0.001;
        }

        if ($fromCrypto || $toCrypto) {
            // Crypto to fiat or fiat to crypto: 0.5%
            return $amount * 0.005;
        }

        // Fiat to fiat: 0.2%
        return $amount * 0.002;
    }

    public function createUserWallet($userId, $currency)
    {
        // Check if user already has wallet for this currency
        $existingWallet = Wallet::where('user_id', $userId)
            ->where('currency', $currency)
            ->first();

        if ($existingWallet) {
            return $existingWallet;
        }

        // Create new wallet
        $wallet = Wallet::create([
            'user_id' => $userId,
            'currency' => $currency,
            'real_balance' => 0,
            'bonus_balance' => 0,
            'is_primary' => false
        ]);

        // Set as primary if it's the first wallet
        $walletCount = Wallet::where('user_id', $userId)->count();
        if ($walletCount === 1) {
            $wallet->update(['is_primary' => true]);
        }

        return $wallet;
    }

    public function getUserWallets($userId, $activeOnly = true)
    {
        $query = Wallet::where('user_id', $userId)
            ->with('currency');

        if ($activeOnly) {
            $query->whereHas('currency', function ($q) {
                $q->where('is_active', true);
            });
        }

        return $query->orderBy('is_primary', 'desc')
            ->orderBy('created_at')
            ->get();
    }

    public function getUserWalletBalances($userId, $baseCurrency = 'VND')
    {
        $wallets = $this->getUserWallets($userId);
        $totalBalance = 0;
        $balances = [];

        foreach ($wallets as $wallet) {
            $balance = $wallet->usable_balance;
            $convertedBalance = $this->convertAmount($balance, $wallet->currency, $baseCurrency);
            
            $balances[] = [
                'currency' => $wallet->currency,
                'balance' => $balance,
                'converted_balance' => $convertedBalance,
                'currency_symbol' => $wallet->currency_symbol ?? $wallet->currency,
                'is_primary' => $wallet->is_primary
            ];

            $totalBalance += $convertedBalance;
        }

        return [
            'total_balance' => $totalBalance,
            'base_currency' => $baseCurrency,
            'wallets' => $balances
        ];
    }

    private function isFiatPair($fromCurrency, $toCurrency)
    {
        $fromFiat = !Currency::where('code', $fromCurrency)->value('is_crypto');
        $toFiat = !Currency::where('code', $toCurrency)->value('is_crypto');
        
        return $fromFiat && $toFiat;
    }

    private function isCryptoPair($fromCurrency, $toCurrency)
    {
        $fromCrypto = Currency::where('code', $fromCurrency)->value('is_crypto');
        $toCrypto = Currency::where('code', $toCurrency)->value('is_crypto');
        
        return $fromCrypto && $toCrypto;
    }

    public function refreshAllExchangeRates()
    {
        $currencies = Currency::active()->get();
        $refreshed = 0;

        foreach ($currencies as $fromCurrency) {
            foreach ($currencies as $toCurrency) {
                if ($fromCurrency->code !== $toCurrency->code) {
                    try {
                        $rate = $this->fetchExchangeRate($fromCurrency->code, $toCurrency->code);
                        if ($rate) {
                            $refreshed++;
                        }
                    } catch (\Exception $e) {
                        Log::warning("Failed to refresh rate {$fromCurrency->code}/{$toCurrency->code}: " . $e->getMessage());
                    }
                }
            }
        }

        return $refreshed;
    }
}
```

### Bước 3: Multi-Currency Wallet Service

```php
// app/Services/MultiCurrencyWalletService.php
<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class MultiCurrencyWalletService
{
    private CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function transferBetweenCurrencies($userId, $fromCurrency, $toCurrency, $amount)
    {
        return DB::transaction(function () use ($userId, $fromCurrency, $toCurrency, $amount) {
            $fromWallet = Wallet::where('user_id', $userId)
                ->where('currency', $fromCurrency)
                ->firstOrFail();

            $toWallet = Wallet::where('user_id', $userId)
                ->where('currency', $toCurrency)
                ->first();

            if (!$toWallet) {
                $toWallet = $this->currencyService->createUserWallet($userId, $toCurrency);
            }

            // Check sufficient balance
            if ($fromWallet->usable_balance < $amount) {
                throw new \Exception('Insufficient balance for currency conversion');
            }

            // Get conversion details
            $conversion = $this->currencyService->convertAmountWithDetails($amount, $fromCurrency, $toCurrency);

            // Deduct from source wallet
            $fromWallet->decrement('real_balance', $amount);

            // Add to destination wallet (minus conversion fee)
            $toWallet->increment('real_balance', $conversion['net_amount']);

            // Record transactions
            $this->recordConversionTransactions($fromWallet, $toWallet, $conversion);

            return [
                'success' => true,
                'conversion' => $conversion,
                'from_wallet' => $fromWallet->fresh(),
                'to_wallet' => $toWallet->fresh()
            ];
        });
    }

    private function recordConversionTransactions($fromWallet, $toWallet, $conversion)
    {
        // Debit transaction
        WalletTransaction::create([
            'wallet_id' => $fromWallet->id,
            'type' => 'currency_conversion',
            'balance_type' => 'real',
            'amount' => -$conversion['original_amount'],
            'currency' => $conversion['from_currency'],
            'balance_before' => $fromWallet->real_balance + $conversion['original_amount'],
            'balance_after' => $fromWallet->real_balance,
            'description' => "Currency conversion to {$conversion['to_currency']}",
            'exchange_rate' => $conversion['exchange_rate'],
            'original_amount' => $conversion['converted_amount'],
            'original_currency' => $conversion['to_currency'],
            'status' => 'completed',
            'processed_at' => now()
        ]);

        // Credit transaction
        WalletTransaction::create([
            'wallet_id' => $toWallet->id,
            'type' => 'currency_conversion',
            'balance_type' => 'real',
            'amount' => $conversion['net_amount'],
            'currency' => $conversion['to_currency'],
            'balance_before' => $toWallet->real_balance - $conversion['net_amount'],
            'balance_after' => $toWallet->real_balance,
            'description' => "Currency conversion from {$conversion['from_currency']}",
            'exchange_rate' => 1 / $conversion['exchange_rate'],
            'original_amount' => $conversion['original_amount'],
            'original_currency' => $conversion['from_currency'],
            'status' => 'completed',
            'processed_at' => now()
        ]);

        // Conversion fee transaction (if applicable)
        if ($conversion['conversion_fee'] > 0) {
            WalletTransaction::create([
                'wallet_id' => $toWallet->id,
                'type' => 'conversion_fee',
                'balance_type' => 'real',
                'amount' => -$conversion['conversion_fee'],
                'currency' => $conversion['to_currency'],
                'balance_before' => $toWallet->real_balance,
                'balance_after' => $toWallet->real_balance,
                'description' => 'Currency conversion fee',
                'status' => 'completed',
                'processed_at' => now()
            ]);
        }
    }

    public function getConversionQuote($fromCurrency, $toCurrency, $amount)
    {
        try {
            return $this->currencyService->convertAmountWithDetails($amount, $fromCurrency, $toCurrency);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'success' => false
            ];
        }
    }

    public function processMultiCurrencyBetting($userId, $betAmount, $preferredCurrency, $campaignId)
    {
        return DB::transaction(function () use ($userId, $betAmount, $preferredCurrency, $campaignId) {
            // Get user's primary wallet
            $primaryWallet = Wallet::where('user_id', $userId)
                ->where('is_primary', true)
                ->first();

            if (!$primaryWallet) {
                throw new \Exception('No primary wallet found');
            }

            $actualAmount = $betAmount;
            $usedWallet = $primaryWallet;

            // If betting currency is different from primary wallet currency
            if ($primaryWallet->currency !== $preferredCurrency) {
                // Check if user has wallet in preferred currency
                $preferredWallet = Wallet::where('user_id', $userId)
                    ->where('currency', $preferredCurrency)
                    ->first();

                if ($preferredWallet && $preferredWallet->usable_balance >= $betAmount) {
                    // Use preferred currency wallet
                    $usedWallet = $preferredWallet;
                } else {
                    // Convert amount to primary wallet currency
                    $actualAmount = $this->currencyService->convertAmount(
                        $betAmount, 
                        $preferredCurrency, 
                        $primaryWallet->currency
                    );
                }
            }

            // Check sufficient balance
            if ($usedWallet->usable_balance < $actualAmount) {
                throw new \Exception('Insufficient balance for betting');
            }

            // Process betting deduction
            $this->deductForBetting($usedWallet, $actualAmount, $campaignId, $betAmount, $preferredCurrency);

            return [
                'wallet_used' => $usedWallet->fresh(),
                'deducted_amount' => $actualAmount,
                'bet_amount' => $betAmount,
                'bet_currency' => $preferredCurrency
            ];
        });
    }

    private function deductForBetting($wallet, $amount, $campaignId, $originalAmount = null, $originalCurrency = null)
    {
        // Use bonus balance first, then real balance
        $bonusUsed = min($amount, $wallet->bonus_balance);
        $realUsed = $amount - $bonusUsed;

        if ($bonusUsed > 0) {
            $wallet->decrement('bonus_balance', $bonusUsed);
            
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'campaign',
                'balance_type' => 'bonus',
                'amount' => -$bonusUsed,
                'currency' => $wallet->currency,
                'balance_before' => $wallet->bonus_balance + $bonusUsed,
                'balance_after' => $wallet->bonus_balance,
                'description' => 'Betting deduction from bonus balance',
                'related_id' => $campaignId,
                'related_type' => 'campaign',
                'original_amount' => $originalAmount,
                'original_currency' => $originalCurrency,
                'status' => 'completed',
                'processed_at' => now()
            ]);
        }

        if ($realUsed > 0) {
            $wallet->decrement('real_balance', $realUsed);
            
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'campaign',
                'balance_type' => 'real',
                'amount' => -$realUsed,
                'currency' => $wallet->currency,
                'balance_before' => $wallet->real_balance + $realUsed,
                'balance_after' => $wallet->real_balance,
                'description' => 'Betting deduction from real balance',
                'related_id' => $campaignId,
                'related_type' => 'campaign',
                'original_amount' => $originalAmount,
                'original_currency' => $originalCurrency,
                'status' => 'completed',
                'processed_at' => now()
            ]);
        }

        $wallet->update(['last_transaction_at' => now()]);
    }

    public function addMultiCurrencyWinnings($userId, $amount, $currency, $campaignId)
    {
        $wallet = Wallet::where('user_id', $userId)
            ->where('currency', $currency)
            ->first();

        if (!$wallet) {
            $wallet = $this->currencyService->createUserWallet($userId, $currency);
        }

        return DB::transaction(function () use ($wallet, $amount, $campaignId) {
            $wallet->increment('real_balance', $amount);

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'campaign',
                'balance_type' => 'real',
                'amount' => $amount,
                'currency' => $wallet->currency,
                'balance_before' => $wallet->real_balance - $amount,
                'balance_after' => $wallet->real_balance,
                'description' => 'Betting winnings',
                'related_id' => $campaignId,
                'related_type' => 'campaign',
                'status' => 'completed',
                'processed_at' => now()
            ]);

            $wallet->update(['last_transaction_at' => now()]);
            return $transaction;
        });
    }

    public function getMultiCurrencyPortfolio($userId, $baseCurrency = 'VND')
    {
        $wallets = $this->currencyService->getUserWallets($userId);
        $balances = $this->currencyService->getUserWalletBalances($userId, $baseCurrency);
        
        $portfolio = [
            'user_id' => $userId,
            'base_currency' => $baseCurrency,
            'total_balance' => $balances['total_balance'],
            'wallets' => [],
            'summary' => [
                'total_currencies' => $wallets->count(),
                'primary_currency' => $wallets->where('is_primary', true)->first()?->currency,
                'crypto_balance' => 0,
                'fiat_balance' => 0
            ]
        ];

        foreach ($balances['wallets'] as $balance) {
            $currency = Currency::where('code', $balance['currency'])->first();
            
            $portfolio['wallets'][] = [
                'currency_code' => $balance['currency'],
                'currency_name' => $currency->name,
                'currency_symbol' => $currency->symbol,
                'is_crypto' => $currency->is_crypto,
                'balance' => $balance['balance'],
                'converted_balance' => $balance['converted_balance'],
                'percentage' => $balances['total_balance'] > 0 ? 
                    round(($balance['converted_balance'] / $balances['total_balance']) * 100, 2) : 0,
                'is_primary' => $balance['is_primary']
            ];

            if ($currency->is_crypto) {
                $portfolio['summary']['crypto_balance'] += $balance['converted_balance'];
            } else {
                $portfolio['summary']['fiat_balance'] += $balance['converted_balance'];
            }
        }

        return $portfolio;
    }
}
```

### Bước 4: Controllers

```php
// app/Http/Controllers/CurrencyController.php
<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use App\Services\MultiCurrencyWalletService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    private CurrencyService $currencyService;
    private MultiCurrencyWalletService $walletService;

    public function __construct(
        CurrencyService $currencyService,
        MultiCurrencyWalletService $walletService
    ) {
        $this->currencyService = $currencyService;
        $this->walletService = $walletService;
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $portfolio = $this->walletService->getMultiCurrencyPortfolio($user->id);
        $availableCurrencies = $this->currencyService->getAllCurrencies();

        return view('wallet.multi-currency', compact('portfolio', 'availableCurrencies'));
    }

    public function getExchangeRate(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $amount = (float) $request->get('amount', 1);

        if (!$from || !$to) {
            return response()->json(['error' => 'Missing currency codes'], 400);
        }

        try {
            $conversion = $this->currencyService->convertAmountWithDetails($amount, $from, $to);
            return response()->json($conversion);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function convert(Request $request)
    {
        $request->validate([
            'from_currency' => 'required|exists:currencies,code',
            'to_currency' => 'required|exists:currencies,code',
            'amount' => 'required|numeric|min:0.01'
        ]);

        $user = $request->user();

        try {
            $result = $this->walletService->transferBetweenCurrencies(
                $user->id,
                $request->from_currency,
                $request->to_currency,
                $request->amount
            );

            return response()->json([
                'success' => true,
                'message' => 'Currency conversion completed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function createWallet(Request $request)
    {
        $request->validate([
            'currency' => 'required|exists:currencies,code'
        ]);

        $user = $request->user();

        try {
            $wallet = $this->currencyService->createUserWallet($user->id, $request->currency);

            return response()->json([
                'success' => true,
                'message' => 'Wallet created successfully',
                'wallet' => $wallet
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function portfolio(Request $request)
    {
        $user = $request->user();
        $baseCurrency = $request->get('base_currency', 'VND');
        
        $portfolio = $this->walletService->getMultiCurrencyPortfolio($user->id, $baseCurrency);

        return response()->json($portfolio);
    }
}
```

### Bước 5: Commands để cập nhật tỷ giá

```php
// app/Console/Commands/UpdateExchangeRates.php
<?php

namespace App\Console\Commands;

use App\Services\CurrencyService;
use Illuminate\Console\Command;

class UpdateExchangeRates extends Command
{
    protected $signature = 'currency:update-rates {--currencies=* : Specific currencies to update}';
    protected $description = 'Update exchange rates from external APIs';

    private CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        parent::__construct();
        $this->currencyService = $currencyService;
    }

    public function handle()
    {
        $this->info('Starting exchange rate update...');

        try {
            $refreshed = $this->currencyService->refreshAllExchangeRates();
            $this->info("Successfully refreshed {$refreshed} exchange rates.");
        } catch (\Exception $e) {
            $this->error('Failed to update exchange rates: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
```

## Validation và Security

### Security Considerations
1. **Rate Limiting**: Limit conversion requests per user
2. **Minimum Amounts**: Set minimum conversion amounts
3. **Fee Transparency**: Clear fee disclosure
4. **Rate Staleness**: Handle stale exchange rates gracefully

### Testing Strategy
1. **Unit Tests**: Test conversion calculations
2. **Integration Tests**: Test API integrations
3. **Feature Tests**: Test conversion workflows

## Kết luận

Hệ thống multi-currency cung cấp:
- ✅ Support for multiple fiat và crypto currencies
- ✅ Real-time exchange rate updates
- ✅ Automatic currency conversion
- ✅ Multi-currency wallet management
- ✅ Transparent fee structure
- ✅ Comprehensive portfolio tracking

**Thời gian ước tính**: 5 ngày
**Priority**: High
**Dependencies**: Wallet system, External APIs 
