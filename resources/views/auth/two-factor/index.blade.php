@extends('layouts.app')

@section('title', 'Bảo mật hai yếu tố (2FA)')

@section('content')
<div class="container mx-auto px-4 py-8"
     x-data="twoFactorAuth({
        isEnabled: {{ $two_factor_enabled ? 'true' : 'false' }},
        phoneVerified: {{ $phone_verified ? 'true' : 'false' }},
        recoveryCodesCount: {{ $recovery_codes_count }},
        availableMethods: {{ json_encode($available_methods) }}
     })">

    {{-- Page Header --}}
    <div class="compass-fade-in mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Bảo mật hai yếu tố (2FA)</h1>
        <p class="mt-1 text-gray-600">Tăng cường bảo vệ cho tài khoản của bạn.</p>
    </div>

    <div class="compass-card max-w-4xl mx-auto compass-slide-up">
        <div class="compass-card-body">
            {{-- STATE: 2FA IS ENABLED --}}
            <template x-if="isEnabled">
                <div class="space-y-6">
                    <div class="compass-alert-success">
                        <h4 class="font-bold">2FA đã được kích hoạt!</h4>
                        <p>Tài khoản của bạn hiện đang được bảo vệ bởi một lớp bảo mật bổ sung.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {{-- Security Status --}}
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-800">Trạng thái bảo mật</h3>
                            <ul class="space-y-3">
                                <li class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        <span class="font-medium">Authenticator App (TOTP)</span>
                                    </div>
                                    <span class="compass-badge-success">Đã kích hoạt</span>
                                </li>
                                <li class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                         <svg class="w-5 h-5 mr-3" :class="phoneVerified ? 'text-green-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                        <span class="font-medium">SMS Backup</span>
                                    </div>
                                    <span :class="phoneVerified ? 'compass-badge-success' : 'compass-badge-secondary'" x-text="phoneVerified ? 'Đã xác thực' : 'Chưa có'"></span>
                                </li>
                                <li class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <span class="font-medium">Mã khôi phục</span>
                                    </div>
                                    <span class="compass-badge-info" x-text="`${recoveryCodesCount} codes`"></span>
                                </li>
                            </ul>
                        </div>
                        {{-- Actions --}}
                        <div class="space-y-4">
                             <h3 class="text-lg font-semibold text-gray-800">Hành động</h3>
                             <div class="flex flex-col space-y-3">
                                <button @click="showGenerateRecoveryCodesModal = true" class="compass-btn-secondary justify-center">Tạo mã khôi phục mới</button>
                                <button @click="showDisableModal = true" class="compass-btn-danger justify-center">Tắt bảo mật hai yếu tố</button>
                             </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- STATE: 2FA IS DISABLED --}}
            <template x-if="!isEnabled">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-8 items-center">
                    <div class="md:col-span-3 space-y-6">
                        <div class="compass-alert-warning">
                            <h4 class="font-bold">Tài khoản của bạn chưa được bảo vệ!</h4>
                            <p>Kích hoạt bảo mật hai yếu tố (2FA) là cách tốt nhất để giữ an toàn cho tài khoản của bạn.</p>
                        </div>

                        <div class="prose prose-sm max-w-none">
                            <p>Bằng cách thêm một lớp bảo mật nữa, bạn có thể ngăn chặn các truy cập trái phép ngay cả khi mật khẩu của bạn bị lộ.</p>
                            <ul>
                                <li>Bảo vệ tài sản trong ví.</li>
                                <li>Ngăn chặn thay đổi thông tin trái phép.</li>
                                <li>Yên tâm hơn khi giao dịch.</li>
                            </ul>
                        </div>
                         <button @click="setup2FA()" class="compass-btn-success w-full md:w-auto">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 20.944A12.02 12.02 0 0012 22a12.02 12.02 0 008.618-3.04 11.955 11.955 0 01-2.298-9.056z"></path></svg>
                            Kích hoạt 2FA ngay
                        </button>
                    </div>
                    <div class="md:col-span-2 space-y-3">
                        <h4 class="font-semibold text-gray-700">Các phương thức hỗ trợ:</h4>
                        <div class="border border-gray-200 rounded-lg divide-y divide-gray-200">
                             <div class="p-3 flex items-center">
                                <svg class="w-6 h-6 text-primary-600 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                <div>
                                    <p class="font-semibold">Authenticator App</p>
                                    <p class="text-sm text-gray-500">Google Authenticator, Authy...</p>
                                </div>
                            </div>
                            <div class="p-3 flex items-center">
                                <svg class="w-6 h-6 text-primary-600 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                <div>
                                    <p class="font-semibold">SMS / Email Backup</p>
                                    <p class="text-sm text-gray-500">Nhận mã dự phòng khi cần</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Setup 2FA Modal --}}
    <div x-show="showSetupModal" class="compass-modal" style="display: none;">
        <div class="compass-modal-overlay" @click="closeSetupModal()"></div>
        <div class="compass-modal-content">
            <div class="compass-modal-header">
                <h3 class="text-lg font-semibold" x-text="setupStep === 'qr' ? 'Bước 1: Quét mã QR' : 'Bước 2: Xác thực mã'"></h3>
                <button @click="closeSetupModal()" class="compass-modal-close-btn">&times;</button>
            </div>
            <div class="compass-modal-body">
                <div x-show="setupStep === 'qr'">
                    <p class="text-center text-gray-600 mb-4">Sử dụng ứng dụng Authenticator (Google Authenticator, Authy, ...) để quét mã này.</p>
                    <div class="flex justify-center" x-html="qrCode"></div>
                    <div class="mt-4 text-center">
                        <p class="text-sm text-gray-500">Không quét được? Nhập thủ công:</p>
                        <code class="compass-code-block" x-text="manualKey"></code>
                    </div>
                </div>
                <div x-show="setupStep === 'verify'">
                     <p class="text-center text-gray-600 mb-4">Nhập mã gồm 6 chữ số từ ứng dụng Authenticator của bạn.</p>
                     <input type="text" class="compass-input text-2xl tracking-widest text-center" x-model="verificationCode" placeholder="_ _ _ _ _ _" maxlength="6">
                     <p x-show="errorMessage" class="text-sm text-red-600 text-center mt-2" x-text="errorMessage"></p>
                </div>
            </div>
            <div class="compass-modal-footer">
                <button @click="closeSetupModal()" class="compass-btn-secondary">Hủy</button>
                <button @click="setupStep === 'qr' ? setupStep = 'verify' : confirm2FA()" class="compass-btn-primary" x-text="setupStep === 'qr' ? 'Đã quét, tiếp tục' : 'Kích hoạt'"></button>
            </div>
        </div>
    </div>

    {{-- Disable 2FA Modal --}}
    <div x-show="showDisableModal" class="compass-modal" style="display: none;">
        <div class="compass-modal-overlay" @click="showDisableModal = false"></div>
        <div class="compass-modal-content max-w-md">
            <div class="compass-modal-header">
                <h3 class="text-lg font-semibold">Tắt bảo mật hai yếu tố?</h3>
                <button @click="showDisableModal = false" class="compass-modal-close-btn">&times;</button>
            </div>
            <div class="compass-modal-body">
                 <div class="compass-alert-danger">
                    <h4 class="font-bold">Hành động nguy hiểm!</h4>
                    <p>Tắt 2FA sẽ làm giảm đáng kể mức độ bảo mật cho tài khoản của bạn.</p>
                </div>
                <div class="mt-4">
                    <label for="disable-password" class="compass-label">Nhập mật khẩu của bạn để xác nhận:</label>
                    <input type="password" id="disable-password" class="compass-input" x-model="disablePassword" placeholder="••••••••">
                    <p x-show="errorMessage" class="text-sm text-red-600 mt-2" x-text="errorMessage"></p>
                </div>
            </div>
            <div class="compass-modal-footer">
                <button @click="showDisableModal = false" class="compass-btn-secondary">Hủy</button>
                <button @click="disable2FA()" class="compass-btn-danger">Xác nhận tắt 2FA</button>
            </div>
        </div>
    </div>

    {{-- Generate Recovery Codes Modal --}}
    <div x-show="showGenerateRecoveryCodesModal" class="compass-modal" style="display: none;">
        <div class="compass-modal-overlay" @click="showGenerateRecoveryCodesModal = false"></div>
        <div class="compass-modal-content max-w-md">
            <div class="compass-modal-header">
                <h3 class="text-lg font-semibold">Tạo mã khôi phục mới</h3>
                <button @click="showGenerateRecoveryCodesModal = false" class="compass-modal-close-btn">&times;</button>
            </div>
            <div class="compass-modal-body">
                 <p class="text-gray-600 mb-4">Việc này sẽ vô hiệu hóa các mã khôi phục cũ của bạn. Vui lòng nhập mật khẩu để tiếp tục.</p>
                <div>
                    <label for="generate-password" class="compass-label">Mật khẩu:</label>
                    <input type="password" id="generate-password" class="compass-input" x-model="generatePassword" placeholder="••••••••">
                    <p x-show="errorMessage" class="text-sm text-red-600 mt-2" x-text="errorMessage"></p>
                </div>
            </div>
            <div class="compass-modal-footer">
                <button @click="showGenerateRecoveryCodesModal = false" class="compass-btn-secondary">Hủy</button>
                <button @click="generateRecoveryCodes()" class="compass-btn-primary">Tạo mã mới</button>
            </div>
        </div>
    </div>

     {{-- Recovery Codes Display Modal --}}
    <div x-show="showRecoveryCodesModal" class="compass-modal" style="display: none;">
        <div class="compass-modal-overlay" @click="showRecoveryCodesModal = false"></div>
        <div class="compass-modal-content max-w-md">
            <div class="compass-modal-header">
                <h3 class="text-lg font-semibold">Lưu trữ các mã khôi phục này!</h3>
                 <button @click="showRecoveryCodesModal = false" class="compass-modal-close-btn">&times;</button>
            </div>
            <div class="compass-modal-body">
                 <p class="text-gray-600 mb-4">Hãy lưu các mã này ở một nơi an toàn. Bạn sẽ cần chúng để truy cập tài khoản nếu mất quyền truy cập vào thiết bị xác thực.</p>
                 <div class="bg-gray-100 p-4 rounded-lg space-y-2">
                    <template x-for="code in recoveryCodes" :key="code">
                        <code class="block text-center font-mono text-lg tracking-widest" x-text="code"></code>
                    </template>
                 </div>
            </div>
            <div class="compass-modal-footer">
                <button @click="copyRecoveryCodes()" class="compass-btn-secondary">Sao chép</button>
                <button @click="showRecoveryCodesModal = false" class="compass-btn-primary">Đã lưu, đóng</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function twoFactorAuth(config) {
    return {
        // Initial state from backend
        isEnabled: config.isEnabled,
        phoneVerified: config.phoneVerified,
        recoveryCodesCount: config.recoveryCodesCount,
        availableMethods: config.availableMethods,

        // Modal visibility
        showSetupModal: false,
        showDisableModal: false,
        showGenerateRecoveryCodesModal: false,
        showRecoveryCodesModal: false,

        // Setup process state
        setupStep: 'qr', // 'qr' or 'verify'
        qrCode: '',
        manualKey: '',
        verificationCode: '',

        // Action state
        disablePassword: '',
        generatePassword: '',
        recoveryCodes: [],
        errorMessage: '',

        // Methods
        async apiCall(url, method = 'POST', body = {}) {
            this.errorMessage = '';
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                });

                const data = await response.json();

                if (!response.ok) {
                    this.errorMessage = data.message || `Lỗi ${response.status}`;
                    return { success: false, data: null };
                }
                return { success: true, data: data };
            } catch (error) {
                this.errorMessage = 'Lỗi kết nối. Vui lòng thử lại.';
                return { success: false, data: null };
            }
        },

        async setup2FA() {
            const { success, data } = await this.apiCall('/two-factor/enable-totp');
            if (success) {
                this.qrCode = `<img src=\"/two-factor/qr-image?${Date.now()}\" alt=\"QR Code\">`;
                this.manualKey = data.data.manual_entry_key;
                this.setupStep = 'qr';
                this.verificationCode = '';
                this.showSetupModal = true;
            }
        },

        async confirm2FA() {
            const { success, data } = await this.apiCall('/two-factor/confirm-totp', 'POST', { code: this.verificationCode });
            if(success) {
                this.isEnabled = true;
                this.recoveryCodes = data.recovery_codes;
                this.recoveryCodesCount = data.recovery_codes.length;
                this.closeSetupModal();
                this.showRecoveryCodesModal = true;
            }
        },

        async disable2FA() {
            const { success } = await this.apiCall('/two-factor/disable', 'DELETE', { password: this.disablePassword });
            if(success) {
                this.isEnabled = false;
                this.showDisableModal = false;
                this.disablePassword = '';
            }
        },

        async generateRecoveryCodes() {
            const { success, data } = await this.apiCall('/two-factor/recovery-codes', 'POST', { password: this.generatePassword });
            if(success) {
                this.recoveryCodes = data.recovery_codes;
                this.recoveryCodesCount = data.recovery_codes.length;
                this.showGenerateRecoveryCodesModal = false;
                this.generatePassword = '';
                this.showRecoveryCodesModal = true;
            }
        },

        copyRecoveryCodes() {
            navigator.clipboard.writeText(this.recoveryCodes.join('\\n')).then(() => {
                alert('Đã sao chép mã khôi phục!');
            });
        },

        closeSetupModal() {
            this.showSetupModal = false;
            // reset state
            setTimeout(() => {
                this.qrCode = '';
                this.manualKey = '';
                this.verificationCode = '';
                this.setupStep = 'qr';
                this.errorMessage = '';
            }, 300); // delay to allow modal to close gracefully
        }
    }
}
</script>
@endpush
