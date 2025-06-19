# Phase 2: Advanced Features Setup Guide

## Tổng quan Phase 2

Phase 2 bao gồm các tính năng nâng cao:
- Real-time campaigns với auto-betting
- Social features (follow, share campaigns)
- Advanced risk management
- Leaderboard và analytics
- Real-time notifications

## Database Setup

### 1. Chạy Migrations

```bash
# Cấu hình database connection trong .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=xsmb_game
DB_USERNAME=root
DB_PASSWORD=your_password

# Clear cache và chạy migrations
php artisan config:clear
php artisan cache:clear
php artisan migrate
```

### 2. Các Tables Mới

- `campaign_auto_rules`: Rules tự động cho campaigns
- `social_follows`: Hệ thống follow users
- `campaign_shares`: Chia sẻ campaigns
- `risk_management_rules`: Quản lý rủi ro

## Models và Relationships

### Các Models Mới

1. **CampaignAutoRule**: Quản lý rules tự động
2. **SocialFollow**: Quan hệ follow giữa users
3. **CampaignShare**: Chia sẻ campaigns
4. **RiskManagementRule**: Quản lý rủi ro

### Relationships Được Thêm

- User có many RiskManagementRules
- User có many SocialFollows (followers/following)
- Campaign có many CampaignAutoRules
- Campaign có many CampaignShares

## Services Architecture

### 1. RealTimeCampaignService
```php
// Xử lý campaigns real-time
- processActiveCampaigns()
- executeAutoRules()
- placeBetByStrategy()
```

### 2. RiskManagementService
```php
// Quản lý rủi ro
- checkUserRisk()
- checkCampaignRisk()
- setupDefaultRiskRules()
```

### 3. SocialService
```php
// Tính năng xã hội
- followUser() / unfollowUser()
- shareCampaign()
- getLeaderboard()
- getSocialFeed()
```

## Controllers

### 1. SocialController
- Social feed và following
- User profiles và leaderboard
- Campaign sharing
- Search và recommendations

### 2. RiskManagementController
- CRUD risk rules
- Templates và statistics
- Real-time risk checking

## Background Jobs

### 1. ProcessRealTimeCampaignsJob
```bash
# Xử lý campaigns real-time
php artisan queue:work --queue=default
```

### 2. CheckRiskManagementJob
```bash
# Kiểm tra risk management
php artisan system:process-realtime --risk
```

## Scheduling

### Console Commands
```bash
# Thêm vào crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Schedule Configuration
- Real-time campaigns: Every 15 minutes (9 AM - 6 PM)
- Risk management: Every 30 minutes (Trading hours)
- Daily risk check: 6 AM

## Frontend Features

### 1. Social Dashboard
- Real-time feed của users theo dõi
- Top performers leaderboard
- User search và recommendations
- Campaign sharing tools

### 2. Risk Management Interface
- Visual risk status dashboard
- Rule creation với templates
- Real-time notifications
- Statistics và analytics

## API Endpoints

### Social APIs
```
GET /api/social/feed
GET /api/social/following-campaigns
GET /api/social/top-performers
GET /api/social/search-users
POST /social/follow/{user}
POST /campaigns/{campaign}/share
```

### Risk Management APIs
```
GET /api/risk-management/check
GET /api/risk-management/statistics
GET /api/risk-management/rule-types
POST /risk-management/setup-defaults
```

## Real-time Features

### 1. Auto-betting Strategies
- **Heatmap Strategy**: Bet theo điểm nóng
- **Streak Strategy**: Bet theo chuỗi dài ngày
- **Pattern Strategy**: Bet theo patterns
- **Hybrid Strategy**: Kết hợp nhiều chiến lược

### 2. Risk Management Types
- **Daily Loss Limit**: Giới hạn thua hàng ngày
- **Consecutive Loss Limit**: Giới hạn thua liên tiếp
- **Balance Threshold**: Ngưỡng số dư tối thiểu
- **Win Streak Protection**: Bảo vệ chuỗi thắng

## Configuration

### Environment Variables
```env
# Queue configuration
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Social features
SOCIAL_FEATURES_ENABLED=true
SHARE_TRACKING_ENABLED=true

# Risk management
RISK_MANAGEMENT_ENABLED=true
DEFAULT_RISK_RULES=true
```

### Queue Workers
```bash
# Khởi động queue workers
php artisan queue:work --queue=default --tries=3 --timeout=300

# Supervisor configuration
[program:xsmb-game-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work --sleep=3 --tries=3 --timeout=300
autostart=true
autorestart=true
numprocs=2
```

## Testing

### 1. Real-time System Testing
```bash
# Test real-time campaign processing
php artisan system:process-realtime --campaigns

# Test risk management
php artisan system:process-realtime --risk --user=1
```

### 2. Social Features Testing
```bash
# Test trong browser
# Tạo users mới, follow lẫn nhau
# Share campaigns, kiểm tra tracking
# Test leaderboard và feed
```

## Security Considerations

### 1. User Privacy
- Public/private campaign settings
- Follow permissions
- Data sharing controls

### 2. Risk Management
- Rate limiting cho API calls
- Validation cho rule parameters
- Audit logs cho risk actions

### 3. Share Tracking
- Anonymized analytics
- GDPR compliance
- User consent management

## Performance Optimization

### 1. Database Indexing
```sql
-- Đã tạo indexes cho:
CREATE INDEX idx_campaign_auto_rules_active ON campaign_auto_rules(campaign_id, is_active);
CREATE INDEX idx_social_follows_follower ON social_follows(follower_id);
CREATE INDEX idx_risk_rules_user_active ON risk_management_rules(user_id, is_active);
```

### 2. Caching Strategy
- Cache user relationships
- Cache leaderboard data
- Cache risk calculation results

### 3. Queue Management
- Separate queues cho different job types
- Priority queues cho critical operations
- Failed job monitoring

## Monitoring và Logging

### 1. Application Logs
```bash
# Monitor logs
tail -f storage/logs/laravel.log | grep "Risk\|Social\|RealTime"
```

### 2. Performance Monitoring
- Database query performance
- Job processing times
- API response times
- User engagement metrics

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Kiểm tra .env configuration
   - Verify database credentials
   - Check database server status

2. **Queue Jobs Not Processing**
   - Verify queue worker running
   - Check failed_jobs table
   - Monitor queue size

3. **Real-time Features Not Working**
   - Check schedule configuration
   - Verify cron setup
   - Monitor command execution

### Debug Commands
```bash
# Check migrations status
php artisan migrate:status

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Test queue
php artisan queue:failed
php artisan queue:retry all

# Check scheduled commands
php artisan schedule:list
```

## Phase 3 Preparation

Phase 2 tạo foundation cho Phase 3 features:
- Real-time notifications infrastructure
- Advanced analytics framework
- Mobile API endpoints
- Payment gateway integration
- Advanced trading algorithms

## Support

Để được hỗ trợ:
1. Check logs trong `storage/logs/`
2. Verify database connections
3. Test với sample data
4. Monitor job queues
5. Check API endpoints với Postman

---

**Lưu ý**: Phase 2 yêu cầu queue workers chạy liên tục để xử lý background jobs. Đảm bảo supervisor hoặc process manager được cấu hình đúng. 
