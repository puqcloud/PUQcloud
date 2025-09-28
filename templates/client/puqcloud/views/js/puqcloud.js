function PUQajax(url, data, timeOut, button, method = 'POST', form = null) {

    if (form && form.length) {
        form.find('.invalid-feedback').remove();
        form.find('.is-invalid').removeClass('is-invalid');
    }

    return new Promise((resolve, reject) => {
        var originalText = button ? button.html() : '';
        var loadingText = '';

        if (button && button.hasClass('btn-icon-only')) {
            loadingText = '<i class="fa fa-fw fa-spin"></i>';
        } else {
            loadingText = '<i class="fa fa-fw fa-spin"></i> ' + translate("Loading...");
        }

        if (button) {
            button.prop('disabled', true);
            button.html(loadingText);
        }

        function handleError(response) {
            if (response.status === 'error' && response.errors) {
                const addMessages = (messages) => {
                    if (typeof messages === 'string') {
                        response.errors.push(messages);
                    } else if (Array.isArray(messages)) {
                        response.errors.push(...messages);
                    } else if (typeof messages === 'object') {
                        Object.values(messages).forEach(value => {
                            if (Array.isArray(value)) {
                                response.errors.push(...value);
                            } else {
                                response.errors.push(value);
                            }
                        });
                    }
                };

                if (response.message) {
                    addMessages(response.message);
                }

                const timeoutValue = (timeOut === 50) ? 20000 : timeOut * 10;
                alert_error(translate('Error'), response.errors, timeoutValue);
                return true;
            }
            return false;
        }

        function makeRequest() {
            var fullUrl = method === 'GET' && $.param(data) ? url + '?' + $.param(data) : url;

            $.ajax({
                url: fullUrl,
                type: method,
                contentType: method === 'GET' ? undefined : 'application/json',
                data: method === 'GET' ? undefined : JSON.stringify(data),
                success: function (response) {

                    if (handleError(response)) {
                        reject(response.errors);
                    }

                    if (response.status === 'success') {
                        if (response.message) {
                            alert_success(translate('Success'), [response.message], timeOut);
                        }
                        resolve(response);
                    }

                    if (response.redirect) {
                        setTimeout(function () {
                            window.location.href = response.redirect;
                        }, timeOut);
                        resolve();
                    }

                    resolve(response);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let response;
                    if (jqXHR.status === 0) {
                        reject({ aborted: true });
                        return;
                    }

                    try {
                        response = jqXHR.responseJSON || JSON.parse(jqXHR.responseText);
                    } catch (e) {
                        response = {};
                    }

                    if (isJsonObject(response.message) && form && form.jquery && form.length > 0) {
                        showFormErrors(form, response);
                    }

                    if (!handleError(response)) {
                        const timeoutValue = (timeOut === 50) ? 10000 : timeOut * 10;
                        alert_error(
                            translate('Error'),
                            [translate('No response from the server. Try again later.')],
                            timeoutValue
                        );
                    }
                    if (response.redirect) {
                        setTimeout(function () {
                            window.location.href = response.redirect;
                        }, timeOut);
                        resolve();
                    }
                    reject(response.errors || ['Unknown error']);
                },
                complete: function () {
                    if (button) {
                        button.prop('disabled', false);
                        button.html(originalText);
                    }
                }
            });
        }

        makeRequest();
    });
}

function alert_error(title, descriptions, timeOut) {
    var descriptionText = descriptions.join('<br>');
    toastr.options = {
        closeButton: true,
        progressBar: true,
        timeOut: timeOut,
        extendedTimeOut: timeOut / 2,
        positionClass: 'toast-bottom-full-width',
        showDuration: 300,
        hideDuration: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };
    toastr.error(descriptionText, title);
}

function alert_success(title, descriptions, timeOut) {
    var descriptionText = descriptions.join('<br>');
    toastr.options = {
        closeButton: true,
        progressBar: true,
        timeOut: timeOut,
        extendedTimeOut: timeOut / 2,
        positionClass: 'toast-bottom-full-width',
        showDuration: 300,
        hideDuration: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };
    toastr.success(descriptionText, title);
}

function initializeDataTable(tableId, ajaxUrl, columnsConfig, getCustomData, customOptions = {}) {

    if ($.fn.DataTable.isDataTable(tableId)) {
        $(tableId).DataTable().destroy();
        $(tableId).empty();
    }

    var defaultOptions = {
        pagingType: 'numbers',
        responsive: true,
        processing: true,
        scrollCollapse: true,
        paging: true,
        searching: true,
        info: true,
        serverSide: true,
        pageLength: 10,
        lengthMenu: [10, 20, 50, 100, 150, 200, 500, 1000],
        language: {
            processing: translate('processing'),
            search: translate('search'),
            lengthMenu: translate('lengthMenu'),
            info: translate('info'),
            infoEmpty: translate('infoEmpty'),
            infoFiltered: translate('infoFiltered'),
            infoPostFix: translate('infoPostFix'),
            loadingRecords: translate('loadingRecords'),
            zeroRecords: translate('zeroRecords'),
            emptyTable: translate('emptyTable'),
            paginate: {
                first: translate('first'),
                previous: translate('previous'),
                next: translate('next'),
                last: translate('last')
            },
            aria: {
                sortAscending: translate('sortAscending'),
                sortDescending: translate('sortDescending')
            },
            thousands: translate('thousands'),
            decimal: translate('decimal')
        },
        ajax: function (data, callback, settings) {

            if (typeof getCustomData !== 'undefined') {
                var customData = getCustomData();
            } else {
                var customData = {};
            }

            var requestData = $.extend({}, data, customData);

            PUQajax(ajaxUrl, requestData, 3000, null, 'GET')
                .then(function (response) {
                    if (response.data) {
                        callback(response.data.original);
                    } else {
                        callback({
                            draw: data.draw,
                            recordsTotal: 0,
                            recordsFiltered: 0,
                            data: []
                        });
                    }
                })
                .catch(function (error) {
                    if (error && error.aborted) return;
                    alert_error(translate('Error'), [translate('Failed to load data')], 30000);
                });
        },
        columns: columnsConfig
    };
    var finalOptions = $.extend({}, defaultOptions, customOptions);
    for (var key in customOptions) {
        if (Array.isArray(customOptions[key])) {
            finalOptions[key] = customOptions[key];
        }
    }

    var dataTable = $(tableId).DataTable(finalOptions);

    return dataTable;

}

function initializeDataTableDC(tableId, ajaxUrl, columnsConfig, getCustomData, customOptions = {}) {
    if ($.fn.DataTable.isDataTable(tableId)) {
        $(tableId).DataTable().destroy();
        $(tableId).empty();
    }

    function loadDataAndInit() {
        var customData = typeof getCustomData !== 'undefined' ? getCustomData() : {};

        $.ajax({
            url: ajaxUrl,
            method: 'GET',
            data: customData,
            dataType: 'json',
            success: function (response) {
                if (!response.data || !response.data.original) {
                    console.error('Invalid response format');
                    return;
                }

                var serverColumns = response.data.original.columns || [];
                var columnsConfigMap = {};
                columnsConfig.forEach(function (col) {
                    columnsConfigMap[col.data] = col;
                });

                var allCustomFieldsKeys = new Set();
                if (response.data.original.data && Array.isArray(response.data.original.data)) {
                    response.data.original.data.forEach(function(row) {
                        if (row.custom_fields && typeof row.custom_fields === 'object') {
                            Object.keys(row.custom_fields).forEach(function(key) {
                                allCustomFieldsKeys.add(key);
                            });
                        }
                    });
                }
                var customFieldsKeys = Array.from(allCustomFieldsKeys);

                var finalColumns = [];
                var columnDefsFromConfig = [];
                var dynamicColumnIndexes = [];

                serverColumns.forEach(function (colName, index) {
                    if (columnsConfigMap[colName]) {
                        var configColumn = $.extend({}, columnsConfigMap[colName]);

                        var isOrderable = configColumn.orderable === true;
                        configColumn.orderable = isOrderable;

                        finalColumns.push(configColumn);

                        columnDefsFromConfig.push({
                            targets: index,
                            orderable: isOrderable
                        });
                    } else if (customFieldsKeys.includes(colName)) {
                        finalColumns.push({
                            data: 'custom_fields.' + colName,
                            title: colName.toUpperCase(),
                            orderable: false,
                            render: function (data) {
                                return data || '';
                            }
                        });
                        dynamicColumnIndexes.push(index);
                    } else {
                        finalColumns.push({
                            data: colName,
                            title: colName,
                            orderable: false
                        });
                        dynamicColumnIndexes.push(index);
                    }
                });

                var $table = $(tableId);
                var theadHtml = '<tr>';
                var tfootHtml = '<tr>';
                finalColumns.forEach(function (col) {
                    theadHtml += '<th>' + (col.title) + '</th>';
                    tfootHtml += '<th>' + (col.title) + '</th>';
                });
                theadHtml += '</tr>';
                tfootHtml += '</tr>';
                $table.find('thead').html(theadHtml);
                $table.find('tfoot').html(tfootHtml);

                var defaultOptions = {
                    order: [],
                    pagingType: 'numbers',
                    responsive: true,
                    processing: true,
                    scrollCollapse: true,
                    paging: true,
                    searching: true,
                    info: true,
                    serverSide: true,
                    pageLength: 10,
                    lengthMenu: [10, 20, 50, 100, 150, 200, 500, 1000],
                    language: {
                        processing: translate('processing'),
                        search: translate('search'),
                        lengthMenu: translate('lengthMenu'),
                        info: translate('info'),
                        infoEmpty: translate('infoEmpty'),
                        infoFiltered: translate('infoFiltered'),
                        infoPostFix: translate('infoPostFix'),
                        loadingRecords: translate('loadingRecords'),
                        zeroRecords: translate('zeroRecords'),
                        emptyTable: translate('emptyTable'),
                        paginate: {
                            first: translate('first'),
                            previous: translate('previous'),
                            next: translate('next'),
                            last: translate('last')
                        },
                        aria: {
                            sortAscending: translate('sortAscending'),
                            sortDescending: translate('sortDescending')
                        },
                        thousands: translate('thousands'),
                        decimal: translate('decimal')
                    },
                    ajax: function (data, callback) {
                        var requestData = $.extend({}, data, customData);
                        PUQajax(ajaxUrl, requestData, 3000, null, 'GET')
                            .then(function (resp) {
                                if (resp.data && resp.data.original) {
                                    var normalizedData = [];
                                    if (resp.data.original.data && Array.isArray(resp.data.original.data)) {
                                        normalizedData = resp.data.original.data.map(function(row) {
                                            var normalizedRow = $.extend({}, row);

                                            if (!normalizedRow.custom_fields || typeof normalizedRow.custom_fields !== 'object') {
                                                normalizedRow.custom_fields = {};
                                            }

                                            customFieldsKeys.forEach(function(fieldKey) {
                                                if (!normalizedRow.custom_fields.hasOwnProperty(fieldKey)) {
                                                    normalizedRow.custom_fields[fieldKey] = '';
                                                }
                                            });

                                            return normalizedRow;
                                        });
                                    }

                                    callback({
                                        draw: data.draw,
                                        recordsTotal: resp.data.original.recordsTotal || 0,
                                        recordsFiltered: resp.data.original.recordsFiltered || 0,
                                        data: normalizedData
                                    });
                                } else {
                                    callback({
                                        draw: data.draw,
                                        recordsTotal: 0,
                                        recordsFiltered: 0,
                                        data: []
                                    });
                                }
                            })
                            .catch(function (error) {
                                if (error && error.aborted) return;
                                alert_error(translate('Error'), [translate('Failed to load data')], 30000);
                            });
                    },
                    columns: finalColumns,
                    columnDefs: []
                };

                var finalColumnDefs = [];

                if (dynamicColumnIndexes.length > 0) {
                    finalColumnDefs.push({
                        targets: dynamicColumnIndexes,
                        orderable: false
                    });
                }

                finalColumnDefs = finalColumnDefs.concat(columnDefsFromConfig);

                if (customOptions.columnDefs && Array.isArray(customOptions.columnDefs)) {
                    finalColumnDefs = finalColumnDefs.concat(customOptions.columnDefs);
                }

                defaultOptions.columnDefs = finalColumnDefs;

                var finalOptions = $.extend(true, {}, defaultOptions, customOptions);

                if (customOptions.columnDefs && Array.isArray(customOptions.columnDefs)) {
                    finalOptions.columnDefs = finalColumnDefs;
                }

                $(tableId).DataTable(finalOptions);
            },
            error: function (jqXHR) {
                if (jqXHR.status === 0) {
                    return;
                }
                alert_error(translate('Error'), [translate('Failed to load initial data')], 30000);
            }
        });
    }

    loadDataAndInit();
    return $(tableId);
}

function initializeAutoReloadTable(options) {
    var reloadInterval = parseInt($(options.intervalSelectId).val());
    var countdown = reloadInterval;

    $(options.circleProgressId).circleProgress({
        value: 0,
        size: 52,
        lineCap: "round",
        fill: {color: options.progressColor}
    });

    function reloadTableProgress() {
        if ($(options.autoReloadCheckboxId).is(':checked')) {
            var progressValue = countdown / reloadInterval;

            $(options.circleProgressId).circleProgress({
                value: progressValue
            }).on("circle-animation-progress", function (event, progress, stepValue) {
                $(this).find("small").html("<span>" + Math.round(progressValue * 100) + "%</span>");
            });

            if (countdown === 0) {
                if ($(options.autoReloadCheckboxId).is(':checked')) {
                    options.dataTable.ajax.reload(null, false);
                }
                countdown = reloadInterval;
            } else {
                countdown--;
            }
        } else {
            $(options.circleProgressId).circleProgress({
                value: 0
            }).on("circle-animation-progress", function (event, progress, stepValue) {
                $(this).find("small").html("<span>0%</span>");
            });
            countdown = reloadInterval;
        }
    }

    $(options.intervalSelectId).on('change', function () {
        reloadInterval = parseInt($(this).val());
        countdown = reloadInterval;
    });

    setInterval(reloadTableProgress, 1000);
}

function formatDateISO(isoString) {
    if (!isoString) {
        return '';
    }
    const date = new Date(isoString);
    if (isNaN(date.getTime())) {
        throw new Error(translate('Invalid date string'));
    }
    const pad = (num) => num.toString().padStart(2, '0');
    const year = date.getFullYear();
    const month = pad(date.getMonth() + 1);
    const day = pad(date.getDate());
    const hours = pad(date.getHours());
    const minutes = pad(date.getMinutes());
    const seconds = pad(date.getSeconds());
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

function formatDateWithoutTimezone(isoString) {
    if (!isoString) {
        return '';
    }
    const date = new Date(isoString);
    const day = String(date.getUTCDate()).padStart(2, '0');
    const month = String(date.getUTCMonth() + 1).padStart(2, '0');
    const year = date.getUTCFullYear();
    const hours = String(date.getUTCHours()).padStart(2, '0');
    const minutes = String(date.getUTCMinutes()).padStart(2, '0');
    const seconds = String(date.getUTCSeconds()).padStart(2, '0');
    return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
}

function renderStatus(data) {
    return data ?
        '<i class="text-danger fa fa-times-circle"></i>' :
        '<i class="text-success fa fa-check-circle"></i>';
}

function renderServiceStatus(status) {
    const colorMap = {
        'active': 'success',
        'completed': 'success',
        'suspended': 'warning',
        'pause': 'warning',
        'pending': 'info',
        'deploying': 'info',
        'terminated': 'dark',
        'fraud': 'dark',
        'not_found': 'dark',
        'cancelled': 'alternate',
        'processing': 'info',
        'failed': 'danger'
    };
    const key = (status || '').toLowerCase();
    const color = colorMap[key] || 'secondary';

    return `<div class="badge bg-${color} text-uppercase">${translate(status)}</div>`;
}

function renderInvoiceType(type) {

    const typeMap = {
        'invoice': {
            icon: 'fa-file-invoice-dollar',
            color: 'primary',
            label: translate('Invoice')
        },
        'proforma': {
            icon: 'fa-file-alt',
            color: 'info',
            label: translate('Proforma')
        },
        'credit_note': {
            icon: 'fa-file-invoice',
            color: 'warning',
            label: translate('Credit Note')
        }
    };

    const key = (type || '').toLowerCase();
    const data = typeMap[key] || {
        icon: 'fa-file',
        color: 'secondary',
        label: translate(type) || 'Unknown'
    };

    return `<div class="badge bg-${data.color}">
                <i class="fas ${data.icon} me-1"></i> ${data.label}
            </div>`;
}

function renderInvoiceStatus(status) {
    const statusMap = {
        'draft': {
            icon: 'fa-pencil-alt',
            color: 'secondary',
            label: translate('Draft')
        },
        'unpaid': {
            icon: 'fa-clock',
            color: 'danger',
            label: translate('Unpaid')
        },
        'paid': {
            icon: 'fa-check-circle',
            color: 'success',
            label: translate('Paid')
        },
        'canceled': {
            icon: 'fa-ban',
            color: 'dark',
            label: translate('Canceled')
        },
        'refunded': {
            icon: 'fa-undo-alt',
            color: 'info',
            label: translate('Refunded')
        },
        'invoiced': {
            icon: 'fa-file-invoice-dollar',
            color: 'primary',
            label: translate('Invoiced')
        },
        'deleted': {
            icon: 'fa-trash',
            color: 'danger',
            label: translate('Deleted')
        }
    };

    const key = (status || '').toLowerCase();
    const data = statusMap[key] || {
        icon: 'fa-question-circle',
        color: 'secondary',
        label: status || 'Unknown'
    };

    return `<div class="badge bg-${data.color}">
                <i class="fas ${data.icon} me-1"></i> ${data.label}
            </div>`;
}

function renderCurrencyAmount(amount, currencyCode = '') {
    return `
        <div class="widget-chart-content">
            <div class="widget-chart-flex">
                <div class="widget-numbers">
                    <div class="widget-chart-flex text-nowrap">
                        <div class="fsize-1 text-nowrap">
                            <span>${amount}</span>
                            <small class="opacity-5">${currencyCode}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderCurrencyAmountSmall(amount, currencyCode = '') {
    return `
        <div class="widget-chart-content fs-7">
            <div class="widget-chart-flex">
                <div class="widget-numbers">
                    <div class="widget-chart-flex text-nowrap">
                        <div class="text-nowrap">
                            <span>${amount}</span>
                            <small class="opacity-5">${currencyCode}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderTransactionType(type) {
    const colorMap = {
        'charge': 'danger',
        'payment': 'success',
        'refund': 'warning',
        'adjustment': 'info',
    };

    const key = (type || '').toLowerCase();
    const color = colorMap[key] || 'secondary';

    return `<div class="badge bg-${color} text-uppercase">${translate(type)}</div>`;
}

function renderEditButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="edit-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-warning">
                        <i class="fa fa-edit"></i>
                    </button>`;
}

function renderDownloadPdfButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="download-pdf-btn mb-2 me-2 btn btn-outline-2x btn-outline-primary">
                <i class="fa fa-download me-1"></i> ${translate('PDF')}
            </button>`;
}

function renderDetailsButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="details-btn mb-2 me-2 btn btn-outline-2x btn-outline-secondary">
                <i class="fa fa-info-circle me-1"></i> ${translate('Details')}
            </button>`;
}

function renderPayNowButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="pay-now-btn mb-2 me-2 btn btn-outline-2x btn-outline-success">
                <i class="fa fa-check-circle me-1"></i> ${translate('Pay Now')}
            </button>`;
}

function renderPaymentButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="payment-btn mb-2 me-2 btn btn-outline-2x btn-outline-success text-danger">
                <i class="fa fa-credit-card me-1"></i> ${translate('Pay Now')}
            </button>`;
}

function renderSetDefaultButton(modelUrl, isDefault = false) {
    const icon = `<i class="fa fa-star" style="color: ${isDefault ? 'white' : '#ccc'}"></i>`;
    const tooltip = isDefault ? translate('Default') : translate('Set as default');

    const buttonClass = isDefault
        ? 'btn-success'
        : 'btn-outline-primary';

    return `<button data-model-url="${modelUrl}" title="${tooltip}" class="set-default-btn mb-2 me-2 btn-icon-only btn ${buttonClass}" ${isDefault ? 'disabled' : ''}>
                ${icon}
            </button>`;
}

function renderVerifyButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="verify-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-success">
                <i class="fa fa-check"></i>
            </button>`;
}

function idleStatus(data) {
    return data
        ? '<i class="fa fa-pause-circle text-warning" title="Paused"></i>'
        : '<i class="fa fa-play-circle text-success" title="Active"></i>';
}

function renderDeleteButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="delete-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-danger">
                        <i class="fa fa-trash-alt"></i>
                    </button>`;
}

function renderActivateButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="activate-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-success">
                        <i class="fa fa-plug"></i>
                    </button>`;
}

function renderDeactivateButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="deactivate-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-danger">
                        <i class="fa fa-times"></i>
                    </button>`;
}

function renderDisconnectButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="disconnect-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-primary">
                <i class="fa fa-unlink"></i>
            </button>`;
}

function renderUpdateButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="update-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-info">
                        <i class="fa fa-sync"></i>
                    </button>`;
}

function renderSettingsButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="settings-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-primary">
                        <i class="fa fa-cog"></i>
                    </button>`;
}

function renderViewButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="view-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-primary">
                    <i class="fa fa-eye"></i>
                </button>`;
}

function renderManageButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="manage-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-secondary">
                <i class="fa fa-cogs"></i>
            </button>`;
}

function renderManageLink(modelUrl) {
    return `<a href="${modelUrl}" class="manage-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-secondary">
                <i class="fa fa-cogs"></i>
            </a>`;
}

function renderOrderButtons(row) {
    return `
            <div role="group" class="mb-2 btn-group btn-group-toggle">
                <button class="btn btn-sm btn-outline-2x btn btn-outline-primary move-down btn-icon-only" data-uuid="${row.uuid}" data-order="${row.order}">
                    <i class="fa fa-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-2x btn btn-outline-primary">${row.order}</button>
                <button class="btn btn-sm btn-outline-2x btn btn-outline-primary move-up btn-icon-only" data-uuid="${row.uuid}" data-order="${row.order}">
                    <i class="fa fa-arrow-up"></i>
                </button>
           </div>
    `;
}

function blockUI(elementId, element = null) {
    $.blockUI.defaults = {
        css: {
            border: 'none',
            padding: '20px',
            backgroundColor: 'rgba(255, 255, 255, 0.9)',
            color: '#333',
            borderRadius: '15px',
            boxShadow: '0 0 15px rgba(0, 0, 0, 0.2)',
            width: 'auto',
            height: 'auto',
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
            textAlign: 'center',
        },
        overlayCSS: {
            backgroundColor: '#333',
            opacity: 0.3,
            cursor: 'wait',
            borderRadius: '5px',
            boxShadow: '0 0 30px rgba(0, 0, 0, 0.7)',
        },
        message: $(
            '<div class="loader-container">' +
            '<div class="line-scale-pulse-out">' +
            '<div class="bg-primary"></div>' +
            '<div class="bg-primary"></div>' +
            '<div class="bg-primary"></div>' +
            '<div class="bg-primary"></div>' +
            '<div class="bg-primary"></div>' +
            '</div>' +
            '<p style="margin-top: 10px; font-size: 14px; color: #333;">' + translate('Loading...') + '</p>' +
            '</div>'
        ),
    };

    if (element === null) {
        $("#" + elementId).block($.blockUI.defaults);
    } else {
        element.block($.blockUI.defaults);
    }
}

function unblockUI(elementId, element = null) {
    if (element === null) {
        $("#" + elementId).unblock();
    } else {
        element.unblock();
    }
};

function initializeSelect2(element, url, selectedValues = [], method = 'GET', timeout = 5000, options = {}, gets = {}, callback = null) {
    if (!element || !$(element).is('select')) {
        console.error('Invalid element or not a <select> element');
        return;
    }

    $(element).css('width', '100%');
    $(element).val(null).trigger('change');

    if (!$(element).data('select2')) {
        let defaultOptions = {
            theme: "bootstrap4",
            width: '100%',
            ajax: {
                transport: function (params, success, failure) {
                    params.data = $.extend(true, {}, params.data, gets);
                    PUQajax(url, params.data, timeout, null, method)
                        .then(function (response) {
                            success(response.data);
                            if (typeof callback === 'function') {
                                setTimeout(() => callback(), 50);
                            }
                        })
                        .catch(function (error) {
                            failure(error);
                        });
                },
                delay: 250,
                processResults: function (data) {
                    return {
                        results: data.results,
                        pagination: {
                            more: data.pagination.more
                        }
                    };
                }
            },
            language: {
                errorLoading: () => translate('error_loading'),
                inputTooLong: (args) => translate('input_too_long').replace('{overChars}', args.input.length - args.maximum).replace('{s}', (args.input.length - args.maximum > 1 ? "s" : "")),
                inputTooShort: (args) => translate('input_too_short').replace('{remainingChars}', args.minimum - args.input.length),
                loadingMore: () => translate('loading_more'),
                maximumSelected: (args) => translate('maximum_selected').replace('{maximum}', args.maximum).replace('{s}', (args.maximum > 1 ? "s" : "")),
                noResults: () => translate('no_results'),
                searching: () => translate('searching')
            },
            placeholder: translate('select_option'),
        };

        let mergedOptions = $.extend(true, {}, defaultOptions, options);

        $(element).select2(mergedOptions);
    }

    if ($(element).prop('multiple')) {
        $(element).empty();
    }

    if (selectedValues) {
        if ($(element).prop('multiple')) {
            selectedValues.forEach(function (selectedValue) {
                if ($(element).find(`option[value="${selectedValue.id}"]`).length === 0) {
                    let option = new Option(selectedValue.text, selectedValue.id, true, true);
                    $(element).append(option);
                }
            });
        } else {
            if (selectedValues) {
                let option = new Option(selectedValues.text, selectedValues.id, true, true);
                $(element).append(option);
            }
        }
        $(element).trigger('change');
    }

    if (typeof callback === 'function') {
        setTimeout(() => callback(), 100);
    }
}

function buildMultipleSelect(element, url, selectedValues, method = 'GET', timeout = 5000) {
    if (selectedValues == null) {
        selectedValues = [];
    }
    if (!element || element.tagName.toLowerCase() !== 'select') {
        console.error('Invalid element or not a <select> element');
        return;
    }

    element.innerHTML = '';

    if ($(element).data('select2')) {
        $(element).select2('destroy');
    }
    PUQajax(url, {}, timeout, null, method)
        .then(function (response) {
            response.data.forEach(function (item) {
                var option = document.createElement('option');
                option.value = item.uuid;
                option.textContent = item.name;
                element.appendChild(option);
            });

            var options = element.options;
            for (var i = 0; i < options.length; i++) {
                if (selectedValues.includes(options[i].value)) {
                    options[i].selected = true;
                }
            }

            if (selectedValues.length > 0) {
                element.dispatchEvent(new Event('change'));
            }

            $(element).select2({});
        })
        .catch(function (error) {
            console.error('Error fetching data:', error);
        });
}

function serializeForm(form) {
    const obj = {};

    form.find(':input').each(function () {
        const item = $(this);
        const name = item.attr('name');
        if (!name) return;

        let value;
        if (item.is(':checkbox')) {
            value = item.is(':checked') ? "yes" : "no";
        } else if (item.is('select[multiple]')) {
            value = item.val() || [];
        } else {
            value = item.val();
        }

        const objectMatch = name.match(/^(\w+)\[([^\]]+)\]$/);

        if (objectMatch) {
            const key = objectMatch[1];
            const subKey = objectMatch[2];

            if (!obj[key]) obj[key] = {};
            obj[key][subKey] = value;

        } else {
            obj[name] = value;
        }
    });

    return obj;
}

function translate(key) {
    if (window.translations && typeof window.translations === 'object') {
        return window.translations[key] !== undefined ? window.translations[key] : key;
    }
    return key;
}

function getActionColor(action) {
    switch (action) {
        case 'api':
            return 'bg-primary';
        case 'web':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
}

function getMethodColor(method) {
    switch (method) {
        case 'GET':
            return 'bg-info';
        case 'POST':
            return 'bg-focus';
        case 'PUT':
            return 'bg-warning';
        case 'DELETE':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function getLogLevelColor(level) {
    switch (level.toLowerCase()) {
        case 'emergency':
        case 'alert':
        case 'critical':
        case 'error':
            return 'bg-danger';
        case 'warning':
            return 'bg-warning';
        case 'notice':
            return 'bg-info';
        case 'info':
            return 'bg-primary';
        case 'debug':
            return 'bg-alternate';
        default:
            return 'bg-dark';
    }
}

function showFormErrors(form, message) {
    if (!form || !message || typeof message.message !== 'object') {
        console.error('Invalid form or message data');
        return;
    }

    form.find('.invalid-feedback').remove();
    form.find('.is-invalid').removeClass('is-invalid').addClass('is-valid');

    if (!message.message) {
        console.warn('No error messages found in message object');
        return;
    }

    $.each(message.message, function (field, messages) {
        var input = form.find(`[name="${field}"]`);

        if (!input.length) {
            console.warn(`Input field "${field}" not found in form`);
            return;
        }

        input.addClass('is-invalid');

        if (!Array.isArray(messages)) {
            console.error(`Messages for field "${field}" are not in the expected format`);
            return;
        }

        $.each(messages, function (index, message) {
            if (typeof message === 'string') {
                input.after(`<em class="invalid-feedback">${message}</em>`);
            }
        });
    });
}

function resetFormValidation(form) {
    if (!form) {
        console.error('Invalid form object');
        return;
    }
    form.find('.invalid-feedback').remove();
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.is-valid').removeClass('is-valid');

}

function isJsonObject(obj) {
    return obj && typeof obj === 'object' && !Array.isArray(obj);
}

function getQueueStatusLabelClass(status) {
    switch (status) {
        case 'completed':
            return 'success';
        case 'queued':
            return 'secondary';
        case 'failed':
            return 'danger';
        case 'pending':
            return 'warning';
        case 'processing':
            return 'primary';
        default:
            return 'info';
    }
}

function getClientStatusLabelClass(status) {
    switch (status) {
        case 'New':
            return 'primary';
        case 'Active':
            return 'success';
        case 'Inactive':
            return 'secondary';
        case 'Closed':
            return 'danger';
        default:
            return 'info';
    }
}

function getModuleStatusLabelClass(status) {
    switch (status) {
        case 'active':
            return 'success';
        case 'inactive':
            return 'secondary';
        case 'error':
            return 'danger';
        default:
            return 'info';
    }
}

function initPerfectScrollbar() {
    setTimeout(function () {
        if ($(".scrollbar-container")[0]) {
            $(".scrollbar-container").each(function () {
                const ps = new PerfectScrollbar($(this)[0], {
                    wheelSpeed: 2,
                    wheelPropagation: false,
                    minScrollbarLength: 20,
                });
            });

            const ps = new PerfectScrollbar(".scrollbar-sidebar", {
                wheelSpeed: 2,
                wheelPropagation: true,
                minScrollbarLength: 20,
            });
        }
    }, 1000);
}

function loadSimpleWidget(id, heading, subheading) {
    const widgetHtml = `
            <div class="card mb-3 widget-content" data-widget-key="${id}">
                <div class="widget-content-wrapper">
                    <div class="widget-content-left">
                        <div class="widget-heading">${translate(heading)}</div>
                        <div class="widget-subheading">${translate(subheading)}</div>
                    </div>
                </div>
            </div>
        `;

    $('#' + id).html(widgetHtml);
}

function generateUrl(template, params) {
    let url = template;
    for (const key in params) {
        url = url.replace(`__${key}__`, encodeURIComponent(params[key]));
    }
    return url;
}

function initializeDatePicker(element, date) {
    if (!date) {
        element.prop("disabled", true);
        return;
    }

    const locale = {
        format: "YYYY-MM-DD HH:mm",
        applyLabel: translate("Apply"),
        cancelLabel: translate("Cancel"),
        daysOfWeek: [
            translate("Su"),
            translate("Mo"),
            translate("Tu"),
            translate("We"),
            translate("Th"),
            translate("Fr"),
            translate("Sa")
        ],
        monthNames: [
            translate("January"),
            translate("February"),
            translate("March"),
            translate("April"),
            translate("May"),
            translate("June"),
            translate("July"),
            translate("August"),
            translate("September"),
            translate("October"),
            translate("November"),
            translate("December")
        ],
        firstDay: 1
    };

    element.val(date);
    element.daterangepicker({
        singleDatePicker: true,
        timePicker: true,
        timePicker24Hour: true,
        showDropdowns: true,
        autoUpdateInput: false,
        startDate: date,
        locale: locale
    }).on("apply.daterangepicker", function (ev, picker) {
        $(this).val(picker.startDate.format(locale.format));
    });

}

function linkify(text, noLabel = false) {
    const regex = /\b(\w+):([0-9a-fA-F\-]{36})/gi;

    return text.replace(regex, (match, label, uuid) => {
        const url = window.routes.adminRedirect.replace('__label__', label.toLowerCase()).replace('__uuid__', uuid);

        if (noLabel) {
            return `<a href="${url}" target="_blank">${uuid}</a>`;
        } else {
            return `<a href="${url}" target="_blank">${label}:${uuid}</a>`;
        }
    });
}

let countdownInterval = null;

function startCountdownModal($el, seconds, label = '') {
    if (countdownInterval !== null) {
        clearInterval(countdownInterval);
    }

    function formatTime(sec) {
        const h = String(Math.floor(sec / 3600)).padStart(2, '0');
        const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
        const s = String(sec % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    $el.text(formatTime(seconds));

    $('#universalModal').off('hidden.bs.modal').on('hidden.bs.modal', function () {
        if (countdownInterval !== null) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
    });

    countdownInterval = setInterval(() => {
        seconds--;
        $el.text(formatTime(seconds));

        if (seconds <= 0) {
            clearInterval(countdownInterval);
            countdownInterval = null;

            if ($('#universalModal').is(':visible')) {
                $('#universalModal').modal('hide');
                toastr.warning(translate("Time expired"));
            }
        }
    }, 1000);
}

function openConfirmActionModal(options) {
    const defaults = {
        fetchUrl: null,
        fetchMethod: 'GET',
        actionUrl: null,
        actionMethod: 'POST',
        actionText: translate('Are you sure you want to proceed?'),
        actionType: 'info',
        confirmButtonText: '<i class="fa fa-save"></i> ' + translate('Confirm'),
        titleText: translate('Confirm'),
        onSuccess: null,
        onError: null,
        button: null,
        fetchData: null,
        hiddenInputs: {}
    };

    const settings = $.extend({}, defaults, options);

    const $modal = $('#universalModal');
    const $modalTitle = $modal.find('.modal-title');
    const $modalBody = $modal.find('.modal-body');
    const $confirmBtn = $('#modalConfirmButton');

    $modalTitle.text(settings.titleText);
    $confirmBtn.html(settings.confirmButtonText).data('action-url', settings.actionUrl);
    $confirmBtn.removeClass(function(index, className) {
        return (className.match(/(^|\s)btn-outline-\S+/g) || []).join(' ');
    });
    $confirmBtn.addClass('btn-outline-' + settings.actionType);

    const typeIcons = {
        info: 'fa-info-circle',
        warning: 'fa-exclamation-triangle',
        danger: 'fa-times-circle',
        success: 'fa-check-circle'
    };
    const icon = typeIcons[settings.actionType] || 'fa-info-circle';

    function renderBody(content) {
        const alertHtml = `
            <div class="alert alert-${settings.actionType} mt-3 text-center fw-bold">
                <i class="fa ${icon} fa-2x"></i><br>
                ${settings.actionText}
            </div>`;

        const html = `
            <form id="universalForm">
                ${content}
                ${alertHtml}
            </form>`;
        $modalBody.html(html);
        $modal.modal('show');
    }

    function renderFetchedData(data) {
        const uuidInput = data?.uuid
            ? `<input type="hidden" name="uuid" value="${data.uuid}">`
            : '';

        const inputField = `
            <div id="countdownTimer" class="text-center my-3 display-6 fw-bold text-danger"></div>
            <div class="row mb-3">
                <div class="col-sm-8 mx-auto">
                    <input type="text" name="code" maxlength="6" style="font-size:18px;" class="form-control form-control-lg text-center" placeholder="${translate('Enter authentication code')}" autofocus>
                </div>
            </div>
            <div id="verifyMessage" class="card-border mb-3 card card-body border-info text-dark small"></div>
        `;

        let hiddenFields = '';
        for (const key in settings.hiddenInputs) {
            hiddenFields += `<input type="hidden" name="${key}" value="${settings.hiddenInputs[key]}">`;
        }

        renderBody(hiddenFields + uuidInput + inputField);

        const $verifyMessage = $('#verifyMessage');
        if (data?.type === 'email') {
            $verifyMessage.html(translate('A code was sent to this email') + ` <b>${data.value}</b>`);
        } else if (data?.type === 'phone') {
            $verifyMessage.html(translate('A code was sent to this phone number') + ` <b>${data.value}</b>`);
        } else if (data?.type === 'totp') {
            $verifyMessage.html(translate('Use the Authenticator app on your device') + ` <b>${data.value}</b>`);
        }

        if (data?.lifetime) {
            const $timer = $('#countdownTimer');
            startCountdownModal($timer, data.lifetime);
        }
    }

    if (settings.fetchData) {
        renderFetchedData(settings.fetchData);
    } else if (settings.fetchUrl) {
        PUQajax(settings.fetchUrl, {}, 50, settings.button, settings.fetchMethod)
            .then(function (response) {
                renderFetchedData(response.data);
            })
            .catch(function (error) {
                let errorHtml = `<div class="alert alert-danger">${translate('Error loading data')}`;
                if (Array.isArray(error)) {
                    errorHtml += '<ul class="mt-2 mb-0">';
                    error.forEach(err => {
                        errorHtml += `<li>${err}</li>`;
                    });
                    errorHtml += '</ul>';
                }
                errorHtml += '</div>';
                renderBody(errorHtml);
            });
    } else {
        let hiddenFields = '';
        for (const key in settings.hiddenInputs) {
            hiddenFields += `<input type="hidden" name="${key}" value="${settings.hiddenInputs[key]}">`;
        }
        renderBody(hiddenFields);
    }

    $confirmBtn.off('click').on('click', function (e) {
        e.preventDefault();
        const data = serializeForm($('#universalForm'));

        PUQajax(settings.actionUrl, data, 50, $(this), settings.actionMethod, $('#universalForm'))
            .then(function (response) {
                $modal.modal('hide');
                if (typeof settings.onSuccess === 'function') {
                    settings.onSuccess(response);
                }
            })
            .catch(function (error) {
                if (typeof settings.onError === 'function') {
                    settings.onError(error);
                }
            });
    });
}

function startCountdown(el, seconds, label = '') {
    if (!el || typeof seconds !== 'number' || seconds < 0) return;

    function updateCountdown() {
        const years = Math.floor(seconds / (365 * 24 * 3600));
        const days = Math.floor(seconds / (24 * 3600));
        const hrs = Math.floor((seconds % (24 * 3600)) / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        let parts = [];
        if (years > 0) parts.push(years + 'y');
        if (days > 0) parts.push(days + 'd');
        parts.push(
            String(hrs).padStart(2, '0') + ':' +
            String(mins).padStart(2, '0') + ':' +
            String(secs).padStart(2, '0')
        );

        const text = (label ? label + ': ' : '') + parts.join('\u00A0');
        el.innerHTML = text;

        if (seconds <= 0) {
            clearInterval(timer);
        }

        seconds--;
    }

    updateCountdown();
    const timer = setInterval(updateCountdown, 1000);
}


document.addEventListener('DOMContentLoaded', function () {
    NProgress.configure({
        showSpinner: false,
        trickleSpeed: 200,
        minimum: 0.08
    });

    let activeRequests = 0;
    let delayTimer;
    let progressTimeout;

    function setNProgressColor(color) {
        const existingStyle = document.getElementById('nprogress-custom-color');
        if (existingStyle) {
            existingStyle.remove();
        }

        const style = document.createElement('style');
        style.id = 'nprogress-custom-color';
        style.textContent = `
                #nprogress .bar {
                    background: ${color} !important;
                }
                #nprogress .peg {
                    box-shadow: 0 0 10px ${color}, 0 0 5px ${color} !important;
                }
            `;
        document.head.appendChild(style);
    }
    setNProgressColor('linear-gradient(90deg, #667eea 0%, #764ba2 100%)');

    function isDownloadUrl(url) {
        if (!url) return false;
        return /\.(pdf|zip|rar|7z|exe|msi|dmg|tar\.gz|tar\.bz2|gz|bz2|docx?|xlsx?|pptx?|mp3|mp4|avi|mov|wmv|flv|mkv|jpg|jpeg|png|gif|svg|webp|ico|apk|deb|rpm|bin|iso)(\?.*)?$/i.test(url);
    }

    function isDownloadRequest(url, headers = {}) {
        if (isDownloadUrl(url)) return true;

        const contentDisposition = headers['content-disposition'] || headers['Content-Disposition'];
        if (contentDisposition && contentDisposition.includes('attachment')) {
            return true;
        }

        return false;
    }

    function startProgress(url, headers = {}) {
        if (isDownloadRequest(url, headers)) return;

        clearTimeout(delayTimer);
        clearTimeout(progressTimeout);
        delayTimer = setTimeout(() => {
            NProgress.start();
            progressTimeout = setTimeout(() => {
                NProgress.done();
            }, 3000);
        }, 100);
    }

    function stopProgress() {
        clearTimeout(delayTimer);
        clearTimeout(progressTimeout);
        setTimeout(() => {
            if (activeRequests <= 0) {
                NProgress.done();
            }
        }, 150);
    }

    window.addEventListener('beforeunload', function () {
        NProgress.start();
    });

    window.addEventListener('load', function () {
        NProgress.done();
    });

    document.body.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        if (
            !href ||
            href.startsWith('#') ||
            href.startsWith('javascript') ||
            link.target === '_blank' ||
            link.download !== null ||
            isDownloadUrl(href)
        ) return;

        startProgress(href);
    });

    (function () {
        const originalAssign = window.location.assign;
        const originalReplace = window.location.replace;
        const originalHref = Object.getOwnPropertyDescriptor(window.location.__proto__, 'href');

        function patchRedirect(fn) {
            return function (url) {
                if (isDownloadUrl(url)) {
                    fn.call(window.location, url);
                    return;
                }
                startProgress(url);
                setTimeout(() => fn.call(window.location, url), 100);
            };
        }

        window.location.assign = patchRedirect(originalAssign);
        window.location.replace = patchRedirect(originalReplace);

        if (originalHref && originalHref.set) {
            Object.defineProperty(window.location, 'href', {
                set: function (url) {
                    if (isDownloadUrl(url)) {
                        originalHref.set.call(window.location, url);
                        return;
                    }
                    startProgress(url);
                    setTimeout(() => originalHref.set.call(window.location, url), 100);
                },
                get: originalHref.get,
                configurable: true,
                enumerable: true
            });
        }
    })();

    if (window.fetch) {
        const origFetch = window.fetch;
        window.fetch = function (input, init) {
            let url = typeof input === 'string' ? input : (input.url || '');

            if (isDownloadUrl(url)) {
                return origFetch(input, init);
            }

            activeRequests++;
            startProgress(url);

            return origFetch(input, init)
                .then(response => {
                    const contentDisposition = response.headers.get('content-disposition');
                    if (contentDisposition && contentDisposition.includes('attachment')) {
                        activeRequests--;
                        stopProgress();
                        return response;
                    }
                    return response;
                })
                .finally(() => {
                    activeRequests--;
                    stopProgress();
                });
        };
    }

    const origOpen = XMLHttpRequest.prototype.open;
    const origSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (method, url, ...rest) {
        this._requestUrl = url;

        if (isDownloadUrl(url)) {
            return origOpen.apply(this, [method, url, ...rest]);
        }

        const loadendHandler = () => {
            const contentDisposition = this.getResponseHeader('content-disposition');
            if (contentDisposition && contentDisposition.includes('attachment')) {
                activeRequests--;
                stopProgress();
                return;
            }

            activeRequests--;
            stopProgress();
        };

        this.addEventListener('loadend', loadendHandler, { once: true });

        activeRequests++;
        startProgress(url);
        return origOpen.apply(this, [method, url, ...rest]);
    };

    if (window.axios) {
        window.axios.interceptors.request.use(config => {
            if (isDownloadUrl(config.url)) {
                return config;
            }
            activeRequests++;
            startProgress(config.url);
            return config;
        });

        window.axios.interceptors.response.use(response => {
            const contentDisposition = response.headers['content-disposition'];
            if (!contentDisposition || !contentDisposition.includes('attachment')) {
                activeRequests--;
                stopProgress();
            }
            return response;
        }, error => {
            activeRequests--;
            stopProgress();
            return Promise.reject(error);
        });
    }

    if (window.jQuery) {
        $(document).ajaxSend((event, jqXHR, ajaxOptions) => {
            if (isDownloadUrl(ajaxOptions.url)) return;
            activeRequests++;
            startProgress(ajaxOptions.url);
        });

        $(document).ajaxComplete((event, jqXHR, ajaxOptions) => {
            const contentDisposition = jqXHR.getResponseHeader('content-disposition');
            if (!contentDisposition || !contentDisposition.includes('attachment')) {
                activeRequests--;
                stopProgress();
            }
        });
    }

    window.addEventListener('focus', function() {
        setTimeout(() => {
            if (activeRequests <= 0) {
                NProgress.done();
            }
        }, 500);
    });
});
