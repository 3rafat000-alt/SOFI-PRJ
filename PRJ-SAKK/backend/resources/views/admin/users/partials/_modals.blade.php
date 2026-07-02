{{--
  Shared Audited Modals — Users module (Privacy-First / View-Only scope)
  Included by: show.blade.php, index.blade.php
  Kept:
    Modal A — Status Change (active|suspended only, reason required)
    Modal B — Bulk Confirm (activate|suspend)
    Modal E — KYC Doc Approve
    Modal F — KYC Doc Reject
--}}

{{-- ============================================================
     MODAL A — حالة الحساب (Status Change)
     ============================================================ --}}
<div
    x-data="statusModal()"
    @open-status-modal.window="open($event.detail)"
    x-show="show"
    x-cloak
    class="modal-backdrop"
    role="dialog"
    aria-modal="true"
    aria-labelledby="status-modal-title"
    @keydown.escape.window="if(show) close()"
>
    <div class="modal" @click.stop x-ref="modalPanel">
        <div class="modal-header">
            <h3 class="text-base font-extrabold" id="status-modal-title" style="color: var(--text-primary);">تعديل حالة الحساب</h3>
            <button type="button" @click="close()" class="btn btn-sm" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;background:transparent;border:none;color:var(--text-muted);cursor:pointer;border-radius:var(--radius-sm);" aria-label="إغلاق">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1.2rem;height:1.2rem;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:1rem;padding:0.65rem;background:var(--surface-hover);border-radius:var(--radius-sm);">
                <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:var(--primary);" aria-hidden="true">
                    <span style="color:#fff;font-size:0.8rem;font-weight:800;" x-text="userInitials"></span>
                </div>
                <div style="min-width:0;">
                    <p style="font-size:0.82rem;font-weight:700;color:var(--text-primary);" x-text="userName"></p>
                    <span class="badge" :class="currentStatusClass" x-text="currentStatusLabel"></span>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                <div>
                    <label for="status-modal-new-status" class="label label-required">الحالة الجديدة</label>
                    <select id="status-modal-new-status" x-model="newStatus" class="input" x-ref="firstFocus">
                        <option value="active">نشط</option>
                        <option value="suspended">موقوف</option>
                    </select>
                </div>
                <div>
                    <label for="status-modal-reason" class="label label-required">سبب التغيير</label>
                    <textarea id="status-modal-reason" x-model="reason" rows="3" class="input" :class="errors.reason ? 'input-error' : ''" placeholder="اكتب سبب التغيير…" aria-describedby="status-modal-reason-hint"></textarea>
                    <p id="status-modal-reason-hint" style="font-size:0.7rem;color:var(--text-muted);margin-top:0.25rem;">سيُحفظ السبب في سجل الأنشطة.</p>
                    <p x-show="errors.reason" style="font-size:0.75rem;font-weight:700;color:var(--danger);margin-top:0.25rem;" role="alert" aria-live="assertive" x-text="errors.reason"></p>
                </div>
                <div x-show="errorMsg" style="padding:0.65rem;font-size:0.82rem;font-weight:700;background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);" role="alert" aria-live="assertive" x-text="errorMsg"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" @click="close()" class="btn btn-secondary btn-sm">إلغاء</button>
            <button type="button" @click="submit()" class="btn btn-sm" :class="newStatus === 'suspended' ? 'btn-danger' : 'btn-success'" :disabled="loading">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;animation:spin 0.6s linear infinite;" x-show="loading"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36"/></svg>
                <span x-show="!loading">تأكيد</span>
            </button>
        </div>
    </div>
</div>

{{-- ============================================================
     MODAL B — إجراء جماعي (Bulk Confirm)
     ============================================================ --}}
<div
    x-data="bulkModal()"
    @open-bulk-modal.window="open($event.detail)"
    x-show="show"
    x-cloak
    class="modal-backdrop"
    role="dialog"
    aria-modal="true"
    aria-labelledby="bulk-modal-title"
    @keydown.escape.window="if(show) close()"
>
    <div class="modal" @click.stop>
        <div class="modal-header">
            <h3 class="text-base font-extrabold" id="bulk-modal-title" style="color: var(--text-primary);">
                <span x-text="action === 'activate' ? 'تفعيل' : 'إيقاف'"></span>
                <span x-text="' ' + userCount + ' مستخدم'"></span>
            </h3>
            <button type="button" @click="close()" class="btn btn-sm" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;background:transparent;border:none;color:var(--text-muted);cursor:pointer;border-radius:var(--radius-sm);" aria-label="إغلاق">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1.2rem;height:1.2rem;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom:0.75rem;padding:0.65rem;background:var(--surface-hover);border-radius:var(--radius-sm);display:flex;flex-direction:column;gap:0.25rem;">
                <template x-for="(name, idx) in previewNames" :key="idx">
                    <p style="font-size:0.82rem;font-weight:700;color:var(--text-primary);" x-text="name"></p>
                </template>
                <p x-show="extraCount > 0" style="font-size:0.75rem;color:var(--text-muted);">و <span x-text="extraCount"></span> آخرون</p>
            </div>
            <div style="height:1px;background:var(--border-light);margin:0.5rem 0;"></div>
            <div>
                <label for="bulk-modal-reason" class="label label-required">سبب الإجراء</label>
                <textarea id="bulk-modal-reason" x-model="reason" rows="3" class="input" :class="errors.reason ? 'input-error' : ''" placeholder="اكتب السبب…" x-ref="firstFocus"></textarea>
                <p x-show="errors.reason" style="font-size:0.75rem;font-weight:700;color:var(--danger);margin-top:0.25rem;" role="alert" aria-live="assertive" x-text="errors.reason"></p>
            </div>
            <div x-show="errorMsg" style="margin-top:0.65rem;padding:0.65rem;font-size:0.82rem;font-weight:700;background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);" role="alert" aria-live="assertive" x-text="errorMsg"></div>
        </div>
        <div class="modal-footer">
            <button type="button" @click="close()" class="btn btn-secondary btn-sm">إلغاء</button>
            <button type="button" @click="submit()" class="btn btn-sm" :class="action === 'activate' ? 'btn-primary' : 'btn-danger'" :disabled="loading">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;animation:spin 0.6s linear infinite;" x-show="loading"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36"/></svg>
                <span x-show="!loading">تأكيد</span>
            </button>
        </div>
    </div>
</div>

{{-- ============================================================
     MODAL E — قبول وثيقة KYC (Doc Approve)
     ============================================================ --}}
<div
    x-data="kycApproveModal()"
    @open-kyc-approve-modal.window="open($event.detail)"
    x-show="show"
    x-cloak
    class="modal-backdrop"
    role="dialog"
    aria-modal="true"
    aria-labelledby="kyc-approve-modal-title"
    @keydown.escape.window="if(show) close()"
>
    <div class="modal" @click.stop>
        <div class="modal-header">
            <h3 class="text-base font-extrabold" id="kyc-approve-modal-title" style="color: var(--text-primary);">قبول الوثيقة</h3>
            <button type="button" @click="close()" class="btn btn-sm" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;background:transparent;border:none;color:var(--text-muted);cursor:pointer;border-radius:var(--radius-sm);" aria-label="إغلاق">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1.2rem;height:1.2rem;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:flex;flex-direction:column;gap:0.65rem;">
            <div style="padding:0.65rem;background:var(--surface-hover);border-radius:var(--radius-sm);">
                <p style="font-size:0.7rem;color:var(--text-muted);">الوثيقة</p>
                <p style="font-size:0.82rem;font-weight:700;color:var(--text-primary);" x-text="docType"></p>
                <p x-show="docNumber" style="font-size:0.82rem;font-family:monospace;color:var(--text-secondary);" dir="ltr" x-text="docNumber"></p>
            </div>
            <div style="padding:0.65rem;background:rgba(181,138,60,0.08);border-radius:var(--radius-sm);">
                <p style="font-size:0.75rem;font-weight:700;color:#92400e;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:0.85rem;height:0.85rem;vertical-align:middle;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    قبول الوثيقة لا يرفع مستوى KYC تلقائياً.
                </p>
            </div>
            <div x-show="errorMsg" style="padding:0.65rem;font-size:0.82rem;font-weight:700;background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);" role="alert" aria-live="assertive" x-text="errorMsg"></div>
        </div>
        <div class="modal-footer">
            <button type="button" @click="close()" class="btn btn-secondary btn-sm">إلغاء</button>
            <button type="button" @click="submit()" class="btn btn-success btn-sm" x-ref="firstFocus" :disabled="loading">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;animation:spin 0.6s linear infinite;" x-show="loading"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36"/></svg>
                <span x-show="!loading">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;"><polyline points="20 6 9 17 4 12"/></svg>
                    قبول
                </span>
            </button>
        </div>
    </div>
</div>

{{-- ============================================================
     MODAL F — رفض وثيقة KYC (Doc Reject)
     ============================================================ --}}
<div
    x-data="kycRejectModal()"
    @open-kyc-reject-modal.window="open($event.detail)"
    x-show="show"
    x-cloak
    class="modal-backdrop"
    role="dialog"
    aria-modal="true"
    aria-labelledby="kyc-reject-modal-title"
    @keydown.escape.window="if(show) close()"
>
    <div class="modal" @click.stop>
        <div class="modal-header" style="background:var(--danger-light);">
            <h3 class="text-base font-extrabold" id="kyc-reject-modal-title" style="color: var(--danger);">رفض الوثيقة</h3>
            <button type="button" @click="close()" class="btn btn-sm" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;background:transparent;border:none;color:var(--text-muted);cursor:pointer;border-radius:var(--radius-sm);" aria-label="إغلاق">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1.2rem;height:1.2rem;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:flex;flex-direction:column;gap:0.65rem;">
            <div style="padding:0.65rem;background:var(--surface-hover);border-radius:var(--radius-sm);">
                <p style="font-size:0.7rem;color:var(--text-muted);">الوثيقة</p>
                <p style="font-size:0.82rem;font-weight:700;color:var(--text-primary);" x-text="docType"></p>
            </div>
            <div>
                <label for="kyc-reject-modal-reason" class="label label-required">سبب الرفض</label>
                <textarea id="kyc-reject-modal-reason" x-model="reason" rows="3" class="input" :class="errors.reason ? 'input-error' : ''" placeholder="اكتب سبب الرفض…" x-ref="firstFocus" aria-describedby="kyc-reject-reason-hint"></textarea>
                <p id="kyc-reject-reason-hint" style="font-size:0.7rem;color:var(--text-muted);margin-top:0.25rem;">سيُخطَر المستخدم بسبب الرفض</p>
                <p x-show="errors.reason" style="font-size:0.75rem;font-weight:700;color:var(--danger);margin-top:0.25rem;" role="alert" aria-live="assertive" x-text="errors.reason"></p>
            </div>
            <div x-show="errorMsg" style="padding:0.65rem;font-size:0.82rem;font-weight:700;background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);" role="alert" aria-live="assertive" x-text="errorMsg"></div>
        </div>
        <div class="modal-footer">
            <button type="button" @click="close()" class="btn btn-secondary btn-sm">إلغاء</button>
            <button type="button" @click="submit()" class="btn btn-danger btn-sm" :disabled="loading">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;animation:spin 0.6s linear infinite;" x-show="loading"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36"/></svg>
                <span x-show="!loading">رفض</span>
            </button>
        </div>
    </div>
</div>
