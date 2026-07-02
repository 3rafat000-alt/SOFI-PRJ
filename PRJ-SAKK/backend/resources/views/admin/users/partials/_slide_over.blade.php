{{--
  Slide-Over Quick-View Panel (Panel Q)
  Included by: index.blade.php
  Uses Alpine component: quickViewPanel() defined in index @push('scripts')
--}}
<div
    x-data="quickViewPanel()"
    @open-quick-view.window="open($event.detail)"
    @keydown.escape.window="if(show) close()"
    x-cloak
>
    {{-- Backdrop --}}
    <div
        class="slide-over-backdrop"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="close()"
        aria-hidden="true"
    ></div>

    {{-- Panel --}}
    <div
        class="slide-over"
        :aria-hidden="show ? 'false' : 'true'"
        role="dialog"
        aria-modal="true"
        aria-labelledby="slide-over-title"
        x-show="show"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="transform -translate-x-full"
        x-transition:enter-end="transform translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="transform translate-x-0"
        x-transition:leave-end="transform -translate-x-full"
    >
        {{-- Header --}}
        <div class="slide-over-header">
            <div style="display:flex;align-items:center;gap:0.6rem;min-width:0;flex:1;">
                {{-- Loaded state --}}
                <div x-show="!loading && userData" style="display:flex;align-items:center;gap:0.6rem;min-width:0;flex:1;">
                    <template x-if="userData">
                        <div style="display:flex;align-items:center;gap:0.6rem;min-width:0;flex:1;">
                            <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:var(--primary);" aria-hidden="true">
                                <span style="color:#fff;font-size:0.8rem;font-weight:800;" x-text="initials(userData.full_name)"></span>
                            </div>
                            <div style="min-width:0;flex:1;">
                                <h2 id="slide-over-title" style="font-size:0.82rem;font-weight:800;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="userData.full_name"></h2>
                                <div style="display:flex;align-items:center;gap:0.25rem;margin-top:0.2rem;">
                                    <span class="badge badge-sm" :class="statusClass(userData.status)" x-text="statusLabel(userData.status)"></span>
                                    <span class="badge badge-secondary badge-sm" x-text="'KYC ' + userData.kyc_level"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Loading skeleton --}}
                <div x-show="loading" style="display:flex;align-items:center;gap:0.6rem;">
                    <div class="skeleton" style="width:36px;height:36px;border-radius:50%;flex-shrink:0;"></div>
                    <div style="display:flex;flex-direction:column;gap:0.25rem;">
                        <div class="skeleton" style="height:12px;width:100px;border-radius:var(--radius-sm);"></div>
                        <div class="skeleton" style="height:10px;width:70px;border-radius:var(--radius-sm);"></div>
                    </div>
                </div>

                {{-- Error title --}}
                <span x-show="!loading && !userData && error" id="slide-over-title" style="font-size:0.82rem;font-weight:700;color:var(--text-primary);">عرض سريع</span>
            </div>
            <button type="button" @click="close()" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;background:transparent;border:none;color:var(--text-muted);cursor:pointer;border-radius:var(--radius-sm);" aria-label="إغلاق">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1.2rem;height:1.2rem;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="slide-over-body">
            {{-- Loading --}}
            <div x-show="loading" style="display:flex;flex-direction:column;gap:0.75rem;">
                <div style="display:flex;flex-direction:column;gap:0.25rem;">
                    <div class="skeleton" style="height:10px;width:80px;border-radius:var(--radius-sm);"></div>
                    <div class="skeleton" style="height:13px;width:100%;border-radius:var(--radius-sm);"></div>
                    <div class="skeleton" style="height:13px;width:75%;border-radius:var(--radius-sm);"></div>
                </div>
                <div style="height:1px;background:var(--border-light);margin:0.25rem 0;"></div>
                <div style="display:flex;flex-direction:column;gap:0.25rem;">
                    <div class="skeleton" style="height:10px;width:70px;border-radius:var(--radius-sm);"></div>
                    <div class="skeleton" style="height:30px;width:100%;border-radius:var(--radius-sm);"></div>
                    <div class="skeleton" style="height:30px;width:100%;border-radius:var(--radius-sm);"></div>
                </div>
                <div style="height:1px;background:var(--border-light);margin:0.25rem 0;"></div>
                <div style="display:flex;flex-direction:column;gap:0.25rem;">
                    <div class="skeleton" style="height:10px;width:100px;border-radius:var(--radius-sm);"></div>
                    <template x-for="i in 3" :key="i">
                        <div class="skeleton" style="height:30px;width:100%;border-radius:var(--radius-sm);"></div>
                    </template>
                </div>
            </div>

            {{-- Error --}}
            <div x-show="!loading && error" style="text-align:center;padding:2.5rem 0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:2.5rem;height:2.5rem;color:var(--border-strong);"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <p style="margin-top:0.5rem;font-size:0.82rem;font-weight:700;color:var(--text-secondary);">تعذّر التحميل</p>
                <button type="button" @click="reload()" class="btn btn-ghost btn-sm" style="color:var(--primary);margin-top:0.65rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36"/></svg>
                    إعادة المحاولة
                </button>
            </div>

            {{-- Data --}}
            <template x-if="!loading && userData">
                <div style="display:flex;flex-direction:column;gap:0.85rem;">
                    {{-- Contact --}}
                    <div>
                        <p style="font-size:0.7rem;font-weight:700;color:var(--text-muted);margin-bottom:0.4rem;">التواصل</p>
                        <div style="display:flex;flex-direction:column;gap:0.35rem;">
                            <div style="display:flex;align-items:center;gap:0.4rem;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;color:var(--text-muted);"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                <span style="font-size:0.82rem;color:var(--text-secondary);" dir="ltr" x-text="userData.email"></span>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.4rem;" x-show="userData.phone">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;color:var(--text-muted);"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                                <span style="font-size:0.82rem;color:var(--text-secondary);" dir="ltr" x-text="userData.phone"></span>
                            </div>
                        </div>
                    </div>

                    <div style="height:1px;background:var(--border-light);"></div>

                    {{-- Wallets --}}
                    <div>
                        <p style="font-size:0.7rem;font-weight:700;color:var(--text-muted);margin-bottom:0.4rem;">الرصيد</p>
                        <template x-if="wallets.length === 0">
                            <p style="font-size:0.75rem;color:var(--text-muted);">لا توجد محافظ</p>
                        </template>
                        <template x-for="wallet in wallets" :key="wallet.currency">
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.25rem 0;">
                                <span style="font-size:0.82rem;font-weight:600;color:var(--text-secondary);" x-text="wallet.currency"></span>
                                <span style="font-size:0.82rem;font-weight:800;color:var(--text-primary);" dir="ltr" x-text="Number(wallet.balance).toFixed(2)"></span>
                            </div>
                        </template>
                    </div>

                    <div style="height:1px;background:var(--border-light);"></div>

                    {{-- Recent Txs --}}
                    <div>
                        <p style="font-size:0.7rem;font-weight:700;color:var(--text-muted);margin-bottom:0.4rem;">آخر 3 معاملات</p>
                        <template x-if="recentTxs.length === 0">
                            <p style="font-size:0.75rem;color:var(--text-muted);">لا توجد معاملات</p>
                        </template>
                        <template x-for="tx in recentTxs" :key="tx.reference">
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.35rem 0;border-bottom:1px solid var(--border-light);">
                                <div>
                                    <p style="font-size:0.75rem;font-family:monospace;color:var(--text-secondary);" dir="ltr" x-text="tx.reference"></p>
                                    <span class="badge badge-sm" :class="txStatusClass(tx.status)" x-text="txStatusLabel(tx.status)"></span>
                                </div>
                                <span style="font-size:0.82rem;font-weight:800;" dir="ltr" :style="['deposit','refund'].includes(tx.status) ? 'color: var(--success)' : 'color: var(--danger)'" x-text="Number(tx.amount).toFixed(2) + ' ' + tx.currency"></span>
                            </div>
                        </template>
                    </div>

                    <div style="height:1px;background:var(--border-light);"></div>

                    {{-- AML + Devices --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.65rem;">
                        <div style="padding:0.65rem;text-align:center;background:var(--surface-hover);border-radius:var(--radius-sm);">
                            <p style="font-size:0.7rem;color:var(--text-muted);">AML مفتوح</p>
                            <p style="font-size:1.2rem;font-weight:800;" :style="amlOpenCount > 0 ? 'color: var(--danger)' : 'color: var(--text-primary)'" x-text="amlOpenCount"></p>
                        </div>
                        <div style="padding:0.65rem;text-align:center;background:var(--surface-hover);border-radius:var(--radius-sm);">
                            <p style="font-size:0.7rem;color:var(--text-muted);">الأجهزة</p>
                            <p style="font-size:1.2rem;font-weight:800;color:var(--text-primary);" x-text="devicesCount"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div style="padding:0.85rem 1.15rem;border-top:1px solid var(--border-light);" x-show="!loading && userData">
            <template x-if="userData">
                <a :href="viewUrl" class="btn btn-primary btn-sm" style="width:100%;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1rem;height:1rem;"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    عرض الملف الكامل
                </a>
            </template>
        </div>
    </div>
</div>
