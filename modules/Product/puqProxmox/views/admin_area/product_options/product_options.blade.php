@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <style>
        .po-card .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.75rem 1rem;
            background: linear-gradient(90deg, rgba(13, 110, 253, 0.04), transparent);
            border-bottom: 1px solid rgba(0, 0, 0, 0.075);
        }

        .po-card .card-header h5 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .po-card .card-header .po-group-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .po-card .po-key {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.75rem;
            color: #6c757d;
        }

        .po-card .po-description {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .po-card .po-source-badge {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .po-option-row {
            display: grid;
            grid-template-columns: 1.5rem 1.3fr 1fr 1fr 1.8fr;
            gap: 0.75rem;
            align-items: center;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .po-option-row.is-header {
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #6c757d;
            background: rgba(0, 0, 0, 0.02);
        }

        .po-option-row:first-child {
            border-top: 0;
        }

        .po-option-row .po-option-key,
        .po-option-row .po-option-value {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.75rem;
            color: #495057;
            word-break: break-all;
        }

        .po-option-row.is-missing {
            background: rgba(220, 53, 69, 0.04);
        }

        .po-option-row.is-out-of-sync {
            background: rgba(255, 193, 7, 0.08);
        }

        .po-option-value-diff {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.72rem;
        }

        .po-option-value-diff .po-actual {
            color: #dc3545;
            text-decoration: line-through;
        }

        .po-option-value-diff .po-expected {
            color: #198754;
        }

        .po-base-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: rgba(13, 110, 253, 0.04);
            border: 1px solid rgba(13, 110, 253, 0.15);
            border-radius: 0.35rem;
            margin-bottom: 1rem;
        }

        .po-base-form label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.1rem;
        }

        .po-base-form .form-text {
            font-size: 0.7rem;
            color: #6c757d;
        }

        .po-chevron {
            transition: transform 0.15s;
        }

        .po-card .card-header.collapsed .po-chevron {
            transform: rotate(-90deg);
        }

        .po-empty {
            padding: 1rem;
            color: #6c757d;
            font-style: italic;
            font-size: 0.85rem;
        }
    </style>
@endsection

@section('content')

    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                        <span class="d-inline-block pe-2">
                            <i class="fas fa-sliders-h"></i>
                        </span>
                        <span class="d-inline-block">{{ $title }}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a><i aria-hidden="true" class="fa fa-home"></i></a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('Product.puqProxmox.Dashboard') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{ $title }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="page-title-actions">
                <button type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                        id="create_missing">
                    <i class="fa fa-magic"></i>
                    {{ __('main.Create Missing') }}
                </button>
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <p class="text-muted small mb-3">
                {{ __('Product.puqProxmox.Product options description') }}
            </p>

            <div class="po-base-form" id="po_base_form">
                <div>
                    <label for="po_base_cpu_cores">{{ __('main.Base CPU Cores') }}</label>
                    <input type="number" min="0" step="1" class="form-control form-control-sm"
                           id="po_base_cpu_cores" name="cpu_cores" value="1">
                    <div class="form-text">{{ __('main.Absolute minus base equals stored delta') }}</div>
                </div>
                <div>
                    <label for="po_base_memory">{{ __('main.Base Memory (MB)') }}</label>
                    <input type="number" min="0" step="1" class="form-control form-control-sm"
                           id="po_base_memory" name="memory" value="1024">
                </div>
                <div>
                    <label for="po_base_rootfs_size">{{ __('main.Base Root Disk (MB)') }}</label>
                    <input type="number" min="0" step="1" class="form-control form-control-sm"
                           id="po_base_rootfs_size" name="rootfs_size" value="10240">
                </div>
                <div>
                    <label for="po_base_mp_size">{{ __('main.Base Additional Disk (MB)') }}</label>
                    <input type="number" min="0" step="1" class="form-control form-control-sm"
                           id="po_base_mp_size" name="mp_size" value="0">
                </div>
            </div>

            <div id="product_options_list"></div>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            const $list = $('#product_options_list');
            const statusUrl = '{{ route('admin.api.Product.puqProxmox.product_options.get') }}';
            const createUrl = '{{ route('admin.api.Product.puqProxmox.product_options.create_missing.post') }}';
            const storageKey = 'puqProxmox_product_options_base_values_v1';

            const baseInputIds = {
                cpu_cores: '#po_base_cpu_cores',
                memory: '#po_base_memory',
                rootfs_size: '#po_base_rootfs_size',
                mp_size: '#po_base_mp_size',
            };

            const i18n = {
                missing: '{{ __('main.Missing') }}',
                partial: '{{ __('main.Partial') }}',
                complete: '{{ __('main.Complete') }}',
                out_of_sync: '{{ __('main.Out of sync') }}',
                empty_dynamic: '{{ __('main.No source records found') }}',
                present: '{{ __('main.Present') }}',
                missing_option: '{{ __('main.Missing') }}',
                source_static: '{{ __('main.Static') }}',
                source_tiered: '{{ __('main.Tiered') }}',
                source_dynamic: '{{ __('main.Dynamic') }}',
                column_key: '{{ __('main.Key') }}',
                column_value: '{{ __('main.Value') }}',
                column_absolute: '{{ __('main.Absolute') }}',
                column_name: '{{ __('main.Name') }}',
            };

            function escapeHtml(text) {
                return $('<div/>').text(text ?? '').html();
            }

            // --- base values: localStorage + current form read ---

            function readBaseValues() {
                const out = {};
                Object.keys(baseInputIds).forEach(function (subject) {
                    const v = parseInt($(baseInputIds[subject]).val(), 10);
                    out[subject] = isNaN(v) || v < 0 ? 0 : v;
                });
                return out;
            }

            function writeBaseValues(values) {
                Object.keys(baseInputIds).forEach(function (subject) {
                    if (values && typeof values[subject] !== 'undefined') {
                        $(baseInputIds[subject]).val(values[subject]);
                    }
                });
            }

            function loadFromStorage() {
                try {
                    const raw = window.localStorage.getItem(storageKey);
                    if (!raw) return null;
                    return JSON.parse(raw);
                } catch (e) {
                    return null;
                }
            }

            function saveToStorage(values) {
                try {
                    window.localStorage.setItem(storageKey, JSON.stringify(values));
                } catch (e) {
                    // quota exceeded / private mode — ignore
                }
            }

            // --- badges ---

            function statusBadge(row) {
                if (row.expected_count === 0) {
                    return `<span class="badge bg-secondary">${i18n.empty_dynamic}</span>`;
                }
                const parts = [];
                if (row.missing_count === 0 && row.out_of_sync_count === 0) {
                    parts.push(`<span class="badge bg-success">${i18n.complete} (${row.present_count}/${row.expected_count})</span>`);
                } else {
                    if (row.missing_count > 0 && row.present_count === 0) {
                        parts.push(`<span class="badge bg-danger">${i18n.missing} (0/${row.expected_count})</span>`);
                    } else if (row.missing_count > 0) {
                        parts.push(`<span class="badge bg-warning text-dark">${i18n.partial} (${row.present_count}/${row.expected_count})</span>`);
                    } else {
                        parts.push(`<span class="badge bg-success">${i18n.complete} (${row.present_count}/${row.expected_count})</span>`);
                    }
                    if (row.out_of_sync_count > 0) {
                        parts.push(`<span class="badge bg-warning text-dark">${i18n.out_of_sync}: ${row.out_of_sync_count}</span>`);
                    }
                }
                return parts.join(' ');
            }

            function sourceBadge(spec) {
                if (spec.source === 'dynamic') {
                    return `<span class="badge bg-info text-dark po-source-badge">${i18n.source_dynamic}: ${escapeHtml(spec.dynamic_kind || '')}</span>`;
                }
                if (spec.source === 'tiered') {
                    return `<span class="badge bg-primary po-source-badge">${i18n.source_tiered}: ${escapeHtml(spec.base_subject || '')}</span>`;
                }
                return `<span class="badge bg-secondary po-source-badge">${i18n.source_static}</span>`;
            }

            // --- option row rendering ---

            function statusIcon(status) {
                if (status === 'present') {
                    return '<i class="fa fa-check text-success" title="' + i18n.present + '"></i>';
                }
                if (status === 'out_of_sync') {
                    return '<i class="fa fa-exclamation-triangle text-warning" title="' + i18n.out_of_sync + '"></i>';
                }
                return '<i class="fa fa-times text-danger" title="' + i18n.missing_option + '"></i>';
            }

            function valueCell(opt) {
                if (opt.status === 'out_of_sync') {
                    return `
                        <div class="po-option-value-diff">
                            <span class="po-actual">${escapeHtml(opt.actual_value)}</span>
                            <span class="po-expected">${escapeHtml(opt.expected_value)}</span>
                        </div>
                    `;
                }
                return `<div class="po-option-value">${escapeHtml(opt.expected_value)}</div>`;
            }

            function renderOptions(options) {
                if (!options || options.length === 0) {
                    return `<div class="po-empty">${i18n.empty_dynamic}</div>`;
                }

                let html = '';
                html += `
                    <div class="po-option-row is-header">
                        <div></div>
                        <div>${i18n.column_key}</div>
                        <div>${i18n.column_absolute}</div>
                        <div>${i18n.column_value}</div>
                        <div>${i18n.column_name}</div>
                    </div>
                `;
                options.forEach(function (opt) {
                    let rowClass = '';
                    if (opt.status === 'missing') {
                        rowClass = 'is-missing';
                    } else if (opt.status === 'out_of_sync') {
                        rowClass = 'is-out-of-sync';
                    }
                    const absoluteCell = opt.absolute !== null && typeof opt.absolute !== 'undefined'
                        ? escapeHtml(opt.absolute)
                        : '<span class="text-muted">—</span>';
                    html += `
                        <div class="po-option-row ${rowClass}">
                            <div>${statusIcon(opt.status)}</div>
                            <div class="po-option-key">${escapeHtml(opt.key)}</div>
                            <div class="po-option-key">${absoluteCell}</div>
                            <div>${valueCell(opt)}</div>
                            <div>${escapeHtml(opt.name)}</div>
                        </div>
                    `;
                });
                return html;
            }

            function renderCard(row, index) {
                const spec = row.spec;
                const collapseId = 'po_group_' + index;
                const hasIssues = (row.missing_count > 0 || row.out_of_sync_count > 0);
                const collapsedClass = hasIssues ? '' : 'collapsed';
                const showClass = hasIssues ? 'show' : '';

                return `
                    <div class="card po-card mb-2">
                        <div class="card-header ${collapsedClass}" data-bs-toggle="collapse" data-bs-target="#${collapseId}">
                            <div style="flex: 1;">
                                <h5>
                                    <i class="fa fa-chevron-down po-chevron me-2"></i>
                                    ${escapeHtml(spec.name)}
                                </h5>
                                <div class="po-key">${escapeHtml(spec.key)}</div>
                                <div class="po-description">${escapeHtml(spec.description)}</div>
                            </div>
                            <div class="po-group-meta">
                                ${sourceBadge(spec)}
                                ${statusBadge(row)}
                            </div>
                        </div>
                        <div id="${collapseId}" class="collapse ${showClass}">
                            <div class="card-body p-0">
                                ${renderOptions(row.options)}
                            </div>
                        </div>
                    </div>
                `;
            }

            // --- network ---

            function loadStatus() {
                blockUI('product_options_list');
                PUQajax(statusUrl, readBaseValues(), 500, null, 'GET')
                    .then(function (response) {
                        const rows = response.data || [];
                        let html = '';
                        rows.forEach(function (row, i) {
                            html += renderCard(row, i);
                        });
                        $list.html(html);
                        unblockUI('product_options_list');
                    })
                    .catch(function (err) {
                        console.error('Error loading product options status', err);
                        unblockUI('product_options_list');
                    });
            }

            // --- wiring ---

            // Restore persisted base values before first load.
            const stored = loadFromStorage();
            if (stored) {
                writeBaseValues(stored);
            }

            // Debounced refresh + persist on any base input change.
            let refreshTimer = null;
            $('#po_base_form input').on('input change', function () {
                saveToStorage(readBaseValues());
                clearTimeout(refreshTimer);
                refreshTimer = setTimeout(loadStatus, 300);
            });

            $('#create_missing').on('click', function () {
                PUQajax(createUrl, readBaseValues(), 2000, $(this), 'POST')
                    .then(function () {
                        loadStatus();
                    });
            });

            loadStatus();
        });
    </script>
@endsection
