@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
<div class="notif-page">
    {{-- Top toolbar: tabs + global actions --}}
    <div class="notif-toolbar">
        <nav class="notif-tabs" aria-label="Notification filters">
            <a href="{{ route('notifications.index', ['tab' => 'all']) }}"
               class="notif-tab {{ ($filters['tab'] ?? 'all') === 'all' ? 'is-active' : '' }}">All</a>
            <a href="{{ route('notifications.index', ['tab' => 'unread']) }}"
               class="notif-tab {{ ($filters['tab'] ?? '') === 'unread' ? 'is-active' : '' }}">Unread</a>
            <a href="{{ route('notifications.index', ['tab' => 'read']) }}"
               class="notif-tab {{ ($filters['tab'] ?? '') === 'read' ? 'is-active' : '' }}">Read</a>
        </nav>
        <div class="notif-toolbar-actions">
            <form action="{{ route('notifications.mark-all-read') }}" method="post" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-teal notif-btn">
                    <i class="fa-regular fa-circle-check me-1"></i> Mark all read
                </button>
            </form>
            <form action="{{ route('notifications.delete-read') }}" method="post" class="d-inline"
                  onsubmit="return confirm('Remove all read notifications from your inbox?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger notif-btn">
                    <i class="fa-regular fa-trash-can me-1"></i> Clear read
                </button>
            </form>
        </div>
    </div>

    @if($notifications->isEmpty())
        <div class="notif-empty">
            <div class="notif-empty-icon"><i class="fa-regular fa-bell-slash"></i></div>
            <p class="notif-empty-title">No notifications</p>
            <p class="notif-empty-hint">When something needs your attention, it will show up here.</p>
        </div>
    @else
        {{-- Bulk row --}}
        <div class="notif-bulk">
            <label class="notif-select-all">
                <input class="form-check-input" type="checkbox" id="selectAllRows" aria-label="Select all on this page">
                <span>Select all on this page</span>
            </label>
            <div class="notif-bulk-btns">
                <button type="button" class="btn btn-sm btn-outline-teal notif-btn" id="btnBulkRead" title="Mark selected as read">
                    <i class="fa-solid fa-check-double me-1"></i> Read
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary notif-btn" id="btnBulkUnread" title="Mark selected as unread">
                    <i class="fa-regular fa-envelope me-1"></i> Unread
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger notif-btn" id="btnBulkDeleteOpen" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                    <i class="fa-regular fa-trash-can me-1"></i> Delete
                </button>
            </div>
        </div>

        <form id="form-bulk-read" action="{{ route('notifications.bulk-mark-read') }}" method="post" class="d-none">@csrf<div id="ids-read"></div></form>
        <form id="form-bulk-unread" action="{{ route('notifications.bulk-mark-unread') }}" method="post" class="d-none">@csrf<div id="ids-unread"></div></form>
        <form id="form-bulk-delete" action="{{ route('notifications.bulk-delete') }}" method="post" class="d-none">
            @csrf
            @method('DELETE')
            <div id="ids-delete"></div>
        </form>

        <ul class="notif-list list-unstyled mb-0">
            @foreach($notifications as $n)
                @php $unread = ! $n->is_read; @endphp
                <li class="notif-item {{ $unread ? 'notif-item--unread' : 'notif-item--read' }}">
                    <div class="notif-item-check">
                        <input class="form-check-input row-select" type="checkbox" value="{{ $n->id }}" aria-label="Select">
                    </div>
                    <div class="notif-item-body">
                        <a href="{{ route('notifications.open', $n) }}" class="notif-item-title notif-item-title--link {{ $unread ? 'fw-semibold' : '' }}">{{ $n->title }}</a>
                        <p class="notif-item-msg">{{ Str::limit($n->message, 280) }}</p>
                        <time class="notif-item-time" datetime="{{ $n->created_at?->toIso8601String() }}">{{ $n->created_at?->diffForHumans() }}</time>
                    </div>
                    <div class="notif-item-actions">
                        @if($unread)
                            <form action="{{ route('notifications.mark-read', $n) }}" method="post" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-teal notif-btn" title="Mark as read">Read</button>
                            </form>
                        @else
                            <form action="{{ route('notifications.mark-unread', $n) }}" method="post" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary notif-btn" title="Mark as unread">Unread</button>
                            </form>
                        @endif
                        <form action="{{ route('notifications.delete', $n) }}" method="post" class="m-0" onsubmit="return confirm('Remove this notification?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger notif-btn" title="Delete">Delete</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="notif-pagination" aria-label="Notification pages">
            {{ $notifications->onEachSide(1)->links() }}
        </div>
    @endif
</div>

<div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content notif-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="bulkDeleteModalLabel" style="color:var(--navy);">Delete selected</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0 text-muted small">Selected notifications will be removed from your inbox. This cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmBulkDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.notif-page {
    max-width: 52rem;
    margin: 0 auto;
    padding: 0 4px;
}
.notif-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem 1.25rem;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    background: var(--white, #fff);
    border: 1px solid var(--border-soft, #e5eaf0);
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(17, 81, 124, 0.04);
}
.notif-tabs {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.notif-tab {
    padding: 0.45rem 0.85rem;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--text-muted, #64748b);
    text-decoration: none;
    border-radius: 8px;
    border: 1px solid transparent;
    transition: color .15s, background .15s, border-color .15s;
}
.notif-tab:hover {
    color: var(--navy, #11517c);
    background: rgba(17, 81, 124, 0.04);
}
.notif-tab.is-active {
    color: var(--navy, #11517c);
    background: rgba(0, 128, 128, 0.08);
    border-color: rgba(0, 128, 128, 0.2);
    font-weight: 600;
}
.notif-toolbar-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}
.notif-btn {
    font-size: 0.8125rem !important;
    padding: 0.35rem 0.65rem !important;
    border-radius: 8px !important;
    white-space: nowrap;
}
.notif-bulk {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    padding: 0.5rem 0.15rem 0.85rem;
    margin-bottom: 0.35rem;
    border-bottom: 1px solid var(--border-soft, #e5eaf0);
}
.notif-select-all {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 0.8125rem;
    color: var(--text-muted, #64748b);
    cursor: pointer;
    user-select: none;
}
.notif-select-all .form-check-input {
    margin-top: 0;
    cursor: pointer;
}
.notif-bulk-btns {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
}
.notif-list {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}
.notif-item {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 0.75rem 1rem;
    align-items: start;
    padding: 1rem 1rem 1rem 0.85rem;
    background: #fff;
    border: 1px solid var(--border-soft, #e5eaf0);
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(17, 81, 124, 0.04);
    transition: border-color .15s, box-shadow .15s;
}
.notif-item:hover {
    border-color: #d0dae5;
    box-shadow: 0 2px 8px rgba(17, 81, 124, 0.06);
}
.notif-item--read {
    opacity: 1;
}
.notif-item--unread {
    border-left: 4px solid var(--teal, #008080);
    padding-left: calc(0.85rem - 3px);
    box-shadow: 0 1px 3px rgba(0, 128, 128, 0.06);
}
.notif-item-check {
    padding-top: 0.15rem;
}
.notif-item-check .form-check-input {
    cursor: pointer;
}
.notif-item-body {
    min-width: 0;
}
.notif-item-title {
    font-size: 0.95rem;
    color: var(--navy, #11517c);
    line-height: 1.35;
    margin: 0 0 0.35rem;
    font-family: 'Poppins', sans-serif;
}
.notif-item-title--link {
    display: inline-block;
    text-decoration: none;
    color: inherit;
}
.notif-item-title--link:hover {
    color: var(--teal, #008080);
    text-decoration: underline;
    text-underline-offset: 2px;
}
.notif-item-msg {
    font-size: 0.875rem;
    color: var(--text-muted, #64748b);
    line-height: 1.5;
    margin: 0 0 0.4rem;
}
.notif-item-time {
    display: block;
    font-size: 0.75rem;
    color: #94a3b8;
}
.notif-item-actions {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0.35rem;
    min-width: 5.5rem;
}
.notif-item-actions .notif-btn {
    width: 100%;
    text-align: center;
}
.notif-empty {
    text-align: center;
    padding: 3rem 1.5rem;
    background: #fff;
    border: 1px dashed var(--border-soft, #e5eaf0);
    border-radius: 12px;
}
.notif-empty-icon {
    font-size: 2.5rem;
    color: #cbd5e1;
    margin-bottom: 0.75rem;
}
.notif-empty-title {
    font-weight: 600;
    color: var(--navy, #11517c);
    margin-bottom: 0.25rem;
}
.notif-empty-hint {
    font-size: 0.875rem;
    color: var(--text-muted, #64748b);
    margin: 0;
}
.notif-pagination {
    margin-top: 1.25rem;
    width: 100%;
    overflow-x: auto;
}
/* Let Laravel’s <nav> span full width so “Showing …” and page buttons can sit apart */
.notif-pagination > nav {
    width: 100%;
    max-width: 100%;
}
/* sm+ row: summary left, pager right, with gap if the row wraps */
.notif-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.notif-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.notif-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.notif-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
/* Mobile simplified pager (prev / next) */
.notif-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.notif-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.notif-pagination .page-link {
    border-radius: 8px;
    color: var(--navy, #11517c);
}
.notif-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
}
.notif-modal {
    border-radius: 12px;
    border: 1px solid var(--border-soft, #e5eaf0);
}
@media (max-width: 576px) {
    .notif-item {
        grid-template-columns: auto 1fr;
        grid-template-rows: auto auto;
    }
    .notif-item-actions {
        grid-column: 1 / -1;
        flex-direction: row;
        flex-wrap: wrap;
        min-width: 0;
        justify-content: flex-start;
    }
    .notif-item-actions .notif-btn {
        width: auto;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const boxes = () => Array.from(document.querySelectorAll('.row-select'));
    document.getElementById('selectAllRows')?.addEventListener('change', function () {
        const on = this.checked;
        boxes().forEach(cb => { cb.checked = on; });
    });
    function collectIds() {
        return boxes().filter(cb => cb.checked).map(cb => cb.value);
    }
    function fillHidden(containerId, ids) {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = '';
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            el.appendChild(input);
        });
    }
    document.getElementById('btnBulkRead')?.addEventListener('click', function () {
        const ids = collectIds();
        if (!ids.length) { alert('Select at least one notification.'); return; }
        fillHidden('ids-read', ids);
        document.getElementById('form-bulk-read').submit();
    });
    document.getElementById('btnBulkUnread')?.addEventListener('click', function () {
        const ids = collectIds();
        if (!ids.length) { alert('Select at least one notification.'); return; }
        fillHidden('ids-unread', ids);
        document.getElementById('form-bulk-unread').submit();
    });
    document.getElementById('confirmBulkDelete')?.addEventListener('click', function () {
        const ids = collectIds();
        if (!ids.length) { alert('Select at least one notification.'); return; }
        fillHidden('ids-delete', ids);
        document.getElementById('form-bulk-delete').submit();
    });
})();
</script>
@endpush
@endsection
