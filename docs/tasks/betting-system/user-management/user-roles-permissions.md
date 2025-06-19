# Phân Quyền Người Dùng

## Mục tiêu
Xây dựng hệ thống phân quyền linh hoạt cho nhiều loại người dùng khác nhau với các mức độ truy cập và chức năng phù hợp.

## Prerequisites
- Laravel authentication đã được thiết lập
- User model đã tồn tại
- Database đã sẵn sàng

## Cấu trúc Phân Quyền

### Các Role (Vai trò)
1. **Admin**: Quản trị viên hệ thống
2. **Moderator**: Quản lý nội dung và hỗ trợ người dùng
3. **Premium User**: Người dùng trả phí
4. **Basic User**: Người dùng miễn phí
5. **Trial User**: Người dùng dùng thử

### Các Permission (Quyền hạn)
1. **Campaign Management**
   - `create_campaign`: Tạo chiến dịch
   - `edit_campaign`: Chỉnh sửa chiến dịch
   - `delete_campaign`: Xóa chiến dịch
   - `view_campaign`: Xem chiến dịch
   - `share_campaign`: Chia sẻ chiến dịch công khai

2. **Betting Operations**
   - `place_manual_bet`: Đặt cược thủ công
   - `place_auto_bet`: Đặt cược tự động
   - `historical_testing`: Kiểm thử lịch sử
   - `unlimited_betting`: Đặt cược không giới hạn

3. **Financial Operations**
   - `deposit_money`: Nạp tiền
   - `withdraw_money`: Rút tiền
   - `view_transactions`: Xem lịch sử giao dịch

4. **Analytics & Reports**
   - `view_basic_analytics`: Xem thống kê cơ bản
   - `view_advanced_analytics`: Xem thống kê nâng cao
   - `export_reports`: Xuất báo cáo

5. **Social Features**
   - `follow_users`: Theo dõi người dùng khác
   - `comment_campaigns`: Bình luận chiến dịch
   - `rate_campaigns`: Đánh giá chiến dịch

6. **Administration**
   - `manage_users`: Quản lý người dùng
   - `manage_system`: Quản lý hệ thống
   - `view_all_campaigns`: Xem tất cả chiến dịch

## Các Bước Thực Hiện

### Bước 1: Cài đặt Spatie Permission Package
```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### Bước 2: Cấu hình User Model
```php
// app/Models/User.php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar',
        'subscription_type', 'subscription_expires_at',
        'balance', 'total_deposit', 'total_withdrawal',
        'is_active', 'last_login_at'
    ];

    protected $casts = [
        'subscription_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'balance' => 'decimal:2',
        'total_deposit' => 'decimal:2',
        'total_withdrawal' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Helper methods
    public function isPremium()
    {
        return $this->subscription_type === 'premium' 
            && $this->subscription_expires_at 
            && $this->subscription_expires_at->isFuture();
    }

    public function isBasic()
    {
        return $this->subscription_type === 'basic';
    }

    public function isTrial()
    {
        return $this->subscription_type === 'trial'
            && $this->subscription_expires_at
            && $this->subscription_expires_at->isFuture();
    }
}
```

### Bước 3: Tạo Seeder cho Roles và Permissions
```php
// database/seeders/RolePermissionSeeder.php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Campaign Management
            'create_campaign',
            'edit_campaign', 
            'delete_campaign',
            'view_campaign',
            'share_campaign',
            
            // Betting Operations
            'place_manual_bet',
            'place_auto_bet',
            'historical_testing',
            'unlimited_betting',
            
            // Financial Operations
            'deposit_money',
            'withdraw_money',
            'view_transactions',
            
            // Analytics & Reports
            'view_basic_analytics',
            'view_advanced_analytics',
            'export_reports',
            
            // Social Features
            'follow_users',
            'comment_campaigns',
            'rate_campaigns',
            
            // Administration
            'manage_users',
            'manage_system',
            'view_all_campaigns'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $moderatorRole = Role::create(['name' => 'moderator']);
        $moderatorRole->givePermissionTo([
            'view_all_campaigns',
            'manage_users',
            'view_advanced_analytics'
        ]);

        $premiumRole = Role::create(['name' => 'premium_user']);
        $premiumRole->givePermissionTo([
            'create_campaign',
            'edit_campaign',
            'delete_campaign',
            'view_campaign',
            'share_campaign',
            'place_manual_bet',
            'place_auto_bet',
            'historical_testing',
            'unlimited_betting',
            'deposit_money',
            'withdraw_money',
            'view_transactions',
            'view_basic_analytics',
            'view_advanced_analytics',
            'export_reports',
            'follow_users',
            'comment_campaigns',
            'rate_campaigns'
        ]);

        $basicRole = Role::create(['name' => 'basic_user']);
        $basicRole->givePermissionTo([
            'create_campaign',
            'edit_campaign',
            'delete_campaign',
            'view_campaign',
            'place_manual_bet',
            'historical_testing',
            'view_basic_analytics',
            'follow_users',
            'comment_campaigns',
            'rate_campaigns'
        ]);

        $trialRole = Role::create(['name' => 'trial_user']);
        $trialRole->givePermissionTo([
            'view_campaign',
            'place_manual_bet',
            'historical_testing',
            'view_basic_analytics'
        ]);
    }
}
```

### Bước 4: Tạo Middleware cho Permission
```php
// app/Http/Middleware/CheckPermission.php
class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!auth()->user()->can($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            abort(403, 'Bạn không có quyền thực hiện hành động này.');
        }

        return $next($request);
    }
}

// app/Http/Kernel.php - thêm vào $routeMiddleware
'permission' => \App\Http\Middleware\CheckPermission::class,
```

### Bước 5: Tạo Service quản lý Role/Permission
```php
// app/Services/UserRoleService.php
class UserRoleService
{
    public function assignRole(User $user, string $roleName)
    {
        // Remove existing role if any
        $user->syncRoles([]);
        
        // Assign new role
        $user->assignRole($roleName);
        
        // Update subscription info based on role
        $this->updateSubscriptionInfo($user, $roleName);
    }

    public function upgradeToRole(User $user, string $roleName, array $subscriptionData = [])
    {
        $this->assignRole($user, $roleName);
        
        if ($roleName === 'premium_user') {
            $user->update([
                'subscription_type' => 'premium',
                'subscription_expires_at' => $subscriptionData['expires_at'] ?? now()->addMonth()
            ]);
        }
    }

    public function checkRoleExpiration(User $user)
    {
        if ($user->isTrial() && $user->subscription_expires_at->isPast()) {
            $this->assignRole($user, 'basic_user');
            $user->update(['subscription_type' => 'basic']);
        }

        if ($user->isPremium() && $user->subscription_expires_at->isPast()) {
            $this->assignRole($user, 'basic_user');
            $user->update(['subscription_type' => 'basic']);
        }
    }

    private function updateSubscriptionInfo(User $user, string $roleName)
    {
        $subscriptionTypes = [
            'trial_user' => 'trial',
            'basic_user' => 'basic',
            'premium_user' => 'premium'
        ];

        if (isset($subscriptionTypes[$roleName])) {
            $user->update(['subscription_type' => $subscriptionTypes[$roleName]]);
        }
    }
}
```

### Bước 6: Tạo Policy cho Campaign
```php
// app/Policies/CampaignPolicy.php
class CampaignPolicy
{
    public function viewAny(User $user)
    {
        return $user->can('view_campaign');
    }

    public function view(User $user, Campaign $campaign)
    {
        return $user->can('view_campaign') && 
               ($campaign->user_id === $user->id || $campaign->is_public || $user->can('view_all_campaigns'));
    }

    public function create(User $user)
    {
        return $user->can('create_campaign');
    }

    public function update(User $user, Campaign $campaign)
    {
        return $user->can('edit_campaign') && $campaign->user_id === $user->id;
    }

    public function delete(User $user, Campaign $campaign)
    {
        return $user->can('delete_campaign') && $campaign->user_id === $user->id;
    }

    public function share(User $user, Campaign $campaign)
    {
        return $user->can('share_campaign') && $campaign->user_id === $user->id;
    }
}
```

### Bước 7: Cập nhật Controllers với Permission
```php
// app/Http/Controllers/CampaignController.php
class CampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:create_campaign')->only(['create', 'store']);
        $this->middleware('permission:edit_campaign')->only(['edit', 'update']);
        $this->middleware('permission:delete_campaign')->only(['destroy']);
    }

    public function index()
    {
        $this->authorize('viewAny', Campaign::class);
        // ...
    }

    public function store(Request $request)
    {
        // Check limits for non-premium users
        if (!auth()->user()->can('unlimited_betting')) {
            $campaignCount = auth()->user()->campaigns()->count();
            if ($campaignCount >= 5) {
                return back()->with('error', 'Bạn đã đạt giới hạn số chiến dịch. Nâng cấp lên Premium để tạo thêm.');
            }
        }
        
        // ...
    }
}
```

### Bước 8: Tạo Admin Panel quản lý User
```php
// app/Http/Controllers/Admin/UserManagementController.php
class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:manage_users']);
    }

    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->role, function($query, $role) {
                return $query->role($role);
            })
            ->when($request->search, function($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
            })
            ->paginate(20);

        $roles = Role::all();
        
        return view('admin.users.index', compact('users', 'roles'));
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        app(UserRoleService::class)->assignRole($user, $request->role);

        return back()->with('success', 'Cập nhật vai trò thành công');
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        
        return back()->with('success', 
            $user->is_active ? 'Kích hoạt tài khoản thành công' : 'Vô hiệu hóa tài khoản thành công'
        );
    }
}
```

### Bước 9: Tạo Views cho Permission Management
```blade
{{-- resources/views/admin/users/index.blade.php --}}
@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Quản Lý Người Dùng</h1>
        
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex">
                <select name="role" class="form-select me-2">
                    <option value="">Tất cả vai trò</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                        </option>
                    @endforeach
                </select>
                
                <input type="text" name="search" placeholder="Tìm kiếm..." 
                       value="{{ request('search') }}" class="form-control me-2">
                
                <button type="submit" class="btn btn-primary">Lọc</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Subscription</th>
                        <th>Số dư</th>
                        <th>Trạng thái</th>
                        <th>Đăng nhập cuối</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($user->avatar)
                                    <img src="{{ $user->avatar }}" class="rounded-circle me-2" width="32" height="32">
                                @endif
                                {{ $user->name }}
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @foreach($user->roles as $role)
                                <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                            @endforeach
                        </td>
                        <td>
                            <span class="badge bg-{{ $user->isPremium() ? 'success' : ($user->isTrial() ? 'warning' : 'secondary') }}">
                                {{ ucfirst($user->subscription_type ?? 'basic') }}
                            </span>
                            @if($user->subscription_expires_at)
                                <small class="d-block">{{ $user->subscription_expires_at->format('d/m/Y') }}</small>
                            @endif
                        </td>
                        <td>{{ number_format($user->balance) }}đ</td>
                        <td>
                            <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $user->last_login_at?->diffForHumans() ?? 'Chưa đăng nhập' }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="showRoleModal({{ $user->id }})">
                                    Đổi vai trò
                                </button>
                                <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-{{ $user->is_active ? 'danger' : 'success' }}">
                                        {{ $user->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{ $users->links() }}
</div>

{{-- Modal đổi vai trò --}}
<div class="modal fade" id="roleModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="roleForm">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Thay Đổi Vai Trò</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="role" class="form-label">Chọn vai trò mới</label>
                        <select name="role" id="role" class="form-select" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">
                                    {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRoleModal(userId) {
    const form = document.getElementById('roleForm');
    form.action = `/admin/users/${userId}/role`;
    
    const modal = new bootstrap.Modal(document.getElementById('roleModal'));
    modal.show();
}
</script>
@endsection
```

### Bước 10: Tạo Routes
```php
// routes/web.php
Route::middleware(['auth', 'permission:manage_users'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
    Route::patch('users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.update-role');
    Route::patch('users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');
});
```

## Command để quản lý Role/Permission

### Tạo Command kiểm tra và cập nhật role
```php
// app/Console/Commands/CheckUserSubscriptions.php
class CheckUserSubscriptions extends Command
{
    protected $signature = 'users:check-subscriptions';
    protected $description = 'Check and update user subscriptions';

    public function handle(UserRoleService $roleService)
    {
        $expiredUsers = User::where('subscription_expires_at', '<', now())
            ->whereIn('subscription_type', ['trial', 'premium'])
            ->get();

        foreach ($expiredUsers as $user) {
            $roleService->checkRoleExpiration($user);
            $this->info("Updated role for user: {$user->email}");
        }

        $this->info("Checked {$expiredUsers->count()} expired subscriptions");
    }
}
```

## Testing

### Unit Tests
```php
// tests/Unit/UserRoleServiceTest.php
class UserRoleServiceTest extends TestCase
{
    public function test_can_assign_role_to_user()
    {
        $user = User::factory()->create();
        $service = app(UserRoleService::class);
        
        $service->assignRole($user, 'premium_user');
        
        $this->assertTrue($user->hasRole('premium_user'));
        $this->assertEquals('premium', $user->subscription_type);
    }
}
```

### Feature Tests
```php
// tests/Feature/PermissionTest.php
class PermissionTest extends TestCase
{
    public function test_basic_user_cannot_create_unlimited_campaigns()
    {
        $user = User::factory()->create();
        $user->assignRole('basic_user');
        
        // Tạo 5 campaigns (limit for basic users)
        Campaign::factory()->count(5)->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user)
            ->post('/campaigns', [
                'name' => 'Test Campaign',
                // ... other data
            ]);
            
        $response->assertSessionHas('error');
    }
}
```

## Monitoring & Analytics

1. **Role Distribution Dashboard**: Theo dõi phân bố vai trò người dùng
2. **Permission Usage Analytics**: Thống kê sử dụng các quyền hạn
3. **Subscription Conversion**: Theo dõi chuyển đổi từ trial/basic lên premium

## Best Practices

1. **Principle of Least Privilege**: Chỉ cấp quyền tối thiểu cần thiết
2. **Regular Audit**: Định kỳ kiểm tra và làm sạch permissions
3. **Documentation**: Ghi chép rõ ràng về từng permission
4. **Testing**: Test kỹ các permission trong automated tests 
