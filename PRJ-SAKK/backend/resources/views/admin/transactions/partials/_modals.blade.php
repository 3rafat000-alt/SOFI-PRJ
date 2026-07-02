{{--
  Audited Modal — Transactions module (append-only / read-first scope)
  Included by: show.blade.php

  Modal R — عكس المعاملة (Reverse)
    The single legitimate admin mutation on a transaction. Allowed only on a
    COMPLETED, non-adjustment row (backend re-enforces). Leaves the original
    intact and appends an audited reversal adjustment. Reason required.
    Triggered via window event 'open-reverse-modal'.
--}}
<div
    x-data="reverseModal()"
    @open-reverse-modal.window="open($event.detail)"
    x-show="show"
    x-cloak
    class="modal-backdrop"
    role="dialog"
    aria-modal="true"
    aria-labelledby="reverse-modal-title"
    @keydown.escape.window="if(show) close()"
>
    <div class="modal" @click.stop x-ref="modalPanel">
        <div class="modal-header" style="background: var(--danger-light);">
            <h3 class="text-base font-extrabold" id="reverse-modal-title" style="color: var(--danger);">عكس المعاملة</h3>
            <button type="button" @click="close()" class="btn btn-sm btn-sukk-icon" aria-label="إغلاق">
                <x-heroicon name="close" class="text-base" aria-hidden="true" />
            </button>
        </div>
        <div class="modal-body space-y-3">
            {{-- Transaction identity row --}}
            <div class="flex items-center justify-between gap-3 p-3" style="background: var(--surface-hover); border-radius: var(--radius-sm);">
                <div class="min-w-0">
                    <p class="text-xs" style="color: var(--text-muted);">المرجع</p>
                    <p class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr" x-text="reference"></p>
                </div>
                <p class="text-lg font-extrabold shrink-0" dir="ltr" :style="amount >= 0 ? 'color: var(--success)' : 'color: var(--danger)'"
                   x-text="(amount >= 0 ? '+' : '−') + symbol + Math.abs(amount).toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></p>
            </div>

            {{-- Consequence warning --}}
            <div class="p-3" style="background: var(--warning-light); border-radius: var(--radius-sm);">
                <p class="text-xs font-semibold" style="color: #92400e;">
                    سيُعلَّم الأصل كـ«معكوس» وتُنشأ معاملة تسوية معاكِسة تُعيد المبلغ للمحفظة. لا يمكن التراجع عن العكس.
                </p>
            </div>

            <div>
                <label for="reverse-modal-reason" class="label label-required">سبب العكس</label>
                <textarea
                    id="reverse-modal-reason"
                    x-model="reason"
                    rows="3"
                    class="input"
                    :class="errors.reason ? 'input-error' : ''"
                    placeholder="اكتب سبب عكس المعاملة…"
                    x-ref="firstFocus"
                    aria-describedby="reverse-modal-reason-hint"
                ></textarea>
                <p id="reverse-modal-reason-hint" class="hint">سيُحفظ السبب في سجل الأنشطة وضمن بيانات معاملة التسوية.</p>
                <p
                    x-show="errors.reason"
                    class="text-xs font-semibold mt-1"
                    style="color: var(--danger);"
                    role="alert"
                    aria-live="assertive"
                    x-text="errors.reason"
                ></p>
            </div>

            <div
                x-show="errorMsg"
                class="p-3 text-sm font-semibold"
                style="background: var(--danger-light); color: var(--danger); border-radius: var(--radius-sm);"
                role="alert"
                aria-live="assertive"
                x-text="errorMsg"
            ></div>
        </div>
        <div class="modal-footer">
            <button type="button" @click="close()" class="btn btn-secondary btn-sm">إلغاء</button>
            <button type="button" @click="submit()" class="btn btn-danger btn-sm" :disabled="loading">
                <x-heroicon name="refresh" class="text-sm animate-spin" x-show="loading" aria-hidden="true" />
                <span x-show="!loading">
                    <x-heroicon name="undo" class="text-sm" aria-hidden="true" />
                    تأكيد العكس
                </span>
            </button>
        </div>
    </div>
</div>
