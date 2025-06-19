# Quản lý Profile Người dùng

## Tổng quan
Triển khai hệ thống quản lý profile người dùng toàn diện với khả năng tùy chỉnh thông tin cá nhân, avatar, preferences và security settings.

## Mục tiêu
- Cho phép người dùng quản lý thông tin cá nhân
- Avatar upload và crop functionality
- Privacy settings và visibility controls
- Profile completion tracking
- Social profile integration

## Phân tích kỹ thuật

### Database Schema

#### Bảng user_profiles
```sql
CREATE TABLE user_profiles (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    avatar VARCHAR(255),
    bio TEXT,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    country VARCHAR(2), -- ISO country code
    timezone VARCHAR(50),
    language VARCHAR(5) DEFAULT 'vi',
    profile_completion DECIMAL(3,2) DEFAULT 0.00,
    is_public BOOLEAN DEFAULT false,
    show_real_name BOOLEAN DEFAULT false,
    show_email BOOLEAN DEFAULT false,
    show_phone BOOLEAN DEFAULT false,
    last_profile_update TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_profiles_user_id (user_id),
    INDEX idx_user_profiles_public (is_public),
    INDEX idx_user_profiles_country (country)
);
```

#### Bảng user_social_links
```sql
CREATE TABLE user_social_links (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    platform VARCHAR(50) NOT NULL, -- facebook, twitter, linkedin, etc.
    username VARCHAR(100),
    url VARCHAR(255),
    is_verified BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_platform (user_id, platform),
    INDEX idx_social_links_user_id (user_id)
);
```

### Bước 1: Tạo Model

```php
// app/Models/UserProfile.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id', 'avatar', 'bio', 'phone', 'date_of_birth', 
        'gender', 'country', 'timezone', 'language',
        'is_public', 'show_real_name', 'show_email', 'show_phone'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_public' => 'boolean',
        'show_real_name' => 'boolean',
        'show_email' => 'boolean',
        'show_phone' => 'boolean',
        'last_profile_update' => 'datetime',
        'profile_completion' => 'decimal:2'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function socialLinks()
    {
        return $this->hasMany(UserSocialLink::class, 'user_id', 'user_id');
    }

    // Accessors
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }
        
        // Default avatar based on name initials
        $initials = strtoupper(substr($this->user->name, 0, 1));
        return "https://ui-avatars.com/api/?name={$initials}&background=3B82F6&color=ffffff&size=200";
    }

    public function getAgeAttribute()
    {
        if (!$this->date_of_birth) {
            return null;
        }
        
        return $this->date_of_birth->age;
    }

    public function getFormattedPhoneAttribute()
    {
        if (!$this->phone) {
            return null;
        }
        
        // Format phone number based on country
        return $this->formatPhoneNumber($this->phone, $this->country);
    }

    // Methods
    public function calculateCompletionPercentage()
    {
        $fields = [
            'avatar' => 15,
            'bio' => 10,
            'phone' => 10,
            'date_of_birth' => 10,
            'gender' => 5,
            'country' => 10,
            'timezone' => 5
        ];

        $completed = 35; // Base for having email and name from user table
        
        foreach ($fields as $field => $weight) {
            if (!empty($this->$field)) {
                $completed += $weight;
            }
        }

        // Bonus for social links
        if ($this->socialLinks()->count() > 0) {
            $completed += 10;
        }

        $this->update(['profile_completion' => min($completed, 100)]);
        return $completed;
    }

    private function formatPhoneNumber($phone, $country)
    {
        // Simple phone formatting - can be enhanced with libphonenumber
        if ($country === 'VN') {
            return preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1 $2 $3', $phone);
        }
        
        return $phone;
    }

    public function getVisibleFields($viewer = null)
    {
        $fields = [
            'name' => true, // Always visible
            'avatar' => true, // Always visible
            'bio' => $this->is_public
        ];

        if ($viewer && $viewer->id === $this->user_id) {
            // User viewing their own profile
            return array_merge($fields, [
                'email' => true,
                'phone' => true,
                'real_name' => true,
                'date_of_birth' => true,
                'gender' => true,
                'country' => true
            ]);
        }

        // Public visibility settings
        return array_merge($fields, [
            'email' => $this->show_email,
            'phone' => $this->show_phone,
            'real_name' => $this->show_real_name
        ]);
    }
}
```

```php
// app/Models/UserSocialLink.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSocialLink extends Model
{
    protected $fillable = [
        'user_id', 'platform', 'username', 'url', 'is_verified'
    ];

    protected $casts = [
        'is_verified' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIconAttribute()
    {
        $icons = [
            'facebook' => 'fab fa-facebook',
            'twitter' => 'fab fa-twitter',
            'instagram' => 'fab fa-instagram',
            'linkedin' => 'fab fa-linkedin',
            'youtube' => 'fab fa-youtube',
            'tiktok' => 'fab fa-tiktok'
        ];

        return $icons[$this->platform] ?? 'fas fa-link';
    }

    public function getDisplayNameAttribute()
    {
        return ucfirst($this->platform);
    }
}
```

### Bước 2: Service Layer

```php
// app/Services/UserProfileService.php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSocialLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class UserProfileService
{
    public function getOrCreateProfile(User $user): UserProfile
    {
        $profile = $user->profile;
        
        if (!$profile) {
            $profile = UserProfile::create([
                'user_id' => $user->id,
                'language' => app()->getLocale(),
                'timezone' => config('app.timezone')
            ]);
        }

        return $profile;
    }

    public function updateProfile(User $user, array $data): UserProfile
    {
        return DB::transaction(function () use ($user, $data) {
            $profile = $this->getOrCreateProfile($user);
            
            // Handle avatar upload
            if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
                $data['avatar'] = $this->uploadAvatar($data['avatar'], $user);
                
                // Delete old avatar
                if ($profile->avatar) {
                    Storage::delete($profile->avatar);
                }
            }

            // Update profile
            $profile->update($data);
            $profile->update(['last_profile_update' => now()]);
            
            // Recalculate completion percentage
            $profile->calculateCompletionPercentage();

            return $profile->fresh();
        });
    }

    public function uploadAvatar(UploadedFile $file, User $user): string
    {
        $filename = 'avatars/' . $user->id . '_' . time() . '.jpg';
        
        // Resize and optimize image
        $image = Image::make($file)
            ->fit(400, 400)
            ->encode('jpg', 85);
        
        Storage::put($filename, $image->stream());
        
        return $filename;
    }

    public function updateSocialLinks(User $user, array $links): void
    {
        DB::transaction(function () use ($user, $links) {
            // Delete existing links
            UserSocialLink::where('user_id', $user->id)->delete();
            
            // Create new links
            foreach ($links as $link) {
                if (!empty($link['username']) || !empty($link['url'])) {
                    UserSocialLink::create([
                        'user_id' => $user->id,
                        'platform' => $link['platform'],
                        'username' => $link['username'] ?? null,
                        'url' => $link['url'] ?? $this->generateSocialUrl($link['platform'], $link['username'] ?? ''),
                    ]);
                }
            }

            // Recalculate profile completion
            $user->profile->calculateCompletionPercentage();
        });
    }

    public function updatePrivacySettings(User $user, array $settings): UserProfile
    {
        $profile = $this->getOrCreateProfile($user);
        
        $allowedSettings = [
            'is_public', 'show_real_name', 'show_email', 'show_phone'
        ];

        $filteredSettings = array_intersect_key($settings, array_flip($allowedSettings));
        
        $profile->update($filteredSettings);
        
        return $profile;
    }

    public function getPublicProfile(User $user, User $viewer = null): array
    {
        $profile = $this->getOrCreateProfile($user);
        $visibleFields = $profile->getVisibleFields($viewer);
        
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $profile->avatar_url,
            'profile_completion' => $profile->profile_completion,
            'member_since' => $user->created_at->format('F Y')
        ];

        // Add fields based on visibility
        if ($visibleFields['bio']) {
            $data['bio'] = $profile->bio;
        }

        if ($visibleFields['email']) {
            $data['email'] = $user->email;
        }

        if ($visibleFields['phone']) {
            $data['phone'] = $profile->formatted_phone;
        }

        if ($visibleFields['real_name'] && $user->real_name) {
            $data['real_name'] = $user->real_name;
        }

        if ($visibleFields['country']) {
            $data['country'] = $profile->country;
        }

        // Add social links for public profiles
        if ($profile->is_public) {
            $data['social_links'] = $profile->socialLinks->map(function ($link) {
                return [
                    'platform' => $link->platform,
                    'username' => $link->username,
                    'url' => $link->url,
                    'icon' => $link->icon,
                    'display_name' => $link->display_name
                ];
            });
        }

        return $data;
    }

    public function searchPublicProfiles(string $query, int $limit = 20): array
    {
        $users = User::whereHas('profile', function ($q) {
                $q->where('is_public', true);
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->orWhereHas('profile', function ($profileQuery) use ($query) {
                      $profileQuery->where('bio', 'LIKE', "%{$query}%");
                  });
            })
            ->with('profile')
            ->limit($limit)
            ->get();

        return $users->map(function ($user) {
            return $this->getPublicProfile($user);
        })->toArray();
    }

    private function generateSocialUrl(string $platform, string $username): ?string
    {
        if (empty($username)) {
            return null;
        }

        $urls = [
            'facebook' => "https://facebook.com/{$username}",
            'twitter' => "https://twitter.com/{$username}",
            'instagram' => "https://instagram.com/{$username}",
            'linkedin' => "https://linkedin.com/in/{$username}",
            'youtube' => "https://youtube.com/@{$username}",
            'tiktok' => "https://tiktok.com/@{$username}"
        ];

        return $urls[$platform] ?? null;
    }

    public function getProfileStats(User $user): array
    {
        $profile = $this->getOrCreateProfile($user);
        
        return [
            'completion_percentage' => $profile->profile_completion,
            'missing_fields' => $this->getMissingFields($profile),
            'public_visibility' => $profile->is_public,
            'social_links_count' => $profile->socialLinks()->count(),
            'last_updated' => $profile->last_profile_update?->diffForHumans()
        ];
    }

    private function getMissingFields(UserProfile $profile): array
    {
        $fields = [
            'avatar' => 'Ảnh đại diện',
            'bio' => 'Tiểu sử',
            'phone' => 'Số điện thoại',
            'date_of_birth' => 'Ngày sinh',
            'gender' => 'Giới tính',
            'country' => 'Quốc gia'
        ];

        $missing = [];
        foreach ($fields as $field => $label) {
            if (empty($profile->$field)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }
}
```

### Bước 3: Controller

```php
// app/Http/Controllers/UserProfileController.php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdatePrivacySettingsRequest;
use App\Http\Requests\UpdateSocialLinksRequest;
use App\Services\UserProfileService;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    private UserProfileService $profileService;

    public function __construct(UserProfileService $profileService)
    {
        $this->profileService = $profileService;
        $this->middleware('auth');
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $this->profileService->getOrCreateProfile($user);
        $stats = $this->profileService->getProfileStats($user);

        return view('profile.show', compact('user', 'profile', 'stats'));
    }

    public function edit(Request $request)
    {
        $user = $request->user();
        $profile = $this->profileService->getOrCreateProfile($user);
        
        return view('profile.edit', compact('user', 'profile'));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        
        try {
            $profile = $this->profileService->updateProfile($user, $request->validated());
            
            return redirect()->route('profile.show')
                ->with('success', 'Profile đã được cập nhật thành công.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Có lỗi xảy ra khi cập nhật profile.']);
        }
    }

    public function updatePrivacy(UpdatePrivacySettingsRequest $request)
    {
        $user = $request->user();
        
        $this->profileService->updatePrivacySettings($user, $request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Cài đặt riêng tư đã được cập nhật.'
        ]);
    }

    public function updateSocialLinks(UpdateSocialLinksRequest $request)
    {
        $user = $request->user();
        
        $this->profileService->updateSocialLinks($user, $request->validated()['links']);
        
        return response()->json([
            'success' => true,
            'message' => 'Liên kết mạng xã hội đã được cập nhật.'
        ]);
    }

    public function publicProfile(Request $request, $userId)
    {
        $user = \App\Models\User::findOrFail($userId);
        $viewer = $request->user();
        
        $profileData = $this->profileService->getPublicProfile($user, $viewer);
        
        return view('profile.public', compact('profileData', 'user'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $profiles = $this->profileService->searchPublicProfiles($query);
        
        return response()->json($profiles);
    }
}
```

### Bước 4: Form Requests

```php
// app/Http/Requests/UpdateProfileRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'bio' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20|regex:/^[\d\+\-\(\)\s]+$/',
            'date_of_birth' => 'nullable|date|before:today|after:1900-01-01',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'country' => 'nullable|string|size:2',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|size:2'
        ];
    }

    public function messages()
    {
        return [
            'avatar.image' => 'Avatar phải là file hình ảnh.',
            'avatar.max' => 'Avatar không được vượt quá 2MB.',
            'bio.max' => 'Tiểu sử không được vượt quá 500 ký tự.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'date_of_birth.before' => 'Ngày sinh phải trước ngày hôm nay.',
            'date_of_birth.after' => 'Ngày sinh không hợp lệ.'
        ];
    }
}
```

### Bước 5: Blade Templates

```php
{{-- resources/views/profile/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Profile của tôi')

@section('content')
<div class="max-w-4xl mx-auto py-6">
    <!-- Profile Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center space-x-6">
            <div class="relative">
                <img src="{{ $profile->avatar_url }}" 
                     alt="Avatar" 
                     class="w-24 h-24 rounded-full object-cover">
                @if($stats['completion_percentage'] == 100)
                    <div class="absolute -top-1 -right-1 bg-green-500 text-white rounded-full p-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                @endif
            </div>
            
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                @if($profile->bio)
                    <p class="text-gray-600 mt-1">{{ $profile->bio }}</p>
                @endif
                <div class="flex items-center mt-2 space-x-4 text-sm text-gray-500">
                    <span>Tham gia {{ $user->created_at->format('M Y') }}</span>
                    @if($profile->country)
                        <span>• {{ $profile->country }}</span>
                    @endif
                </div>
            </div>
            
            <div class="text-right">
                <div class="text-sm text-gray-500 mb-2">Hoàn thành profile</div>
                <div class="flex items-center space-x-2">
                    <div class="w-16 bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" 
                             style="width: {{ $stats['completion_percentage'] }}%"></div>
                    </div>
                    <span class="text-sm font-semibold">{{ $stats['completion_percentage'] }}%</span>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex space-x-3 mt-6">
            <a href="{{ route('profile.edit') }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Chỉnh sửa Profile
            </a>
            @if($profile->is_public)
                <a href="{{ route('profile.public', $user->id) }}" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    Xem Profile công khai
                </a>
            @endif
        </div>
    </div>

    <!-- Profile Completion Tips -->
    @if($stats['completion_percentage'] < 100)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-yellow-800 mb-2">Hoàn thiện profile của bạn</h3>
            <p class="text-yellow-700 text-sm mb-3">
                Hoàn thiện profile giúp bạn kết nối tốt hơn với cộng đồng. Còn thiếu:
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach($stats['missing_fields'] as $field)
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">{{ $field }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-2">Visibility</h3>
            <p class="text-sm text-gray-600 mb-3">
                Profile của bạn {{ $profile->is_public ? 'công khai' : 'riêng tư' }}
            </p>
            <button onclick="toggleVisibility()" 
                    class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                {{ $profile->is_public ? 'Chuyển riêng tư' : 'Chuyển công khai' }}
            </button>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-2">Social Links</h3>
            <p class="text-sm text-gray-600 mb-3">
                {{ $stats['social_links_count'] }} liên kết mạng xã hội
            </p>
            <a href="{{ route('profile.edit') }}#social" 
               class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                Quản lý liên kết
            </a>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-2">Last Updated</h3>
            <p class="text-sm text-gray-600">
                {{ $stats['last_updated'] ?? 'Chưa cập nhật' }}
            </p>
        </div>
    </div>
</div>

<script>
function toggleVisibility() {
    fetch('{{ route('profile.privacy') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            is_public: {{ $profile->is_public ? 'false' : 'true' }}
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>
@endsection
```

## Validation và Security

### Security Considerations
1. **File Upload Security**: Validate file types và scan for malware
2. **Privacy Controls**: Respect user privacy settings
3. **Data Sanitization**: Clean user input
4. **Rate Limiting**: Limit profile updates

### Testing Strategy
1. **Unit Tests**: Test profile completion calculation
2. **Feature Tests**: Test profile update workflows
3. **Security Tests**: Test file upload security

## Kết luận

Hệ thống quản lý profile cung cấp:
- ✅ Profile management hoàn chỉnh
- ✅ Avatar upload với image processing
- ✅ Privacy controls chi tiết
- ✅ Social media integration
- ✅ Profile completion tracking
- ✅ Public profile search

**Thời gian ước tính**: 2 ngày
**Priority**: High
**Dependencies**: User authentication system 
