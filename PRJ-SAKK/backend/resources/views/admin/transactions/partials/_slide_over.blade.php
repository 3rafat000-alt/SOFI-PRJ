{{--
  Slide-Over Quick-View Panel — transaction summary.
  Alpine component: txQuickViewPanel() defined in index @push('scripts').
--}}
<div x-data="txQuickViewPanel()"
     @open-quick-view.window="open($event.detail)"
     @keydown.escape.window="if(show) close()"
     x-cloak>

    {{-- Backdrop --}}
    <div class="slide-over-backdrop" x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="close()" aria-hidden="true"></div>

    {{-- Panel --}}
    <div class="slide-over" :aria-hidden="show ? 'false' : 'true'"
         role="dialog" aria-modal="true" aria-labelledby="tx-slide-over-title"
         x-show="show"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="transform translate-x-full"
         x-transition:enter-end="transform translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="transform translate-x-0"
         x-transition:leave-end="transform translate-x-full">

        {{-- Header --}}
        <div class="slide-over-header">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                {{-- Loading skeleton --}}
                <div x-show="loading" class="flex items-center gap-3">
                    <div class="skeleton" style="width:40px;height:40px;border-radius:50%"></div>
                    <div class="space-y-1.5">
                        <div class="skeleton" style="height:14px;width:112px;border-radius:var(--radius-sm)"></div>
                        <div class="skeleton" style="height:10px;width:80px;border-radius:var(--radius-sm)"></div>
                    </div>
                </div>

                {{-- Populated --}}
                <template x-if="!loading && tx">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" style="background:var(--primary-ring)" aria-hidden="true">
                            <x-heroicon name="receipt_long" class="text-white text-base" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-extrabold truncate" id="tx-slide-over-title" style="color:var(--text-primary)" dir="ltr" x-text="tx.reference"></h2>
                            <div class="flex items-center gap-1 mt-0.5">
                                <span class="badge badge-sm" :class="statusClass(tx.status)" x-text="statusLabel(tx.status)"></span>
                                <span class="badge badge-secondary badge-sm" x-text="tx.type_label"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <span x-show="!loading && !tx && error" class="text-sm font-bold" style="color:var(--text-primary)" id="tx-slide-over-title">عرض سريع</span>
            </div>
            <button type="button" @click="close()" class="btn btn-sm btn-sukk-icon shrink-0" aria-label="إغلاق">
                <x-heroicon name="close" class="text-base" />
            </button>
        </div>

        {{-- Body --}}
        <div class="slide-over-body">
            {{-- Loading --}}
            <div x-show="loading" class="space-y-4">
                <div class="skeleton" style="height:40px;width:66%;border-radius:var(--radius-sm)"></div>
                <div class="stng-hr" style="margin:.75rem 0"></div>
                <template x-for="i in 4" :key="i">
                    <div class="skeleton" style="height:24px;width:100%;border-radius:var(--radius-sm);margin-bottom:.5rem"></div>
                </template>
            </div>

            {{-- Error --}}
            <div x-show="!loading && error" class="text-center py-12">
                <x-heroicon name="error_outline" class="text-4xl" style="color:var(--border-strong)" />
                <p class="mt-2 text-sm font-bold" style="color:var(--text-secondary)">تعذّر التحميل</p>
                <button type="button" @click="reload()" class="btn btn-ghost btn-sm mt-3" style="color:var(--primary)">
                    <x-heroicon name="refresh" /> إعادة المحاولة
                </button>
            </div>

            {{-- Populated --}}
            <template x-if="!loading && tx">
                <div class="space-y-4">
                    {{-- Amount headline --}}
                    <div>
                        <p class="stng-fld-lbl">المبلغ</p>
                        <p class="text-3xl font-extrabold" dir="ltr"
                           :style="tx.amount >= 0 ? 'color:var(--success)' : 'color:var(--danger)'"
                           x-text="(tx.amount >= 0 ? '+' : '−') + symbol(tx.currency) + Math.abs(tx.amount).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2})"></p>
                        <p class="stng-date mt-1" dir="ltr" x-text="tx.created_at"></p>
                    </div>

                    <div class="stng-hr" style="margin:.5rem 0"></div>

                    {{-- Breakdown --}}
                    <dl class="space-y-3">
                        <div class="flex justify-between items-center">
                            <dt style="color:var(--text-muted);font-size:var(--font-size-sm)">التصنيف</dt>
                            <dd class="font-bold text-sm" style="color:var(--text-primary)" x-text="tx.category"></dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt style="color:var(--text-muted);font-size:var(--font-size-sm)">الرسوم</dt>
                            <dd class="font-bold text-sm" style="color:var(--text-primary)" dir="ltr" x-text="symbol(tx.currency) + Number(tx.fee).toFixed(2)"></dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt style="color:var(--text-muted);font-size:var(--font-size-sm)">صافي المبلغ</dt>
                            <dd class="font-bold text-sm" style="color:var(--text-primary)" dir="ltr" x-text="symbol(tx.currency) + Number(tx.net_amount).toFixed(2)"></dd>
                        </div>
                    </dl>

                    <div class="stng-hr" style="margin:.5rem 0"></div>

                    {{-- User --}}
                    <div x-show="user">
                        <p class="stng-fld-lbl mb-2">المستخدم</p>
                        <template x-if="user">
                            <a :href="'/admin/users/' + user.id" class="flex items-center gap-2 py-1">
                                <x-heroicon name="person" style="color:var(--text-muted);font-size:.9rem" />
                                <span class="min-w-0">
                                    <span class="text-sm font-bold block truncate" style="color:var(--text-primary)" x-text="user.full_name"></span>
                                    <span class="text-xs block truncate" style="color:var(--text-muted)" dir="ltr" x-text="user.email"></span>
                                </span>
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="card-footer" x-show="!loading && tx">
            <template x-if="tx">
                <a :href="viewUrl" class="btn btn-primary btn-sm w-full">
                    <x-heroicon name="open_in_new" /> عرض التفاصيل الكاملة
                </a>
            </template>
        </div>
    </div>
</div>
