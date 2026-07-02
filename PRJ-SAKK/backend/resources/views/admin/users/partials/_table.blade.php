{{--
  admin.users.partials._table — AJAX results fragment
  Identity-card table: each row = بطاقة تعريفية مصغرة
  Receives: $users (LengthAwarePaginator)
--}}
<div class="up-table-wrap">
<table class="up-tbl">
  <thead>
    <tr>
      <th class="up-tbl-cb">
        <input type="checkbox" @change="toggleAll($event.target.checked)" :checked="selectedUsers.length > 0 && selectedUsers.length === document.querySelectorAll('#up-results input[type=checkbox][value]').length" aria-label="تحديد الكل">
      </th>

      <th class="up-tbl-user up-tbl-sortable @if(request('sort')==='last_name') up-tbl-sorted @endif"
          tabindex="0" @click="sortBy('last_name')" @keydown.enter.prevent="sortBy('last_name')" @keydown.space.prevent="sortBy('last_name')"
          role="columnheader button" aria-sort="{{ request('sort')==='last_name' ? (request('dir')==='asc'?'ascending':'descending') : 'none' }}">
        <span>المستخدم</span>
        <span class="up-tbl-sort" x-text="sortIcon('last_name')"></span>
      </th>
      <th class="up-tbl-status up-tbl-sortable @if(request('sort')==='status') up-tbl-sorted @endif"
          tabindex="0" @click="sortBy('status')" @keydown.enter.prevent="sortBy('status')" @keydown.space.prevent="sortBy('status')"
          role="columnheader button" aria-sort="{{ request('sort')==='status' ? (request('dir')==='asc'?'ascending':'descending') : 'none' }}">
        <span>الحالة</span>
        <span class="up-tbl-sort" x-text="sortIcon('status')"></span>
      </th>
      <th class="up-tbl-kyc up-tbl-sortable @if(request('sort')==='kyc_level') up-tbl-sorted @endif"
          tabindex="0" @click="sortBy('kyc_level')" @keydown.enter.prevent="sortBy('kyc_level')" @keydown.space.prevent="sortBy('kyc_level')"
          role="columnheader button" aria-sort="{{ request('sort')==='kyc_level' ? (request('dir')==='asc'?'ascending':'descending') : 'none' }}">
        <span>KYC</span>
        <span class="up-tbl-sort" x-text="sortIcon('kyc_level')"></span>
      </th>
      <th class="up-tbl-balance">الرصيد</th>
      <th class="up-tbl-activity up-tbl-sortable @if(request('sort')==='last_login_at') up-tbl-sorted @endif"
          tabindex="0" @click="sortBy('last_login_at')" @keydown.enter.prevent="sortBy('last_login_at')" @keydown.space.prevent="sortBy('last_login_at')"
          role="columnheader button" aria-sort="{{ request('sort')==='last_login_at' ? (request('dir')==='asc'?'ascending':'descending') : 'none' }}">
        <span>آخر نشاط</span>
        <span class="up-tbl-sort" x-text="sortIcon('last_login_at')"></span>
      </th>
      <th class="up-tbl-actions">إجراءات</th>
    </tr>
  </thead>
  <tbody>
    @forelse($users as $user)
    @php
      $statusVal = $user->status instanceof \App\Enums\UserStatus ? $user->status->value : $user->status;
      $kycVal    = $user->kyc_status instanceof \App\Enums\KycStatus ? $user->kyc_status->value : $user->kyc_status;
      $initials  = mb_strtoupper(mb_substr($user->first_name,0,1)).mb_strtoupper(mb_substr($user->last_name,0,1));
      $balances  = $user->wallets->groupBy('currency')->map(fn($ws)=>(float)$ws->sum('balance'));
      $usd = $balances->get('USD',0); $syp = $balances->get('SYP',0);
      $edgeClass = match($statusVal) {
        'active'    => 'ut-edge--active',
        'suspended' => 'ut-edge--suspended',
        'banned'    => 'ut-edge--banned',
        default     => 'ut-edge--pending',
      };
      $statusLabel = match($statusVal) {
        'active'    => 'نشط', 'suspended' => 'موقوف', 'banned' => 'محظور',
        default     => 'قيد الانتظار',
      };
      $kycLevel = $user->kyc_level ?? 0;
    @endphp
    <tr class="ut-row" data-uuid="{{ $user->uuid }}">
      {{-- Bulk checkbox --}}
      <td class="ut-cb">
        <input type="checkbox" value="{{ $user->uuid }}" x-on:change="toggleUser('{{ $user->uuid }}')"
               x-bind:checked="isSelected('{{ $user->uuid }}')"
               aria-label="تحديد {{ $user->first_name }} {{ $user->last_name }}">
      </td>

      {{-- User column: avatar + name/email --}}
      <td class="ut-user">
        <div class="ut-user-wrap">
          <div class="ut-avatar">{{ $initials }}</div>
          <div class="ut-user-info">
            <a href="{{ route('admin.users.show', $user->id) }}" class="ut-name" target="_blank">{{ $user->first_name }} {{ $user->last_name }}</a>
            <span class="ut-email">{{ $user->email }}</span>
          </div>
        </div>
      </td>

      {{-- Status column --}}
      <td class="ut-status-cell">
        <span class="ut-status">
          <span class="ut-dot @switch($statusVal) @case('active') ut-dot--active @break @case('suspended') ut-dot--suspended @break @case('banned') ut-dot--banned @break @default ut-dot--pending @endswitch"></span>
          {{ $statusLabel }}
        </span>
      </td>

      {{-- KYC column --}}
      <td class="ut-kyc-cell">
        <div class="ut-kyc-wrap">
          <div class="ut-kyc-dots">
            @for($i=1;$i<=3;$i++)
            <span class="ut-dot" style="background:{{ $i<=$kycLevel ? 'var(--success)' : 'var(--border-strong)' }};"></span>
            @endfor
          </div>
          <span class="ut-kyc-label @switch($kycVal) @case('verified') ut-kyc--verified @break @case('submitted') ut-kyc--submitted @break @case('rejected') ut-kyc--rejected @break @default ut-kyc--pending @endswitch">
            @switch($kycVal)
              @case('verified') موثّق @break
              @case('submitted') مقدّم @break
              @case('rejected') مرفوض @break
              @default معلّق
            @endswitch
          </span>
        </div>
      </td>

      {{-- Balance column --}}
      <td class="ut-balance-cell">
        <div class="ut-balance">
          @if($usd > 0)<div class="ut-balance-usd">{!! \App\Support\Money::format($usd, 'USD') !!}</div>@endif
          @if($syp > 0)<div class="ut-balance-syp">{!! \App\Support\Money::format($syp, 'SYP') !!}</div>@endif
          @if($usd==0 && $syp==0)<span class="ut-balance-empty">—</span>@endif
        </div>
      </td>

      {{-- Activity column --}}
      <td class="ut-activity-cell">
        @if($user->last_login_at)
        <span class="ut-activity-time" title="{{ $user->last_login_at->format('Y/m/d H:i') }}">{{ $user->last_login_at->diffForHumans() }}</span>
        @else
        <span class="ut-activity-none">لم يسجل</span>
        @endif
      </td>

      {{-- Actions column --}}
      <td class="ut-actions-cell">
        <div class="ut-actions">
          <a href="{{ route('admin.users.show', $user->id) }}" target="_blank" class="ut-act" title="عرض">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </a>
          @if($statusVal === 'active')
          <button type="button" @click="openStatusModal({{ $user->id }}, '{{ addslashes($user->first_name.' '.$user->last_name) }}', '{{ $initials }}', '{{ $statusVal }}')" class="ut-act ut-act--warn" title="إيقاف">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
          </button>
          @else
          <button type="button" @click="openStatusModal({{ $user->id }}, '{{ addslashes($user->first_name.' '.$user->last_name) }}', '{{ $initials }}', '{{ $statusVal }}')" class="ut-act ut-act--ok" title="تفعيل">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </button>
          @endif
        </div>
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="7" class="ut-empty">
        <div class="ut-empty-inner">
          <div class="ut-empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          </div>
          @if(request()->hasAny(['search','status','kyc_level','kyc_status','two_fa','aml_flagged','date_from','date_to']))
          <div class="ut-empty-title">لا توجد نتائج</div>
          <div class="ut-empty-desc">جرّب تعديل معايير البحث أو إزالة الفلاتر</div>
          <a href="{{ route('admin.users') }}" class="ut-empty-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            تصفير الفلاتر
          </a>
          @else
          <div class="ut-empty-title">لا يوجد مستخدمون بعد</div>
          <div class="ut-empty-desc">ستظهر الحسابات هنا بعد أول تسجيل في المنصة</div>
          @endif
        </div>
      </td>
    </tr>
    @endforelse
  </tbody>
</table>
  {{-- Bulk bar (inside table wrap) --}}
  <div class="up-bulk-bar" x-show="selectedUsers.length > 0" x-cloak>
    <div class="up-bulk-info">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span>تم تحديد <strong x-text="selectedUsers.length"></strong> مستخدم</span>
    </div>
    <div style="display:flex;gap:6px;">
      <button type="button" @click="openBulkModal('activate')" class="up-bulk-act up-bulk-act--activate">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        تفعيل
      </button>
      <button type="button" @click="openBulkModal('suspend')" class="up-bulk-act up-bulk-act--suspend">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        إيقاف
      </button>
    </div>
    <button type="button" @click="selectedUsers = []" class="up-bulk-close" aria-label="إلغاء">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
</div>

{{-- Pagination --}}
@if($users->hasPages())
<div class="up-pager">
  <span class="up-pager-info">عرض {{ $users->firstItem() }}–{{ $users->lastItem() }} من {{ number_format($users->total()) }}</span>
  <div class="up-pager-nav">
    @if($users->onFirstPage())
    <span class="up-pn-disabled">‹</span>
    @else
    <a href="{{ $users->previousPageUrl() }}" rel="prev" aria-label="السابق">‹</a>
    @endif
    @foreach($users->getUrlRange(max(1,$users->currentPage()-2), min($users->lastPage(),$users->currentPage()+2)) as $page => $url)
      @if($page == $users->currentPage())
      <span aria-current="page">{{ $page }}</span>
      @else
      <a href="{{ $url }}">{{ $page }}</a>
      @endif
    @endforeach
    @if($users->hasMorePages())
    <a href="{{ $users->nextPageUrl() }}" rel="next" aria-label="التالي">›</a>
    @else
    <span class="up-pn-disabled">›</span>
    @endif
  </div>
</div>
@endif
